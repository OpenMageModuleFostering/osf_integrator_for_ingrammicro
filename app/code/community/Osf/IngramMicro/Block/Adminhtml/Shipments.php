<?php
/**
 * Osf Global Services
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 *
 * Shipments Block
 *
 * @category    Osf IngramMicro
 * @package     Osf_IngramMicro
 * @author      Osf Global Services
 */

class Osf_IngramMicro_Block_Adminhtml_Shipments extends Mage_Adminhtml_Block_Sales_Order_View_Tab_Shipments
{
    protected function _prepareColumns()
    {
        $this->addColumn('increment_id', array(
            'header' => Mage::helper('sales')->__('Shipment #'),
            'index' => 'increment_id',
        ));

        $this->addColumn('shipping_name', array(
            'header' => Mage::helper('sales')->__('Ship to Name'),
            'index' => 'shipping_name',
        ));

        $this->addColumn('shipment_status', array(
            'header' => Mage::helper('sales')->__('Ingrammicro Shipment Status'),
            'index' => 'shipment_status',
            'renderer'  => 'Osf_IngramMicro_Block_Adminhtml_Shipments_Renderer_Status',
        ));

        $this->addColumn('created_at', array(
            'header' => Mage::helper('sales')->__('Date Shipped'),
            'index' => 'created_at',
            'type' => 'datetime',
        ));

        $this->addColumn('total_qty', array(
            'header' => Mage::helper('sales')->__('Total Qty'),
            'index' => 'total_qty',
            'type'  => 'number',
        ));

        return parent::_prepareColumns();
    }
}

/* Filename: Shipments.php */
/* Location: app/code/local/Osf/IngramMicro/Block/Adminhtml/Shipments.php */