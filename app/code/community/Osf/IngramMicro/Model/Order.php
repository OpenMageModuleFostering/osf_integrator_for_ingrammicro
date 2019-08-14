<?php 
/**
 * Osf Global Services
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 *
 * Order Model
 *
 * @category    Osf
 * @package     Osf_Ingrammicro
 * @author      Osf Global Services
 */

class Osf_Ingrammicro_Model_Order extends Mage_Core_Model_Abstract {

    protected $account_number;
    protected $XMLUser;
    protected $XMLPass;
    protected $XMLObj;
    protected $order;
    protected $queue;
    protected $logFile = 'ingrammicro.log';
    
    public function _construct()
	{
		$this->account_number = Mage::getStoreConfig('osf_ingrammicro/general/account_number');
        $this->XMLUser      = Mage::getStoreConfig('osf_ingrammicro/xmllogin/xml_username');
		$this->XMLPass      = Mage::helper('core')->decrypt(Mage::getStoreConfig('osf_ingrammicro/xmllogin/xml_password'));
        $this->test_mode      = Mage::getStoreConfig('osf_ingrammicro/general/test_mode');

        parent::_construct();
	}
    
    private function _addChildElement( &$parent, $key, $value ) {
        
        if (is_array($value)) {
            $node = $parent->addChild($key);
            foreach ($value as $k=>$v) {
                $this->_addChildElement($node, $k, $v);
            }
        }else{
             $node = $parent->addChild($key, $value);
        }
        
        return $parent;
    }

    /**
     * Builds an Ingram Micro XML object from order data.
     * This is used later on to the API
     * @param  array $orderData
     * @return XML Object
     */
    public function buildXMLData($orderData) {
        
        $this->XMLObj = new SimpleXMLElement('<?xml version="1.0" encoding="ISO-8859-1"?><OrderRequest></OrderRequest>');
		
        //version
        $this->XMLObj->addChild('Version','2.0');
        
		// credentials
		$credentials = $this->XMLObj->addChild('TransactionHeader');
        foreach ($orderData['credentials'] as $key => $value) {
            $credentials->addChild($key, $value);
        }

		// Order Header Information
		$orderHeaderInformation = $this->XMLObj->addChild('OrderHeaderInformation');
		foreach ($orderData['orderHeaderInformation'] as $lvl1_key => $lvl1_val) {        
            $this->_addChildElement( $orderHeaderInformation, $lvl1_key, $lvl1_val);		
		}

		// Order Request Items
		$items = $this->XMLObj->addChild('OrderLineInformation');
        
        $commentText    = '';
        if ($this->test_mode) {
            $commentText = '///TEST PO DO NOT SHIP';
        }
        
		foreach ($orderData['items'] as $item) {
			$itemNode = $items->addChild('ProductLine');
			$itemNode->addChild('SKU', $item['sku']);
            $itemNode->addChild('Quantity', $item['qty']);         
            $itemNode = $items->addChild('CommentLine');
			$itemNode->addChild('CommentText', $commentText );
		}
        
        $this->XMLObj->addChild('ShowDetail','0');
		
		return $this->XMLObj->asXML();
	}

	/**
     *  Build the order array so it can be sent to xml process
     *
     * @param object
     * @param array
     * @return string
     */
	public function buildOrderArray($order, $shipment)
	{
		$this->order = $order;
		$data = array(
			'orderHeaderInformation'  => $this->buildOrderHeaderInformation($shipment),
			'items'                   => $this->buildItems($shipment),
            'credentials'             => $this->buldCredentials($shipment)
		);

		return $this->buildXMLData($data);
	}

	/**
     * Building the credentials element for the request XML object
     * @param  object $shipment Magento Shipment Object
     * @return array
     */
    public function buldCredentials ( $shipment ) {
        
        $shipment = array(
            'SenderID'      => 'Website',
            'ReceiverID'    => 'Website',
            'CountryCode'   => 'FT',
            'LoginID'       => $this->XMLUser,
            'Password'      => $this->XMLPass,
            'TransactionID' => $shipment->getIncrementId()

        );
        
        return $shipment;
    }
    
