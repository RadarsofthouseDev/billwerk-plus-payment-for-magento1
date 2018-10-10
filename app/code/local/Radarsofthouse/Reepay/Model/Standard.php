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
class Radarsofthouse_Reepay_Model_Standard extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'reepay';
     
    protected $_isInitializeNeeded = true;
    protected $_canUseInternal = false;
    protected $_canUseForMultishipping = false;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_isGateway = true;

    protected $_formBlockType = 'reepay/form_reepay';
    protected $_infoBlockType = 'reepay/info_reepay';

    /**
     * Return Order place redirect url
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('reepay/standard/redirect');
    }

    /**
     * Get instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }

    /**
     * Get payment icons from config
     *
     * @return string
     */
    public function getPaymentIcons()
    {
        return trim($this->getConfigData('payment_icons'));
    }
    
    /**
     * Capture payment online
     *
     * @param Varien_Object $payment
     * @param int $amount
     * @return Radarsofthouse_Reepay_Model_Standard $this
     */
    public function capture(Varien_Object $payment, $amount)
    {
        $order = $payment->getOrder();
        $amount = $amount;

        Mage::helper('reepay')->log('capture : '.$order->getIncrementId());

        $apiKey = Mage::helper('reepay/apikey')->getPrivateKey();
        $charge = Mage::helper('reepay/charge')->settle($apiKey, $order->getIncrementId());
        if (!empty($charge)) {
            if ($charge['state'] == 'settled') {
                $_payment = $order->getPayment();
                Mage::helper('reepay')->setReepayPaymentState($_payment, 'settled');
                $order->save();
                Mage::helper('reepay')->log($charge);
            }
        }
 
        return $this;
    }

    /**
     * Refund payment
     *
     * @param Varien_Object $payment
     * @param int $amount
     * @return Radarsofthouse_Reepay_Model_Standard $this
     */
    public function refund(Varien_Object $payment, $amount)
    {
        $order = $payment->getOrder();
        $amount = $amount;

        Mage::helper('reepay')->log('refund : '.$order->getIncrementId());

        $apiKey = Mage::helper('reepay/apikey')->getPrivateKey();
        $refund = Mage::helper('reepay/refund')->create($apiKey, array('invoice' => $order->getIncrementId()));
        if (!empty($refund)) {
            if ($refund['state'] == 'refunded') {
                $_payment = $order->getPayment();
                Mage::helper('reepay')->setReepayPaymentState($_payment, 'refunded');
                $order->save();
                Mage::helper('reepay')->log($refund);
            }
        }

        return $this;
    }
}
