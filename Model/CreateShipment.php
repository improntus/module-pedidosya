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
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class CreateShipment
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2023 Improntus
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
     * @var TimezoneInterface $_timezone
     */
    protected $_timezone;

    /**
     * @var TimezoneInterface $timezone
     */
    protected $timezone;

    public function __construct(
        Context $context,
        PedidosYaFactory $pedidosYaFactory,
        OrderRepository $orderRepository,
        Webservice $webservice,
        ScopeConfigInterface $scopeConfigInterface,
        PedidosYaHelper $pedidosYaHelper,
        ManagerInterface $manager,
        DateTime $date,
        TimezoneInterface $timezone
    ) {
        $this->_pedidosYaFactory    = $pedidosYaFactory;
        $this->_orderRepository     = $orderRepository;
        $this->_webservice          = $webservice;
        $this->_context             = $context;
        $this->_scopeConfig         = $scopeConfigInterface;
        $this->_pedidosYaHelper     = $pedidosYaHelper;
        $this->_date                = $date;
        $this->_messageManager      = $manager;
        $this->timezone             = $timezone;
    }

    /**
     * @param $orderId
     * @param null $order
     * @throws LocalizedException
     */
    public function create($orderId, $order = null)
    {
        if ($this->_pedidosYaHelper->isActive()) {
            if ($orderId) {
                try {
                    $order = $this->_orderRepository->get($orderId);
                } catch (\Exception $e) {
                    $this->_messageManager->addErrorMessage(__('An error occurred trying to generate the shipment Pedidos Ya: ') . $e->getMessage());
                    $this->_pedidosYaHelper->log($e->getMessage());
                }
            }

            if ($order->getShippingMethod() == 'pedidosya_pedidosya' && $order instanceof AbstractModel) {
                $statuses = $this->_pedidosYaHelper->getStatusOrderAllowed();
                $notAllowedPedidosYaStatus = ['pedidosya_sent', 'pedidosya_error'];
                $orderStatus = $order->getStatus();

                $pedidosYa = $this->_pedidosYaFactory->create();
                $pedidosYa = $pedidosYa->getCollection()
                    ->addFieldToFilter('order_id', ['eq' => $order->getId()])
                    ->getFirstItem();

                $alreadySent = $pedidosYa->getStatus() == 'pedidosya_sent';

                if (in_array($orderStatus, $statuses) && !$alreadySent || $pedidosYa->getStatus() == 'pedidosya_cancelled') {
                    if (!in_array($pedidosYa->getPedidosyaStatus(), $notAllowedPedidosYaStatus) || $pedidosYa->getPedidosyaStatus() == 'pedidosya_cancelled') {
                        $pedidosYa->setOrderId($order->getId());
                        $pedidosYa->setIncrementId($order->getIncrementId());

                        if ($pedidosYaEstimateData = $order->getPedidosyaEstimatedata()) {

                            /**
                             * If ReferenceId is -1 the order has created in Backend
                             * and I need update this by EntityId
                             */
                            $data = json_decode($pedidosYaEstimateData);
                            if ($data->referenceId==-1) {
                                $data->referenceId=$order->getEntityId();
                                $order->setPedidosyaEstimatedata(json_encode($data));
                            }

                            if (isset($data->deliveryTime)) {
                                // Get Preapration Time
                                $preparationTime = $this->_pedidosYaHelper->getPreparationTime();
                                // Get current date and times based on store timezone
                                $currentDateTime = date('Y-m-d\TH:i:s\Z');
                                // Add Preparation Time to Current Date
                                $data->deliveryTime = gmdate('Y-m-d\TH:i:s\Z', strtotime("{$currentDateTime} + {$preparationTime} minutes"));
                            }

                            $data->waypoints[0]->phone = preg_replace("/[^0-9]/", "", $data->waypoints[0]->phone);
                            $data->waypoints[1]->phone = preg_replace("/[^0-9]/", "", $order->getShippingAddress()->getTelephone());
                            $data->waypoints[1]->name = $order->getShippingAddress()->getFirstname()." ".$order->getShippingAddress()->getLastname();
                            $data->notificationMail =  $order->getShippingAddress()->getEmail();
                            $data->referenceId = '#' . $order->getIncrementId();

                            if ($this->_pedidosYaHelper->checkWaypointAvailability($order->getPedidosyaSourceWaypoint(), $data->deliveryTime)) {
                                /**
                                 * Create Shipping
                                 */
                                $createShippingResult = $this->_webservice->createShipping($data, $order->getStoreId());

                                /**
                                 * Check PYa Shipping Response
                                 */
                                $shippingStatus = strtolower($createShippingResult->status ?? '');
                                if ($shippingStatus === "confirmed" || $shippingStatus === "preorder") {
                                    // Set PreOrder
                                    $pedidosYa->setInfoPreorder(json_encode($createShippingResult));
                                    $pedidosYa->save();

                                    if ($this->_pedidosYaHelper->getIntegrationMode()) {
                                        // API MODE
                                        $confirmShippingResult = $createShippingResult;
                                    } else {
                                        // E-COMMERCE ONLY
                                        $confirmShippingResult = $this->_webservice->confirmShipping($createShippingResult, $order->getStoreId());
                                    }

                                    // Set Default Return Status
                                    $returnStatus = $this->_pedidosYaHelper::PEDIDOSYA_ERROR_WS;
                                    if (isset($confirmShippingResult->confirmationCode)) {
                                        // Save Confirmed Data
                                        $pedidosYa->setInfoConfirmed(json_encode($confirmShippingResult));
                                        $pedidosYa->setStatus('pedidosya_sent');
                                        $pedidosYa->save();
                                        // Set Comment
                                        $statusCommentHistory = __('PedidosYa Confirmation Code: %1', $confirmShippingResult->confirmationCode);
                                        // Set Return Status
                                        $returnStatus = $this->_pedidosYaHelper::PEDIDOSYA_OK;
                                        // Create Shipment
                                        $this->_pedidosYaHelper->createShipment($order, $pedidosYa);
                                    } else {
                                        // Get Error Message / Code
                                        $errorMessage = $createShippingResult->message ?? $createShippingResult->code;
                                        // Set Comment
                                        $statusCommentHistory = __('PedidosYa Confirmation ERROR: %1', $errorMessage);
                                        // Log error
                                        $this->_pedidosYaHelper->log($createShippingResult);
                                    }

                                    // Save Order
                                    $order->addStatusHistoryComment($statusCommentHistory);
                                    $order->save();
                                    return $returnStatus;
                                } else {
                                    $this->_pedidosYaHelper->log(json_encode($createShippingResult));
                                    $errorMessage = $createShippingResult->message ?? $createShippingResult->code;
                                    $order->addStatusHistoryComment("PedidosYa Pre Order ERROR: $errorMessage");
                                    $order->save();
                                    return $errorMessage;
                                }
                            } else {
                                return $this->_pedidosYaHelper::PEDIDOSYA_ERROR_TIME;
                            }
                        } else {
                            return $this->_pedidosYaHelper::PEDIDOSYA_ERROR_DATA;
                        }
                    }
                } elseif (!$alreadySent) {
                    return $this->_pedidosYaHelper::PEDIDOSYA_ERROR_STATUS;
                }
            }
        }
    }
}