    public function buildOrderHeaderInformation($shipment) {
        $shippingAddress = $shipment->getShippingAddress();
		$streetArr = $shippingAddress->getStreet();
        
        $customerPO     = $shipment->getIncrementId();
        $endUserPO      = $shipment->getIncrementId();
        $autoRelease    = '';
        if ($this->test_mode) {
            $autoRelease    = 'H';
        }
        
		$shipment = array(
            'BillToSuffix'          => '',
            'AddressingInformation' => array(
                'CustomerPO'            => $customerPO,
                'ShipToAttention'       => '',
                'EndUserPO'             =>  $endUserPO,
                'ShipTo' => array(
                    'Address' => array(
                        'ShipToAddress1'    => $shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname(),
                        'ShipToAddress2'    => $streetArr[0],
                        'ShipToAddress3'    => (count($streetArr) > 1)? $streetArr[1] : null,
                        'ShipToCity'        => $shippingAddress->getCity(),
                        'ShipToProvince'    => $shippingAddress->getRegion(),
                        'ShipToPostalCode'  => trim($shippingAddress->getPostcode()),
                    )
                ),
            ),
            'ProcessingOptions' => array(
                'CarrierCode'               => '',
                'AutoRelease'               => $autoRelease,
                'ThirdPartyFreightAccount'  => '',
                'KillOrderAfterLineError'   => '',
                'ShipmentOptions'   => array(
                    'BackOrderFlag'     => 'Y',
                    'SplitShipmentFlag' => 'n',
                    'SplitLine'         => 'N',
                    'ShipFromBranches'  => ''
                )
            ),
            'DynamicMessage'        => array(
                'MessageLines'  => ''
            )
		);
		return $shipment;
    }

    /**
     * Map order items to array for xml
     *
     * @param array
     * @return array
     */
    public function buildItems($shipment)
    {
        $items = array();
        foreach ($shipment->getAllItems() as $itemObj) {
            $item = array();
            $item['sku'] = $itemObj->getSku();
            $item['customerPartNumber'] = $itemObj->getSku();
            $item['name'] = $itemObj->getName();
            $item['price'] = $itemObj->getPrice();
            $item['qty'] = (int)$itemObj->getQty();
            $item['comment1'] = null;
            $item['comment2'] = null;
            $item['shipfrom'] = null;
            $item['specialPriceRef'] = null;
            $items[] = $item;
        }

        return $items;
    }
    
    /**
     * Process the order response
     *
     * @param $xmlResponse
     * @param $order
     * @param $xmlData
     * @internal param $object
     * @return array
     */
	public function processOrderResponse($xmlResponse, $order, $xmlData)
	{
		$this->order = $order;
		$xmlResponseObject = simplexml_load_string($xmlResponse);
        
        if($xmlResponseObject === false){
            Mage::log('Ingrammicro: The response was not a xml string', null, $this->logFile);
            return;
        }

		if(is_null($xmlResponseObject->TransactionHeader)){
			Mage::log('Ingrammicro: The response xml does not contain the TransactionHeader node', null, $this->logFile);
			return;
		}
        
        // Check if there is any error in the response
		if ( 
                isset($xmlResponseObject->TransactionHeader->ErrorStatus['ErrorNumber']) &&
                !empty($xmlResponseObject->TransactionHeader->ErrorStatus['ErrorNumber'])
        ){
			Mage::log('Order Response Error: ' . 
				$xmlResponseObject->TransactionHeader->ErrorStatus['ErrorNumber'] . 
				'Order Respose Error Details' . 
				$xmlResponseObject->TransactionHeader->ErrorStatus, null, $this->logFile);
			$this->errorResponse($xmlResponseObject, $xmlData);
			return;
		}

		$branchOrderNumber = $xmlResponseObject->BranchOrderNumber;
		if( isset($branchOrderNumber) ){
			$this->acceptedResponse($xmlResponseObject);
		} else {
			$this->rejectedResponse($xmlResponseObject);
		}

		return;
	}

