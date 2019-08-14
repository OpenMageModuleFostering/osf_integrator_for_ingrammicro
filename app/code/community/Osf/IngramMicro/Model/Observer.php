<?php 
/**
 * Osf Global Services
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * 
 * Observer Model
 *
 * @category    Osf
 * @package     Osf_IngramMicro
 * @author      Osf Global Services
 */

class Osf_IngramMicro_Model_Observer extends Mage_Core_Model_Abstract {
    
    protected $vendor 	= 'Ingrammicro';
	protected $logFile	= 'ingrammicro.log';
    
    /**
     * Process the order so it can be sent to Ingrammicro
     *
     * @param object
     * @return object
     */
	public function processOrder($observer)
	{

		Mage::log('Ingrammicro: Start procesing order', null, $this->logFile);

		// get the order from Magento
		$order = $observer->getOrder();

		// check if order is in 
		if( $order->getStatus() == Mage_Sales_Model_Order::STATE_PROCESSING ){
			Mage::log($this->vendor . ': End processing order, order already processed', null, $this->logFile);
			return;
		}

		$orderItems = $this->checkItems($order->getAllItems());
		if ( $orderItems === false ) {
			Mage::log($this->vendor . ': End processing order, no ' . $this->vendor .' items', null, $this->logFile);
			return $this;
		}

		// check that the order has a shipment
		$shipments = $order->getShipmentsCollection();
		if(count($shipments) > 0){
			return;
		}

		$shipment = $this->createShipment($order,$orderItems);

		// build the xml that will be sent to Ingram Micro
		$xmlData    = Mage::getModel('osf_ingrammicro/order')->buildOrderArray($order, $shipment);
   		Mage::log($xmlData, null, $this->logFile);

		// send the process order request
		$xmlResponse = Mage::helper('osf_ingrammicro/connect')->sendXMLRequest($xmlData);
		Mage::log($xmlResponse, null, $this->logFile);
        
		// process the response that has arrived from Ingrammicro
		$response = Mage::getModel('osf_ingrammicro/order')->processOrderResponse($xmlResponse, $order, $xmlData);
		Mage::log('Ingrammicro: After process response', null, $this->logFile);

		Mage::log('Ingrammicro: End procesing order', null, $this->logFile);
		return $this;
	}

	/**
     * Check if the order contains products from ingrammicro and return the items
     * @param $items
     * @return bool
     */
	public function checkItems($items)
	{
		$outItems = array();
		foreach ($items as $item) {
			$p_id = $item->getProduct()->getId();
			$vendor = Mage::getModel('catalog/product')->load($p_id)->getData('osf_product_vendor');
			if(trim($vendor) == $this->vendor){
				$outItems[] = $item;
			}
		}

		return (empty($outItems))? false : $outItems;
	}

	/**
     * Create the shipment for the order
     *
     * @param $items
     * @return bool
     */
	public function createShipment($order, $orderItems)
	{		
		$itemsQtys = array();
		foreach ($orderItems as $orderItem) {
			$itemsQtys[$orderItem->getId()] = $orderItem->getData('qty_ordered');
		}

		$shipment = $order->prepareShipment($itemsQtys);
		if ($shipment) {
			$shipment->register();
			$shipment->setShipmentStatus(Osf_IngramMicro_Model_Source::SHIPMENT_STATUS_PENDING);
			$shipment->addComment($this->vendor . ' Items');
			$shipment->getOrder()->setIsInProcess(true);

			try {
				$shipment->save();
			} catch (Mage_Core_Exception $e) {
				Mage::log($this->vendor . ': ' . $e->getMessage(), null, $this->logFile);
			}
		}

		return $shipment;
	}

}

/* Filename: Observer.php */
/* Location: app/code/local/Osf/IngramMicro/Model/Observer.php */
