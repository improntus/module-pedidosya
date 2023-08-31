<?php

namespace Improntus\PedidosYa\Helper;

use Improntus\PedidosYa\Model\Waypoint;
use Magento\Framework\HTTP\ClientInterface;
use Improntus\PedidosYa\Model\WaypointFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Convert\Order;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Shipping\Model\ShipmentNotifier;
use Magento\Shipping\Model\Tracking\Result;
use Magento\Shipping\Model\Tracking\Result\StatusFactory;
use Magento\Shipping\Model\Tracking\ResultFactory;
use Magento\Store\Model\ScopeInterface;
use Improntus\PedidosYa\Model\TokenFactory;
use Improntus\PedidosYa\Model\PedidosYaFactory;
use Improntus\PedidosYa\Helper\Logger\Logger as PedidosYaLogger;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class Data
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2023 Improntus
 * @package Improntus\PedidosYa\Helper
 */
class Data extends AbstractHelper
{
    const PEDIDOSYA_OK                              = 'pedidosya_ok';
    const PEDIDOSYA_ERROR_STATUS                    = 'not_allowed_status';
    const PEDIDOSYA_ERROR_TIME                      = 'not_allowed_time';
    const PEDIDOSYA_ERROR_WS                        = 'pedidosya_error_ws';
    const PEDIDOSYA_ERROR_DATA                      = 'pedidosya_error_data';
    const PEDIDOSYA_ERROR_RIDER                     = 'not_rider_available';
    const PEDIDOSYA_DEFAULT_VALUES_COUNTRY          = ["AR" => 15000, "UY" => 100000, "CL" => 100000, "PA" => 500, "BO" => 1000, "DM" => 2000, "PY" => 1000000,
                                                       "VE" => 40, "PE" => 200, "EC" => 100, "GT" => 600, "CR" => 100000, "HN" => 2000, "SV" => 35, "NI" => 2500];

    /**
     * PeYa Auth Endpoint
     */
    const AUTH_ENDPOINT = "https://auth-api.pedidosya.com/%s/%s";

    /**
     * PeYa Endpoint
     */
    const API_ENDPOINT = "https://courier-api.pedidosya.com/%s/%s";

    /**
     * PeYa Token Expiration in Minutes
     */
    const TOKEN_EXPIRATION = 45;

    /**
     * PeYa API Version
     */
    protected $apiVersion = "v1";

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var TokenFactory
     */
    protected $_tokenFactory;

    /**
     * @var WaypointFactory
     */
    protected $_waypointFactory;

    /**
     * @var PedidosYaFactory
     */
    protected $_pedidosYaFactory;

    /**
     * @var ShipmentNotifier
     */
    protected $_shipmentNotifier;

    /**
     * @var TrackFactory
     */
    protected $_trackFactory;

    /**
     * @var Order
     */
    protected $_convertOrder;

    /**
     * @var StatusFactory
     */
    protected $_trackStatusFactory;
    /**
     * @var ResultFactory
     */
    protected $_trackResultFactory;

