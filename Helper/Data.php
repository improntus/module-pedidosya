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
use Zend\Log\Logger;
use Zend\Log\Writer\Stream;
use Improntus\PedidosYa\Model\TokenFactory;
use Improntus\PedidosYa\Model\PedidosYaFactory;

/**
 * Class Data
 * @package Improntus\PedidosYa\Helper
 */
class Data extends AbstractHelper
{
    const STATUS_NOT_ALLOWED    = 'not_allowed';
    const PEDIDOSYA_OK          = 'pedidosya_ok';
    const PEDIDOSYA_ERROR_WS    = 'pedidosya_error_ws';
    const PEDIDOSYA_ERROR_DATA  = 'pedidosya_error_data';

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
        PedidosYaFactory $pedidosYaFactory
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
     * @return string
     */
    public function isActive()
    {
        return (bool)$this->_scopeConfig->getValue('carriers/pedidosya/active', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
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
     * @return string
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

        $token->setLastestUse(date("Y-m-d H:i:s"));
        $token->save();

        return $token->getToken();
    }

    /**
     * @param $destWaypoint
     * @return mixed
     */
    public function getClosestSourceWaypoint($destWaypoint) {
        $waypointCollection = $this->_waypointFactory->create()->getCollection();
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
        $days = [1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday', 7 => 'sunday'];
        $waypoint = $this->getWaypointById($waypointId);
        $day = $days[date('w', strtotime($deliveryTime))];
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
     * @param $mensaje
     * @param false $isError
     * @param false $isArray
     */
    public function log($mensaje, $isError = false, $isArray = false)
    {
        if($isError){
            $file = 'error_pedidosya_'.date('m_Y').'.log';
        }else{
            $file = 'pedidosya'.date('m_Y').'.log';
        }

        $writer = new Stream(BP . '/var/log/'.$file);
        $logger = new Logger();
        $logger->addWriter($writer);

        if($isArray){
            $logger->info(print_r($mensaje, true));
        }else{
            $logger->info($mensaje);
        }
    }
}

