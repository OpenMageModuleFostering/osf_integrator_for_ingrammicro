 <?php 
/**
 * Osf Global Services
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 *
 * Status Block
 *
 * @category    Osf IngramMicro
 * @package     Osf_IngramMicro
 * @author      Osf Global Services
 */

class Osf_IngramMicro_Block_Adminhtml_Shipments_Renderer_Status extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
	public function render(Varien_Object $row)
	{
		$label = '';
		if( is_null($row->getShipmentStatus()) ){
			$shipment = Mage::getModel('sales/order_shipment')->load( $row->getEntityId() );
			$label = Mage::getModel('osf_ingrammicro/source')->getStatusText($shipment->getShipmentStatus());
		} else {
			$label = Mage::getModel('osf_ingrammicro/source')->getStatusText($row->getShipmentStatus());
		}

		return $label;
	}
}

/* Filename: Status.php */
/* Location: app/code/local/Osf/IngramMicro/Block/Adminhtml/Shipments/Renderer/Status.php */