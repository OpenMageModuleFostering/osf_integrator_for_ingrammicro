<?php

/* Require the Magmi files */
require_once( Mage::getModuleDir('', 'Osf_IngramMicro') . DS . "lib/magmi/inc/magmi_defs.php");
require_once( Mage::getModuleDir('', 'Osf_IngramMicro') . DS . "lib/magmi/integration/inc/magmi_datapump.php");
/* End require Magmi files */

/**
 *
 * Osf Global Services
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 *
 * Data Helper
 * 
 * @category OSF
 * @package OSF_IngraMicro
 * @author OSF Global Services
 * 
 */
class Osf_IngramMicro_Helper_Data extends Mage_Core_Helper_Data {

    protected $importArray = array();
	protected $profile = "Ingrammicro";
	public $resource;
	public $writeConn;
	public $readConn;
	public $vendorTable;
	public $vendorId;
	public $logFile; 

	public function __construct(){
		// getting the resources 
		$this->resource = Mage::getSingleton('core/resource');
		$this->writeConn = $this->resource->getConnection('core_write');
		$this->readConn = $this->resource->getConnection('core_read');
		$this->logFile = 'ingrammicro.log';
	}

	/**
     * Starts the import using Magmi
     *
     * @param none
     * @return bool
     */
	public function startMagmiImport()
	{
		/* Factory Create the product import */
		$magmiDp = Magmi_DataPumpFactory::getDataPumpInstance("productimport");

		/* Init the profile and the import method */
		$magmiDp->beginImportSession($this->profile,"create");

		/* looping thought the entire array of products */
		foreach ($this->importArray as $productKey => $productValue) {
			$res = $magmiDp->ingest($productValue);
			if($res['ok'] !== true){
				Mage::log('Magmi: Import of product failed, sku:' . $productValue['sku'], null,$this->logFile);
			}
		}

		/* End import session */
		$magmiDp->endImportsession();
		return;
	}

	/**
     * Set the import array data
     *
     * @param array
     * @return array
     */
	public function setImportArray($dataIn)
	{
		return $this->importArray = $dataIn;
	}
}

/* Filename: Data.php */
/* Location: app/code/local/Osf/IngramMicro/Helper/Data.php */