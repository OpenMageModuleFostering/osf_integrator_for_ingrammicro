<?php 
/**
 * Osf Global Services
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * 
 * Queue Model
 *
 * @category    Osf
 * @package     Osf_Ingrammicro
 * @author      Osf Global Services
 */

class Osf_Ingrammicro_Model_Queue extends Mage_Core_Model_Abstract 
{
	protected function _construct()
	{
		$this->_init('osf_ingrammicro/queue');
	}

	public function loadByField($field, $fieldValue)
	{
		$collection = $this->getCollection()
					->addFieldToFilter($field, $fieldValue);
		return $collection->getFirstItem();
	}
}

/* Filename: Queue.php */
/* Location: app/code/local/Osf/Ingrammicro/Model/Queue.php */