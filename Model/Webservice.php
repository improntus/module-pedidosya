<?php
namespace Improntus\PedidosYa\Model;

use Improntus\PedidosYa\Helper\Data as HelperPedidosYa;

/**
 * Class Webservice
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2025 Improntus (http://www.improntus.com/)
 * @package Improntus\PedidosYa\Model
 */
class Webservice
{
    /**
     * @var string
     */
    protected $_clientId;

    /**
     * @var string
     */
    protected $_clientSecret;

    /**
     * @var string
     */
    protected $_username;

    /**
     * @var string
     */
    protected $_password;

    /**
     * @var HelperPedidosYa
     */
    protected $_helper;

    /**
     * @var bool
     */
    protected $_integrationMode;

    /**
     * @var string
     */
    private $_accessToken;

    /**
     * @param HelperPedidosYa $helperPedidosYa
     */
    public function __construct(
        HelperPedidosYa $helperPedidosYa
    ) {
        /**
         * @todo: Replace CURL With Magento\Framework\HTTP\ClientInterface
         */
        $this->_helper = $helperPedidosYa;
    }

    public function login($storeId = null)
    {
        // Get Integration Mode
        $this->_integrationMode = $this->_helper->getIntegrationMode($storeId);

        /**
         * Determine Integration Mode
         */
        switch ($this->_integrationMode) {
            case "api":
                // API
                $this->_accessToken = $this->_helper->getApiToken($storeId);
                break;
            case "eco":
            default:
                // E-COMMERCE
                $this->loginEcommerce($storeId);
                break;
        }
    }

    /**
     * Login using Legacy Mode
     * @param $storeId
     * @return false|void
     */
    private function loginEcommerce($storeId)
    {
        // Is there any token?
        if ($token = $this->_helper->getToken($storeId)) {
            $this->_accessToken = $token;
        } else {
            /**
             *   Get Credentials
             */
            $this->_clientId = $this->_helper->getClientId($storeId);
            $this->_clientSecret = $this->_helper->getClientSecret($storeId);
            $this->_username = $this->_helper->getUsername($storeId);
            $this->_password = $this->_helper->getPassword($storeId);

            /**
             * Init Curl
             */
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $curl = curl_init();

            /**
             * Prepare Data & Send Request
             */
            $WebserviceURL = $this->_helper->getWebServiceURL("token?client_id={$this->_clientId}&client_secret={$this->_clientSecret}&password={$this->_password}&username={$this->_username}&grant_type=password", true);
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            curl_setopt_array(
                $curl,
                [
                    CURLOPT_URL => $WebserviceURL,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_CUSTOMREQUEST => "POST",
                ]
            );
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $response = curl_exec($curl);

            /**
             * Has Error?
             */
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            if ($error = curl_error($curl)) {
                $this->_helper->log("An error occurred while generating the token: $error");
                return false;
            }

            /**
             * Decode Response
             */
            $response = json_decode($response);

            /**
             * Has Access Token?
             */
            if (isset($response->access_token)) {
                $this->_accessToken = $response->access_token;
                $this->_helper->saveToken($response->access_token, $storeId);
            } else {
                $this->_accessToken = null;
                return false;
            }
        }
    }


