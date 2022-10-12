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
    protected $_canRefund = true;
    protected $_isGateway = true;
    protected $_canCapturePartial = true;
    protected $_canRefundInvoicePartial = true;
    protected $_isAutoCapture = false;
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
     * @param int/float $amount
     * @return Radarsofthouse_Reepay_Model_Standard $this
     */
    public function capture(Varien_Object $payment, $amount)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $payment->getOrder();
        $adminSession = Mage::getSingleton('adminhtml/session');
        $originalAmount  = $amount;

        if($amount > $order->getGrandTotal()){
            $amount = $order->getGrandTotal();
        }

        if($amount != $originalAmount) {
            Mage::log("Change capture amount from {$originalAmount} to {$amount} for order". $order->getIncrementId());
        }

        if ($adminSession->getLatestCapturedInvoice()->getOrderId() == $order->getId()) {
            Mage::helper('reepay')->log("ADMIN capture : ".$order->getIncrementId());
            
            $orderInvoices = $order->getInvoiceCollection();
            $invoice = $adminSession->getLatestCapturedInvoice();
            $options = array();
            $options['key'] = count($orderInvoices);
            $options['amount'] = $amount*100;
            $options['ordertext'] = "settled";
            // $orderLines = $this->getOrderLinesFromInvoice($invoice);
            // $options['order_lines'] = $orderLines;

            $apiKey = Mage::helper('reepay/apikey')->getPrivateKey($order->getStoreId());
            $charge = Mage::helper('reepay/charge')->settle($apiKey, $order->getIncrementId(), $options);
            if (!empty($charge)) {
                if( isset($charge["error"]) ){
                    Mage::helper('reepay')->log($charge);
                    Mage::throwException($charge["error"]);
                    return;
                }

                if ($charge['state'] == 'settled') {
                    $_payment = $order->getPayment();
                    Mage::helper('reepay')->setReepayPaymentState($_payment, 'settled');
                    $order->save();

                    // separate transactions for partial capture
                    $payment->setIsTransactionClosed(false);
                    $payment->setTransactionId($charge['transaction']);
                    $transactionData = array(
                        'handle' => $charge['handle'],
                        'transaction' => $charge['transaction'],
                        'state' => $charge['state'],
                        'amount' => $amount,
                        'customer' => $charge['customer'],
                        'currency' => $charge['currency'],
                        'created' => $charge['created'],
                        'authorized' => $charge['authorized'],
                        'settled' => $charge['settled']
                    );
                    $payment->setTransactionAdditionalInfo(
                        Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                        $transactionData
                    );
                    Mage::helper('reepay')->log("set capture transaction data");
                } else {
                    Mage::helper('reepay')->log("Charge state is not settled", Zend_Log::ERR);
                }
            }
        } else {
            Mage::helper('reepay')->log('ADMIN capture action : Wrong captured invoice data', Zend_Log::ERR);
        }
 
        return $this;
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
            $line['amount'] = $this->toInt($amount);
            $line['quantity'] = $this->toInt($item->getQty());
            $orderLines[] = $line;
        }
        return $orderLines;
    }

    /**
     * convert variable to integer
     *
     * @return int
     */
    public function toInt($number){
        return (int)($number."");
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
            if( isset($refund["error"]) ){
                Mage::helper('reepay')->log($refund);
                Mage::throwException($refund["error"]);
                return;
            }

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
     * Check payment type is "auto_capture" payment
     *
     * @return $bool
     */
    public function isAutoCapture(){
        return $this->_isAutoCapture;
    }
}
