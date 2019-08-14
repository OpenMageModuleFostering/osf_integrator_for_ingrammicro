<?php 
/**
 * Osf Global Services
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * 
 * Callback Model
 *
 * @category    Osf
 * @package     Osf_Ingrammicro
 * @author      Osf Global Services
 */

class Osf_IngramMicro_Model_Callback extends Mage_Core_Model_Abstract 
{
    public $logFile = 'ingrammicro.log';

    public function receiveShipNotice($xmlString){
        Mage::log('IngramMicro: ASN: Start process', null,$this->logFile);
        if(empty($xmlString)){
            Mage::log('IngramMicro: Error: The ship notice is empty', null,$this->logFile);
            return false;
        }

        // turn on custom error handeling
        libxml_use_internal_errors(true);
        $xmlData = simplexml_load_string($xmlString);

        // get errors if they exist
        $errors = libxml_get_errors();
        if(!empty($errors)){
            Mage::log('IngramMicro: Error: XML parsing errors: ' . $xmlString, null,$this->logFile);
            return false;
        }

        $result = Mage::getModel('osf_ingrammicro/shipnotice')->processShipNotice($xmlData);
        if($result !== true){
            Mage::log('IngramMicro: Error: General Error' . $xmlString, null,$this->logFile);
            return false;
        }

        return true;
    }

}

/* Filename: Callback.php */
/* Location: app/code/local/Osf/Ingrammicro/Model/Callback.php */