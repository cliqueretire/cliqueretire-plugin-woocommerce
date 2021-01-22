<?php
/**
 * Ebox.IT
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is available through the world-wide-web at this URL:
 * http://www.cliqueretire.com.br/licencing
 *
 * @category   Ebox
 * @copyright  Copyright (c) 2021 by Clique Retire (http://www.cliqueretire.com.br)
 * @author     Christiano de Chermont <chermont@cliqueretire.com.br>
 * @license    http://www.cliqueretire.com.br/licencing
 */

class Ebox_Cliqueretire_Core
{
    public $id = 'ebox_cliqueretire';

    /**
     * Webhook Error Messages.
     */
    const ERROR_API_KEY_MISSING = 'An API Key is required';
    const ERROR_API_KEY_MISMATCH = 'The API Key provided does not match the configured API Key';
    const ERROR_BAD_REQUEST = 'An invalid request was recieved';
    const ERROR_ORDER_MISSING = 'The order id requested was not found or has a status that is not available for shipping';
    const NOTICE_SHIPMENT_STATUS = 'Ignoring the order status update, as we only respond to ready_for_pickup state';
    const ERROR_SHIPMENT_FAILED = 'The shipment record was not able to be created at this time, please try again.';
    const SUCCESS_SHIPMENT_CREATED = 'The shipment record was created successfully.';

    /**
     * Instace of Ebox Cliqueretire
     */
    private static $instance;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if (!function_exists('is_plugin_active_for_network')) {
            require_once(ABSPATH . '/wp-admin/includes/plugin.php');
        }

        // Check if WooCommerce is active
        if (!class_exists('woocommerce')) {
            return;
        }

        $this->s = new Ebox_Cliqueretire_Settings();
        $this->log = new Ebox_Cliqueretire_Log();

