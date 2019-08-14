<?php
/**
 *
 * Osf Global Services
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * 
 * Error Helper
 * 
 * @category OSF
 * @package OSF_IngraMicro
 * @author OSF Global Services
 * 
 */
class Osf_IngramMicro_Helper_Error extends Mage_Core_Helper_Data {
    public $logFile;

    public function __construct(){
        $this->logFile = 'ingrammicro.log';
        parent::__construct();
    }

    /**
     * Receive calls from ingram micro with ship notices
     *
     * @param array
     * @return string
     */
    public function buildXmlErrorMsg($errors){
        foreach ($errors as $error) {
            $errorMsg[] = trim($error->message);
        }
        return $errorMsg;
    }
}
