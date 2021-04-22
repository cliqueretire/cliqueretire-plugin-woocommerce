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

class Ebox_Cliqueretire_Method extends WC_Shipping_Method
{
    protected $api;
    protected $helper;

    /**
     * Constructor.
     */
    public function __construct($instance_id = 0)
    {
        $this->api = new Ebox_Cliqueretire_Api();
        $this->log = new Ebox_Cliqueretire_Log();
        $this->helper = new Ebox_Cliqueretire_Helper();

        $settings = new Ebox_Cliqueretire_Settings_Method();

        $this->id                   = 'ebox_cliqueretire';
        $this->instance_id          = absint($instance_id);
        $this->instance_form_fields = $settings->getFields(true);
        $this->title                = __('Clique Retire', 'woocommerce-cliqueretire');
        $this->method_title         = __('Clique Retire', 'woocommerce-cliqueretire');
        $this->method_description   = __('Utilize a Clique Retire como opção de entrega em um dos pontos de retirada distribuídos por todo o Brasil.');
        $this->supports              = array(
            'shipping-zones',
            'instance-settings',
            // Disable instance modal settings due to array not saving correctly
            // https://github.com/bobbingwide/woocommerce/commit/1e8d9d4c95f519df090e3ec94d8ea08eb8656c9f
            // 'instance-settings-modal',
        );

        $this->init();
    }

