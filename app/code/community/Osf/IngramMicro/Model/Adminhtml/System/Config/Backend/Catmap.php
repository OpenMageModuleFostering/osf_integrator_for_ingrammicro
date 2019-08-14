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

class Osf_IngramMicro_Model_Adminhtml_System_Config_Backend_Catmap
	extends Mage_Core_Model_Config_Data
{
	public function _afterSave()
	{
		$csv_file = $_FILES["groups"]["tmp_name"]["general"]["fields"]["category_mapping_import"]["value"];
	   
		if ( $csv_file ) {
			//Extract data from CSV file
			$csv = new Varien_File_Csv;
			$csv->setDelimiter("|");
			$data = $csv->getData( $csv_file );

			$write  = Mage::getSingleton('core/resource')->getConnection('core_write');
			$read   = Mage::getSingleton('core/resource')->getConnection('core_read'); 
			$table  = Mage::getSingleton('core/resource')->getTableName('osf_ingrammicro/catmap');

			$read->query("DELETE from $table");
			$result_num = $write->insertArray(
				$table,
				array('ingrammicro_category','magento_category'),
				$data
			);
		}
	}
}
