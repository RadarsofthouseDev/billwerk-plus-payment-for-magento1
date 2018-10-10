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
class Radarsofthouse_Reepay_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * get extension configuration by key
     *
     * @param string $key
     * @return string|boolean
     */
    public function getConfig($key)
    {
        $store = Mage::app()->getStore();
        switch ($key) {
            case 'version':
                return Mage::getStoreConfig('payment/reepay/version', $store);
            case 'active':
                return Mage::getStoreConfig('payment/reepay/active', $store);
            case 'title':
                return Mage::getStoreConfig('payment/reepay/title', $store);
            case 'instructions':
                return Mage::getStoreConfig('payment/reepay/instructions', $store);
            case 'private_key':
                return Mage::getStoreConfig('payment/reepay/private_key', $store);
            case 'api_key':
                return Mage::getStoreConfig('payment/reepay/api_key', $store);
            case 'display_type':
                return Mage::getStoreConfig('payment/reepay/display_type', $store);
            case 'auto_capture':
                return Mage::getStoreConfig('payment/reepay/auto_capture', $store);
            case 'send_email_after_payment':
                return Mage::getStoreConfig('payment/reepay/send_email_after_payment', $store);
            case 'order_status_before_payment':
                return $this->getOrderState(Mage::getStoreConfig('payment/reepay/order_status_before_payment', $store));
            case 'order_status_after_payment':
                return $this->getOrderState(Mage::getStoreConfig('payment/reepay/order_status_after_payment', $store));
            case 'allowspecific':
                return Mage::getStoreConfig('payment/reepay/allowspecific', $store);
            case 'specificcountry':
                return Mage::getStoreConfig('payment/reepay/specificcountry', $store);
            case 'allowwed_payment':
                return Mage::getStoreConfig('payment/reepay/allowwed_payment', $store);
            case 'payment_icons':
                return Mage::getStoreConfig('payment/reepay/payment_icons', $store);
            case 'test_mode':
                $apiKeyType = Mage::getStoreConfig('payment/reepay/api_key_type', $store);
                if ($apiKeyType) {
                    return 0;
                } else {
                    return 1;
                }
                break;
            case 'sort_order':
                return Mage::getStoreConfig('payment/reepay/sort_order', $store);
            default:
                return false;
        }
    }

    /**
     * Get magento order state
     *
     * @param string $status
     * @return string Magento order state
     */
    public function getOrderState($status)
    {
        if ($status == 'pending') {
            return Mage_Sales_Model_Order::STATE_NEW;
        } elseif ($status == 'processing') {
            return Mage_Sales_Model_Order::STATE_PROCESSING;
        } elseif ($status == 'complete') {
            return Mage_Sales_Model_Order::STATE_COMPLETE;
        } elseif ($status == 'closed') {
            return Mage_Sales_Model_Order::STATE_CLOSED;
        } elseif ($status == 'canceled') {
            return Mage_Sales_Model_Order::STATE_CANCELED;
        } elseif ($status == 'holded') {
            return Mage_Sales_Model_Order::STATE_HOLDED;
        } else {
            return $status;
        }
    }
    
    /**
     * Get current version of the extension
     *
     * @return string
     */
    public function getInstalledVersion()
    {
        return (string) Mage::getConfig()->getNode()->modules->Radarsofthouse_Reepay->version;
    }
    
    /**
     *  Set Reepay payment state function
     *
     * @param $payment
     * @param string $state
     * @return void
     */
    public function setReepayPaymentState($payment, $state)
    {
        $_additionalData = unserialize($payment->getAdditionalData());
        $_additionalData['state'] = $state;
        $payment->setAdditionalData(serialize($_additionalData));

        $_additionalInfo = unserialize($payment->getAdditionalInformation());
        $_additionalInfo['raw_details_info']['state'] = $state;
        $payment->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $_additionalInfo);
        
        $payment->save();
    }

    /**
     * Log
     *
     * @param $val
     * @param boolean $is_api
     * @return void
     */
    public function log($val, $logType = Zend_Log::DEBUG, $isApi = false)
    {
        if (Mage::getStoreConfig('payment/reepay/log', Mage::app()->getStore()) == 2) {
            // log all
            Mage::log($val, $logType, 'reepay_debug.log');
        } elseif (Mage::getStoreConfig('payment/reepay/log', Mage::app()->getStore()) == 1 && $isApi) {
            // log only API
            Mage::log($val, $logType, 'reepay_debug.log');
        }
    }
}
