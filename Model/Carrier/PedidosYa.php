<?php

namespace Improntus\PedidosYa\Model\Carrier;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Directory\Helper\Data;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Shipping\Model\Simplexml\ElementFactory;
use Magento\Shipping\Model\Tracking\Result\StatusFactory;
use Psr\Log\LoggerInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Improntus\PedidosYa\Helper\Data as PedidosYaHelper;
use Improntus\PedidosYa\Model\Webservice;
use Magento\Framework\Xml\Security;
use Magento\Checkout\Model\Session;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class PedidosYa
 * @author Improntus <http://www.improntus.com> - Elevating Digital Transformation | Adobe Solution Partner
 * @copyright Copyright (c) 2025 Improntus
 * @package Improntus\PedidosYa\Model\Carrier
 */
class PedidosYa extends AbstractCarrierOnline implements CarrierInterface
{
    const CARRIER_CODE = 'pedidosya';

    /**
     * @var string
     */
    protected $_code = self::CARRIER_CODE;

    /**
     * @var
     */
    protected $_webservice;

    /**
     * @var PedidosYaHelper
     */
    protected $_helper;

    /**
     * @var RateRequest
     */
    protected $_rateRequest;

    /**
     * @var ResultFactory
     */
    protected $_rateResultFactory;

    /**
     * @var MethodFactory
     */
    protected $_rateMethodFactory;

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * Rate result data
     *
     * @var Result
     */
    protected $_result;

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var CartRepositoryInterface
     */
    protected $_quoteRepository;

