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

class Ebox_Cliqueretire_Api
{
    const API_ENDPOINT_PRODUCTION = 'https://services.cliqueretire.com.br/ebox/api/v1';
    const API_ENDPOINT_STAGING = 'https://services-stg.cliqueretire.com.br/ebox/api/v1';
    const API_TIMEOUT = 30;
    const API_USER_AGENT = 'Ebox_Cliqueretire for WooCommerce';

    private $apiKey = null;
    public $debug = false;
    protected $object;
    public function __construct()
    {
        $this->settings = new Ebox_Cliqueretire_Settings();
        $this->log = new Ebox_Cliqueretire_Log();
        $this->apiId = get_option('wc_settings_cliqueretire_api_id');
        $this->apiKey = get_option('wc_settings_cliqueretire_api_key');
        $this->debug = get_option('wc_settings_cliqueretire_debug');
        $this->environment = get_option('wc_settings_cliqueretire_environment');
        $this->object = new Ebox_Cliqueretire_Object();
    }

    private function getApiKey()
    {
        return $this->apiKey;
    }

    private function getApiId()
    {
        return $this->apiId;
    }

    public function setApiId($apiId)
    {
        return $this->apiId = $apiId;
    }

    public function setApiKey($apiKey)
    {
        return $this->apiKey = $apiKey;
    }

    public function setEnvironment($environment)
    {
        return $this->environment = $environment;
    }

    public function getApiUrl($path)
    {
        if ( $this->environment == 'staging' ) {
            return self::API_ENDPOINT_STAGING . '/' . $path;
        }
        else {
            return self::API_ENDPOINT_PRODUCTION . '/' . $path;
        }
    }

    public function getApiArgs($requestData, $requestMethod)
    {
        $apiArgs = array(
            'blocking'     => true,
            'method'       => $requestMethod,
            'timeout'      => self::API_TIMEOUT,
            'user-agent'   => self::API_USER_AGENT . 'v' . EBOX_CLIQUERETIRE_VERSION,
            'headers'      => array(
                'content-type' => 'application/json',
                'api-id' => $this->getApiId(),
                'api-key' => $this->getApiKey()
                )
        );

        if (!empty($requestData)) {
            $apiArgs['body'] = json_encode($requestData);
        }

        return $apiArgs;
    }

    public function call($uri, $requestData, $requestMethod = 'POST', $exceptionOnResponseError = true)
    {
        $url = $this->getApiUrl($uri);
        $args = $this->getApiArgs($requestData, $requestMethod);

        $this->log->add(
            'CLIQUERETIRE - API REQUEST',
            $uri,
            array(
                'url' => $url,
                'requestData' => $requestData
            )
        );

        try {
            $response = wp_remote_request(
                $url,
                $args
            );

            $responseCode = wp_remote_retrieve_response_code($response);

            if ($exceptionOnResponseError) {
                if ($responseCode < 200 ||
                    $responseCode > 300) {
                    throw new Exception('An API Request Error Occured');
                }
            }
        }
        catch (Exception $e) {
            $metaData = array(
                'url' => $url,
                'requestData' => $requestData,
                'responseData' => wp_remote_retrieve_body($response)
            );

            $this->log->exception($e, $metaData);
            $this->log->add(
                'CLIQUERETIRE - API REQUEST ERROR',
                $uri,
                $metaData,
                'error'
            );

            return false;
        }

        $jsonResponseData = wp_remote_retrieve_body($response);

        $responseData = json_decode($jsonResponseData);

        $this->log->add(
            'CLIQUERETIRE - API RESPONSE',
            $uri,
            array(
                'url' => $url,
                'requestData' => $requestData,
                'responseData' => $responseData
            )
        );

        return $responseData;
    }

    public function getQuote($quoteData)
    {
        $quote = $this->call('shippingRates/'.$quoteData['url'].'/'.$quoteData['zipCode'], null, 'GET', false);

        if (!$quote) {
            return false;
        }

        return $quote;
    }

    public function sendOrder($orderData)
    {

        $order = $orderData['retailer_reference'];
        $phone = $orderData['receiver_contact_number'];
        $email = $orderData['user_attributes']['email'];
        $fullName = $orderData['user_attributes']['first_name'].' '.$orderData['user_attributes']['last_name'];
        $orderAddress = $orderData['delivery_address'];
        $boxCode = explode(' ',trim($orderAddress))[0];

        $requestData = array(
            "orderNumber" => (string) $order,
            "endCustomer" => array(
                "fullName" => $fullName,
                "cellphone" => "55".$phone,
                "email" => $email
            ),
            "volumes" => array(
                array(
                    "expressNumber" => (string) $order
                )
            ),
            "boxCode" => (string) $boxCode
        );

        $order = $this->call('orders', $requestData, 'POST', false);

        if (!$order) {
            return false;
        }

        return $order;
    }

    public function getOrdersMerchant()
    {
        return $this->call('orders', null, 'GET', false);
    }

    public function putMerchant($merchantData)
    {
        $requestData = array(
            'merchant' => $merchantData
        );

        return $this->call('merchant', $requestData, 'PUT');
    }
}