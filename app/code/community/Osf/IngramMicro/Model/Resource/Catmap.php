<?php 
/**
 * Osf Global Services
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * 
 * Catmap Model
 *
 * @method ()
 *
 * @category    Osf
 * @package     Osf_IngramMicro
 * @author      Osf Global Services
 */

class Osf_IngramMicro_Model_Resource_Catmap extends Mage_Core_Model_Resource_Db_Abstract 
{
	protected function _construct()
	{
		$this->_init('osf_ingrammicro/catmap', 'catmap_id');
	}
}

/* Filename: Catmap.php */
/* Location: app/code/local/Osf/IngramMicro/Model/Resource/Catmap.php */