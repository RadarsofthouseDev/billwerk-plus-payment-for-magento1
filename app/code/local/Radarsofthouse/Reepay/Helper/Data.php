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
     * @param int $store
     * @return string|boolean
     */
    public function getConfig($key, $store = null)
    {
        if ($store === null) {
            $store = Mage::app()->getStore()->getId();
        }
        
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
        $_additionalData = array();
        if (!empty($payment->getAdditionalData())) {
            $_additionalData = unserialize($payment->getAdditionalData());
        }
        $_additionalData['state'] = $state;
        $payment->setAdditionalData(serialize($_additionalData));

        $_additionalInfo = array();
        if (!empty($payment->getAdditionalInformation())) {
            if (is_array($payment->getAdditionalInformation())) {
                $_additionalInfo = $payment->getAdditionalInformation();
            } else {
                $_additionalInfo = unserialize($payment->getAdditionalInformation());
            }
        }
        $_additionalInfo['raw_details_info']['state'] = $state;
        $payment->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $_additionalInfo);
        
        $payment->save();

        $order = $payment->getOrder();
        $reepayStatus = Mage::getModel('reepay/status')->getCollection()->addFieldToFilter('order_id', $order->getIncrementId());
        if (count($reepayStatus) > 0) {
            foreach ($reepayStatus as $reepayStatusItem) {
                $reepayStatusItem->setStatus($state);
                $reepayStatusItem->save();
            }
        }
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

    /**
     * Create payment sesion on Reepay (for window payment)
     *
     * @param Mage_Sales_Model_Order $order
     * @return string $sessionId
     */
    public function createReepaySession($order)
    {
        $apiKey = Mage::helper('reepay/apikey')->getPrivateKey($order->getStoreId());
        
        $customer = $this->getCustomerData($order);
        $billingAddress = $this->getOrderBillingAddress($order);
        $shippingAddress = $this->getOrderShippingAddress($order);
        $orderLines = $this->getOrderLines($order);
        $orderData = array(
            'handle' => $order->getIncrementId(),
            'currency' => $order->getOrderCurrencyCode(),
            'order_lines' => $orderLines,
            'billing_address' => $billingAddress,
            'shipping_address' => $shippingAddress,
        );

        $paymentMethods = $this->getPaymentMethods($order);

        $settle = false;
        if ($this->getConfig('auto_capture', $order->getStoreId()) == 1) {
            $settle = true;
        }

        $localMapping = array(
            'da_DK' => 'da_DK',
            'sv_SE' => 'sv_SE',
            'nb_NO' => 'no_NO',
            'nn_NO' => 'no_NO',
            'en_AU' => 'en_GB',
            'en_CA' => 'en_GB',
            'en_IE' => 'en_GB',
            'en_NZ' => 'en_GB',
            'en_GB' => 'en_GB',
            'en_US' => 'en_GB',
            'de_AT' => 'de_DE',
            'de_DE' => 'de_DE',
            'de_CH' => 'de_DE',
            'fr_CA' => 'fr_FR',
            'fr_FR' => 'fr_FR',
            'es_AR' => 'es_ES',
            'es_CL' => 'es_ES',
            'es_CO' => 'es_ES',
            'es_CR' => 'es_ES',
            'es_MX' => 'es_ES',
            'es_PA' => 'es_ES',
            'es_PE' => 'es_ES',
            'es_ES' => 'es_ES',
            'es_VE' => 'es_ES',
            'nl_NL' => 'nl_NL',
            'pl_PL' => 'pl_PL',
        );

        $options = array();
        
        if (!empty($localMapping[Mage::app()->getLocale()->getLocaleCode()])) {
            $options['locale'] = $localMapping[Mage::app()->getLocale()->getLocaleCode()];
        }

        $options['accept_url'] = Mage::app()->getStore($order->getStoreId())->getBaseUrl().'reepay/standard/accept/';
        $options['cancel_url'] = Mage::app()->getStore($order->getStoreId())->getBaseUrl().'reepay/standard/cancel/';

        $res = Mage::helper('reepay/session')->chargeCreateWithNewCustomer(
            $apiKey,
            $customer,
            $orderData,
            $paymentMethods,
            $settle,
            $options
        );
        $this->log('reepay/session : chargeCreateWithNewCustomer response');
        $this->log($res);

        $sessionId = $res['id'];

        return $sessionId;
    }

    /**
     * Prepare cuatomer data from order.
     *
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public function getCustomerData($order)
    {
        $testMode = false;
        if ($this->getConfig('test_mode', $order->getStoreId()) == 1) {
            $testMode = true;
        }

        return array(
            'handle' => $order->getBillingAddress()->getEmail(),
            'email' => $order->getBillingAddress()->getEmail(),
            'first_name' => $order->getBillingAddress()->getFirstname(),
            'last_name' => $order->getBillingAddress()->getLastname(),
            'address' => $order->getBillingAddress()->getStreet(1),
            'address2' => $order->getBillingAddress()->getStreet(2),
            'city' => $order->getBillingAddress()->getCity(),
            'country' => $order->getBillingAddress()->getCountryId(),
            'phone' => $order->getBillingAddress()->getTelephone(),
            'company' => $order->getBillingAddress()->getCompany(),
            'postal_code' => $order->getBillingAddress()->getPostcode(),
            'vat' => $order->getBillingAddress()->getVatId(),
            'test' => $testMode,
            'generate_handle' => false,
        );
    }

    /**
     * Prepare billing address from order.
     *
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public function getOrderBillingAddress($order)
    {
        return array(
            'company' => $order->getBillingAddress()->getCompany(),
            'vat' => $order->getBillingAddress()->getVatId(),
            'attention' => '',
            'address' => $order->getBillingAddress()->getStreet(1),
            'address2' => $order->getBillingAddress()->getStreet(2),
            'city' => $order->getBillingAddress()->getCity(),
            'country' => $order->getBillingAddress()->getCountryId(),
            'email' => $order->getBillingAddress()->getEmail(),
            'phone' => $order->getBillingAddress()->getTelephone(),
            'first_name' => $order->getBillingAddress()->getFirstname(),
            'last_name' => $order->getBillingAddress()->getLastname(),
            'postal_code' => $order->getBillingAddress()->getPostcode(),
            'state_or_province' => $order->getBillingAddress()->getRegion(),
        );
    }

    /**
     * Prepare shipping address from order.
     *
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public function getOrderShippingAddress($order)
    {
        return array(
            'company' => $order->getShippingAddress()->getCompany(),
            'vat' => $order->getShippingAddress()->getVatId(),
            'attention' => '',
            'address' => $order->getShippingAddress()->getStreet(1),
            'address2' => $order->getShippingAddress()->getStreet(2),
            'city' => $order->getShippingAddress()->getCity(),
            'country' => $order->getShippingAddress()->getCountryId(),
            'email' => $order->getShippingAddress()->getEmail(),
            'phone' => $order->getShippingAddress()->getTelephone(),
            'first_name' => $order->getShippingAddress()->getFirstname(),
            'last_name' => $order->getShippingAddress()->getLastname(),
            'postal_code' => $order->getShippingAddress()->getPostcode(),
            'state_or_province' => $order->getShippingAddress()->getRegion(),
        );
    }

    /**
    * Prepare order_lines for payment gateway
    *
    * @param Mage_Sales_Model_Order $order
    * @return array $orderLines
    */
    public function getOrderLines($order)
    {
        $orderGrandTotal = (int)($order->getGrandTotal() * 100);
        $total = 0;
        $orderLines = array();

        // products
        $orderitems = $order->getAllVisibleItems();
        foreach ($orderitems as $orderitem) {
            $amount = $orderitem->getPriceInclTax() * 100;
            $line = array();
            $line['ordertext'] = $orderitem->getProduct()->getName();
            $line['amount'] = (int)round($amount);
            $line['quantity'] = (int)$orderitem->getQtyOrdered();
            $line['vat'] = $orderitem->getTaxPercent()/100;
            $line['amount_incl_vat'] = "true";
            $orderLines[] = $line;
            $total = $total + ($orderitem->getRowTotal() * 100);
        }
        /*
        // tax
        $taxAmount = ($order->getTaxAmount() * 100);
        if ($taxAmount != 0) {
            $line = array();
            $line['ordertext'] = $this->__('Tax.');
            $line['amount'] = (int)$taxAmount;
            $line['quantity'] = 1;
            $orderLines[] = $line;
            $total = $total + $taxAmount;
        }
        */

        // shipping
        $shippingAmount = ($order->getShippingInclTax() * 100);
        if ($shippingAmount != 0) {
            $line = array();
            $line['ordertext'] = $order->getShippingDescription();
            $line['quantity'] = 1;
            $line['amount'] = (int)$shippingAmount;
            if ($order->getShippingTaxAmount() > 0) {
                $line['vat'] = $order->getShippingTaxAmount()/$order->getShippingAmount();
                $line['amount_incl_vat'] = "true";
            } else {
                $line['vat'] = 0;
                $line['amount_incl_vat'] = "true";
            }

            $orderLines[] = $line;
            $total = $total + $shippingAmount;
        }

        // discount
        $discountAmount = ($order->getDiscountAmount() * 100);
        if ($discountAmount != 0) {
            $line = array();
            $line['ordertext'] = $order->getDiscountDescription();
            $line['amount'] = (int)$discountAmount;
            $line['quantity'] = 1;
            $line['vat'] = 0;
            $line['amount_incl_vat'] = "true";
            $orderLines[] = $line;
            $total = $total + $discountAmount;
        }

        /*
        // other
        if ((int)$total != $orderGrandTotal) {
            $line = array();
            $line['ordertext'] = $this->__('etc.');
            $line['amount'] = (int)($orderGrandTotal - $total);
            $line['quantity'] = 1;
            $line['vat'] = 0;
            $line['amount_incl_vat'] = "true";
            $orderLines[] = $line;
        }
        */

        return $orderLines;
    }

    /**
     * Get allowwed payments from configuration
     *
     * @return array $_paymentMethods
     */
    public function getPaymentMethods($order)
    {
        $_paymentMethods = array();

        if ($order->getPayment()->getMethodInstance()->getCode() == 'reepay_viabill') {
            $_paymentMethods[] = 'viabill';
        } elseif ($order->getPayment()->getMethodInstance()->getCode() == 'reepay_mobilepay') {
            $_paymentMethods[] = 'mobilepay';
        } else {
            $paymentMethods = $this->getConfig('allowwed_payment');
            $_paymentMethods = explode(',', $paymentMethods);
        }

        return $_paymentMethods;
    }

    /**
     * Prepare payment data from charge response
     *
     * @param array $paymentData
     * @return array $paymentData
     */
    public function preparePaymentData($paymentData)
    {
        if (isset($paymentData['amount'])) {
            $paymentData['amount'] = $this->convertAmount($paymentData['amount']);
        }

        if (isset($paymentData['authorized_amount'])) {
            $paymentData['authorized_amount'] = $this->convertAmount($paymentData['authorized_amount']);
        }

        if (isset($paymentData['refunded_amount'])) {
            $paymentData['refunded_amount'] = $this->convertAmount($paymentData['refunded_amount']);
        }

        if (isset($paymentData['order_lines'])) {
            unset($paymentData['order_lines']);
        }

        if (isset($paymentData['billing_address'])) {
            unset($paymentData['billing_address']);
        }

        if (isset($paymentData['shipping_address'])) {
            unset($paymentData['shipping_address']);
        }

        if (isset($paymentData['source'])) {
            $_source = $paymentData['source'];
            unset($paymentData['source']);
            $paymentData['source_type'] = $_source['type'];
            $paymentData['source_fingerprint'] = $_source['fingerprint'];
            $paymentData['source_card_type'] = $_source['card_type'];
            $paymentData['source_exp_date'] = $_source['exp_date'];
            $paymentData['source_masked_card'] = $_source['masked_card'];
        }

        return $paymentData;
    }
    
    /**
     * Create payment transaction
     *
     * @param Mage_Sales_Model_Order $order
     * @param array $paymentData
     * @return int (Magento Transaction ID)
     */
    public function addTransactionToOrder($order, $paymentData = array())
    {
        try {
            // prepare transaction data
            $paymentData = $this->preparePaymentData($paymentData);

            $payment = $order->getPayment();
            $payment->setTransactionId($paymentData['transaction']);
            $payment->setAdditionalData(serialize($paymentData));
            $payment->setAdditionalInformation(
                Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                (array) $paymentData
            );
            $payment->setParentTransactionId(null);
            $payment->save();

            $state = '';
            $isClosed = 0;
            if ($paymentData['state'] == 'authorized') {
                $state = Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH;
                $isClosed = 0;
            } elseif ($paymentData['state'] == 'settled') {
                $state = Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE;
                $isClosed = 1;
            }

            $transaction = $payment->addTransaction($state);
            $transaction->setAdditionalInformation(
                Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                (array) $paymentData
            );
            $transaction->setTxnId($paymentData['transaction']);
            $transaction->setIsClosed($isClosed);
            $transaction->save();

            $orderStore = Mage::getModel('core/store')->load($order->getStoreId());
            $grandTotal = Mage::helper('core')->currencyByStore($order->getGrandTotal(), $orderStore, true, false);

            $order->setState(
                Mage::helper('reepay')->getConfig('order_status_after_payment', $order->getStoreId()),
                true,
                __('Reepay : The authorized amount is %s.', $grandTotal),
                false
            );
            $order->save();
 
            return  $transaction->getTransactionId();
        } catch (Exception $e) {
            Mage::helper('reepay')->log('ERROR : addTransactionToOrder()');
            Mage::helper('reepay')->log($e->getMessage());
        }
    }

    /**
     * Prepare capture transaction data
     *
     * @param array $transactionData
     * @return array $transactionData
     */
    public function prepareCaptureTransactionData($transactionData)
    {
        if (isset($transactionData['amount'])) {
            $transactionData['amount'] = $this->convertAmount($transactionData['amount']);
        }

        if (isset($transactionData['card_transaction'])) {
            $cardTransaction = $transactionData['card_transaction'];
            unset($transactionData['card_transaction']);
            $transactionData['card_transaction_ref_transaction'] = $cardTransaction['ref_transaction'];
            $transactionData['card_transaction_fingerprint'] = $cardTransaction['fingerprint'];
            $transactionData['card_transaction_card_type'] = $cardTransaction['card_type'];
            $transactionData['card_transaction_exp_date'] = $cardTransaction['exp_date'];
            $transactionData['card_transaction_masked_card'] = $cardTransaction['masked_card'];
        }

        return $transactionData;
    }

    /**
     * Create capture transaction
     *
     * @param Mage_Sales_Model_Order $order
     * @param array $transactionData
     * @return int (Magento Transaction ID)
     */
    public function addCaptureTransactionToOrder($order, $transactionData = array())
    {
        try {
            // prepare transaction data
            $transactionData = $this->prepareCaptureTransactionData($transactionData);

            $payment = $order->getPayment();
            $payment->setTransactionId($transactionData['id']);
            $payment->setAdditionalData(serialize($transactionData));
            $payment->setAdditionalInformation(
                Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                (array) $transactionData
            );
            $payment->setParentTransactionId($transactionData['card_transaction_ref_transaction']);
            $payment->save();

            $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
            $transaction->setAdditionalInformation(
                Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                (array) $transactionData
            );
            $transaction->setTxnId($transactionData['id']);
            $transaction->setIsClosed(0);
            $transaction->save();
            return  $transaction->getTransactionId();
        } catch (Exception $e) {
            Mage::helper('reepay')->log('ERROR : addTransactionToOrder()');
            Mage::helper('reepay')->log($e->getMessage());
        }
    }

    /**
     * Prepare refund transaction data
     *
     * @param array $transactionData
     * @return array $transactionData
     */
    public function prepareRefundTransactionData($transactionData)
    {
        if (isset($transactionData['amount'])) {
            $transactionData['amount'] = $this->convertAmount($transactionData['amount']);
        }

        if (isset($transactionData['card_transaction'])) {
            $cardTransaction = $transactionData['card_transaction'];
            unset($transactionData['card_transaction']);
            $transactionData['card_transaction_ref_transaction'] = $cardTransaction['ref_transaction'];
        }

        return $transactionData;
    }

    /**
     * Create refund transaction
     *
     * @param Mage_Sales_Model_Order $order
     * @param array $transactionData
     * @return int (Magento Transaction ID)
     */
    public function addRefundTransactionToOrder($order, $transactionData = array())
    {
        try {
            // prepare transaction data
            $transactionData = $this->prepareRefundTransactionData($transactionData);

            $payment = $order->getPayment();
            $payment->setTransactionId($transactionData['id']);
            $payment->setAdditionalData(serialize($transactionData));
            $payment->setAdditionalInformation(
                Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                (array) $transactionData
            );
            $payment->setParentTransactionId($transactionData['card_transaction_ref_transaction']);
            $payment->save();

            $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND);
            $transaction->setAdditionalInformation(
                Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                (array) $transactionData
            );
            $transaction->setTxnId($transactionData['id']);
            $transaction->setIsClosed(0);
            $transaction->save();
 
            return  $transaction->getTransactionId();
        } catch (Exception $e) {
            Mage::helper('reepay')->log('ERROR : addTransactionToOrder()');
            Mage::helper('reepay')->log($e->getMessage());
        }
    }

    /**
     * Convert integer amount to 2 decimal places
     *
     * @param int $amount
     * @return float
     */
    public function convertAmount($amount)
    {
        return number_format((float)($amount/100), 2, '.', '');
    }
}
