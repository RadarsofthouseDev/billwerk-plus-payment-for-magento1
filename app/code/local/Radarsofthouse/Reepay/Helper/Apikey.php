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
class Radarsofthouse_Reepay_Helper_Apikey extends Mage_Core_Helper_Abstract
{
    /**
     * Get config private key.
     *
     * @return void
     */
    public function getPrivateKey()
    {
        $store = Mage::app()->getStore();
        $apiKeyType = Mage::getStoreConfig('payment/reepay/api_key_type', $store);
        if ($apiKeyType) {
            return Mage::getStoreConfig('payment/reepay/private_key', $store);
        } else {
            return Mage::getStoreConfig('payment/reepay/private_key_test', $store);
        }
    }

    /**
     * Get config public key.
     *
     * @return void
     */
    public function getPublicKey()
    {
        $store = Mage::app()->getStore();
        $apiKeyType = Mage::getStoreConfig('payment/reepay/api_key_type', $store);
        if ($apiKeyType) {
            return Mage::getStoreConfig('payment/reepay/api_key', $store);
        } else {
            return Mage::getStoreConfig('payment/reepay/api_key_test', $store);
        }
    }
}
