<?php
/**
 * Frisbii Pay extension for Magento
 *
 * @author      Radarsofthouse Team <info@radarsofthouse.dk>
 * @category    Radarsofthouse
 * @package     Radarsofthouse_Reepay
 * @copyright   Radarsofthouse (https://www.radarsofthouse.dk/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
class Radarsofthouse_Reepay_Model_Applepay extends Radarsofthouse_Reepay_Model_Standard
{
    protected $_code = 'reepay_applepay';

    protected $_formBlockType = 'reepay/form_applepay';
    protected $_infoBlockType = 'reepay/info_applepay';

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
            $paymentIcon = 'applepay';
        }

        return $paymentIcon;
    }

    /**
     * Get is available : allowwed only Safari browser
     *
     * @return bollean
     */
    public function isAvailable($quote = null){
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        if(stripos( $user_agent, 'Edg') !== false){
            return false;
        }else if(stripos( $user_agent, 'Chrome') !== false){
            return false;
        }else if(stripos( $user_agent, 'Safari') !== false){
            return parent::isAvailable($quote);   
        }else{
            return false;
        }
    }
}