    /**
     * @var PedidosYaLogger
     */
    protected $_pedidosYaLogger;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ClientInterface
     */
    protected $httpClient;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param TokenFactory $tokenFactory
     * @param WaypointFactory $waypointFactory
     * @param ShipmentNotifier $shipmentNotifier
     * @param TrackFactory $trackFactory
     * @param Order $convertOrder
     * @param StatusFactory $trackStatusFactory
     * @param ResultFactory $trackResultFactory
     * @param PedidosYaFactory $pedidosYaFactory
     * @param PedidosYaLogger $pedidosYaLogger
     * @param TimezoneInterface $timezone
     * @param StoreManagerInterface $storeManager
     * @param ClientInterface $httpClient
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        TokenFactory $tokenFactory,
        WaypointFactory $waypointFactory,
        ShipmentNotifier $shipmentNotifier,
        TrackFactory $trackFactory,
        Order $convertOrder,
        StatusFactory $trackStatusFactory,
        ResultFactory $trackResultFactory,
        PedidosYaFactory $pedidosYaFactory,
        PedidosYaLogger $pedidosYaLogger,
        TimezoneInterface $timezone,
        StoreManagerInterface $storeManager,
        ClientInterface $httpClient
    ) {
        $this->_scopeConfig         = $scopeConfig;
        $this->_tokenFactory        = $tokenFactory;
        $this->_waypointFactory     = $waypointFactory;
        $this->_pedidosYaFactory    = $pedidosYaFactory;
        $this->_shipmentNotifier    = $shipmentNotifier;
        $this->_trackFactory        = $trackFactory;
        $this->_convertOrder        = $convertOrder;
        $this->_trackStatusFactory  = $trackStatusFactory;
        $this->_trackResultFactory  = $trackResultFactory;
        $this->_pedidosYaLogger     = $pedidosYaLogger;
        $this->timezone             = $timezone;
        $this->storeManager         = $storeManager;
        $this->httpClient           = $httpClient;
        parent::__construct($context);
    }

    /**
     * @return string
     */
    public function getIntegrationMode($storeId = null)
    {
        // Get Integration Mode
        $integrationMode = $this->_scopeConfig->isSetFlag(
            'shipping/pedidosya/integration_mode',
            ScopeInterface::SCOPE_STORE,
            $storeId ?: $this->getCurrentStoreId()
        );

        // Update API Version
        $this->apiVersion = $integrationMode ? 'v3' : 'v1';

        // Return Integration Mode
        return $integrationMode;
    }

    /**
     * @return string
     */
    public function getClientId($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'shipping/pedidosya/ecommerce/client_id',
            ScopeInterface::SCOPE_STORE,
            $storeId ?: $this->getCurrentStoreId()
        );
    }

    /**
     * @return string
     */
    public function getClientSecret($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'shipping/pedidosya/ecommerce/client_secret',
            ScopeInterface::SCOPE_STORE,
            $storeId ?: $this->getCurrentStoreId()
        );
    }

    /**
     * @return string
     */
    public function getUsername($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'shipping/pedidosya/ecommerce/username',
            ScopeInterface::SCOPE_STORE,
            $storeId ?: $this->getCurrentStoreId()
        );
    }

    /**
     * @return string
     */
    public function getPassword($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'shipping/pedidosya/ecommerce/password',
            ScopeInterface::SCOPE_STORE,
            $storeId ?: $this->getCurrentStoreId()
        );
    }

    /**
     * @return string
     */
    public function getApiToken($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'shipping/pedidosya/api/token',
            ScopeInterface::SCOPE_STORE,
            $storeId ?: $this->getCurrentStoreId()
        );
    }

    /**
     * @return string
     */
    public function getVolumeAttribute($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'carriers/pedidosya/product_volume_attribute',
            ScopeInterface::SCOPE_STORE,
            $storeId ?: $this->getCurrentStoreId()
        );
    }


    /**
     * @return bool
     */
    public function isActive($storeId = null)
    {
        return $this->_scopeConfig->isSetFlag(
            'carriers/pedidosya/active',
            ScopeInterface::SCOPE_STORE,
            $storeId ?:$this->getCurrentStoreId()
        );
    }

    /**
     * @return int
     */
    public function getAssumeShippingAmount($storeId = null)
    {
        return (int)$this->_scopeConfig->getValue(
            'carriers/pedidosya/assume_shipping_amount',
            ScopeInterface::SCOPE_STORE,
            $storeId ?:$this->getCurrentStoreId()
        );
    }

    /**
     * @return float
     */
    public function getMaxWeight($storeId = null)
    {
        return (float)$this->_scopeConfig->getValue(
            "carriers/pedidosya/max_package_weight",
            ScopeInterface::SCOPE_STORE,
            $storeId ?:$this->getCurrentStoreId()
        );
    }

    /**
     * @return bool
     */
    public function getAutomaticShipment($storeId = null)
    {
        return $this->_scopeConfig->isSetFlag(
            "carriers/pedidosya/automatic_shipment",
            ScopeInterface::SCOPE_STORE,
            $storeId ?:$this->getCurrentStoreId()
        );
    }

    /**
     * @return string
     */
    public function getMode($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'carriers/pedidosya/mode',
            ScopeInterface::SCOPE_STORE,
            $storeId ?:$this->getCurrentStoreId()
        );
    }

    /**
     * @return string
     */
    public function getCategory($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'carriers/pedidosya/category',
            ScopeInterface::SCOPE_STORE,
            $storeId ?:$this->getCurrentStoreId()
        );
    }

    /**
     * @return int
     */
    public function getPreparationTime($storeId = null)
    {
        return (int)$this->_scopeConfig->getValue(
            'carriers/pedidosya/preparation_time',
            ScopeInterface::SCOPE_STORE,
            $storeId ?:$this->getCurrentStoreId()
        );
    }

    /**
     * @return false|string[]
     */
    public function getStatusOrderAllowed($storeId = null)
    {
        $statusOrderAllowed = $this->_scopeConfig->getValue(
            'carriers/pedidosya/status_allowed',
            ScopeInterface::SCOPE_STORE,
            $storeId ?:$this->getCurrentStoreId()
        );
        return explode(',', $statusOrderAllowed);
    }

    /**
     * @return string
     */
    public function getTitle($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'carriers/pedidosya/title',
            ScopeInterface::SCOPE_STORE,
            $storeId ?:$this->getCurrentStoreId()
        );
    }

    /**
     * @return boolean
     */
    public function getFreeShipping($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'carriers/pedidosya/free_shipping',
            ScopeInterface::SCOPE_STORE,
            $storeId ?:$this->getCurrentStoreId()
        );
    }

    /**
     * @return string
     */
    public function getDefaultCountryAmount($storeId = null)
    {
        return $this->_scopeConfig->getValue(
            'carriers/pedidosya/country_max_amount_insured',
            ScopeInterface::SCOPE_STORE,
            $storeId ?:$this->getCurrentStoreId()
        );
    }

    /**
     * @return bool
     */
    public function getDebugMode($storeId = null)
    {
        return $this->_scopeConfig->isSetFlag(
            'carriers/pedidosya/debug',
            ScopeInterface::SCOPE_STORE,
            $storeId ?:$this->getCurrentStoreId()
        );
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrentStoreId()
    {
        return $this->storeManager->getStore()->getStoreId();
    }

    /**
     * @param $token
     */
    public function saveToken($token, $storeId = null)
    {
        $storeId = $storeId ?:$this->getCurrentStoreId();
        $tokenFactory = $this->_tokenFactory->create()
                             ->getCollection()
                             ->addFieldToFilter('store_id', $storeId)
                             ->getFirstItem();
        $tokenFactory->setStoreId($storeId);
        $tokenFactory->setToken($token);
        $tokenFactory->setLatestUse($this->timezone->date()->format("Y-m-d H:i:s"));
        $tokenFactory->save();
    }

    /**
     * @return false
     */
    public function getToken($storeId = null)
    {
        /**
         * Get storeId
         */
        $storeId = $storeId?: $this->getCurrentStoreId();

        /**
         * Get Access Token
         */
        $tokenFactory = $this->_tokenFactory->create();
        $token = $tokenFactory->getCollection()
            ->addFieldToFilter('store_id', $storeId)
            ->getFirstItem();

        /**
         * Has Access Token?
         */
        if (!$token->getToken()) {
            return false;
        }

        /**
         * Check Expiration
         */
        $now = strtotime($this->timezone->date()->format("Y-m-d H:i:s"));
        $difference = ($now - strtotime($token->getLatestUse()))/ 60;
        if ($difference > self::TOKEN_EXPIRATION) {
            return false;
        }

        /**
         * set Access Token
         */
        $accessToken = $token->getToken();

        /**
         * This Request is only to validate that the access token is valid
         */
        if (!$this->checkAccessToken($accessToken)) {
            return false;
        }

        /**
         * Update Latest Use
         */
        $token->setLatestUse($this->timezone->date()->format("Y-m-d H:i:s"));
        $token->save();

        /**
         * Return Access Token
         */
        return $accessToken;
    }

    /**
     * Allows to determine if the token is really valid for the API
     * @param $token
     * @return true
     */
    protected function checkAccessToken($token)
    {
        /**
         * Get Endpoint
         */
        $WebServiceURL = $this->getWebServiceURL("categories");

        /**
         * Set Headers
         */
        $this->httpClient->setHeaders(
            [
                "Authorization" => $token,
                "Content-Type" => "application/json",
                "Origin" => "Magento"
            ]
        );

        /**
         * Send Request
         */
        $this->httpClient->get($WebServiceURL);

        /**
         * Get Status Code
         */
        $httpCode = $this->httpClient->getStatus();

        /**
         * Decode Request
         */
        $WebServiceResponse = json_decode($this->httpClient->getBody()) ?: "";

        /**
         * Compare HTTP Code
         */
        if ($httpCode !== 200) {
            $message = isset($WebServiceResponse->messages) ? $WebServiceResponse->messages[0] : "Unknown";
            $this->log("Error: {$message} - Invalid Access Token REFRESH");
            return false;
        }

        /**
         * Return Access Token Valid
         */
        return true;
    }

    /**
     * @param $destWaypoint
     * @return mixed
     */
    public function getClosestSourceWaypoint($destWaypoint)
    {
        $waypointCollection = $this->_waypointFactory->create()->getCollection()->addFieldToFilter('enabled', 1);
        $closestSourceWaypoint = false;
        $minorDistance = 0;

        foreach ($waypointCollection as $_sourceWaypoint) {
            $theta = $destWaypoint->waypoints[0]->longitude - $_sourceWaypoint['longitude'];
            $distance = (sin(deg2rad($destWaypoint->waypoints[0]->latitude)) *
                            sin(deg2rad($_sourceWaypoint['latitude']))) +
                                (cos(deg2rad($destWaypoint->waypoints[0]->latitude)) *
                                    cos(deg2rad($_sourceWaypoint['latitude'])) * cos(deg2rad($theta)));
            $distance = acos($distance);
            $distance = rad2deg($distance);
            $distance = $distance * 60 * 1.1515;
            $distance = $distance * 1.609344;

            $tmpDistance = round($distance, 2);

            if ($closestSourceWaypoint == false) {
                $closestSourceWaypoint = $_sourceWaypoint;
                $minorDistance = $tmpDistance;
            } elseif ($minorDistance > $tmpDistance) {
                $closestSourceWaypoint = $_sourceWaypoint;
                $minorDistance = $tmpDistance;
            }
        }
        return $closestSourceWaypoint;
    }

    /**
     * @param $order
     * @param $pedidosYa
     * @throws LocalizedException
     */
    public function createShipment($order, $pedidosYa)
    {
        if ($order->canShip()) {
            $orderShipment = $this->_convertOrder->toShipment($order);

            foreach ($order->getAllItems() as $orderItem) {
                if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                    continue;
                }

                $qty = $orderItem->getQtyToShip();
                $shipmentItem = $this->_convertOrder->itemToShipmentItem($orderItem)->setQty($qty);
                $orderShipment->addItem($shipmentItem);
            }
            $orderShipment->register();
            $orderShipment->getOrder()->setIsInProcess(true);
            $this->createTracking($order, $pedidosYa, $orderShipment);
        } else {
            $this->createTracking($order, $pedidosYa);
        }
    }

    /**
     * @param $order
     * @param $pedidosYa
     * @param $orderShipment
     */
    public function createTracking($order, $pedidosYa, $orderShipment = null)
    {
        $pedidosYaConfirmedData = json_decode($pedidosYa->getInfoConfirmed());
        $trackingUrl = $pedidosYaConfirmedData->shareLocationUrl ?? '';
        if (!$orderShipment) {
            $orderShipment = $this->_trackFactory->create()->getCollection()
                ->addFieldToFilter('order_id', ['eq' => $order->getEntityId()])
                ->getFirstItem();
            $orderShipment->setTrackNumber($pedidosYaConfirmedData->confirmationCode);
            $orderShipment->setTrackUrl($trackingUrl);
            $order->save();
            $orderShipment->save();
        } else {
            $orderShipment->addTrack(
                $this->_trackFactory->create()
                    ->setNumber($pedidosYaConfirmedData->confirmationCode)
                    ->setCarrierCode('pedidosya')
                    ->setTitle('Pedidos Ya Tracking')
                    ->setUrl($trackingUrl)
            )->save();
            $orderShipment->save();
            $orderShipment->getOrder()->save();
            $this->_shipmentNotifier->notify($orderShipment);
        }
    }

    /**
     * @param $waypointId
     * @return Waypoint
     */
    public function getWaypointById($waypointId)
    {
        return $this->_waypointFactory->create()->load($waypointId);
    }

    /**
     * @param $tracking
     * @return Result
     */
    public function getTrackingUrl($tracking)
    {
        $track = $this->_trackFactory->create()
            ->getCollection()
            ->addFieldToFilter('track_number', $tracking);

        if ($track->getFirstItem()) {
            $pedidosYa = $this->_pedidosYaFactory->create()
                ->getCollection()
                ->addFieldToFilter('order_id', ['eq' => $track->getFirstItem()->getOrderId()])
                ->getFirstItem();
            $infoConfirmed = json_decode($pedidosYa->getInfoConfirmed());
        }

        if (!is_array($tracking)) {
            $trackings = [$tracking];
        }
        $result = $this->_trackResultFactory->create();

        foreach ($trackings as $tracking) {
            $status = $this->_trackStatusFactory->create();
            $status->setCarrier('pedidosya');
            $status->setCarrierTitle($this->getTitle());
            $status->setTracking($tracking);
            $status->setPopup(1);
            $status->setUrl($infoConfirmed->shareLocationUrl);

            $result->append($status);
        }

        return $result;
    }

    /**
     * Return Waypoint Availability
     * @param $waypointId
     * @param $deliveryTime
     * @return bool
     */
    public function checkWaypointAvailability($waypointId, $deliveryTime)
    {
        /**
         * Fix Format W: Numerical representation of the day of the week 0 (Sunday) > 6 (Saturday)
         * Implement Timezone Interface to get day
         */
        $days = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
        $waypoint = $this->getWaypointById($waypointId);
        $day = $days[date("w", strtotime($this->timezone->date()->format("Y-m-d\TH:i:s\Z")))];
        $openHour = $waypoint->getData('working_hours_'. $day. '_open');
        $closeHour = $waypoint->getData('working_hours_'. $day. '_close');
        $deliveryTime = new \DateTime($deliveryTime);
        $deliveryHour = $deliveryTime->format("H");

        /**
         * Check Waypoint Availability
         */
        if ($deliveryHour >= $openHour && $deliveryHour <= $closeHour) {
            return true;
        }
        return false;
    }

    /**
     * @param $message
     */
    public function log($message)
    {
        $this->_pedidosYaLogger->error($message);
    }

    /**
     * getWebServiceURL
     * Return WebService URL
     * @return string
     */
    public function getWebServiceURL($endpoint, $authEndpoint = false)
    {
        /**
         * Auth or API?
         */
        $WebserviceURL = $authEndpoint ? self::AUTH_ENDPOINT : self::API_ENDPOINT;

        /**
         * Get API Version
         */
        $apiVersion = $this->apiVersion;

        /**
         * TODO TEMPORAL?
         * If the endpoint is estimates/coverage, it forces to use version 1 since
         * we require the coordinates of the client's address to determine the nearest Waypoint
         */
        if (strpos($endpoint, "estimates/coverage") !== false) {
            $apiVersion = 'v1';
        }

        /**
         * Return WebService URL
         */
        return vsprintf(
            $WebserviceURL,
            [
                $apiVersion,
                $endpoint
            ]
        );
    }
}
