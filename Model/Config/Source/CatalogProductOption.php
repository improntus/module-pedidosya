<?php

namespace Improntus\PedidosYa\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Eav\Model\Entity\TypeFactory;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class CatalogProductOption
 * @package Improntus\PedidosYa\Model\Config\Source
 */
class CatalogProductOption implements ArrayInterface
{
    /**
     * @var AttributeFactory
     */
    protected $_attributeFactory;

    /**
     * @var TypeFactory
     */
    protected $_eavTypeFactory;

    /**
     * @param AttributeFactory $attributeFactory
     * @param TypeFactory $typeFactory
     */
    public function __construct(
        AttributeFactory $attributeFactory,
        TypeFactory $typeFactory
    )
    {
        $this->_attributeFactory    = $attributeFactory;
        $this->_eavTypeFactory      = $typeFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];

        $entityType = $this->_eavTypeFactory->create()->loadByCode('catalog_product');
        $collection = $this->_attributeFactory->create()
            ->getCollection()
            ->addFieldToFilter('entity_type_id', $entityType->getId())
            ->setOrder('attribute_code', 'ASC');

        foreach ($collection as $attribute) {
            if($attribute->getFrontendLabel() != NULL) {
                $options[] = ['value' => $attribute->getAttributeCode(), 'label' => __($attribute->getAttributeCode())];
            }
        }
        return $options;
    }
}

