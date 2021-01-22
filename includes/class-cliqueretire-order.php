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

class Ebox_Cliqueretire_Order
{
    private $api;
    private $s;
    private $helper;
    private $woocommerce;

    const CARRIER_CODE = 'ebox_cliqueretire';

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->api = new Ebox_Cliqueretire_Api();
        $this->s = new Ebox_Cliqueretire_Settings();
        $this->helper = new Ebox_Cliqueretire_Helper();
        $this->woocommerce = $GLOBALS['woocommerce'];
    }

    /**
     * Remove a pending sync
     *
     * Called when an order moves out from "processing"
     * status to a hold status
     *
     * @param  int     $order_id    The Order Id
     * @return boolean              True or false
     */
    public function removePendingSync($orderId)
    {
        $order = new WC_Order($orderId);

        if (get_post_meta($orderId, '_ebox_cliqueretire_sync', true) == 'false') {
            delete_post_meta($orderId, '_ebox_cliqueretire_sync');
        }
    }

    /**
     * Add a pending sync
     *
     * @param  int     $orderId    The Order Id
     * @return boolean              True or false
     */
    public function addPendingSync($orderId)
    {
        $isEnabled = get_option('wc_settings_cliqueretire_enabled');
        $autoSyncOrders = get_option('wc_settings_cliqueretire_auto_sync_orders');

        if ($isEnabled != 'yes' || $autoSyncOrders == 'no') {
            return;
        }

        if (get_post_meta($orderId, '_ebox_cliqueretire_sync', true) == 'true') {
            return;
        }

        // Get the orders_item_id meta with key shipping
        $order = new WC_Order($orderId);

        // Only add the order as pending when it's in a "processing" status
        if (!$order->has_status('processing')) {
            return;
        }

        // If we are only syncing cliqueretire quoted orders, ensure it's a cliqueretire quoted order
        if ($autoSyncOrders == 'all_cliqueretire' && !$this->isCliqueretireShippingMethod($order)) {
            return;
        }
        
        add_post_meta($orderId, '_ebox_cliqueretire_sync', 'false', true);
        // attempt to sync the order now
        $this->syncOrder($orderId);
    }

    protected function isCliqueretireShippingMethod($order)
    {
        $shippingMethods = $order->get_shipping_methods();

        foreach ($shippingMethods as $shippingMethod) {
            // Since Woocommerce v3.4.0, the instance_id is saved in a seperate property of the shipping method
            // To add support for v3.4.0, we'll append the instance_id, as this is how we store a mapping in Cliqueretire
            if (isset($shippingMethod['instance_id']) && !empty($shippingMethod['instance_id'])) {
                $shippingMethodId = sprintf(
                    '%s:%s',
                    $shippingMethod['method_id'],
                    $shippingMethod['instance_id']
                );
            }
            else {
                $shippingMethodId = $shippingMethod['method_id'];
            }

            // Check if the shipping method chosen is a cliqueretire method
            if (stripos($shippingMethod['method_id'], 'Ebox_Cliqueretire') !== FALSE) {
                return true;
            }
        }

        return false;
    }

    protected function getShippingMethodId($order)
    {
        $shippingMethods = $order->get_shipping_methods();

        foreach ($shippingMethods as $shippingMethod) {
            // Since Woocommerce v3.4.0, the instance_id is saved in a seperate property of the shipping method
            // To add support for v3.4.0, we'll append the instance_id, as this is how we store a mapping in Cliqueretire
            if (isset($shippingMethod['instance_id']) && !empty($shippingMethod['instance_id'])) {
                $shippingMethodId = sprintf(
                    '%s:%s',
                    $shippingMethod['method_id'],
                    $shippingMethod['instance_id']
                );
            }
            else {
                $shippingMethodId = $shippingMethod['method_id'];
            }

            // Check if the shipping method chosen is Ebox_Cliqueretire
            if (stripos($shippingMethodId, 'Ebox_Cliqueretire') !== FALSE) {
                return $shippingMethodId;
            }

            // If we have anything after shipping_method:instance_id
            // then ignore it
            if (substr_count($shippingMethodId, ':') > 1) {
                $firstOccurence = strrpos($shippingMethodId, ':');
                $secondOccurence = strpos($shippingMethodId, ':', $firstOccurence);
                $shippingMethodId = substr($shippingMethodId, 0, $secondOccurence);
            }
        }

        return false;
    }

    /**
     * Sync all pending orders
     *
     * Adds _ebox_cliqueretire_sync meta key value to all orders
     * that have been scheduled for sync with cliqueretire
     * @return [type] [description]
     */
    public function syncOrders()
    {
        global $woocommerce;

        $orderPostArg = array(
            'post_status' => 'wc-processing',
            'post_type' => 'shop_order',
            'meta_query' => array(
                array(
                    'key' => '_ebox_cliqueretire_sync',
                    'value' => 'false',
                    'compare' => '='
                )
            ),
        );

        // Get all woocommerce orders that are processing
        $orderPosts = get_posts($orderPostArg);

        foreach ($orderPosts as $orderPost) {
            $this->syncOrder($orderPost->ID);
        }
    }

    /**
     * Manual action - Send order to cliqueretire
     *
     * @param  object $order order to be send
     */
    public function sendOrder($order)
    {
        if (version_compare($this->woocommerce->version, '2.6.0', '<=')) {
            $orderId = $order->id;
        }
        else {
            $orderId = $order->get_id();
        }

        update_post_meta($orderId, '_ebox_cliqueretire_sync', 'false');

        // attempt to sync the order now
        $this->syncOrder($orderId);
    }

    /**
     * Manual action - Send bulk orders to cliqueretire
     *
     * @param  string $redirectTo return url
     * @param  string $action selected bulk order action
     */
    public function sendBulkOrders($redirectTo, $action, $orderIds)
    {
        // only process when the action is a cliqueretire bulk-ordders action
        if ($action != 'cliqueretire_bulk_orders_action') {
            return $redirectTo;
        }

        foreach ($orderIds as $orderId) {
            // Mark Cliqueretire sync as false as for this manual action
            // we want to schedule orders for sync even if synced already
            update_post_meta($orderId, '_ebox_cliqueretire_sync', 'false');
        }

        // Create the schedule for the orders to sync
        wp_schedule_single_event(current_time('timestamp'), 'syncOrders');

        return add_query_arg(array('cliqueretire_sync' => '2'), $redirectTo);
    }

    public function syncOrder($orderId)
    {
        // Get the orders_item_id meta with key shipping
        $order = new WC_Order($orderId);
        $orderItems = $order->get_items();

        // if WooCommerce version is equal or less than 2.6 then use
        // different data mapper for it
        if (version_compare($this->woocommerce->version, '2.6.0', '<=')) {
            $orderData = (new Ebox_Cliqueretire_Data_Mapper_Order_V26())->__invoke($order)->toArray();
        }
        else {
            $orderData = (new Ebox_Cliqueretire_Data_Mapper_Order())->__invoke($order)->toArray();
        }

        // If there are no order items, return early
        if (count($orderItems) == 0) {
            update_post_meta($orderId, '_ebox_cliqueretire_sync', 'true', 'false');

            return;
        }

        // Send the API request
        $apiResponse = $this->api->sendOrder($orderData);

        if ($apiResponse && $apiResponse->id) {
            update_post_meta($orderId, '_ebox_cliqueretire_sync', 'true', 'false');

            $orderComment = 'Order Synced with Clique Retire. Tracking number: ' . $apiResponse->id . '.';
            $order->add_order_note($orderComment, 0);
        }
        else {
            $orderComment = 'Order Failed to sync with Clique Retire.';

            if ($apiResponse && isset($apiResponse->messages)) {
                $messages = $apiResponse->messages;

                foreach ($messages as $field => $message) {
                    $orderComment .= sprintf(
                        '%c%s - %s',
                        10, // ASCII Code for NewLine
                        $field,
                        implode(', ', $message)
                    );
                }
            }
            elseif ($apiResponse && isset($apiResponse->error) && isset($apiResponse->error_description)) {
                $orderComment .= sprintf(
                    '%c%s - %s',
                    10, // ASCII Code for NewLine
                    $apiResponse->error,
                    $apiResponse->error_description
                );
            }

            $order->add_order_note($orderComment, 0);
        }
    }
}
