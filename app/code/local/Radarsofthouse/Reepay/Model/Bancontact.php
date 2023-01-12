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
class Radarsofthouse_Reepay_Model_Bancontact extends Radarsofthouse_Reepay_Model_Standard
{
    protected $_code = 'reepay_bancontact';
    protected $_isAutoCapture = true;

    protected $_formBlockType = 'reepay/form_bancontact';
    protected $_infoBlockType = 'reepay/info_bancontact';

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
     * Get payment icons from config
     *
     * @return string
     */
    public function getPaymentIcons()
    {
        $paymentIcon = '';
        if ($this->getConfigData('show_icon')) {
            $paymentIcon = 'bancontact';
        }

        return $paymentIcon;
    }
    
    /**
     * Skip settle action for auto capture payment
     *
     * @return Radarsofthouse_Reepay_Model_Swish $this
     */
    public function capture(Varien_Object $payment, $amount)
    {
        return $this;
    }
}