	/**
     * The accepted response handle
     *
     * @param object
     * @return null
     */
	public function acceptedResponse($xmlObject)
	{
		$transactionHeader  = $xmlObject->TransactionHeader;
        $orderNumbers       = $xmlObject->OrderInfo->OrderNumbers;
        
        
		Mage::log('Ingrammicro PO accepted: '. $transactionHeader->TransactionID, null, $this->logFile);

		$shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($transactionHeader->TransactionID);
		$shipment->setShipmentStatus(Osf_IngramMicro_Model_Source::SHIPMENT_STATUS_READY);

        //Set shipping comment
        $comment = 'Ingram Micro Branch Order Number: ' . $orderNumbers->BranchOrderNumber;
        if (!$comment instanceof Mage_Sales_Model_Order_Shipment_Comment) {
            $comment = Mage::getModel('sales/order_shipment_comment')
                ->setComment($comment);
        }

        $shipment->addComment($comment);
        
		try{
			$shipment->save();
            $shipment->getCommentsCollection()->save();
		} catch (Exception $e){
			Mage::log('Ingrammicro: Response Error: '. $e->getMessage(), null, $this->logFile);
		}

		return;
	}

	/**
     * The rejected response handle
     *
     * @param object
     * @return null
     */
	public function rejectedResponse($xmlObject)
	{	
		# TO DO some checks about the response
		$transactionHeader = $xmlObject->TransactionHeader;
		Mage::log('Ingrammicro PO rejected', null, $this->logFile);
		// load the shipment and cancel it
		$shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($transactionHeader->TransactionID);
		$shipment->setShipmentStatus(Osf_IngramMicro_Model_Source::SHIPMENT_STATUS_CANCELED);
		try{
			$shipment->save();
		} catch (Exception $e){
			Mage::log('Ingrammicro: Response Error: ' . $e->getMessage(), null, $this->logFile);
		}

		return;
	}
    
    /**
     * The error response handle
     *
     * @param $orderResponse
     * @param $xmlData
     * @throws Exception
     * @internal param $object
     * @return null
     */
	public function errorResponse($orderResponse,$xmlData)
	{
		# for unknown error need to clarify what to do
		# to do check if xmlObject is an object
		$xmlObject = simplexml_load_string($xmlData);
        $queue = Mage::getModel('osf_ingrammicro/queue')->loadByField('order_id', $this->order->getId());
		if($queue->getId() === null){
			$queue = Mage::getModel('osf_ingrammicro/queue');
			$queue->setOrderId($this->order->getId());
			$queue->setPoId( $xmlObject->OrderRequest->TransactionHeader->TransactionID );
			$queue->setOrderXml(trim($xmlData));
			$queue->setRetry(0);
			$queue->setPrevError($orderResponse->ErrorMessage . ' : ' . $orderResponse->ErrorDetail);
		} else {
			$retry = $queue->getRetry();
			if($retry >= 5){
				$queue->delete();
			}
			$retry++;
			$queue->setRetry($retry);
		}

		$queue->save();
		
        # Put order on hold
        $this->order->setStatus(Mage_Sales_Model_Order::STATE_HOLDED);

        try{
            $this->order->save();
            // $shipment->save();
        } catch (Exception $e){
            Mage::log('Ingrammicro: Response Error: unknown error order on hold'.$e->getMessage(), null, $this->logFile);
        }

		return;
	}

	/**
     * The retry the orders gave known errors
     *
     * @param null
     * @return null
     */
	public function retry()
	{
		$jobs = Mage::getModel('osf_ingrammicro/queue')->getCollection();
		foreach ($jobs as $job) {
			$order = Mage::getModel('sales/order')->load($job->getOrderId());
            $order->setStatus(Mage_Sales_Model_Order::STATE_PROCESSING);
            $order->save();
			$xmlResponse = Mage::helper('osf_ingrammicro/connect')->sendXMLRequest($job->getOrderXml());
			$response = Mage::getModel('osf_ingrammicro/order')->processOrderResponse($xmlResponse, $order, $job->getOrderXml());
		}

		return;
	}
}

/* Filename: Order.php */
/* Location: app/code/local/Osf/Ingrammicro/Model/Order.php */