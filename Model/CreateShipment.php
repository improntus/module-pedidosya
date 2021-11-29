<?php

namespace Improntus\PedidosYa\Model;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\OrderRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Improntus\PedidosYa\Helper\Data as PedidosYaHelper;
use Magento\Framework\Message\ManagerInterface;

/**
 * Class CreateShipment
 * @package Improntus\PedidosYa\Model
 */
class CreateShipment
{
    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var OrderRepository
     */
    protected $_orderRepository;

    /**
     * @var Webservice
     */
    protected $_webservice;

    /**
     * @var Context
     */
    protected $_context;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var PedidosYaFactory
     */
    protected $_pedidosYaFactory;

    /**
     * @var PedidosYaHelper
     */
    protected $_pedidosYaHelper;

    /**
     * @var DateTime
     */
    protected $_date;

    /**
     * @var ManagerInterface
     */
    protected $_messageManager;

    /**
     * @param Context $context
     * @param PedidosYaFactory $pedidosYaFactory
     * @param OrderRepository $orderRepository
     * @param Webservice $webservice
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param PedidosYaHelper $pedidosYaHelper
     * @param ManagerInterface $manager
     * @param DateTime $date
     */
    public function __construct(
        Context $context,
        PedidosYaFactory $pedidosYaFactory,
        OrderRepository $orderRepository,
        Webservice $webservice,
        ScopeConfigInterface $scopeConfigInterface,
        PedidosYaHelper $pedidosYaHelper,
        ManagerInterface $manager,
        DateTime $date
    )
    {
        $this->_pedidosYaFactory    = $pedidosYaFactory;
        $this->_orderRepository     = $orderRepository;
        $this->_webservice          = $webservice;
        $this->_context             = $context;
        $this->_scopeConfig         = $scopeConfigInterface;
        $this->_pedidosYaHelper     = $pedidosYaHelper;
        $this->_date                = $date;
        $this->_messageManager      = $manager;
    }

    /**
     * @param $orderId
     * @param null $order
     * @throws LocalizedException
     */
    public function create($orderId, $order = NULL)
    {
        if ($this->_pedidosYaHelper->isActive()) {
            if($orderId) {
                try {
                    $order = $this->_orderRepository->get($orderId);
                } catch (\Exception $e) {
                    $this->_messageManager->addErrorMessage(__('An error occurred trying to generate the shipment Pedidos Ya: ') . $e->getMessage());
                    $this->_pedidosYaHelper->log($e->getMessage());
                }
            }

            if($order->getShippingMethod() == 'pedidosya_pedidosya' && $order instanceof AbstractModel) {
                $statuses = $this->_pedidosYaHelper->getStatusOrderAllowed();
                $notAllowedPedidosYaStatus = ['pedidosya_sent', 'pedidosya_error'];
                $orderStatus = $order->getStatus();

                $pedidosYa = $this->_pedidosYaFactory->create();
                $pedidosYa = $pedidosYa->getCollection()
                    ->addFieldToFilter('order_id', ['eq' => $order->getId()])
                    ->getFirstItem();

                $alreadySent = $pedidosYa->getStatus() == 'pedidosya_sent';

                if(in_array($orderStatus, $statuses) && !$alreadySent || $pedidosYa->getStatus() == 'pedidosya_cancelled') {
                    if(!in_array($pedidosYa->getPedidosyaStatus(), $notAllowedPedidosYaStatus) || $pedidosYa->getPedidosyaStatus() == 'pedidosya_cancelled') {
                        $pedidosYa->setOrderId($order->getId());
                        $pedidosYa->setIncrementId($order->getIncrementId());

                        if($pedidosYaEstimateData = $order->getPedidosyaEstimatedata()) {
                            $data = json_decode($pedidosYaEstimateData);
                            if(isset($data->deliveryTime)) {
                                $preparationTime = $this->_pedidosYaHelper->getPreparationTime();
                                $data->deliveryTime = gmdate('Y-m-d\TH:i:s\Z', strtotime(date('Y-m-d\TH:i:s\Z') . '+'. $preparationTime . ' minutes'));
                            }
                            $data->waypoints[1]->phone = $order->getShippingAddress()->getTelephone();
                            $data->notificationMail =  $order->getShippingAddress()->getEmail();
                            $data->referenceId = '#' . $order->getIncrementId();

                            if($this->_pedidosYaHelper->checkWaypointAvailability($order->getPedidosyaSourceWaypoint(), $data->deliveryTime)) {
                                $createShippingResult = $this->_webservice->createShipping($data);

                                if($createShippingResult) {
                                    $pedidosYa->setInfoPreorder(json_encode($createShippingResult));
                                    $pedidosYa->save();

                                    if(isset($createShippingResult->referenceId))
                                        $order->addStatusHistoryComment("Pedidos Ya: Preorder OK. Confirmation Code: " . $createShippingResult->referenceId);

                                    $confirmShippingResult = $this->_webservice->confirmShipping($createShippingResult);

                                    if(isset($confirmShippingResult->confirmationCode)) {
                                        $pedidosYa->setInfoConfirmed(json_encode($confirmShippingResult));
                                        $pedidosYa->save();
                                        $order->addStatusHistoryComment("Pedidos Ya: Confirm Order OK. Confirmation Code: " . $confirmShippingResult->confirmationCode);
                                    } else {
                                        $pedidosYa->setStatus('pedidosya_error');
                                        $pedidosYa->save();
                                        $order->addStatusHistoryComment("Pedidos Ya: Confirm Order ERROR.");
                                        return $this->_pedidosYaHelper::PEDIDOSYA_ERROR_WS;
                                    }

                                    $pedidosYa->setStatus('pedidosya_sent');
                                    $pedidosYa->save();
                                    $order->save();
                                    $this->_pedidosYaHelper->createShipment($order, $pedidosYa);
                                    return $this->_pedidosYaHelper::PEDIDOSYA_OK;
                                } else {
                                    $order->addStatusHistoryComment("Pedidos Ya: Preorder ERROR.");
                                    return $this->_pedidosYaHelper::PEDIDOSYA_ERROR_WS;
                                }
                            } else {
                                return $this->_pedidosYaHelper::PEDIDOSYA_ERROR_TIME;
                            }
                        } else {
                            return $this->_pedidosYaHelper::PEDIDOSYA_ERROR_DATA;
                        }
                    }
                } elseif(!$alreadySent) {
                    return $this->_pedidosYaHelper::PEDIDOSYA_ERROR_STATUS;
                }
            }
        }
    }
}