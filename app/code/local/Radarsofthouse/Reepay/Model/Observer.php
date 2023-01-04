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
class Radarsofthouse_Reepay_Model_Observer extends Varien_Event_Observer
{
    /**
     * Cancle order payment observer <sales_order_payment_cancel>
     *
     * @param $observer
     * @return void
     */
    public function cancleOrder($observer)
    {
        $order = $observer->getEvent()->getPayment()->getOrder();

        $paymentMethod = $order->getPayment()->getMethodInstance()->getCode();
        if ($paymentMethod == 'reepay' ||
            $paymentMethod == 'reepay_mobilepay' ||
            $paymentMethod == 'reepay_viabill' ||
            $paymentMethod == 'reepay_anyday' ||
            $paymentMethod == 'reepay_paypal' ||
            $paymentMethod == 'reepay_klarnapaynow' ||
            $paymentMethod == 'reepay_klarnapaylater' ||
            $paymentMethod == 'reepay_klarnasliceit' ||
            $paymentMethod == 'reepay_klarnadirectbanktransfer' ||
            $paymentMethod == 'reepay_klarnadirectdebit' ||
            $paymentMethod == 'reepay_applepay' ||
            $paymentMethod == 'reepay_swish' ||
            $paymentMethod == 'reepay_vipps' ||
            $paymentMethod == 'reepay_resurs' ||
            $paymentMethod == 'reepay_forbrugsforeningen' ||
            $paymentMethod == 'reepay_googlepay' ||
            $paymentMethod == 'reepay_ideal' ||
            $paymentMethod == 'reepay_blik' ||
            $paymentMethod == 'reepay_p24' ||
            $paymentMethod == 'reepay_verkkopankki' ||
            $paymentMethod == 'reepay_giropay' ||
            $paymentMethod == 'reepay_sepa'
        ) {
            Mage::helper('reepay')->log('cancel order observer : '.$order->getIncrementId());
            $apiKey = Mage::helper('reepay/apikey')->getPrivateKey($order->getStoreId());

            // refund for SWISH payment
            if ($order->getPayment()->getMethodInstance()->isAutoCapture()){
                $captureTransactions = Mage::getModel('sales/order_payment_transaction')->getCollection()
                    ->addAttributeToFilter('order_id', array('eq' => $order->getId()))
                    ->addAttributeToFilter('txn_type', array('eq' => 'capture'));
                if(count($captureTransactions) > 0){
                    foreach($captureTransactions as $transaction){
                        $transactionData = $transaction->getData();
                        if( isset( $transactionData['additional_information']['raw_details_info']['amount'] ) ){
                            Mage::helper('reepay')->log('Refund '. $paymentMethod .' : '.$order->getIncrementId());
                            $amount = ($transactionData['additional_information']['raw_details_info']['amount'])*100;
                            $options = array();
                            $options['invoice'] = $order->getIncrementId();
                            $options['key'] = 90;
                            $options['amount'] = (int)($amount."");
                            $options['ordertext'] = "refund";
                            $refund = Mage::helper('reepay/refund')->create($apiKey, $options);
                            if (!empty($refund)) {
                                if( isset($refund["error"]) ){
                                    Mage::helper('reepay')->log('Refund '. $paymentMethod .' payment error : '.$refund["error"]);
                                    Mage::throwException('Refund '. $paymentMethod .' payment error : '.$refund["error"]);
                                }
                            }
                        }
                    }
                }
            }

            $cancle = Mage::helper('reepay/charge')->cancel($apiKey, $order->getIncrementId());
            if (!empty($cancle)) {
                if ($cancle['state'] == 'cancelled') {
                    $_payment = $order->getPayment();
                    Mage::helper('reepay')->setReepayPaymentState($_payment, 'cancelled');
                    $order->save();
                    Mage::helper('reepay')->log($cancle);
                }
            }
        }
    }

