<?php 
/**
 * Osf Global Services
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * 
 * Shipnotice Model
 *
 * @category    Osf
 * @package     Osf_Ingrammicro
 * @author      Osf Global Services
 */

class Osf_IngramMicro_Model_Shipnotice extends Mage_Core_Model_Abstract {   

   

    public $logFile;    

    public function _construct()
    {
        $this->logFile = 'ingrammicro.log';
    }


    /**
     * Process the ship notice
     *
     * @param object
     * @return bool
     */
    public function processShipNotice($xmlData)
    {
        $despatchAdviceHeader = $xmlData->DespatchAdviceHeader;
        if(empty($despatchAdviceHeader)){
            return 'receiveShipNotice_missing_despatchAdviceHeader';
        }

        $purchaseOrderId = $despatchAdviceHeader->CustomerPO;
        if(empty($purchaseOrderId)){
            return 'receiveShipNotice_missing_CustomerPO';
        }

        $shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($purchaseOrderId);
        if(is_null($shipment->getId())){
            return 'receiveShipNotice_shipment_not_found';
        }

        $order = $shipment->getOrder();
        if(is_null($order->getId())){
            return 'receiveShipNotice_order_not_found';
        }

        // create the invoice
        $invoice = $this->createInvoice($xmlData, $order, $shipment);

        if($invoice === false){
            $error = true;
        }
        
        // set the shipment status to shipped
        $shipment->setShipmentStatus(Osf_IngramMicro_Model_Source::SHIPMENT_STATUS_SHIPPED);

        // send shippment email
        $shipment->sendEmail(true, '');
        $shipment->setEmailSent(true);
        
        try {
            $shipment->save();
        } catch(Exception $e) {
            Mage::log('Ingrammicro: Ship Notice Error:'. $e->getMessage(), null, $this->logFile);
            return 'receiveShipNotice_other_errors';
        }
        
        return true;
    }

    /**
     * Create and invoice based on the ship noitice 
     *
     * @param object
     * @return bool
     */
    public function createInvoice($xmlData, $order, $shipment)
    {
        // init and build an array with the received sku from the ship notices items
        $shippedSku = array();
        
        // check if the order was processed before
        if($shipment->getShipmentStatus() == Osf_IngramMicro_Model_Source::SHIPMENT_STATUS_SHIPPED){
            return false;
        }

        $trackingNumbers = array();
        foreach ($xmlData->LineHeader->LineItem as $item) {
            $sku = (string)$item->Product['SKU'];
            $qty = (int)$item->Product['DespatchQuantity'];
            $shippedSku[$sku] = $qty;
            $trackingNumbers[] = $item->PackageHeader->IdentificationHeader->Identification['TrackingNumber'];
        }

        
        
        $uniqueTrackNumbers = array_unique($trackingNumbers);

        // add tracking number
        foreach ($uniqueTrackNumbers as $trackNumber) {
            $trackNo = Mage::getModel('sales/order_shipment_track')
                    ->setNumber($trackNumber)
                    ->setCarrierCode($xmlData->ConsignmentHeader->CarrierCode)
                    ->setTitle($xmlData->ConsignmentHeader->CarrierName);
            $shipment->addTrack($trackNo);
        }

        // get the order items
        $items = $order->getItemsCollection();
        $orderItemsCount = count($items);
        $asnItemsCount = count($xmlData->LineHeader->LineItem);

        // init and build an array for the invoice with the qtys and ids
        $qtys = array();
        foreach($items as $orderItem){

            if(in_array($orderItem->getSku(), array_keys($shippedSku))){
                $qtys[$orderItem->getId()] = $shippedSku[$orderItem->getSku()];
            }
        }

        // add the invoiced qtys
        $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice($qtys);
        $amount = $invoice->getGrandTotal();
        $invoice->register()->pay();
        $invoice->getOrder()->setIsInProcess(true);

        // adding a commnet to the order
        $history = $invoice->getOrder()->addStatusHistoryComment(
            'Amount of $' . $amount . ' captured automatically.', false
        );
        $history->setIsCustomerNotified(true);
        // save order
        $order->save();

        // make the invoice transaction
        Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder())
            ->save();

        // save invoice and send the email to the costumer
        try {
            $invoice->save();
            // send email to customer
            $invoice->sendEmail(true, '');
            if($orderItemsCount == $asnItemsCount){
                $order->setStatus(Mage_Sales_Model_Order::STATE_COMPLETE);
                $order->save();
            }
        } catch (Exception $e){
            Mage::log('Ingram Micro: Invoice could not be created:'. $e->getMessage(), null, $this->logFile);
            return false;
        }

        return true;
    }

}

/* Filename: Shipnotice.php */
/* Location: app/code/local/Osf/Ingrammicro/Model/Shipnotice.php */