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
    protected $_canUseInternal = true;
    protected $_canUseForMultishipping = false;
    protected $_canCapture = true;
    protected $_canVoid = true;
    protected $_canRefund = true;
    protected $_isGateway = true;
    protected $_canCapturePartial = true;
    protected $_canRefundInvoicePartial = true;
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
     * override getConfigData()
     *
     */
    public function getConfigData($field, $storeId = null)
    {
        // set specific config path
        if (null === $storeId) {
            $storeId = $this->getStore();
        }
        $path = 'payment/'.$this->getCode().'/'.$field;
        
        // set golbal config path
        if (Mage::getStoreConfig($path, $storeId) === null) {
            $path = 'payment/reepay/'.$field;
        }

        return Mage::getStoreConfig($path, $storeId);
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
        parent::capture($payment, $amount);

        if ($amount <= 0) {
            Mage::throwException(Mage::helper('paygate')->__('Invalid amount for capture.'));
        }

        $payment->setAmount($amount);

        $order = $payment->getOrder();
        Mage::helper('reepay')->log("ADMIN capture : " . $order->getIncrementId());

        $options = array(
            'key' => uniqid(),
            'amount' => $amount * 100,
            'ordertext' => 'settled',
            //'order_lines' => $this->getOrderLinesFromInvoice($invoice),
        );

        // Don't pass amount if there's full invoicing
        if ((float) $order->getGrandTotal() === (float) $amount) {
            $options = array(
                'key' => uniqid()
            );
        }

        $apiKey = Mage::helper('reepay/apikey')->getPrivateKey($order->getStoreId());
        $charge = Mage::helper('reepay/charge')->settle($apiKey, $order->getIncrementId(), $options);

        if (empty($charge) || $charge['state'] !== 'settled') {
            Mage::helper('reepay')->log("Charge state is not settled", Zend_Log::ERR);
            Mage::throwException(Mage::helper('reepay')->__('Charge state is not settled'));
        }

        Mage::helper('reepay')->setReepayPaymentState($order->getPayment(), 'settled');
        $order->save();

        // Add Transaction
        $payment->setStatus(self::STATUS_APPROVED)
                ->setTransactionId($charge['transaction'])
                ->setIsTransactionClosed(0);

        // Add Transaction fields
        $payment->setTransactionAdditionalInfo(
            Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
            array(
                'handle' => $charge['handle'],
                'transaction' => $charge['transaction'],
                'state' => $charge['state'],
                'amount' => $amount,
                'customer' => $charge['customer'],
                'currency' => $charge['currency'],
                'created' => $charge['created'],
                'authorized' => $charge['authorized'],
                'settled' => $charge['settled']
            )
        );

        Mage::helper('reepay')->log("set capture transaction data");

        return $this;
    }

    /**
     * Cancel payment
     *
     * @param   Varien_Object $payment
     * @return  $this
     */
    public function cancel(Varien_Object $payment)
    {
        parent::cancel($payment);

        $order = $payment->getOrder();
        Mage::helper('reepay')->log("ADMIN cancel : " . $order->getIncrementId());

        $apiKey = Mage::helper('reepay/apikey')->getPrivateKey($order->getStoreId());
        $cancel = Mage::helper('reepay/charge')->cancel($apiKey, $order->getIncrementId());

        if (empty($cancel) || $cancel['state'] !== 'cancelled') {
            Mage::helper('reepay')->log("State is not cancelled", Zend_Log::ERR);
            Mage::throwException(Mage::helper('reepay')->__('Cancellation is failed'));
        }

        // Add Cancel Transaction
        $payment->setStatus(self::STATUS_DECLINED)
                ->setTransactionId($cancel['transaction'] . '-cancel')
                ->setIsTransactionClosed(1); // Closed

        // Add Transaction fields
        $payment->setAdditionalInformation(
            Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
            array(
                'handle' => $cancel['handle'],
                'transaction' => $cancel['transaction'],
                'state' => $cancel['state'],
                'amount' => $cancel['amount'],
                'customer' => $cancel['customer'],
                'currency' => $cancel['currency'],
                'created' => $cancel['created'],
                'authorized' => $cancel['authorized'],
                'cancelled' => $cancel['cancelled']
            )
        );

        Mage::helper('reepay')->setReepayPaymentState($order->getPayment(), 'cancelled');

        return $this;
    }

    /**
     * Void payment
     *
     * @param Varien_Object $payment
     * @return $this
     */
    public function void(Varien_Object $payment)
    {
        return $this->cancel($payment);
    }

    /**
     * prepare order line by invoice
     *
     * @param $invoice
     * @return array $orderLines
     */
    public function getOrderLinesFromInvoice($invoice)
    {
        $orderLines = array();
        foreach ($invoice->getAllItems() as $item) {
            Mage::helper('reepay')->log($item->getData());
            $amount = ($item->getRowTotal() * 100) / $item->getQty();
            $line = array();
            $line['ordertext'] = $item->getName();
            $line['amount'] = (int)$amount;
            $line['quantity'] = (int)$item->getQty();
            $orderLines[] = $line;
        }
        return $orderLines;
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
        $creditmemos = $order->getCreditmemosCollection();

        $options = array();
        $options['invoice'] = $order->getIncrementId();
        $options['key'] = count($creditmemos);
        $options['amount'] = $amount*100;
        $options['ordertext'] = "refund";

        Mage::helper('reepay')->log('refund : '.$order->getIncrementId());

        $apiKey = Mage::helper('reepay/apikey')->getPrivateKey($order->getStoreId());
        $refund = Mage::helper('reepay/refund')->create($apiKey, $options);
        if (!empty($refund)) {
            if ($refund['state'] == 'refunded') {
                $_payment = $order->getPayment();
                Mage::helper('reepay')->setReepayPaymentState($_payment, 'refunded');
                $order->save();

                // separate transactions for partial capture
                $payment->setIsTransactionClosed(false);
                $payment->setTransactionId($refund['transaction']);
                
                $transactionData = array(
                    'invoice' => $refund['invoice'],
                    'transaction' => $refund['transaction'],
                    'state' => $refund['state'],
                    'amount' => Mage::helper('reepay')->convertAmount($refund['amount']),
                    'type' => $refund['type'],
                    'currency' => $refund['currency'],
                    'created' => $refund['created']
                );
                $payment->setTransactionAdditionalInfo(
                    Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                    $transactionData
                );
                Mage::helper('reepay')->log("set refund transaction data");
            } else {
                Mage::helper('reepay')->log("Refund state is not refunded", Zend_Log::ERR);
            }
        }

        return $this;
    }

    /**
     * Check void availability
     *
     * @param   Varien_Object $payment
     * @return  bool
     */
    public function canVoid(Varien_Object $payment)
    {
        if ($payment instanceof Mage_Sales_Model_Order_Invoice
            || $payment instanceof Mage_Sales_Model_Order_Creditmemo
        ) {
            return false;
        }

        return $this->_canVoid;
    }

    /**
     * Can be edit order (renew order)
     *
     * @return bool
     */
    public function canEdit()
    {
        return false;
    }
}
