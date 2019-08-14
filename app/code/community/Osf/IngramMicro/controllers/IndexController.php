<?php
/**
 * Osf Global Services
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * 
 * Index controller
 *
 * @category    Osf
 * @package     Osf_IngramMicro
 * @author      Osf Global Services
 */

class Osf_IngramMicro_IndexController extends Mage_Core_Controller_Front_Action
{
	/**
     * The default index action
     *
     * @return string
     *
     */
	public function indexAction()
	{
		echo "Nothing here";
		return;
	}

	/**
     * The Action that starts the import of products from IngramMicro
     *
     * @return null
     *
     */
	public function startImportAction()
	{
		return Mage::getModel('osf_ingrammicro/import')->processData();
	}

	/**
     * Retry to send purchase ordersCreate Shipment
     *
     * @return null
     *
     */
	public function retryCronAction()
	{
		return Mage::getModel('osf_ingrammicro/order')->retry();
	}

	/**
     * Check for ship notices to finalize the purchase order
     *
     * @return null
     *
     */
	public function shipNoticeCronAction()
	{
		return Mage::getModel('osf_ingrammicro/shipping_notice')->processNotices();
	}
}

/* Filename: IndexController.php */
/* Location: ../app/code/community/Osf/IngramMicro/controllers/IndexController.php */