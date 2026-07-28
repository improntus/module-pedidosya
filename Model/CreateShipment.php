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
 * @author Improntus <http://www.improntus.com> - Adobe Gold Technology Partner | Adobe Gold Solution Partner
 * @copyright Copyright (c) 2026 Improntus
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
     * @var TimezoneInterface $timezone
     */
    protected $timezone;

    /**
     * In-flight guard to prevent observer re-entry (sales_order_save_after) recursion
     *
     * @var bool
     */
    protected $inProgress = false;

    public function __construct(
        Context $context,
        PedidosYaFactory $pedidosYaFactory,
        OrderRepository $orderRepository,
        Webservice $webservice,
        PedidosYaHelper $pedidosYaHelper,
        ManagerInterface $manager,
        DateTime $date,
        TimezoneInterface $timezone
    ) {
        $this->_pedidosYaFactory    = $pedidosYaFactory;
        $this->_orderRepository     = $orderRepository;
        $this->_webservice          = $webservice;
        $this->_context             = $context;
        $this->_pedidosYaHelper     = $pedidosYaHelper;
        $this->_date                = $date;
        $this->_messageManager      = $manager;
        $this->timezone             = $timezone;
    }

    /**
     * @param $orderId
     * @param null $order
     * @param bool $forceRetry Bypass the terminal pedidosya_error guard (manual admin dispatch only)
     * @throws LocalizedException
     */
    public function create($orderId, $order = null, $forceRetry = false)
    {
        if ($this->inProgress) {
            return;
        }

        if ($this->_pedidosYaHelper->isActive()) {
            $this->inProgress = true;
            try {
            if ($orderId) {
                try {
                    $order = $this->_orderRepository->get($orderId);
                } catch (\Exception $e) {
                    $this->_messageManager->addErrorMessage(__('An error occurred trying to generate the shipment PedidosYa: ') . $e->getMessage());
                    $this->_pedidosYaHelper->log($e->getMessage());
                }
            }

            if ($order->getShippingMethod() == 'pedidosya_pedidosya' && $order instanceof AbstractModel) {
                $statuses = $this->_pedidosYaHelper->getStatusOrderAllowed();
                /**
                 * 'pedidosya_sent' is always terminal (success). 'pedidosya_error' blocks the
                 * automatic flow to avoid retry loops, but a manual admin dispatch may force a retry.
                 */
                $notAllowedPedidosYaStatus = ['pedidosya_sent'];
                if (!$forceRetry) {
                    $notAllowedPedidosYaStatus[] = 'pedidosya_error';
                }
                $orderStatus = $order->getStatus();

                $pedidosYa = $this->_pedidosYaFactory->create();
                $pedidosYa = $pedidosYa->getCollection()
                    ->addFieldToFilter('order_id', ['eq' => $order->getId()])
                    ->getFirstItem();

                $alreadySent = $pedidosYa->getStatus() == 'pedidosya_sent';

                if (in_array($orderStatus, $statuses) && !$alreadySent || $pedidosYa->getStatus() == 'pedidosya_cancelled') {
                    if (!in_array($pedidosYa->getStatus(), $notAllowedPedidosYaStatus) || $pedidosYa->getStatus() == 'pedidosya_cancelled') {
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
                            $data->waypoints[1]->name = $order->getShippingAddress()->getFirstname() . ' ' . $order->getShippingAddress()->getLastname();
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

                                    /**
                                     * Determine Integration Mode
                                     */
                                    switch ($this->_pedidosYaHelper->getIntegrationMode($order->getStoreId())) {
                                        case "api":
                                            // API
                                            $confirmShippingResult = $createShippingResult;
                                            break;
                                        case "eco":
                                        default:
                                            // E-COMMERCE
                                            $confirmShippingResult = $this->_webservice->confirmShipping($createShippingResult, $order->getStoreId());
                                            break;
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
                                        // Persist terminal error status BEFORE saving the order to prevent re-dispatch
                                        $pedidosYa->setStatus('pedidosya_error');
                                        $pedidosYa->save();
                                        // Log concise error; full (redacted) payload only in debug
                                        $this->_pedidosYaHelper->log('PedidosYa Confirmation ERROR: ' . $errorMessage);
                                        if ($this->_pedidosYaHelper->getDebugMode($order->getStoreId())) {
                                            $this->_pedidosYaHelper->log($this->_pedidosYaHelper->redactForLog($createShippingResult));
                                        }
                                    }

                                    // Save Order
                                    $order->addStatusHistoryComment($statusCommentHistory);
                                    $order->save();
                                    return $returnStatus;
                                } else {
                                    $errorMessage = $createShippingResult->message ?? $createShippingResult->code;
                                    // Persist terminal error status BEFORE saving the order to prevent re-dispatch
                                    $pedidosYa->setStatus('pedidosya_error');
                                    $pedidosYa->save();
                                    // Log concise error; full (redacted) payload only in debug
                                    $this->_pedidosYaHelper->log('PedidosYa Pre Order ERROR: ' . $errorMessage);
                                    if ($this->_pedidosYaHelper->getDebugMode($order->getStoreId())) {
                                        $this->_pedidosYaHelper->log($this->_pedidosYaHelper->redactForLog($createShippingResult));
                                    }
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
            } finally {
                $this->inProgress = false;
            }
        }
    }
}