    /**
     * @param $estimatePriceData
     * @return false|mixed
     */
    public function getEstimatePrice($estimatePriceData)
    {
        /**
         *  Get AccessToken
         */
        $this->login();

        /**
         * Init Curl
         */
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $curl = curl_init();

        /**
         * Prepare Data & Send Request
         */
        $jsonData = json_encode($estimatePriceData);

        /**
         * Get Endpoint depend Integration Mode
         */
        switch ($this->_integrationMode) {
            case "api":
                $endpoint = "shippings/estimates";
                break;
            case "eco":
            default:
                $endpoint =  "estimates/shippings";
                break;
        }

        $url = $this->_helper->getWebServiceURL($endpoint);
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL => $url,
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $jsonData,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    "Authorization: {$this->_accessToken}",
                    "Content-Type: application/json",
                    "Origin: Magento"
                ],
            ]
        );
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $response = curl_exec($curl);

        /**
         * Decode Response
         */
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $responseObject = json_decode($response);

        /**
         * Get HTTP Code
         */
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($httpcode != 200) {
            $response = json_decode($response);
            $this->_helper->log('Error Webservice:');
            if (isset($response->message)) {
                $this->_helper->log($response->message);
            }
        }

        /**
         * Has Price?
         */
        if ($this->_integrationMode == "api" && isset($responseObject->deliveryOffers[0]->pricing->total)) {
            return $responseObject;
        } else {
            return isset($responseObject->price->total) ? $responseObject : false;
        }
    }

    /**
     * @return false|mixed
     */
    public function getCategories()
    {
        /**
         *  Get AccessToken
         */
        $this->login();

        /**
         * Init Curl
         */
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $curl = curl_init();

        /**
         * Send Request
         */
        $url = $this->_helper->getWebServiceURL("categories");
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL => $url,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    "Authorization: {$this->_accessToken}",
                    "Content-Type: application/json",
                    "Origin: Magento"
                ],
            ]
        );
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $response = curl_exec($curl);

        /**
         * Get HTTP Code
         */
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        /**
         * Has Error?
         */
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        if ($error = curl_error($curl)) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $this->_helper->log("ERROR: there was an error requesting an estimate price: $error");
            return false;
        }

        /**
         * Compare http code
         */
        if ($httpcode != 200) {
            $response = json_decode($response) ?: [];
            if (isset($response->messages)) {
                $this->_helper->log('Error:');
                $this->_helper->log($response->messages[0]);
            }
            return false;
        }

        return json_decode($response);
    }

    /**
     * @param $data
     * @return false|mixed
     */
    public function createShipping($data, $storeId = null)
    {
        /**
         *  Get AccessToken
         */
        $this->login($storeId);

        /**
         * Init Curl
         */
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $curl = curl_init();

        /**
         * Prepare Data & Send Request
         */
        $jsonData = json_encode($data);
        $url = $this->_helper->getWebServiceURL("shippings");

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL => $url,
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $jsonData,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    "Authorization: {$this->_accessToken}",
                    "Content-Type: application/json",
                    "Origin: Magento"
                ],
            ]
        );
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $response = curl_exec($curl);

        /**
         * Decode Response
         */
        $responseObject = json_decode($response);

        /**
         * Get HTTP Code
         */
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        /**
         * Compare HTTP Code
         */
        if ($httpcode != 200) {
            /**
             * 400 Web Service ERROR
             */
            if ($httpcode == 400) {
                $this->_helper->log("Error WebService: {$responseObject->message}");
                return $responseObject;
            }

            /**
             * Other error
             */
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $this->_helper->log("Error WebService: There was an error in createShipping method: ". curl_error($curl));
        }

        return $responseObject;
    }

    /**
     * @param $data
     * @return false|mixed
     */
    public function confirmShipping($data, $storeId = null)
    {
        /**
         *  Get AccessToken
         */
        $this->login($storeId);

        /**
         * Init Curl
         */
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $curl = curl_init();

        /**
         * Prepare Data & Send Request
         */
        $confirmData['id'] = $data->id;
        $url = $this->_helper->getWebServiceURL("shippings/{$data->id}/confirm");
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL => $url,
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $confirmData,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    "Authorization: {$this->_accessToken}",
                    "Content-Type: application/json",
                    "Origin: Magento"
                ],
            ]
        );
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $response = curl_exec($curl);

        /**
         * Decode Response
         */
        $responseObject = json_decode($response);

        /**
         * Get HTTP Code
         */
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        /**
         * Compare HTTP Code
         */
        if ($httpcode != 200) {
            /**
             * 400 Web Service ERROR
             */
            if ($httpcode == 400) {
                $this->_helper->log("Error WebService: {$responseObject->message}");
                return $responseObject;
            }

            /**
             * Other Error
             */
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $this->_helper->log("Error WebService - There was an error in confirmShipping method:" . curl_error($curl));
        }

        return $responseObject;
    }

    /**
     * @param $waypointData
     * @return false|mixed
     */
    public function getEstimateCoverage($waypointData)
    {
        /**
         *  Get AccessToken
         */
        $this->login();

        /**
         * Init Curl
         */
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $curl = curl_init();

        /**
         * Encode Waypoint Data
         */
        $jsonData = json_encode($waypointData);

        /**
         * Send Request
         */
        $url = $this->_helper->getWebServiceURL("estimates/coverage?mapRequired=false");
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL => $url,
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $jsonData,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    "Authorization: {$this->_accessToken}",
                    "Content-Type: application/json",
                    "Origin: Magento"
                ],
            ]
        );
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $response = curl_exec($curl);

        /**
         * Decode Response
         */
        $responseObject = json_decode($response);

        /**
         * Get HTTP Code
         */
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        /**
         * Compare HTTP Code
         */
        if ($httpcode != 200) {
            /**
             * 400 Web Service ERROR
             */
            if ($httpcode == 400) {
                $this->_helper->log("Error WebService: {$responseObject->message}");
                return $responseObject;
            }

            /**
             * Other Error
             */
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $this->_helper->log("Error WS There was an error in getEstimateCoverage method: " . curl_error($curl));
        }

        return $responseObject;
    }

    /**
     * @param $id
     * @param $reason
     * @return false|mixed
     */
    public function cancelShippingOrder($id, $reason, $storeId = null)
    {
        /**
         *  Get AccessToken
         */
        $this->login($storeId);

        /**
         * Init Curl
         */
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $curl = curl_init();

        /**
         * Prepare Data & Send Request
         */
        $url = $this->_helper->getWebServiceURL("shippings/{$id}/cancel");
        $reason = json_encode($reason);
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL => $url,
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $reason,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    "Authorization: {$this->_accessToken}",
                    "Content-Type: application/json",
                    "Origin: Magento"
                ],
            ]
        );
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $response = curl_exec($curl);

        /**
         * Decode Response
         */
        $responseObject = json_decode($response);

        /**
         * Get HTTP Code
         */
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        /**
         * Compare HTTP Code
         */
        if ($httpcode != 200) {
            /**
             * 400 Web Service ERROR
             */
            if ($httpcode == 400) {
                $this->_helper->log("Error WebService: {$responseObject->message}");
                return $responseObject;
            }

            /**
             * Other Error
             */
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $this->_helper->log("Error WS There was an error in confirmShipping method: " . curl_error($curl));
        }

        return $responseObject;
    }

    /**
     * @param $id
     * @return false|mixed
     */
    public function getShippingOrderDetails($id)
    {
        /**
         *  Get AccessToken
         */
        $this->login();

        /**
         * Init Curl
         */
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $curl = curl_init();

        /**
         * Prepare Data & Send Request
         */
        $url = $this->_helper->getWebServiceURL("shippings/{$id}");
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL => $url,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    "Authorization: {$this->_accessToken}",
                    "Content-Type: application/json",
                    "Origin: Magento"
                ],
            ]
        );
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $response = curl_exec($curl);

        /**
         * Decode Response
         */
        $responseObject = json_decode($response);

        /**
         * Get HTTP Code
         */
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        /**
         * Compare HTTP Code
         */
        if ($httpcode != 200) {
            /**
             * 400 Web Service ERROR
             */
            if ($httpcode == 400) {
                $this->_helper->log("Error WebService: {$responseObject->message}");
                return $responseObject;
            }

            /**
             * Other Error
             */
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $this->_helper->log("Error WS There was an error in getShippingOrderDetails method: " . curl_error($curl));
        }

        return $responseObject;
    }
}
