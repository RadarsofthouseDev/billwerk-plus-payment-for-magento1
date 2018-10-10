<?php
/**
 * Reepay payment extension for Magento
 *
 * @author      Radarsofthouse Team <info@radarsofthouse.dk>
 * @category    Radarsofthouse
 * @package     Radarsofthouse_Reepay
 * @copyright   Radarsofthouse (https://www.radarsofthouse.dk/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
class Radarsofthouse_Reepay_Model_Observer extends Varien_Event_Observer
{
    /**
     * Cancle order payment observer <sales_order_payment_cancel>
     *
     * @param $observer
     * @return void
     */
    public function cancleOrder($observer)
    {
        $order = $observer->getEvent()->getPayment()->getOrder();

        Mage::helper('reepay')->log('cancel order observer : '.$order->getIncrementId());

        $apiKey = Mage::helper('reepay/apikey')->getPrivateKey();
        $cancle = Mage::helper('reepay/charge')->cancel($apiKey, $order->getIncrementId());
        if (!empty($cancle)) {
            if ($cancle['state'] == 'cancelled') {
                $_payment = $order->getPayment();
                Mage::helper('reepay')->setReepayPaymentState($_payment, 'cancelled');
                $order->save();
                Mage::helper('reepay')->log($cancle);
            }
        }
    }
}