    /**
     * Initialize plugin parts.
     *
     * @since 1.0.0
     */
    public function init()
    {
        // Initiate instance settings as class variables

        // Use property "quote_enabled", as "enabled" is used by the parent method
        $this->quote_enabled           = $this->get_option('enabled');

        $this->title                   = $this->get_option('title');
        $this->filter_enabled          = 'no'; // depreciated
        $this->filter_enabled_products = array(); // depreciated
        $this->filter_attribute        = $this->get_option('filter_attribute');
        $this->filter_attribute_code   = $this->get_option('filter_attribute_code');
        $this->filter_attribute_value  = $this->get_option('filter_attribute_value');
        $this->margin_days             = $this->get_option('margin_days');
        $this->margin_days_amount      = $this->get_option('margin_days_amount');

        $this->margin                  = $this->get_option('margin');
        $this->margin_amount           = $this->get_option('margin_amount');

        // *****************
        // Shipping Method
        // *****************

        // *****************
        // Shipping Method Save Event
        // *****************

        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    /**
     * Add shipping method.
     *
     * Add shipping method to WooCommerce.
     *
     */
    public static function add_shipping_method($methods)
    {
        if (class_exists('Ebox_Cliqueretire_Method')) {
            $methods['ebox_cliqueretire'] = 'Ebox_Cliqueretire_Method';
        }

        return $methods;
    }

    /**
     * Calculate shipping.
     *
     * @param mixed $package
     * @return void
     */
    public function calculate_shipping($package = array())
    {
        // Check if the module is enabled and used for shipping quotes
        if (get_option('wc_settings_cliqueretire_enabled') != 'yes'
            || $this->quote_enabled != 'yes') {
            return;
        }

        $quoteDestination = $package['destination'];
        $quoteCart = $package['contents'];

        // Check if we can ship the products by enabled filtering
        if (!$this->_canShipEnabledProducts($package)) {
            return;
        }

        // Check if we can ship the products by attribute filtering
        if (!$this->_canShipEnabledAttributes($package)) {
            return;
        }

        $this->_processShippingQuotes($quoteDestination, $quoteCart);
    }

    private function getParcelAttributes($items)
    {
        $itemDetails = array();

        foreach ($items as $cartItemId => $item) {
            $itemDetail = array();

            // If product is variation, load variation ID
            if ($item['variation_id']) {
                $cartItem = wc_get_product($item['variation_id']);
            }
            else {
                $cartItem = wc_get_product($item['product_id']);
            }

            $itemWeight = $cartItem->get_weight();
            $itemHeight = $cartItem->get_height();
            $itemLength = $cartItem->get_length();
            $itemWidth = $cartItem->get_width();

            $itemDetail['qty'] = $item['quantity'];

            if (!empty($itemWeight)) {
                $itemDetail['weight'] = $this->helper->convertWeight($itemWeight);
            }
            else {
                // stub weight to 0.2kg
                $itemDetail['weight'] = 0.2;
            }

            if (!defined('CLIQUERETIRE_IGNORE_ITEM_DIMENSIONS')
                || !CLIQUERETIRE_IGNORE_ITEM_DIMENSIONS) {
                if (!empty($itemHeight)) {
                    $itemDetail['depth'] = $this->helper->convertDimension($itemHeight);
                }

                if (!empty($itemLength)) {
                    $itemDetail['length'] = $this->helper->convertDimension($itemLength);
                }

                if (!empty($itemWidth)) {
                    $itemDetail['width'] = $this->helper->convertDimension($itemWidth);
                }
            }

            $itemDetails[] = $itemDetail;
        }

        return $itemDetails;
    }

    private function _processShippingQuotes($quoteDestination, $quoteCart)
    {

        $dropoffPostcode = $quoteDestination['postcode'];
        $items = WC()->cart->get_cart();

        if (empty($dropoffPostcode)) {
            $this->log->add(
                'Quote Request',
                'A postcode is required for a live quote'
            );
            return;
        }

        $quoteData = array(
            'zipCode' => $dropoffPostcode,
            'url' => $_SERVER['HTTP_HOST'],
            'parcel_attributes' => $this->getParcelAttributes($items)
        );


        $shippingQuotes = $this->api->getQuote($quoteData);

        if (is_object($shippingQuotes)) {
            $this->_addStandardQuote($shippingQuotes);
        }
        else {
            return false;
        }
    }

    /**
     * Get the dropoff address value for a quote
     *
     * @param array $quoteDestination
     * @return string|null
     */
    private function getDropoffAddress($quoteDestination)
    {
        $addresses = [
            $quoteDestination['address'],
            $quoteDestination['address_2'],
        ];

        $addresses = array_filter($addresses, function ($address) {
            $address = trim($address);
            
            return !empty($address);
        });

        if (empty($addresses)) {
            return null;
        }

        return implode(', ', $addresses);
    }

    private function _addStandardQuote($shippingQuote)
    {
            $quotePrice = $this->_getQuotePrice($shippingQuote->absoluteValue);
            $quoteDays = $this->_getQuoteDays($shippingQuote->estimateDays);
            $rate = array(
                // unique id for each rate
                'id'    => 'Ebox_Cliqueretire',
                'label' => sprintf(
                    '%s - Retira em %s dias úteis',
                    $this->method_title,
                    $quoteDays
                ),
                'cost' => $quotePrice,
                'meta_data' => array(
                    'service_level' => 'test',
                    'courier_allocation' => 'test2'
                )
             );

            $this->add_rate($rate);
    }

    /**
     * Get the quote price, including the margin amount
     * @param  float $quotePrice The quote amount
     * @return float             The quote amount, with margin
     *                           if applicable
     */
    private function _getQuotePrice($quotePrice)
    {
        switch ($this->margin) {
            case 'yes-fixed':
                $quotePrice += (float) $this->margin_amount;
                break;
            case 'yes-percentage':
                $quotePrice *= (1 + ( (float) $this->margin_amount / 100));
        }

        // ensure we get the lowest price, but not below 0.
        $quotePrice = max(0, $quotePrice);

        return $quotePrice;
    }

    private function _getQuoteDays($quoteDays)
    {
        switch ($this->margin_days) {
            case 'yes':
                $quoteDays += (int) $this->margin_days_amount;
                break;
        }

        // ensure we get the lowest price, but not below 0.
        $quoteDays = max(0, $quoteDays);

        return $quoteDays;
    }

    /**
     * Checks if we can ship the products in the cart
     *
     * @depreciated - this functionality is only available on
     * the legacy shipping method - it will be removed in Q1 2018
     */
    private function _canShipEnabledProducts($package)
    {
        if ($this->filter_enabled == 'no') {
            return true;
        }

        if ($this->filter_enabled_products == null) {
            return false;
        }

        $allowedProducts = $this->filter_enabled_products;

        $products = $package['contents'];
        $productIds = array();

        foreach ($products as $itemKey => $product) {
            $productIds[] = $product['product_id'];
        }

        if (!empty($allowedProducts)) {
            // If item is not enabled return false
            if ($productIds != array_intersect($productIds, $allowedProducts)) {
                $this->log->add(
                    'Can Ship Enabled Products',
                    'Returning false'
                );

                return false;
            }
        }

        $this->log->add(
            'Can Ship Enabled Products',
            'Returning true'
        );

        return true;
    }

    private function _canShipEnabledAttributes($package)
    {
        if ($this->filter_attribute == 'no') {
            return true;
        }

        $attributeCode = $this->filter_attribute_code;

        // Check if there is an attribute code set
        if (empty($attributeCode)) {
            return true;
        }

        $attributeValue = $this->filter_attribute_value;

        // Check if there is an attribute value set
        if (empty($attributeValue)) {
            return true;
        }

        $products = $package['contents'];

        foreach ($products as $itemKey => $product) {
            $productObject = new WC_Product($product['product_id']);
            $productAttributeValue = $productObject->get_attribute($attributeCode);

            if (strpos($productAttributeValue, $attributeValue) === false) {
                $this->log->add(
                    'Can Ship Enabled Attributes',
                    'Returning false'
                );

                return false;
            }
        }

        $this->log->add(
            'Can Ship Enabled Attributes',
            'Returning true'
        );

        return true;
    }
}
