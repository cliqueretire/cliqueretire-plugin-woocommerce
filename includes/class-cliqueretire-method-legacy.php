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

class Ebox_Cliqueretire_Method_Legacy extends Ebox_Cliqueretire_Method
{
    protected $api;
    protected $helper;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->api = new Ebox_Cliqueretire_Api();
        $this->log = new Ebox_Cliqueretire_Log();
        $this->helper = new Ebox_Cliqueretire_Helper();

        $this->id                   = 'ebox_cliqueretire_legacy';
        $this->title                = __('Clique Retire (Legacy)', 'woocommerce-cliqueretire');
        $this->method_title         = __('Clique Retire (Legacy)', 'woocommerce-cliqueretire');
        $this->method_description   = __(
            '<p>
                Faça com que a Clique Retire forneça a cotação do frete em tempo real.
                Basta ativar habilitar forma de envio e definir suas preferências para começar.
            </p>

            <div style="font-style:italic; padding:5px 20px; background-color:#fcf8e3; border-color:#faf2cc; color:#8a6d3b;">
                <h4>Aviso de depreciação: esta configuração é para a versão 2.6 do WooCommerce apenas e será depreciada em breve. </h4>
                <p>
                    Este método de envio está depreciado e foi substituído por métodos de envio que suportam <a href="https://docs.woocommerce.com/document/setting-up-shipping-zones/"> Zonas de envio </a>.
                    Você pode habilitar e configurar cotações ao vivo por meio de zonas de envio. <a href="'. admin_url('admin.php?page=wc-settings&tab=shipping').'"> Clique aqui para configurar suas Zonas de Envio </a>.
                </p>
            </div>'
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
        $this->quote_enabled           = $this->get_option('enabled');
        $this->title                   = $this->get_option('title');
        $this->filter_enabled          = $this->get_option('filter_enabled'); // depreciated
        $this->filter_enabled_products = $this->get_option('filter_enabled_products'); // depreciated
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

        // Load the settings form, but only when the settings form fields is required
        add_filter('woocommerce_settings_api_form_fields_ebox_cliqueretire', array($this, 'init_form_fields'));

        $this->init_form_fields();
        $this->init_settings();

        // *****************
        // Shipping Method Save Event
        // *****************

        // Save settings in admin
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init_form_fields()
    {
        $settings = new Ebox_Cliqueretire_Settings_Method();
        $this->form_fields = $settings->getFields();

        return $this->form_fields;
    }

    /**
     * Add shipping method.
     *
     * Add shipping method to WooCommerce.
     *
     */
    public static function add_shipping_method($methods)
    {
        if (class_exists('Ebox_Cliqueretire_Method_Legacy')) {
            $methods['ebox_cliqueretire_legacy'] = 'Ebox_Cliqueretire_Method_Legacy';
        }

        return $methods;
    }
}