    /**
     * Send email to customer when admin have created an order in the backend
     *
     * @param $observer
     * @return void
     */
    public function checkoutSubmitAllAfter(Varien_Event_Observer $observer)
    {
        $orderId = $observer->getEvent()->getOrder()->getIncrementId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        Mage::helper('reepay')->log('Admin created order : '.$orderId);

        if ($order->getPayment()->getMethodInstance()->getCode() == 'reepay' ||
            $order->getPayment()->getMethodInstance()->getCode() == 'reepay_mobilepay' ||
            $order->getPayment()->getMethodInstance()->getCode() == 'reepay_viabill' ||
            $order->getPayment()->getMethodInstance()->getCode() == 'reepay_anyday' ||
            $order->getPayment()->getMethodInstance()->getCode() == 'reepay_paypal' ||
            $order->getPayment()->getMethodInstance()->getCode() == 'reepay_klarnapaynow' ||
            $order->getPayment()->getMethodInstance()->getCode() == 'reepay_klarnapaylater' ||
            $order->getPayment()->getMethodInstance()->getCode() == 'reepay_klarnasliceit' ||
            $order->getPayment()->getMethodInstance()->getCode() == 'reepay_klarnadirectbanktransfer' ||
            $order->getPayment()->getMethodInstance()->getCode() == 'reepay_klarnadirectdebit' ||
            $order->getPayment()->getMethodInstance()->getCode() == 'reepay_applepay' ||
            $order->getPayment()->getMethodInstance()->getCode() == 'reepay_swish' ||
            $order->getPayment()->getMethodInstance()->getCode() == 'reepay_vipps' ||
            $order->getPayment()->getMethodInstance()->getCode() == 'reepay_resurs' ||
            $order->getPayment()->getMethodInstance()->getCode() == 'reepay_forbrugsforeningen' ||
            $order->getPayment()->getMethodInstance()->getCode() == 'reepay_googlepay' ||
            $order->getPayment()->getMethodInstance()->getCode() == 'reepay_ideal' ||
            $order->getPayment()->getMethodInstance()->getCode() == 'reepay_blik' ||
            $order->getPayment()->getMethodInstance()->getCode() == 'reepay_p24' ||
            $order->getPayment()->getMethodInstance()->getCode() == 'reepay_verkkopankki' ||
            $order->getPayment()->getMethodInstance()->getCode() == 'reepay_giropay' ||
            $order->getPayment()->getMethodInstance()->getCode() == 'reepay_sepa'
        ) {
            try {
                $sessionId = Mage::helper('reepay')->createReepaySession($order);

                if (empty($sessionId)) {
                    Mage::log('Cannot create Reepay payment session', null, 'reepay-observer.log');
                    Mage::throwException('Cannot create Reepay payment session');

                    return;
                }

                $mailTemplate = Mage::getModel('core/email_template');
                $vars = array(
                    'increment_id' => $order->getIncrementId(),
                    'payment_url' => 'https://checkout.reepay.com/#/'.$sessionId,
                );
                $mailTemplate->setDesignConfig(array(
                    'area' => 'frontend',
                    'store' => $order->getStoreId(),
                ));
                $mailTemplate->sendTransactional(
                    'reepay_payment',
                    'sales',
                    $order->getCustomerEmail(),
                    $order->getBillingAddress()->getFirstname() . ' ' . $order->getBillingAddress()->getLastname(),
                    $vars,
                    $order->getStoreId()
                        );
                        
                Mage::log('onCheckoutSubmitAllAfter', null, 'reepay-observer.log');
            } catch (Exception $e) {
                Mage::log('onCheckoutSubmitAllAfter() exception: '.$e->getMessage(), null, 'reepay-observer.log');
                Mage::throwException('Error: '.$e->getMessage());
            }
        }
    }


    /**
     * "sales_order_payment_capture" event observer
     * set latest captured invoice ID (for partial capture)
     *
     * @param $observer
     * @return void
     */
    public function setLatestCapturedInvoice(Varien_Event_Observer $observer)
    {
        $adminSession = Mage::getSingleton('adminhtml/session');
        $adminSession->setLatestCapturedInvoice($observer->getInvoice());
        Mage::helper('reepay')->log('ADMIN setLatestCapturedInvoice observer : order '.$observer->getInvoice()->getOrderId());
    }

    /**
     * disable block cache
     *
     * @param $observer
     * @return void
     */
    public function disableBlockCache(Varien_Event_Observer $observer)
    {
        $blockClass = get_class($observer->getBlock()); 
        if (strpos($blockClass, 'Radarsofthouse_Reepay') !== false) {
            $observer->getBlock()->setCacheLifetime(null);
        }
    }

}
