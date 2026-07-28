<?php

namespace Improntus\PedidosYa\Controller\Adminhtml\Waypoint;

use Improntus\PedidosYa\Model\WaypointFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;

/**
 * Class Save
 * @author Improntus <http://www.improntus.com> - Adobe Gold Technology Partner | Adobe Gold Solution Partner
 * @copyright Copyright (c) 2026 Improntus
 * @package Improntus\PedidosYa\Controller\Adminhtml\Waypoint
 */
class Save extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    /**
     * Waypoint fields that may be mass-assigned from the request (entity_id is handled separately)
     *
     * @var string[]
     */
    private const ALLOWED_FIELDS = [
        'enabled', 'name', 'address', 'additional_information', 'telephone',
        'instructions', 'region', 'city', 'postcode', 'latitude', 'longitude',
        'working_hours_monday_open', 'working_hours_monday_close',
        'working_hours_tuesday_open', 'working_hours_tuesday_close',
        'working_hours_wednesday_open', 'working_hours_wednesday_close',
        'working_hours_thursday_open', 'working_hours_thursday_close',
        'working_hours_friday_open', 'working_hours_friday_close',
        'working_hours_saturday_open', 'working_hours_saturday_close',
        'working_hours_sunday_open', 'working_hours_sunday_close',
    ];

    /**
     * @var WaypointFactory
     */
    protected $_waypointFactory;

    /**
     * @var Filesystem
     */
    protected $_filesystem;

    /**
     * @param Context $context
     * @param WaypointFactory $waypointFactory
     * @param Filesystem $filesystem
     */
    public function __construct
    (
        Context $context,
        WaypointFactory $waypointFactory,
        Filesystem $filesystem
    )
    {
        $this->_waypointFactory     = $waypointFactory;
        $this->_filesystem          = $filesystem;
        parent::__construct($context);
    }

    /**
     * @return void
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            $this->_redirect('pedidosya/waypoint/index');
            return;
        }

        try {
            $rowData = $this->_waypointFactory->create();

            if (!empty($data['entity_id'])) {
                $rowData->load((int) $data['entity_id']);
                if (!$rowData->getEntityId()) {
                    throw new LocalizedException(__('The waypoint no longer exists.'));
                }
            }

            $filteredData = array_intersect_key($data, array_flip(self::ALLOWED_FIELDS));
            $this->validateWaypointData($filteredData);

            $rowData->addData($filteredData);
            $rowData->save();
            $this->messageManager->addSuccessMessage(__('Waypoint was saved'));

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }
        $this->_redirect('pedidosya/waypoint/index');
    }

    /**
     * @param array $data
     * @return void
     * @throws LocalizedException
     */
    private function validateWaypointData(array $data)
    {
        if (!isset($data['latitude']) || $data['latitude'] === '' || !is_numeric($data['latitude'])
            || (float) $data['latitude'] < -90 || (float) $data['latitude'] > 90) {
            throw new LocalizedException(__('Please enter a valid latitude (between -90 and 90).'));
        }

        if (!isset($data['longitude']) || $data['longitude'] === '' || !is_numeric($data['longitude'])
            || (float) $data['longitude'] < -180 || (float) $data['longitude'] > 180) {
            throw new LocalizedException(__('Please enter a valid longitude (between -180 and 180).'));
        }

        if (!isset($data['postcode']) || !preg_match('/^[A-Za-z0-9\- ]{1,20}$/', (string) $data['postcode'])) {
            throw new LocalizedException(__('Please enter a valid postcode.'));
        }
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Improntus_PedidosYa::waypoint_edit');
    }
}
