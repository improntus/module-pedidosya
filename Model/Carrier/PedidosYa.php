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

/**
 * Class PedidosYa
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
        array                   $data = []
    )
    {
        $this->_rateResultFactory = $rateFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->_helper            = $pedidosYaHelper;
        $this->_webservice        = $webservice;
        $this->_request           = $request;
        $this->_checkoutSession   = $checkoutSession;
        $this->_quoteRepository   = $quoteRepository;
        $this->_date              = $date;

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
        if (!$this->getConfigFlag('active'))
            return false;

        $helper = $this->_helper;

        $result = $this->_rateResultFactory->create();
        $method = $this->_rateMethodFactory->create();

        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));
        $method->setMethod($this->_code);
        $method->setMethodTitle($this->getConfigData('description'));

        $webservice = $this->_webservice;

        $itemsWspedidosYa = [];
        $totalPrice = 0;
        $totalVolume = 0;

        //$category = $this->_helper->getCategory();

        foreach($request->getAllItems() as $_item) {
            if($_item->getProductType() == 'configurable')
                continue;

            $_product = $_item->getProduct();

            if($_item->getParentItem())
                $_item = $_item->getParentItem();


            $volumeCode = $this->_helper->getVolumeAttribute() ? $this->_helper->getVolumeAttribute() : 'volume';
            $volume = (int) $_product->getResource()->getAttributeRawValue($_product->getId(), $volumeCode, $_product->getStoreId()) * $_item->getQty();
            $totalVolume += $volume;

            $totalPrice += $_product->getFinalPrice();

            $itemsWspedidosYa[] = [
                //'categoryId'    => $category,
                'value'         => $_item->getPrice(),
                'description'   => $_item->getName(),
                'quantity'      => $_item->getQty(),
                'volume'        => $volume,
                'weight'        => $_product->getWeight() * 1000 * $_item->getQty(),
            ];
        }

        $totalWeight  = $request->getPackageWeight();

        /**
         * Maximum insured amount by Pedidos Ya
         */
        if ($request->getPackageValue() > $helper->getDefaultCountryAmount()) {
            $error = $this->_rateErrorFactory->create();
            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage(__('Maximum value exceeded'));
            $result->append($error);
            return $result;
        }

        if($totalWeight > (int)$helper->getMaxWeight()) {
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
        $isFreeShipping = $helper->getFreeShipping() ? $request->getFreeShipping() : false;

        if($request->getDestStreet() && $request->getDestPostcode()) {
            $waypointData['waypoints'][] = [
                'addressStreet' => trim(preg_replace('/\n/', ' ', $request->getDestStreet())),
                'city'          => $request->getDestCity()
            ];

            $waypointCoverage = $this->_webservice->getEstimateCoverage($waypointData);

            if($isFreeShipping == 1) {
                $method->setPrice(0);
                $method->setCost(0);
                $result->append($method);
            } elseif($waypointCoverage == false) {
                $error = $this->_rateErrorFactory->create();
                $error->setCarrier($this->_code);
                $error->setCarrierTitle($this->getConfigData('title'));
                $error->setErrorMessage(__('There are no shipping estimate for the address entered'));
                $result->append($error);
                return $result;
            } else if(isset($waypointCoverage->waypoints[0])) {
                if($waypointCoverage->waypoints[0]->status == 'NOT_FOUND') {
                    $error = $this->_rateErrorFactory->create();
                    $error->setCarrier($this->_code);
                    $error->setCarrierTitle($this->getConfigData('title'));
                    $error->setErrorMessage(__('There are no shipping estimate for the address entered'));
                $result->append($error);
                return $result;
                }
            }

            $closestSourceWaypoint = $this->_helper->getClosestSourceWaypoint($waypointCoverage);

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
                    "deliveryTime"  => $this->_date->gmtDate('Y-m-d\TH:i:s\Z'),
                    "volume"        => $totalVolume,
                    "weight"        => $totalWeight,
                    "items"         => $itemsWspedidosYa,
                    "waypoints"     => $waypoints
                ];

            $shippingPrice = $webservice->getEstimatePrice($estimatePriceData);

            if($isFreeShipping == 1) {
                $this->_checkoutSession->setPedidosyaEstimatedata(json_encode($estimatePriceData));
                $this->_checkoutSession->setPedidosyaSourceWaypoint($closestSourceWaypoint->getEntityId());
            } elseif(isset($shippingPrice->price)) {
                $price = $shippingPrice->price->total;
                $assumeShippingPrice = $this->_helper->getAssumeShippingAmount();
                $total = $price - $assumeShippingPrice;
                if($total > 0) {
                    $method->setPrice($total);
                    $method->setCost($total);
                } else {
                    $method->setPrice(0);
                    $method->setCost(0);
                }

                $this->_checkoutSession->setPedidosyaEstimatedata(json_encode($estimatePriceData));
                $this->_checkoutSession->setPedidosyaSourceWaypoint($closestSourceWaypoint->getEntityId());
                $result->append($method);
            } else {
                $error = $this->_rateErrorFactory->create();
                $error->setCarrier($this->_code);
                $error->setCarrierTitle($this->getConfigData('title'));
                $error->setErrorMessage(__('There are no shipping estimate for the address entered'));
                $result->append($error);
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