    /**
     * @var DateTime
     */
    protected $_date;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param Security $xmlSecurity
     * @param ElementFactory $xmlElFactory
     * @param ResultFactory $rateFactory
     * @param MethodFactory $rateMethodFactory
     * @param \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory
     * @param \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory
     * @param StatusFactory $trackStatusFactory
     * @param RegionFactory $regionFactory
     * @param CountryFactory $countryFactory
     * @param CurrencyFactory $currencyFactory
     * @param Data $directoryData
     * @param StockRegistryInterface $stockRegistry
     * @param RequestInterface $request
     * @param Webservice $webservice
     * @param PedidosYaHelper $pedidosYaHelper
     * @param Session $checkoutSession
     * @param CartRepositoryInterface $quoteRepository
     * @param DateTime $date
     * @param TimezoneInterface $timezone
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        Security $xmlSecurity,
        ElementFactory $xmlElFactory,
        ResultFactory $rateFactory,
        MethodFactory $rateMethodFactory,
        \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory,
        \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory,
        StatusFactory $trackStatusFactory,
        RegionFactory           $regionFactory,
        CountryFactory          $countryFactory,
        CurrencyFactory         $currencyFactory,
        Data                    $directoryData,
        StockRegistryInterface  $stockRegistry,
        RequestInterface        $request,
        Webservice              $webservice,
        PedidosYaHelper         $pedidosYaHelper,
        Session                 $checkoutSession,
        CartRepositoryInterface $quoteRepository,
        DateTime                $date,
        TimezoneInterface       $timezone,
        array                   $data = []
    ) {
        $this->_rateResultFactory = $rateFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->_helper            = $pedidosYaHelper;
        $this->_webservice        = $webservice;
        $this->_request           = $request;
        $this->_checkoutSession   = $checkoutSession;
        $this->_quoteRepository   = $quoteRepository;
        $this->_date              = $date;
        $this->timezone           = $timezone;
        parent::__construct(
            $scopeConfig,
            $rateErrorFactory,
            $logger,
            $xmlSecurity,
            $xmlElFactory,
            $rateFactory,
            $rateMethodFactory,
            $trackFactory,
            $trackErrorFactory,
            $trackStatusFactory,
            $regionFactory,
            $countryFactory,
            $currencyFactory,
            $directoryData,
            $stockRegistry,
            $data
        );
    }

    /**
     * @return bool
     */
    public function isTrackingAvailable()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isCityRequired()
    {
        return true;
    }

    /**
     * @param null $countryId
     * @return bool
     */
    public function isZipCodeRequired($countryId = null)
    {
        if ($countryId != null) {
            return !$this->_directoryData->isZipCodeOptional($countryId);
        }
        return true;
    }

    /**
     * @return bool
     */
    public function isStateProvinceRequired()
    {
        return true;
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return ['pedidosya' => $this->getConfigData('title')];
    }

    /**
     * @param RateRequest $request
     * @return bool|Result|null
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $result = $this->_rateResultFactory->create();
        $method = $this->_rateMethodFactory->create();
        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));
        $method->setMethod($this->_code);
        $method->setMethodTitle($this->getConfigData('description'));

        $helper = $this->_helper;
        $webservice = $this->_webservice;
        $debugMode = $helper->getDebugMode();
        $itemsWspedidosYa = [];
        $totalPrice = 0;
        $totalVolume = 0;

        /**
         * Items
         */
        foreach ($request->getAllItems() as $_item) {
            if ($_item->getProductType() == 'configurable') {
                continue;
            }

            $_product = $_item->getProduct();

            if ($_item->getParentItem()) {
                $_item = $_item->getParentItem();
            }

            $volumeCode = $this->_helper->getVolumeAttribute() ? $this->_helper->getVolumeAttribute() : 'volume';
            $volume = (int) $_product->getResource()->getAttributeRawValue($_product->getId(), $volumeCode, $_product->getStoreId()) * $_item->getQty();
            $totalVolume += $volume;
            $totalPrice += $_product->getFinalPrice();

            $quantity = ceil($_item->getQty());
            $itemWeight = (float)$_product->getWeight();
            if (is_null($_product->getWeight())) {
                $itemWeight = 0.1; // 100 Grs
            }

            // Add Item
            $itemsWspedidosYa[] = [
                'value'         => $_item->getPrice(),
                'description'   => $_item->getName(),
                'quantity'      => $quantity,
                'volume'        => $volume,
                'weight'        => $itemWeight * $_item->getQty(),
            ];
        }

        $totalWeight = $request->getPackageWeight();

        /**
         * Maximum insured amount by Pedidos Ya
         */
        if ($request->getPackageValue() > (int)$helper->getDefaultCountryAmount()) {
            $error = $this->_rateErrorFactory->create();
            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage(__('Maximum value exceeded'));
            $result->append($error);
            return $result;
        }

        if ($totalWeight > (int)$helper->getMaxWeight()) {
            $error = $this->_rateErrorFactory->create();
            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage(__('Maximum weight exceeded'));
            $result->append($error);
            return $result;
        }

        /**
         * Apply Free Shipping?
         */
        $isFreeShipping = $helper->getFreeShipping() && $request->getFreeShipping();

        if ($request->getDestStreet() && $request->getDestPostcode()) {
            /**
             * Start Log
             */
            if ($debugMode) {
                $helper->log("=============== START LOG ===============");
                $integrationModeLog = $helper->getIntegrationMode();
                $helper->log("== INTEGRATION MODE: $integrationModeLog ==");
            }

            $waypointData['waypoints'][] = [
                'addressStreet' => trim(preg_replace('/\n/', ' ', $request->getDestStreet())),
                'city'          => $request->getDestCity()
            ];

            if ($debugMode) {
                $helper->log(json_encode(["Waypoint Coverage Send Data: " => $waypointData]));
            }

            $waypointCoverage = $this->_webservice->getEstimateCoverage($waypointData);

            if ($debugMode) {
                $helper->log(json_encode(["Waypoint Coverage Get Data:" => $waypointCoverage]));
            }

            if (!$waypointCoverage) {
                $error = $this->_rateErrorFactory->create();
                $error->setCarrier($this->_code);
                $error->setCarrierTitle($this->getConfigData('title'));
                $error->setErrorMessage(__('There are no shipping estimate for the address entered'));
                $result->append($error);
                return $result;
            }

            if (isset($waypointCoverage->waypoints[0])) {
                if (isset($waypointCoverage->waypoints[0]->status)
                        && $waypointCoverage->waypoints[0]->status == 'NOT_FOUND') {
                    $error = $this->_rateErrorFactory->create();
                    $error->setCarrier($this->_code);
                    $error->setCarrierTitle($this->getConfigData('title'));
                    $error->setErrorMessage(__('There are no shipping estimate for the address entered'));
                    $result->append($error);
                    return $result;
                }
            }

            if (!isset($waypointCoverage->waypoints) || (isset($waypointCoverage->code)
                    && $waypointCoverage->code == 'INVALID_TOKEN')) {
                $error = $this->_rateErrorFactory->create();
                $error->setCarrier($this->_code);
                $error->setCarrierTitle($this->getConfigData('title'));
                $error->setErrorMessage(__('An error occurred when quoting shipping'));
                $result->append($error);
                return $result;
            }

            $closestSourceWaypoint = $this->_helper->getClosestSourceWaypoint($waypointCoverage);

            if ($debugMode) {
                $helper->log(json_encode(["Closest Source Waypoint:" => $closestSourceWaypoint->getData()]));
            }

            $waypoints[] = [
                "type"              => "PICK_UP",
                "addressStreet"     => $closestSourceWaypoint['address'],
                "addressAdditional" => $closestSourceWaypoint['additional_information'],
                "city"              => $closestSourceWaypoint['city'],
                "latitude"          => floatval($closestSourceWaypoint['latitude']),
                "longitude"         => floatval($closestSourceWaypoint['longitude']),
                "phone"             => $closestSourceWaypoint['telephone'],
                "name"              => $closestSourceWaypoint['name'],
                "instructions"      => $closestSourceWaypoint['instructions'],
                "order"             => 1
            ];

            $waypoints[] = [
                "type"              =>  "DROP_OFF",
                "addressStreet"     =>  $waypointData['waypoints'][0]['addressStreet'],
                "city"              =>  $waypointData['waypoints'][0]['city'],
                "phone"             =>  "11111111",
                "name"              =>  "EstimateName",
                "order"             =>  2
            ];

            /**
             * Get ReferenceId
             */
            $referenceId = $this->_checkoutSession->getQuoteId() ?: -1;

            $estimatePriceData =
                [
                    "referenceId"   => $referenceId,
                    "isTest"        => $this->_helper->getMode() == 'testing',
                    "deliveryTime"  => $this->timezone->date()->format('Y-m-d\TH:i:s\Z'),
                    "volume"        => $totalVolume,
                    "weight"        => $totalWeight,
                    "items"         => $itemsWspedidosYa,
                    "waypoints"     => $waypoints
                ];

            if ($debugMode) {
                $helper->log(json_encode(["Estimate Data:" => $estimatePriceData]));
            }

            $shippingPrice = $webservice->getEstimatePrice($estimatePriceData);

            if ($debugMode) {
                $helper->log(json_encode(["Shipping Price" => $shippingPrice]));
            }

            /**
             * Set Inicial Price
             */
            $method->setPrice(0);
            $method->setCost(0);

            if ($isFreeShipping) {
                $result->append($method);
            } elseif (isset($shippingPrice->price)) {
                /**
                 * Get Price and Discount assume amount
                 */
                $price = $shippingPrice->price->total;
                $assumeShippingPrice = $this->_helper->getAssumeShippingAmount();
                $total = $price - $assumeShippingPrice;

                /**
                 * Set Price
                 */
                if ($total > 0) {
                    $method->setPrice($total);
                    $method->setCost($total);
                }
            } elseif (isset($shippingPrice->deliveryOffers[0]->pricing->total)) {
                /**
                 * Get Price and Discount assume amount
                 */
                $price = $shippingPrice->deliveryOffers[0]->pricing->total;
                $assumeShippingPrice = $this->_helper->getAssumeShippingAmount();
                $total = $price - $assumeShippingPrice;

                /**
                 * Set Price
                 */
                if ($total > 0) {
                    $method->setPrice($total);
                    $method->setCost($total);
                }
            } else {
                $error = $this->_rateErrorFactory->create();
                $error->setCarrier($this->_code);
                $error->setCarrierTitle($this->getConfigData('title'));
                $error->setErrorMessage(__('There are no shipping estimate for the address entered'));
                $result->append($error);
                return $result;
            }

            /**
             * Add Shipping Method
             */
            $result->append($method);
            $this->_checkoutSession->setPedidosyaEstimatedata(json_encode($estimatePriceData));
            $this->_checkoutSession->setPedidosyaSourceWaypoint($closestSourceWaypoint->getEntityId());

            /**
             * End Log
             */
            if ($debugMode) {
                $helper->log("=============== END LOG ===============");
            }
        }

        return $result;
    }

    /**
     * @param DataObject $request
     * @return void
     */
    protected function _doShipmentRequest(DataObject $request)
    {
        // TODO: Implement _doShipmentRequest() method.
    }

    /**
     * @param DataObject $request
     * @return $this|PedidosYa
     */
    public function proccessAdditionalValidation(DataObject $request)
    {
        return $this;
    }

    /**
     * @param $trackings
     * @return \Magento\Shipping\Model\Tracking\Result
     */
    public function getTracking($trackings)
    {
        return $this->_helper->getTrackingUrl($trackings);
    }
}
