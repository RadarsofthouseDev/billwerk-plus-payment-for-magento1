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
class Radarsofthouse_Reepay_Model_System_Config_Source_Dropdown_Paymenticons
{
    /**
     * Reeturn Reepay payment icons
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'american-express', 'label' => __('American express')),
            array('value' => 'dankort', 'label' => __('Dankort')),
            array('value' => 'diners-club-international', 'label' => __('Diners club international')),
            array('value' => 'discover', 'label' => __('Discover')),
            array('value' => 'forbrugsforeningen', 'label' => __('Forbrugsforeningen')),
            array('value' => 'jcb', 'label' => __('JCB')),
            array('value' => 'maestro', 'label' => __('Maestro')),
            array('value' => 'mastercard', 'label' => __('Mastercard')),
            array('value' => 'mobilepay', 'label' => __('Mobilepay')),
            array('value' => 'unionpay', 'label' => __('Unionpay')),
            array('value' => 'viabill', 'label' => __('Viabill')),
            array('value' => 'visa', 'label' => __('Visa')),
            array('value' => 'visa-electron', 'label' => __('Visa electron')),
            array('value' => 'klarna-pay-later', 'label' => __('Klarna Pay Later')),
            array('value' => 'klarna-pay-now', 'label' => __('Klarna Pay Now')),
            array('value' => 'klarna-slice-it', 'label' => __('Klarna Slice It')),
            array('value' => 'applepay', 'label' => __('Apple Pay')),
            array('value' => 'paypal', 'label' => __('PayPal')),
            array('value' => 'resurs', 'label' => __('Resurs Bank')),
            array('value' => 'vipps', 'label' => __('Vipps')),
            array('value' => 'googlepay', 'label' => __('Google Pay')),
            array('value' => 'ideal', 'label' => __('iDEAL')),
            array('value' => 'blik', 'label' => __('BLIK')),
            array('value' => 'p24', 'label' => __('Przelewy24 (P24)')),
            array('value' => 'verkkopankki', 'label' => __('Verkkopankki')),
            array('value' => 'giropay', 'label' => __('giropay')),
            array('value' => 'sepa', 'label' => __('SEPA Direct Debit'))
        );
    }
}
