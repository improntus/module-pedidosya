<?php

namespace Improntus\PedidosYa\Model;

use Improntus\PedidosYa\Helper\Data as HelperPedidosYa;

/**
 * Class Webservice
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
     * @var string
     */
    private $_accessToken;

    /**
     * @param HelperPedidosYa $helperPedidosYa
     */
    public function __construct(
        HelperPedidosYa $helperPedidosYa
    )
    {
        $this->_helper = $helperPedidosYa;

        $this->_clientId = $helperPedidosYa->getClientId();
        $this->_clientSecret = $helperPedidosYa->getClientSecret();
        $this->_username = $helperPedidosYa->getUsername();
        $this->_password = $helperPedidosYa->getPassword();

        $this->login();
    }

    /**
     * @return bool
     */
    public function login()
    {
        if($token = $this->_helper->getToken()) {
            $this->_accessToken = $token;
        } else {
            $curl = curl_init();
            curl_setopt_array($curl,
                [
                    CURLOPT_URL => "https://auth-api.pedidosya.com/v1/token?client_id={$this->_clientId}&client_secret={$this->_clientSecret}&grant_type=password&password={$this->_password}&username={$this->_username}",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_CUSTOMREQUEST => "POST",
                ]);

            $response = curl_exec($curl);

            if(curl_error($curl)) {
                $error = 'An error occurred while generating the token: '. curl_error($curl);
                $this->_helper->log($error);
                return false;
            }

            $response = json_decode($response);

            if(isset($response->access_token)) {
                $this->_accessToken = $response->access_token;
                $this->_helper->saveToken($response->access_token);
            } else {
                $this->_accessToken = null;
                return false;
            }

        }
        return true;
    }

    /**
     * @param $estimatePriceData
     * @return false|mixed
     */
    public function getEstimatePrice($estimatePriceData)
    {
        $curl = curl_init();
        $jsonData = json_encode($estimatePriceData);
        $url = "https://courier-api.pedidosya.com/v1/estimates/shippings";
        curl_setopt_array($curl,
            [
                CURLOPT_URL => $url,
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $jsonData,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    "Authorization: {$this->_accessToken}",
                    "Content-Type: application/json"
                ],
            ]);

        $response = curl_exec($curl);
        $responseObject = json_decode($response);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if($httpcode != 200) {
            $response = json_decode($response);
            $this->_helper->log('Error:');
            if(isset($response->message))
                $this->_helper->log($response->message);
        }

        if(isset($responseObject->price->total)) {
            return $responseObject;
        } else {
            return false;
        }
    }

    /**
     * @return false|mixed
     */
    public function getCategories()
    {
        $curl = curl_init();
        $url = "https://courier-api.pedidosya.com/v1/categories";
            curl_setopt_array($curl,
            [
                CURLOPT_URL => $url,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    "Authorization: {$this->_accessToken}",
                    "Content-Type: application/json"
                ],
            ]);

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if(curl_error($curl))
        {
            $error = 'There was an error requesting a estimate price: '. curl_error($curl);
            $this->_helper->log('Error:');
            $this->_helper->log($error);

            return false;
        } elseif($httpcode != 200) {
            $response = json_decode($response);
            $this->_helper->log('Error:');
            $this->_helper->log($response->messages[0]);
            return false;
        }

        return json_decode($response);
    }

    /**
     * @param $data
     * @return false|mixed
     */
    public function createShipping($data)
    {
        $curl = curl_init();
        $jsonData = json_encode($data);
        $url = "https://courier-api.pedidosya.com/v1/shippings";
        curl_setopt_array($curl,
            [
                CURLOPT_URL => $url,
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $jsonData,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    "Authorization: {$this->_accessToken}",
                    "Content-Type: application/json"
                ],
            ]);

        $response = curl_exec($curl);
        $responseObject = json_decode($response);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if($httpcode != 200) {
            if($httpcode == 400) {
                $this->_helper->log("Error: " .$responseObject->message);
                return false;
            }
            $error = 'There was an error in createShipping method: '. curl_error($curl);
            $this->_helper->log("Error: " .$error);
            return false;
        }

        if(isset($responseObject->price)) {
            return $responseObject;
        } else {
            return false;
        }
    }

    /**
     * @param $data
     * @return false|mixed
     */
    public function confirmShipping($data)
    {
        $curl = curl_init();
        $confirmData['id'] = $data->id;
        $url = "https://courier-api.pedidosya.com/v1/shippings/". $confirmData['id'] ."/confirm";
        curl_setopt_array($curl,
            [
                CURLOPT_URL => $url,
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $confirmData,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    "Authorization: {$this->_accessToken}",
                    "Content-Type: application/json"
                ],
            ]);

        $response = curl_exec($curl);
        $responseObject = json_decode($response);

        if(curl_error($curl))
        {
            $error = 'There was an error in confirmShipping method: '. curl_error($curl);
            $this->_helper->log('Error:');
            $this->_helper->log($error);

            return false;
        }

        if(isset($responseObject->price)) {
            return $responseObject;
        } else {
            return false;
        }
    }

    /**
     * @param $waypointData
     * @return false|mixed
     */
    public function getEstimateCoverage($waypointData)
    {
        $curl = curl_init();
        $jsonData = json_encode($waypointData);

        $mapRequired = 'false';
        $mapRequired = 'mapRequired='.$mapRequired;
        $url = "https://courier-api.pedidosya.com/v1/estimates/coverage?" .$mapRequired;
        curl_setopt_array($curl,
            [
                CURLOPT_URL => $url,
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $jsonData,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    "Authorization: {$this->_accessToken}",
                    "Content-Type: application/json"
                ],
            ]);

        $response = curl_exec($curl);

        if(curl_error($curl)) {
            $error = 'There was an error in getEstimateCoverage method: '. curl_error($curl);
            $this->_helper->log($error );

            return false;
        }
        return json_decode($response);
    }

    /**
     * @param $id
     * @param $reason
     * @return false|mixed
     */
    public function cancelShippingOrder($id, $reason)
    {
        $curl = curl_init();
        $url = "https://courier-api.pedidosya.com/v1/shippings/". $id ."/cancel";
        $reason = json_encode($reason);

        curl_setopt_array($curl,
            [
                CURLOPT_URL => $url,
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $reason,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    "Authorization: {$this->_accessToken}",
                    "Content-Type: application/json"
                ],
            ]);

        $response = curl_exec($curl);
        $responseObject = json_decode($response);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if($httpcode != 200) {
            $this->_helper->log('Error:');
            if(isset($responseObject->message)) {
                $this->_helper->log($responseObject->message);
                return $responseObject;
            }
            return false;
        }

        if(isset($responseObject->status)) {
            return $responseObject;
        } else {
            return false;
        }
    }

    /**
     * @param $id
     * @return false|mixed
     */
    public function getShippingOrderDetails($id)
    {
        $curl = curl_init();
        $url = "https://courier-api.pedidosya.com/v1/shippings/". $id;

        curl_setopt_array($curl,
            [
                CURLOPT_URL => $url,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    "Authorization: {$this->_accessToken}",
                    "Content-Type: application/json"
                ],
            ]);

        $response = curl_exec($curl);
        $responseObject = json_decode($response);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if($httpcode != 200) {
            $response = json_decode($response);
            $this->_helper->log('Error:');
            if(isset($response->messages))
                $this->_helper->log($response->messages[0]);
            return false;
        }

        if(isset($responseObject->status)) {
            return $responseObject;
        } else {
            return false;
        }
    }
}