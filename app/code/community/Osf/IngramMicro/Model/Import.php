<?php 
/**
 * Osf Global Services
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * 
 * Import Model
 *
 * @category    Osf
 * @package     Osf_IngramMicro
 * @author      Osf Global Services
 */

class Osf_IngramMicro_Model_Import extends Mage_Core_Model_Abstract 
{
	public $fileHeader = array();
	public $readConnection;
	public $resource;
	public $categoryMap;
	public $ingramMicroVendorId;
	public $test_mode;
	public $allowedSkus;
	protected $logFile  = 'ingrammicro.log';
	protected $vendor   = 'Ingrammicro';

	public function _construct()
	{

		$this->resource = Mage::getSingleton('core/resource');
		$this->buildCategoryMap();
		$this->readConnection 	= Mage::getSingleton('core/resource')->getConnection('core_read');
		$this->test_mode 		= Mage::getStoreConfig('osf_ingrammicro/general/test_mode');
		$this->buildAllowedSkus();
		if(!$this->checkMagmiConfig()){
			echo "Could not create magmi config file. Please make the folder ... temporary writeable";
			die();
		}
		parent::_construct();
	}
	
	/**
	 * Process the received csv
	 *
	 * @param none
	 * @return bool
	 */
	public function processData()
	{

		/* getting the file from the server */
		$productsFile = Mage::helper('osf_ingrammicro/connect')->getProductsFile();
		if($productsFile === false){
			Mage::log("Ingram Micro: Error in getting products file", null, $this->logFile);
			return false;
		}
		
		$file = new SplFileObject($productsFile);
		
		/* init vars */
		$productsData 	= array();
		$row1 			= 0;
		$fileHeader 	= array();
		
		/* looping throught the csv */
		$index = 0;
		while(!$file->eof()){
			$row = $file->fgetcsv(",");
			
			if ( $this->test_mode && $index > 5 ) {
				break;
			}

			if( isset($row[1]) && !empty( $this->allowedSkus ) && !in_array( trim($row[1]), $this->allowedSkus) ){
				continue;
			}
			
			/* setting the product data */
			if ( isset($row[1])) {
				$productsData[] = $this->createDataArr($row);
			}
			$index++;
		}
		
		Mage::log( $this->vendor . ": Magmi import started", null, $this->logFile);
		
		/* setting the data to be imported and starting the import */
		Mage::helper('osf_ingrammicro/data')->setImportArray($productsData);
		Mage::helper('osf_ingrammicro/data')->startMagmiImport();

		Mage::log( $this->vendor . ": Magmi import finished", null, $this->logFile);

		echo "Import finished.";

		return;
	}
	
	/**
	 * Construct the product array for import
	 *
	 * @param array $row
	 * @return array $productData
	 */
	public function createDataArr($row)
	{
		
		$productData 		= array();
		$productData['sku'] = $row[1];

		Mage::log( $this->vendor . ": Start building data array", null, $this->logFile);

		if( !$this->productExists( $row[1] ) ){
		
			$productData['url_key']             = Mage::getModel('catalog/product_url')->formatUrlKey( trim($row[4]) );
			$productData['url_rewrite']         = 1;
			$productData['mpn']                 = trim($row[7]);
			$productData['name']                = trim($row[4]);
			$productData['short_description']   = trim($row[4]);
			$productData['description']         = trim($row[4]);
			$productData['manufacturer']        = trim($row[3]);
			$productData['brand']               = trim($row[3]);
			$productData['msrp']                = (float)$row[6];
			$productData['is_oversized']        = ($row[18] === 'N')? 1 : 0;
			$productData['osf_product_cost']	= (float)$row[14];
			$productData['price']               = (float)$row[14];
			$productData['weight']              = (!empty($row[8]))? (float)$row[8] : 1;
			$productData['upc']                 = $row[9];
			$productData['item_length']         = (float)$row[10];
			$productData['item_width']          = (float)$row[12];
			$productData['item_height']         = (float)$row[13];
			$productData['visibility']          = Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE;
			$productData['tax_class_id']        = 2;
			$productData['type_id']             = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
			$productData['attribute_set_id']    = 4;
			if ( isset( $this->categoryMap[trim($row[20]) . '_' . trim($row[18])]['category_paths'] ) ) {
				$productData['categories']          = $this->categoryMap[trim($row[20]) . '_' . trim($row[18])]['category_paths'];	
			}
			$productData['osf_product_vendor']  = $this->vendor;
		}

		$productData['cost']                = (float)$row[14];
		$productData['inventory']  			= trim($row[23]);
		$productData['qty']  				= trim($row[23]);
		$productData['status'] = ($row[0] !== 'D')? Mage_Catalog_Model_Product_Status::STATUS_ENABLED 
													: Mage_Catalog_Model_Product_Status::STATUS_DISABLED;

		Mage::log( $this->vendor . ": Finish building data array", null, $this->logFile);

		return $productData;
	}

