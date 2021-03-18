<?php
/*
 * Plugin Name:             WooCommerce Clique Retire
 * Description:             Através do plugin é possível oferecer centenas de pontos de retirada integrados com a Clique Retire
 * Version:                 1.0.0
 * Author:                  Clique Retire
 * Author URL:              http://www.cliqueretire.com.br
 * Text Domain:             woocommerce-cliqueretire
 * WC requires at least:    2.6.0
 * WC Tested Up To:         3.9.3
 */

define('EBOX_CLIQUERETIRE_VERSION', '1.0.0');

// import core classes
include_once('includes/class-cliqueretire-helper.php');
include_once('includes/class-cliqueretire-settings.php');
include_once('includes/class-cliqueretire-settings-method.php');
include_once('includes/class-cliqueretire-core.php');

include_once('includes/class-cliqueretire-log.php');
include_once('includes/class-cliqueretire-order.php');
include_once('includes/class-cliqueretire-object.php');
include_once('includes/class-cliqueretire-api.php');

function init_cliqueretire_core()
{
//     include_once('includes/class-upgrade.php');
//     $upgrade = new Ebox_Cliqueretire_Upgrade();
//     $upgrade->run();
    
    // import helper classes   
    include_once('includes/class-cliqueretire-data-mapper-order.php');
    include_once('includes/class-cliqueretire-data-mapper-order-v26.php');
    include_once('includes/class-cliqueretire-data-mapper-order-item.php');
    include_once('includes/class-cliqueretire-data-mapper-order-item-v26.php');
    include_once('includes/class-cliqueretire-shipment.php');

    $cliqueretire = Ebox_Cliqueretire_Core::instance();

    add_filter(
        'woocommerce_settings_tabs_array',
        array(
            'Ebox_Cliqueretire_Settings',
            'addSettingsTab',
        ),
        50
    );
}

// add cliqueretire core functionality
add_action('woocommerce_init', 'init_cliqueretire_core', 99999);

// register cliqueretire script
// add_action('admin_enqueue_scripts', 'register_cliqueretire_script');

// add cliqueretire widget
add_action('woocommerce_after_shipping_rate', 'addCliqueRetireWidget', 10, 2);

function addCliqueRetireWidget($method, $index)
{
    $chosen_methods = WC()->session->get('chosen_shipping_methods');
    $chosen_shipping = $chosen_methods[0];

    if ($chosen_shipping === 'Ebox_Cliqueretire' && $method->get_id() === 'Ebox_Cliqueretire')
    {
        $api = new Ebox_Cliqueretire_Api();
        $url_api = $api->getApiUrl('');

        include "pages/cliqueretire-widget.php";
    }

}

function register_cliqueretire_script()
{
    wp_register_script(
        'cliqueretire-script',
        plugin_dir_url(__FILE__) . 'assets/js/cliqueretire.js',
        array('jquery'),
        EBOX_CLIQUERETIRE_VERSION,
        true
    );
}

function init_cliqueretire_method()
{   
    include_once('includes/class-cliqueretire-method.php');
    include_once('includes/class-cliqueretire-method-legacy.php');

    // add shipping methods
    add_filter('woocommerce_shipping_methods', array('Ebox_Cliqueretire_Method', 'add_shipping_method'));
    add_filter('woocommerce_shipping_methods', array('Ebox_Cliqueretire_Method_Legacy', 'add_shipping_method'));
}

// add shipping method class
add_action('woocommerce_shipping_init', 'init_cliqueretire_method');

// register the cron job hooks when activating / de-activating the module
register_activation_hook(__FILE__, array('Ebox_Cliqueretire_Core', 'order_sync_schedule'));
register_deactivation_hook(__FILE__, array('Ebox_Cliqueretire_Core', 'order_sync_deschedule'));
