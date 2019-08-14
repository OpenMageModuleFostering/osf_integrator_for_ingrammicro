<?php 
/**
 * Osf Global Services
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * 
 * Source Model
 *
 * @category    Osf
 * @package     Osf_Ingrammicro
 * @author      Osf Global Services
 */

class Osf_IngramMicro_Model_Source extends Varien_Object {

	const SHIPMENT_STATUS_PENDING    = 0;
	const SHIPMENT_STATUS_READY      = 1;
	const SHIPMENT_STATUS_SHIPPED    = 2;
	const SHIPMENT_STATUS_CANCELED   = 3;
	const SHIPMENT_STATUS_ONHOLD     = 4;

	public $statuses;

	public function _construct() {
		
		$this->statuses = array(
							"Pending",
							"Ready",
							"Shipped",
							"Canceled",
							"Onhold"
						);
	}

	public function getStatusText($status) {

		return $this->statuses[$status];
	}
}

/* Filename: Source.php */
/* Location: app/code/community/Osf/Ingrammicro/Model/Source.php */