        $this->init();
    }

    /**
     * Instance.
     *
     * An global instance of the class. Used to retrieve the instance
     * to use on other files/plugins/themes.
     *
     * @since 1.0.0
     *
     * @return object Instance of the class.
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Initialize plugin parts.
     *
     * @since 1.0.0
     */
    public function init()
    {
        add_action('syncOrders', array($this, 'syncOrders'));

        // *****************
        // Order Sync
        // *****************

        $order = new Ebox_Cliqueretire_Order;
        // If a new order is recieved, add pending sync
        add_action('woocommerce_checkout_update_order_meta', array($order, 'addPendingSync'));
        // If a order transitions into "processing", add pending sync
        add_action('woocommerce_order_status_processing', array($order, 'addPendingSync'));
        // If the order transition into "on-hold", remove pending sync
        add_action('woocommerce_order_status_on-hold', array($order, 'removePendingSync'));

        // *****************
        // Authority To Leave
        // *****************

        // Add authority to leave field to checkout
        // add_action('woocommerce_after_order_notes', array($this, 'add_authority_to_leave'));

        // Update the order meta with authority to leave value
        //add_action('woocommerce_checkout_update_order_meta', array($this, 'update_authority_to_leave_order_meta'));

        // Display the authority to leave on the orders edit page
        add_action('woocommerce_admin_order_data_after_shipping_address', 'authority_to_leave_display_admin_order', 10, 1);

        function authority_to_leave_display_admin_order($order)
        {
            if (version_compare(WC()->version, '3.0.0') >= 0) {
                $orderId = $order->get_id();
            }
            else {
                $orderId = $order->id;
            }

            echo '<p><strong>'.__('Authority to leave').':</strong> ' . get_post_meta( $orderId, 'authority_to_leave', true ) . '</p>';
        }

        // Add the cliqueretire settings tab functionality
        add_action('woocommerce_settings_tabs_cliqueretire_settings_tab', 'Ebox_Cliqueretire_Settings::addFields');
        add_action('woocommerce_update_options_cliqueretire_settings_tab', 'Ebox_Cliqueretire_Settings::updateSettings');

        // Validate the api key when the setting is changed
        add_action('update_option_wc_settings_cliqueretire_api_key', array($this, 'after_api_key_update'), 10, 2);


        //**********************/
        // Webhook functionality
        //**********************/

        // create filter to get $_GET['cliqueretire_api_key']
        add_filter('query_vars', array($this, 'add_query_vars'), 0);

        // handle API request if 'cliqueretire_api_key' is set
        add_action('parse_request', array($this, 'handle_webhook_request'), 0);

        // create 'cliqueretire/shipment_create' endpoint
        add_action('init', array($this, 'add_webhook_endpoint'), 0);

        // Add Send to Cliqueretire order action
        add_action('woocommerce_order_actions', array($this, 'cliqueretire_add_order_meta_box_action') );

        // Process Cliqueretire send order action
        add_action('woocommerce_order_action_cliqueretire_order_action', array($order, 'sendOrder') );

        // Add Bulk Send to Cliqueretire orders action
        add_action('bulk_actions-edit-shop_order', array($this, 'cliqueretire_send_bulk_orders_action'), 20, 1);

        // Process Cliqueretire bulk orders send action
        add_action('handle_bulk_actions-edit-shop_order', array($order, 'sendBulkOrders'), 10, 3 );

        add_action('admin_notices', array($this, 'order_sync_notice') );

        // if (get_option('wc_settings_cliqueretire_shippingcalculator_city_enabled') == 'yes') {
        //     // Enable suburb/city field for Shipping calculator
        //     add_filter('woocommerce_shipping_calculator_enable_city', '__return_false');
        // }

        // Add the shipment meta boxes when viewing an order.
        add_action('add_meta_boxes_shop_order', array($this, 'ebox_add_shipment_meta_box'));
    }

    /**
     * Add the Cliqueretire Shipment Meta Box
     */
    function ebox_add_shipment_meta_box()
    {
        $orderId = get_the_ID();
        $shipmentData = get_post_meta($orderId, '_ebox_cliqueretire_shipment', true);

        if (empty($shipmentData)) {
            return;
        }

        add_meta_box(
            'ebox_shipment_fields',
            __('Shipments - Powered by Cliqueretire', 'woocommerce-cliqueretire'),
            array(
                $this,
                'ebox_add_shipment_meta_box_content'
            ),
            'shop_order',
            'side',
            'high'
        );
    }

    /**
     * Render the Cliqueretire Shipment Meta Box Content
     */
    function ebox_add_shipment_meta_box_content($order)
    {
        $shipmentData = get_post_meta($order->ID, '_ebox_cliqueretire_shipment', true);
        $count = count($shipmentData);
        $shipmentDetails = '';
        $i = 1;

        foreach ($shipmentData as $shipment) {
            // Render the Courier Name
            if (!empty($shipment['courier_name'])) {
                $shipmentDetails .= '<strong>Courier:</strong>&nbsp;';
                $shipmentDetails .= '<span>' .$shipment['courier_name']. '</span>';
                $shipmentDetails .= '<br/>';
            }

            // Render the Courier Job ID
            if (!empty($shipment['booked_at'])) {
                $shipmentDetails .= '<strong>Booked At:</strong>&nbsp;';
                $shipmentDetails .= '<span>' .$shipment['booked_at']. '</span>';
                $shipmentDetails .= '<br/>';
            }

            // Render the Cliqueretire Tracking Link
            if (!empty($shipment['tracking_number'])) {
                $shipmentDetails .= '<strong>Cliqueretire Track #:</strong>&nbsp;';
                $shipmentDetails .= '<a target="_blank" href="'. $shipment['tracking_url']. '">';
                $shipmentDetails .= $shipment['tracking_number'];
                $shipmentDetails .= '</a><br/>';
            }

            if ($i < $count) {
                $shipmentDetails .= '<hr/>';
            }

            $i++;
        }

        echo $shipmentDetails;
    }

    /**
     * Add a custom action to order actions select box
     *
     * @param  array $actions order actions array to display
     * @return array updated actions
     */
    public function cliqueretire_add_order_meta_box_action($actions)
    {
        // add "Send to Cliqueretire" custom order action
        $actions['cliqueretire_order_action'] = __('Send to Cliqueretire');

        return $actions;
    }

    /**
     * Add a custom bulk order action to order actions select box on orders list page
     *
     * @param  array $actions order actions array to display
     * @return array updated actions
     */
    public function cliqueretire_send_bulk_orders_action($actions)
    {
        // add "Send to Cliqueretire" bulk action on the orders listing page
        $actions['cliqueretire_bulk_orders_action'] = __('Send to Cliqueretire');

        return $actions;
    }

    public function order_sync_notice()
    {
        if (!isset($_GET['cliqueretire_sync'])) {
            return;
        }

        echo '<div class="updated notice is-dismissable">'
                . '<p>Orders have been scheduled to sync with Cliqueretire - they will be synced shortly.</p>'
            . '</div>';
    }

    public function add_webhook_endpoint()
    {
        add_rewrite_rule('^cliqueretire/shipment_create/?([0-9]+)?/?', 'index.php?cliqueretire_api_key=$matches[1],', 'top');
    }

    public function add_query_vars($vars)
    {
        $vars[] = 'cliqueretire_api_key';

        return $vars;
    }

    public function handle_webhook_request()
    {
        global $wp;

        if (isset($wp->query_vars['cliqueretire_api_key'])) {
            $shipment = new Ebox_Cliqueretire_Shipment();
            $shipment->handle();

            exit;
        }
    }

    public function after_api_key_update($currentApiKey, $newApiKey)
    {
        $environment = $_POST['wc_settings_cliqueretire_environment'];
        $newApiId = $_POST['wc_settings_cliqueretire_api_id'];
        $isValidApiKey = $this->validate_keys($newApiKey, $currentApiKey, $newApiId, $environment);

        if (!$isValidApiKey) {
            //return;
        }

        $this->register_shopping_cart_name();
        $this->register_webhook($newApiKey, $newApiId, $environment);
    }

    private function register_webhook($newApiKey, $newApiId, $environment = null)
    {
        $this->api = new Ebox_Cliqueretire_Api();

        // Set the api key temporarily to the requested key
        $this->api->setApiKey($newApiKey);

        if (!empty($environment)) {
            // use the environment passed
            $this->api->setEnvironment($environment);
        }

        $webhookUrl = get_site_url() . '/cliqueretire/shipment_create?cliqueretire_api_key=' . $newApiKey;

        $requestData = array(
            'webhook_url' => $webhookUrl
        );

        $this->log->add(
            'Registering Webhook Url',
            $newApiKey,
            array(
                'webhook_url' => $webhookUrl
            )
        );

        try {
            $apiResponse = $this->api->putMerchant($requestData);

            if ($apiResponse
                && !property_exists($apiResponse, 'error')
                && property_exists($apiResponse, 'response')) {
                $this->log->add(
                    'Registering Web Hook Response',
                    'Webhook Registration Successful'
                );

                $this->show_webhook_notice(true);

                return true;
            }
            else {
                $this->log->add(
                    'Registering Web Hook Response',
                    'An error occurred during webhook register'
                );

                $this->show_webhook_notice(false);

                return false;
            }
        }
        catch (Exception $e) {
            $this->log->exception($e);
        }
    }

    private function register_shopping_cart_name()
    {
        $requestData = array(
            'shipping_cart_method_name' => 'woocommerce'
        );

        $this->log->add('Registering shopping cart name', '', $requestData);

        try {
            $apiResponse = $this->api->putMerchant($requestData);

            if (empty($apiResponse)
                || property_exists($apiResponse, 'error')) {
                $this->show_cart_registration_notice();
            }
        }
        catch (Exception $e) {
            $this->log->exception($e);

            $this->show_cart_registration_notice();
        }
    }

    private function validate_keys($newApiKey, $oldApiKey = null, $newApiId, $environment = null)
    {
        if (is_null($oldApiKey)) {
            $oldApiKey = get_option('wc_settings_cliqueretire_api_key');
        }
        // if (is_null($oldApiId)) {
        //     $oldApiId = get_option('wc_settings_cliqueretire_api_id');
        // }
        $this->log->add(
            'Validating API Key',
            $newApiKey,
            array(
                'old_api_key' => $oldApiKey,
                'new_api_key' => $newApiKey,
                'new_api_id' => $newApiId
            )
        );

        $this->api = new Ebox_Cliqueretire_Api();
        // Set the api key temporarily to the requested key
        $this->api->setApiId($newApiId);
        $this->api->setApiKey($newApiKey);

        if (!empty($environment)) {
            // use the environment passed
            $this->api->setEnvironment($environment);
        }

        try {
            $apiResponse = $this->api->getOrdersMerchant();
            if ($apiResponse->message === 'Unauthorized') {
                $this->log->add(
                    'Validating API Key Result',
                    'API Key ' . $newApiKey . 'is INVALID'
                );
                print('error');
                $this->show_api_notice(false);

                return false;
            }else{
                $this->log->add(
                    'Validating API Key Result',
                    'API Key ' . $newApiKey . 'is VALID'
                );
                print('foii');
                $this->show_api_notice(true);

                return true;
            }

        }
        catch (Exception $e) {
            $this->log->exception($e);
        }
    }

    public function show_api_notice($isValid)
    {
        if (!$isValid) {
            echo '<div class="error notice">'
                . '<p>Invalid Cliqueretire API Key detected - Cliqueretire will not function correctly.</p>'
                . '</div>';
        }
        else {
            echo '<div class="updated notice">'
                . '<p>Cliqueretire API Key is Valid</p>'
                . '</div>';
        }
    }

    public function show_webhook_notice($isValid)
    {
        if (!$isValid) {
            echo '<div class="error notice">'
                . '<p>Cliqueretire Webhook ' . get_site_url() . '/cliqueretire/shipment_create was not registered</p>'
                . '</div>';
        }
        else {
            echo '<div class="updated notice">'
                . '<p>Cliqueretire Webhook ' . get_site_url() . '/cliqueretire/shipment_create has now been registered</p>'
                . '</div>';
        }
    }

    public function show_cart_registration_notice()
    {
        echo '<div class="error notice">'
            . '<p>The request to update the shopping cart integration name failed - please try again.</p>'
            . '</div>';
    }

    /**
     * Add the cliqueretire order sync to the cron scheduler
     */
    public static function order_sync_schedule()
    {
        if (!wp_next_scheduled('syncOrders')) {
            wp_schedule_event(current_time('timestamp'), 'hourly', 'syncOrders');
        }
    }

    /**
     * Remove the cliqueretire order sync to the cron scheduler
     */
    public static function order_sync_deschedule()
    {
        wp_clear_scheduled_hook('syncOrders');
    }

    public function syncOrders()
    {
        if (class_exists('WC_Order')) {
            $orders = new Ebox_Cliqueretire_Order();
            $orders->syncOrders();
        }
    }
}
