<?php
/**
 * Billwerk+ payment extension for Magento
 *
 * @author      Radarsofthouse Team <info@radarsofthouse.dk>
 * @category    Radarsofthouse
 * @package     Radarsofthouse_Reepay
 * @copyright   Radarsofthouse (https://www.radarsofthouse.dk/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
class Radarsofthouse_Reepay_Model_Klarnadirectbanktransfer extends Radarsofthouse_Reepay_Model_Standard
{
    protected $_code = 'reepay_klarnadirectbanktransfer';

    protected $_formBlockType = 'reepay/form_klarnadirectbanktransfer';
    protected $_infoBlockType = 'reepay/info_klarnadirectbanktransfer';

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
            $paymentIcon = 'klarna-direct-bank-transfer';
        }

        return $paymentIcon;
    }
}
