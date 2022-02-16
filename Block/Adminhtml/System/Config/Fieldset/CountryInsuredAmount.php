<?php
namespace Improntus\PedidosYa\Block\Adminhtml\System\Config\Fieldset;

Use Improntus\PedidosYa\Helper\Data;
use Magento\Framework\View\Element\Template;
Use Magento\Framework\View\Element\Template\Context;

class CountryInsuredAmount extends Template
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @param Context $context
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        Data $helper
    )
    {
        parent::__construct($context);
        $this->helper = $helper;
    }

    /**
     * Method getCountryInsuredAmount
     * @return false|string
     */
    public function getCountryInsuredAmount(){
        return json_encode($this->helper::PEDIDOSYA_DEFAULT_VALUES_COUNTRY,JSON_NUMERIC_CHECK);
    }

}
