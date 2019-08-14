<?php 
/**
 * Osf Global Services
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * 
 * Connect Helper
 *
 * @category    Osf
 * @package     Osf_IngraMicro
 * @author      Osf Global Services
 */

class Osf_IngramMicro_Helper_Connect extends Mage_Core_Helper_Data
{

	public $ftpHost;
	public $ftpUser;
	public $ftpPass;
	public $XMLEndpoint;
	public $tmpLocation;
	public $prodFileName;
	public $logFile = 'ingrammicro.log';
	public $vendor 	= 'Ingrammicro';
	protected $logH = 'Error: ';

	public function __construct()
	{
		$this->ftpHost      = Mage::getStoreConfig('osf_ingrammicro/ftplogin/ftp_host');
		$this->ftpUser      = Mage::getStoreConfig('osf_ingrammicro/ftplogin/ftp_user');
		$this->ftpPass      = Mage::helper('core')->decrypt(Mage::getStoreConfig('osf_ingrammicro/ftplogin/ftp_password'));
		$this->ftpRemoteDir = Mage::getStoreConfig('osf_ingrammicro/ftplogin/ftp_remote_dir');
		$this->prodFileName = Mage::getStoreConfig('osf_ingrammicro/ftplogin/ftp_prod_file');
		$this->XMLEndpoint  = Mage::getStoreConfig('osf_ingrammicro/xmllogin/xml_endpoint');
		$this->XMLUser      = Mage::getStoreConfig('osf_ingrammicro/xmllogin/xml_username');
		$this->XMLPass      = Mage::helper('core')->decrypt(Mage::getStoreConfig('osf_ingrammicro/xmllogin/xml_password'));
		$this->tmpLocation  = Mage::getBaseDir('var') . DS . 'ingrammicro' . DS;
		
		// check if the ingrammicro folder exists if not create it
		if(false === $this->checkDir())
			return false;
		return true;
	}

	/**
	 * Get the products file from Ingram Micro FTP
	 *
	 * @param none
	 * @return string
	 */
	public function getProductsFile()
	{
		//init the ftp and download the products file
		$ftp = Mage::helper('osf_ingrammicro/ftp');
		if(is_null($this->ftpHost) || is_null($this->ftpUser) || is_null($this->ftpPass)){
			// move this log to ingrammicro log
			Mage::log( $this->vendor . ': Error: ftphost or ftpuser or ftppass can not be empty', null, $this->logFile);
			return false;
		}
		
		Mage::log( $this->vendor . ': Start downloading import file', null, $this->logFile);
		$ftp->setRemoteDirectory($this->ftpRemoteDir);
		$ftp->ftpConnect($this->ftpHost, $this->ftpUser, $this->ftpPass);
		$ftp->downloadFile($this->tmpLocation . $this->prodFileName, $this->prodFileName);
		$ftp->ftpClose();
		Mage::log( $this->vendor . ': End downloading import file', null, $this->logFile);

		// Checking the download file extension
		$fileObj = new SplFileInfo($this->tmpLocation . $this->prodFileName);

		if($fileObj->getExtension() === 'ZIP'){
			
			// init the zip archive object and open so i can unzip the file
			$zip = new ZipArchive;
			$result = $zip->open($this->tmpLocation . $this->prodFileName);

			// checking the result of the open zip
			if($result === true){
				$zip->extractTo($this->tmpLocation);
				$zip->close();
			} else { 
				throw new Exception("Zip: Failed, code: ". $result, 1);
			}

			// prepareing the name of the file because inside of the archive is a TXT file
			$filename = str_replace($fileObj->getExtension(), 'TXT', $this->prodFileName);
		} else {
			// if the file is not archived the file name is ok
			$filename = $this->prodFileName;
		}
		
		//$filename = 'PRICE.TXT';

		$fullPath = Mage::getBaseDir('var') . DS . 'ingrammicro' . DS . $filename;
		return $fullPath;
	}
	
	/**
	 * Send the process order xml request
	 *
	 * @param string
	 * @return string
	 */
	public function sendXMLRequest($xml)
	{
		/* init the http client */
		$client = new Zend_Http_Client($this->XMLEndpoint);
		/* set the method type and the timeout for the http call */
		$client->setMethod(Zend_Http_Client::POST);
		$client->setConfig(array(
			'timeout'      => 30)
		);
		
		$client->setRawData($xml, 'text/xml');

		/* makeing the request to the server and receiving the request */
		$response = $client->request();
		return $response->getBody();
	}

	public function checkDir() {
		if(file_exists($this->tmpLocation) === false){
			if(!mkdir($this->tmpLocation, 0777, true)){
				Mage::log($this->logH . 'Ingrammicro folder does not exist in var folder and it could not be created', 
					null, 
					$this->logFile);
				return false;
			}
		}
		return true;
	}

}

/* Filename: Connect.php */
/* Location: app/code/local/Osf/IngraMicro/Helper/Connect.php */