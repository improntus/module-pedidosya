<?php

namespace Improntus\PedidosYa\Helper;

use Improntus\PedidosYa\Model\Waypoint;
use Improntus\PedidosYa\Model\WaypointFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
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
        TimezoneInterface $timezone
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
        $this->timezone = $timezone;
        parent::__construct($context);
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->_scopeConfig->getValue('shipping/pedidosya/client_id', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return $this->_scopeConfig->getValue('shipping/pedidosya/client_secret', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->_scopeConfig->getValue('shipping/pedidosya/username', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->_scopeConfig->getValue('shipping/pedidosya/password', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getVolumeAttribute()
    {
        return $this->_scopeConfig->getValue('carriers/pedidosya/product_volume_attribute', ScopeInterface::SCOPE_STORE);
    }


    /**
     * @return bool
     */
    public function isActive()
    {
        return (bool)$this->_scopeConfig->getValue('carriers/pedidosya/active', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return int
     */
    public function getAssumeShippingAmount()
    {
        return (int)$this->_scopeConfig->getValue('carriers/pedidosya/assume_shipping_amount', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return float
     */
    public function getMaxWeight()
    {
        return (float)$this->_scopeConfig->getValue("carriers/pedidosya/max_package_weight", ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return bool
     */
    public function getAutomaticShipment()
    {
        return (bool)$this->_scopeConfig->getValue("carriers/pedidosya/automatic_shipment", ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getMode()
    {
        return $this->_scopeConfig->getValue('carriers/pedidosya/mode', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->_scopeConfig->getValue('carriers/pedidosya/category', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return int
     */
    public function getPreparationTime()
    {
        return (int)$this->_scopeConfig->getValue('carriers/pedidosya/preparation_time', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return false|string[]
     */
    public function getStatusOrderAllowed()
    {
        $statusOrderAllowed = $this->_scopeConfig->getValue('carriers/pedidosya/status_allowed', ScopeInterface::SCOPE_STORE);
        return explode(',', $statusOrderAllowed);
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->_scopeConfig->getValue('carriers/pedidosya/title', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return boolean
     */
    public function getFreeShipping()
    {
        return $this->_scopeConfig->getValue('carriers/pedidosya/free_shipping', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getDefaultCountryAmount()
    {
        return $this->_scopeConfig->getValue('carriers/pedidosya/country_max_amount_insured', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return bool
     */
    public function getDebugMode()
    {
        return (bool)$this->_scopeConfig->getValue('carriers/pedidosya/debug', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param $token
     */
    public function saveToken($token)
    {
        $tokenFactory = $this->_tokenFactory->create()->getCollection()->getFirstItem();
        $tokenFactory->setToken($token);
        $tokenFactory->setLastestUse(date("Y-m-d H:i:s"));
        $tokenFactory->save();
    }

    /**
     * @return false
     */
    public function getToken()
    {
        $tokenFactory = $this->_tokenFactory->create();
        $token = $tokenFactory->getCollection()->getFirstItem();

        if(!$token->getToken())
            return false;

        $now = strtotime(date("Y-m-d H:i:s"));
        $tokenExpiration = 45;
        $difference = ($now- strtotime($token->getLastestUse()))/ 60;

        if($difference > $tokenExpiration)
            return false;

        /**
         * I check if the token is valid
         */
        $curl = curl_init();
        $url = "https://courier-api.pedidosya.com/v1/categories";
        curl_setopt_array($curl,
            [
                CURLOPT_URL => $url,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    "Authorization: {$token->getToken()}",
                    "Content-Type: application/json"
                ],
            ]);

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        /**
         * Check status request
         */
        if(curl_error($curl)){
            $this->log("ERROR: curl ".curl_error($curl));
            return false;
        } elseif($httpcode != 200) {
            $response = json_decode($response);
            $this->log("Error: {$response->messages[0]} - Invalid Access Token REFRESH");
            return false;
        }

        $token->setLastestUse(date("Y-m-d H:i:s"));
        $token->save();

        return $token->getToken();
    }

    /**
     * @param $destWaypoint
     * @return mixed
     */
    public function getClosestSourceWaypoint($destWaypoint) {
        $waypointCollection = $this->_waypointFactory->create()->getCollection()->addFieldToFilter('enabled', 1);
        $closestSourceWaypoint = false;
        $minorDistance = 0;

        foreach ($waypointCollection as $_sourceWaypoint) {
            $theta = $destWaypoint->waypoints[0]->longitude - $_sourceWaypoint['longitude'];
            $distance = (sin(deg2rad($destWaypoint->waypoints[0]->latitude)) * sin(deg2rad($_sourceWaypoint['latitude']))) + (cos(deg2rad($destWaypoint->waypoints[0]->latitude)) * cos(deg2rad($_sourceWaypoint['latitude'])) * cos(deg2rad($theta)));
            $distance = acos($distance);
            $distance = rad2deg($distance);
            $distance = $distance * 60 * 1.1515;
            $distance = $distance * 1.609344;

            $tmpDistance = round($distance,2);

            if($closestSourceWaypoint == false) {
                $closestSourceWaypoint = $_sourceWaypoint;
                $minorDistance = $tmpDistance;
            } elseif($minorDistance > $tmpDistance) {
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

            foreach ($order->getAllItems() AS $orderItem) {
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
     * @param null $orderShipment
     * @throws MailException
     */
    public function createTracking($order, $pedidosYa, $orderShipment = NULL)
    {
        $pedidosYaConfirmedData = json_decode($pedidosYa->getInfoConfirmed());
        if(!$orderShipment) {
            $orderShipment = $this->_trackFactory->create()->getCollection()
                ->addFieldToFilter('order_id', ['eq' => $order->getEntityId()])
                ->getFirstItem();
            $orderShipment->setTrackNumber($pedidosYaConfirmedData->confirmationCode);
            $order->save();
            $orderShipment->save();
        } else {
            $orderShipment->addTrack(
                $this->_trackFactory->create()
                    ->setNumber($pedidosYaConfirmedData->confirmationCode)
                    ->setCarrierCode('pedidosya')
                    ->setTitle('Pedidos Ya Tracking')
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
    public function getWaypointById($waypointId) {
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

        if($track->getFirstItem()) {
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

        $date = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $deliveryTime);
        $deliveryHour = $date->format('H');

        if($openHour < $deliveryHour && $openHour < $closeHour) {
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
}

