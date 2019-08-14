<?php
/**
 * Osf Global Services
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 *
 * Callback controller
 *
 * @category    Osf
 * @package     Osf_IngramMicro
 * @author      Osf Global Services
 */

class Osf_IngramMicro_CallbackController extends Mage_Core_Controller_Front_Action
{
    public function apiAction()
    {
        if(!$this->getRequest()->isPost()){
            $this->_redirect('/');
            return;
        }

        $xmlString = $this->getRequest()->getRawBody();
        Mage::log('IngramMicro: Received ASN: '.$xmlString, null,'ingrammicro.log');
        Mage::getModel('osf_ingrammicro/callback')->receiveShipNotice($xmlString);
        return;
    }
}

/* Filename: CallbackController.php */
/* Location: ../app/code/local/Osf/IngramMicro/controllers/CallbackController.php */