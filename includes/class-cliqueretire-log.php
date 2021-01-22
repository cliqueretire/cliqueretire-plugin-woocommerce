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

class Ebox_Cliqueretire_Log
{
    public function add($errorType, $message, $metaData = null, $severity = 'info')
    {
        // If debug mode is active, log all info serverities, otherwise log only errors
        if (get_option('wc_settings_cliqueretire_debug') == 'yes' || $severity == 'error') {
            error_log('-- ' . $errorType . ' --');
            error_log($message);

            if (!is_null($metaData)) {
                error_log(json_encode($metaData));
            }
        }
    }

    /**
    * add function.
    *
    * Uses the build in logging method in WooCommerce.
    * Logs are available inside the System status tab
    *
    * @access public
    * @param  string|array|object
    * @return void
    */
    public function exception($exception)
    {
        error_log($exception->getMessage());
    }
}