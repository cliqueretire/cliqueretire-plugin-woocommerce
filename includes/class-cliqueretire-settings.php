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
 * @copyright  Copyright (c) by Ebox.IT Pty Ltd (http://www.cliqueretire.com.br)
 * @author     Christiano de Chermont <chermont@cliqueretire.com.br>
 * @license    http://www.cliqueretire.com.br/licencing
 */

class Ebox_Cliqueretire_Settings
{
    /**
     * Add a new settings tab to the WooCommerce settings tabs array.
     *
     * @param array $settingsTab Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
     * @return array $settingsTab Array of WooCommerce setting tabs & their labels, including the Subscription tab.
     */
    public static function addSettingsTab($settingsTab)
    {
        $settingsTab['cliqueretire_settings_tab'] = __('Clique Retire', 'woocommerce-cliqueretire');

        return $settingsTab;
    }

    /**
     * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
     *
     * @uses woocommerce_admin_fields()
     */
    public static function addFields()
    {
        woocommerce_admin_fields(self::getFields());

        // include custom script on cliqueretire settings page
        wp_enqueue_script('cliqueretire-script');
    }

    /**
     * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
     *
     * @uses woocommerce_update_options()
     */
    public static function updateSettings()
    {
        woocommerce_update_options(self::getFields());
    }

    /**
     * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
     *
     * @return array Array of settings for @see woocommerce_admin_fields() function.
     */
    public static function getFields()
    {
        $shippingMethodOptions = self::getShippingMethodOptions();

        $settings = array(
            'title_general' => array(
                'id' => 'cliqueretire-settings-general-title',
                'name' => __( 'Configurações Gerais', 'woocommerce-cliqueretire' ),
                'type' => 'title',
                'desc' => 'Configurações gerais permitem que você conecte sua loja WooCommerce com a Clique Retire.',
                'desc_tip' => true,
            ),

            'enabled' => array(
                'id' => 'wc_settings_cliqueretire_enabled',
                'title' => __('Habilitado', 'woocommerce-cliqueretire'),
                'desc'     => 'Habilita ou desabilita a forma de envio pela Clique Retire',
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
                'default' => 'no',
                'type' => 'select',
                'options' => array(
                    'no' => __('Não', 'woocommerce-cliqueretire'),
                    'yes' => __('Sim', 'woocommerce-cliqueretire'),
                ),
            ),
            'api_id' => array(
                'id' => 'wc_settings_cliqueretire_api_id',
                'title' => __('API ID', 'woocommerce-cliqueretire'),
                'desc' => 'Seu API ID fornecido pela Clique Retire',
                'desc_tip' => true,
                'default' => '',
                'name' => 'api_id',
                'type' => 'text',
                'css' => 'min-width: 350px; border-radius: 3px;',
            ),
            'api_key' => array(
                'id' => 'wc_settings_cliqueretire_api_key',
                'title' => __('API Key', 'woocommerce-cliqueretire'),
                'desc' => 'Seu API Key fornecido pela Clique Retire',
                'desc_tip' => true,
                'default' => '',
                'name' => 'api_key',
                'type' => 'text',
                'css' => 'min-width: 350px; border-radius: 3px;',
            ),

            'debug' => array(
                'id' => 'wc_settings_cliqueretire_debug',
                'title' => __('Modo Debug', 'woocommerce-cliqueretire'),
                'desc' => __('Se o modo debug estiver habilitado, todos os eventos e requisições serão registrados em um arquivo debug', 'woocommerce-cliqueretire'),
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
                'default' => 'no',
                'type' => 'select',
                'options' => array(
                    'no' => __('Não', 'woocommerce-cliqueretire'),
                    'yes' => __('Sim', 'woocommerce-cliqueretire'),
                ),
            ),

            'environment' => array(
                'id' => 'wc_settings_cliqueretire_environment',
                'title' => __('Ambiente', 'woocommerce-cliqueretire'),
                'desc' => __('Se comunica com a Clique Retire no ambiente informado', 'woocommerce-cliqueretire'),
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
                'default' => 'production',
                'type' => 'select',
                'options' => array(
                    'staging' => __('Staging (Testes)', 'woocommerce-cliqueretire'),
                    'production' => __('Produção', 'woocommerce-cliqueretire'),
                ),
            ),

            'section_general_end' => array(
                 'id' => 'cliqueretire-settings-general-end',
                 'type' => 'sectionend',
            ),

            'title_order' => array(
                'id' => 'cliqueretire-settings-orders-title',
                'name' => __( 'Sincronização de pedidos', 'woocommerce-cliqueretire' ),
                'type' => 'title',
                'desc' => 'Configuração de quando um pedido deve ser enviado para Clique Retire.',
            ),

            'auto_sync_orders' => array(
                'id' => 'wc_settings_cliqueretire_auto_sync_orders',
                'title' => __('Sincronizar pedidos automaticamente', 'woocommerce-cliqueretire'),
                'desc' => __('Determines whether to automatically sync all orders, or only Cliqueretire Quoted or Mapped orders to Cliqueretire', 'woocommerce-cliqueretire'),
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
                'default' => 'all_cliqueretire',
                'type' => 'select',
                'options' => array(
                    'no' => __('Não', 'woocommerce-cliqueretire'),
                    'all' => __('Sim - Sincronizar automaticamente todos os novos pedidos', 'woocommerce-cliqueretire'),
                    'all_cliqueretire' => __('Sim - Sincronizar automaticamente somente os pedidos para Clique Retire', 'woocommerce-cliqueretire'),
               ),
            ),

            'section_order_end' => array(
                 'id' => 'cliqueretire-settings-order-end',
                 'type' => 'sectionend',
            )  
        );

        return apply_filters('wc_settings_cliqueretire_settings', $settings);
    }

    /**
     * Get WooCommerce Product Attributes
     *
     * @return array
     */
    public static function getProductAttributes()
    {
        $productAttributes = array();
        $placeHolder = array('' => '-- Please Select --');

        $attributeTaxonomies = wc_get_attribute_taxonomies();

        if (empty($attributeTaxonomies)) {
            return $placeHolder;
        }

        foreach ($attributeTaxonomies as $tax) {
            $productAttributes[$tax->attribute_name] = __($tax->attribute_label, 'woocommerce-cliqueretire');
        }

        // Add custom attribute as option
        $productAttributes['_custom'] = 'Use custom product attribute';

        return array_merge($placeHolder, $productAttributes);
    }

    /**
     * Get the shipping method options that should
     * be available for shipping method mapping
     *
     * @return array
     */
    public static function getShippingMethodOptions()
    {
        // If we have a WooCommerce installation
        // with Shipping Zones Support
        if (class_exists('WC_Shipping_Zones')) {
            $shippingMethodsWithZones = self::getShippingMethodsWithZones();
            $shippingMethodsWithoutZones = self::getShippingMethodsWithoutZones();

            $shippingMethodsOptions = array_merge($shippingMethodsWithZones, $shippingMethodsWithoutZones);
        }
        // Otherwise, fallback to legacy methods only display
        else {
            $shippingMethodsOptions = self::getShippingMethodsLegacy();
        }

        return $shippingMethodsOptions;
    }

    /**
     * Get the shipping method options with zone details
     *
     * @return array
     */
    protected static function getShippingMethodsWithZones()
    {
        $shippingMethodOptions = array();
        $zones = WC_Shipping_Zones::get_zones();

        foreach ($zones as $zone) {
            $shippingMethods = $zone['shipping_methods'];

            foreach ($shippingMethods as $shippingMethod) {
                if ($shippingMethod->id == 'ebox_cliqueretire') {
                    continue;
                }

                $shippingMethodKey = $shippingMethod->id . ':' . $shippingMethod->instance_id;
                $shippingMethodLabel = (property_exists($shippingMethod, 'title') ? $shippingMethod->title : $shippingMethod->method_title);

                $shippingMethodOptions[$shippingMethodKey] = sprintf(
                    '%s Zone — %s',
                    $zone['zone_name'],
                    $shippingMethodLabel
                );
            }
        }

        return $shippingMethodOptions;
    }

    /**
     * Get the shipping method options without zone details
     * - used to support legacy methods used in a zone-supported environment
     *
     * @return array
     */
    protected static function getShippingMethodsWithoutZones()
    {
        $shippingMethodOptions = array();
        $shippingMethods = WC_Shipping_Zones::get_zone_by()->get_shipping_methods();

        foreach ($shippingMethods as $shippingMethod) {
            if ($shippingMethod->id == 'ebox_cliqueretire' || $shippingMethod->id == 'ebox_cliqueretire_legacy') {
                continue;
            }

            $shippingMethodKey = $shippingMethod->id. ':' . $shippingMethod->instance_id;
            $shippingMethodLabel = (property_exists($shippingMethod, 'title') ? $shippingMethod->title : $shippingMethod->method_title);

            $shippingMethodOptions[$shippingMethodKey] = sprintf(
                'Default Zone - %s',
                $shippingMethodLabel
            );
        }

        return $shippingMethodOptions;
    }

    /**
     * Get the shipping method options using the legacy functionality
     *
     * @return array
     */
    protected static function getShippingMethodsLegacy()
    {
        $shippingMethodOptions = array();
        $shippingMethods = WC()->shipping()->get_shipping_methods();

        foreach ($shippingMethods as $shippingMethod) {
            if ($shippingMethod->id == 'ebox_cliqueretire' || $shippingMethod->id == 'ebox_cliqueretire_legacy') {
                continue;
            }

            $shippingMethodKey = $shippingMethod->id;
            $shippingMethodLabel = (property_exists($shippingMethod, 'method_title') ? $shippingMethod->method_title : $shippingMethod->title);

            $shippingMethodOptions[$shippingMethodKey] = sprintf(
                '%s',
                $shippingMethodLabel
            );
        }

        return $shippingMethodOptions;
    }
}