	/**
	 * Check if a product with the sku exists in Magento
	 *
	 * @param array $sku
	 * @return bool
	 */
	public function productExists($sku)
	{
		$tableName = $this->resource->getTableName('catalog_product_entity');
		$select = $this->readConnection
            ->select()
            ->from($tableName, array(new Zend_Db_Expr('count(entity_id)')))
            ->where($this->readConnection->quoteInto('sku=?', $sku));
        $countSku = $this->readConnection->fetchOne($select);

	    return ($countSku != 0)? true :false;
	}
	
	/**
	 * Validate the product based on the required validations
	 *
	 * @param array $row
	 * @return bool
	 */
	public function validate($row) {
		
		//Check if product can be mapped with a category
		if (isset($row[20]) && isset($row[18])) {
			$catMap = $this->getCategoryId( trim($row[20]) . '_' . trim($row[18]) );
			if ( !isset($catMap['category_paths']) ) {
				return false;
			}
		}
		
		//It seems like sometimes a space appears at the end of import files.
		if ( !isset($row[1])) {
			return false;
		}
		
		return true;
	}
	
	public function buildCategoryMap() {

		$collection = Mage::getModel('osf_ingrammicro/catmap')->getCollection(); 

		foreach ( $collection as $entry ) {
			$this->categoryMap[ $entry->getIngrammicroCategory() ]['category_paths']   = $entry->getMagentoCategory();
		}

		return;
	}

    /**
     * Check if the db configuration for magmi exists and if not create it
     *
     * @return bool
     */
	public function checkMagmiConfig()
	{
		$filename = Mage::getModuleDir('', 'Osf_IngramMicro') . DS . 'lib' . DS . "magmi/conf/magmi.ini";
		if(file_exists($filename)){
			return true;
		}

		$config  = Mage::getConfig()->getResourceConnectionConfig("default_setup");
		$file = new SplFileObject($filename,"w");
		$fileText = "[DATABASE]\nconnectivity = \"net\"\nhost = \"" 
		. $config->host . "\"\nport = \"3306\"\nunix_socket = \ndbname = \"" 
		. $config->dbname . "\"\nuser = \"" 
		. $config->username . "\"\npassword = " 
		. $config->password . "\ntable_prefix = \n[MAGENTO]\nversion = \"1.7.x\"\nbasedir = \"../../\"\n[GLOBAL]\n"
		."step = \"0.5\"\nmultiselect_sep = \",\"\ndirmask = \"755\"\nfilemask = \"644\"\n";

		try{
			$file->fwrite($fileText);
		} catch (Exception $e){
			Mage::log('Magmi Conf folder not writeable');
			return false;
		}

		return true;
	}

	 public function buildAllowedSkus(){

		$filename 			= Mage::getStoreConfig('osf_ingrammicro/ftplogin/filtered_skus_file');
		if ( is_file( Mage::getBaseDir('media') . DS . 'admin-config-uploads' . DS . $filename ) ) {
			$allowedSkusFile 	= Mage::getBaseDir('media') . DS . 'admin-config-uploads' . DS . $filename;
			$skusFile 			= new SplFileObject($allowedSkusFile);
			while ( !$skusFile->eof() ) {
				$row 					= $skusFile->fgetcsv();
				$this->allowedSkus[] 	= trim($row[0]);
			}
		}

		return;
	}
}



/* Filename: Import.php */
/* Location: app/code/local/Osf/IngramMicro/Model/Import.php */