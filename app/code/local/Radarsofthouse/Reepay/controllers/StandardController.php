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
class Radarsofthouse_Reepay_StandardController extends Mage_Core_Controller_Front_Action
{
    const DISPLAY_EMBEDDED = 1;
    const DISPLAY_OVERLAY = 2;
    const DISPLAY_WINDOW = 3;
    
    /**
     * Reepay payment page (reepay/standard/redirect)
     *
     * @return void
     */
    public function redirectAction()
    {
        $session = Mage::getSingleton('checkout/session');
        
        if (empty($session->getLastRealOrderId())) {
            $this->_redirect('checkout/cart');

            return;
        }
        
        if (empty($session->getQuoteId())) {
            $this->_redirect('checkout/cart');

            return;
        }
        
        $quote = Mage::getModel('sales/quote')->load($session->getQuoteId());
        
        if (empty($quote->getReservedOrderId())) {
            $this->_redirect('checkout/cart');

            return;
        }
        
        if ($quote->getReservedOrderId() != $session->getLastRealOrderId()) {
            $this->_redirect('checkout/cart');

            return;
        }

        $session->setReepayOrderIncrementId($quote->getReservedOrderId());
        Mage::helper('reepay')->log('reepay/standard/redirect : '.$quote->getReservedOrderId());
        Mage::helper('reepay')->log('Display type : '.Mage::helper('reepay')->getConfig('display_type'));

        $order = Mage::getModel('sales/order')->loadByIncrementId($quote->getReservedOrderId());

        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle($this->__('Reepay payment'));
        
        $sessionId = null;
        // create new Reepay session
        $sessionId = Mage::helper('reepay')->createReepaySession($order);

        $session->setReepaySessionOrder($quote->getReservedOrderId());
        $session->setReepaySessionID($sessionId);

        if (!empty(Mage::helper('reepay')->getConfig('order_status_before_payment'))) {
            $order->setState(
                Mage::helper('reepay')->getConfig('order_status_before_payment'),
                true,
                'Reepay : Order status before the payment is made',
                null
            );
            $order->save();
        }
        
        
        if ($order->getPayment()->getMethodInstance()->getCode() == 'reepay_viabill') {
            // force viabill into payment window always
            $this->getLayout()->getBlock('reepay_index')
                ->setPaymentSessionId($sessionId)
                ->setTemplate('reepay/window.phtml');
        } elseif (Mage::helper('reepay')->getConfig('display_type') == SELF::DISPLAY_EMBEDDED) {
            $this->getLayout()->getBlock('reepay_index')
                ->setPaymentSessionId($sessionId)
                ->setTemplate('reepay/embedded.phtml');
        } elseif (Mage::helper('reepay')->getConfig('display_type') == SELF::DISPLAY_OVERLAY) {
            $this->getLayout()->getBlock('reepay_index')
                ->setPaymentSessionId($sessionId)
                ->setTemplate('reepay/overlay.phtml');
        } elseif (Mage::helper('reepay')->getConfig('display_type') == SELF::DISPLAY_WINDOW) {
            $this->getLayout()->getBlock('reepay_index')
                ->setPaymentSessionId($sessionId)
                ->setTemplate('reepay/window.phtml');
        }

        $this->renderLayout();
    }

    /**
     * Create payment transaction
     *
     * @param Mage_Sales_Model_Order $order
     * @param array $paymentData
     * @return int Transaction ID
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

            $grandTotal = Mage::helper('core')->currency($order->getGrandTotal(), true, false);

            $order->setState(
                Mage::helper('reepay')->getConfig('order_status_after_payment'),
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
     * Prepare payment data from charge response
     *
     * @param array $paymentData
     * @return array $paymentData
     */
    public function preparePaymentData($paymentData)
    {
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
     * Accept from window payment
     */
    public function acceptAction()
    {
        Mage::helper('reepay')->log('reepay/standard/accept');

        $session = Mage::getSingleton('checkout/session');

        $params = $this->getRequest()->getParams();
        if (empty($params['invoice']) || empty($params['id'])) {
            Mage::helper('reepay')->log('Not found parameters');

            return;
        }
        
        $orderId = $params['invoice'];
        $id = $params['id'];
        $_isAjax = $params['_isAjax'];
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        $reepayStatus = Mage::getModel('reepay/status')->getCollection()->addFieldToFilter('order_id', $orderId);


        

        if ($reepayStatus->getSize() > 0) {
            Mage::helper('reepay')->log('order : '.$orderId.' have been accepted already');
            if ($_isAjax == 1) {
                $result = array();
                $result['status'] = 'success';

                if (!empty($order->getRemoteIp())) {
                    // place online
                    $result['redirect_url'] = Mage::getUrl('checkout/onepage/success');
                } else {
                    // place by admin
                    $result['redirect_url'] = Mage::getUrl('reepay/standard/success');
                }
                $this->getResponse()->setHeader('Content-type', 'application/json');
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
            } else {
                if (!empty($order->getRemoteIp())) {
                    // place online
                    $this->_redirect('checkout/onepage/success', array('_secure' => true));
                } else {
                    // place by admin
                    $this->_redirect('reepay/standard/success', array('_secure' => true));
                }
            }

            return;
        }

        $apiKey = Mage::helper('reepay/apikey')->getPrivateKey($order->getStoreId());
        $charge = Mage::helper('reepay/charge')->get($apiKey, $orderId);

        $data = array(
            'order_id' => $orderId,
            'first_name' => $order->getBillingAddress()->getFirstname(),
            'last_name' => $order->getBillingAddress()->getLastname(),
            'email' => $order->getCustomerEmail(),
            'token' => $params['id'],
            'masked_card_number' => $charge['source']['masked_card'],
            'fingerprint' => $charge['source']['fingerprint'],
            'card_type' => $charge['source']['card_type'],
            'status' => $charge['state'],
        );

        $reepayOrderStatus = Mage::getModel('reepay/status');
        $reepayOrderStatus->setData($data);
        $reepayOrderStatus->save();
        Mage::helper('reepay')->log('save Model:reepay/status');

        $this->addTransactionToOrder($order, $charge);

        // delete reepay session
        $res = Mage::helper('reepay/session')->delete($apiKey, $id);
        Mage::helper('reepay')->log('delete reepay session : '.$id);

        // unset reepay session id on checkout session
        if ($session->getReepaySessionID() && $session->getReepaySessionOrder()) {
            $session->unsReepaySessionID();
            $session->unsReepaySessionOrder();
        }

        $sendEmailAfterPayment = Mage::helper('reepay')->getConfig('send_email_after_payment');
        if ($sendEmailAfterPayment) {
            $order->setEmailSent(true);
            $order->sendNewOrderEmail();
            $order->save();
            Mage::helper('reepay')->log('send_email_after_payment');
        }
        
        if ($_isAjax == 1) {
            Mage::helper('reepay')->log('reepay/standard/accept : return ajax request');
            $result = array();
            $result['status'] = 'success';

            if (!empty($order->getRemoteIp())) {
                // place online
                $result['redirect_url'] = Mage::getUrl('checkout/onepage/success');
            } else {
                // place by admin
                $result['redirect_url'] = Mage::getUrl('reepay/standard/success');
            }

            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        } else {
            Mage::helper('reepay')->log('reepay/standard/accept : redirect to success page');
            if (!empty($order->getRemoteIp())) {
                // place online
                $this->_redirect('checkout/onepage/success', array('_secure' => true));
            } else {
                // place by admin
                $this->_redirect('reepay/standard/success', array('_secure' => true));
            }
        }
    }

    /**
     * success page for order which created by admin
     */
    public function successAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Cancel from window payment
     */
    public function cancelAction()
    {
        Mage::helper('reepay')->log('reepay/standard/cancel');

        $session = Mage::getSingleton('checkout/session');

        $params = $this->getRequest()->getParams();
        if (empty($params['invoice']) || empty($params['id'])) {
            Mage::helper('reepay')->log('Not found parameters');

            return;
        }

        $orderId = $params['invoice'];
        $id = $params['id'];
        $_isAjax = $params['_isAjax'];
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        
        if ($order->canCancel()) {
            try {
                $order->cancel();
                $order->getStatusHistoryCollection(true);
                $order->addStatusHistoryComment('Reepay : order have been cancelled by payment page');
                $order->save();

                $_payment = $order->getPayment();
                Mage::helper('reepay')->setReepayPaymentState($_payment, 'cancelled');
                $order->save();

                Mage::helper('reepay')->log('Cancelled : '.$order->getIncrementId());

                // delete reepay session
                $apiKey = Mage::helper('reepay/apikey')->getPrivateKey($order->getStoreId());
                $res = Mage::helper('reepay/session')->delete($apiKey, $id);
                Mage::helper('reepay')->log('delete reepay session : '.$id);
            } catch (Exception $e) {
                Mage::helper('reepay')->log('cancel by window payment (Exception) : '.$e->getMessage(), Zend_Log::ERR);
            }
        }

        // unset reepay session id on checkout session
        if ($session->getReepaySessionID() && $session->getReepaySessionOrder()) {
            $session->unsReepaySessionID();
            $session->unsReepaySessionOrder();
        }
        
        if ($_isAjax == 1) {
            Mage::helper('reepay')->log('reepay/standard/cancel : return ajax request');
            $result = array(
                'status' => 'success',
                'redirect_url' => Mage::getUrl('checkout/onepage/failure'),
            );
            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        } else {
            Mage::helper('reepay')->log('reepay/standard/cancel : redirect to failure page');
            $this->_redirect('checkout/onepage/failure', array('_secure' => true));
        }
    }

    /**
     * Log error from overlay/embedded
     */
    public function errorAction()
    {
        Mage::helper('reepay')->log('reepay/standard/error');
        
        $params = $this->getRequest()->getParams();

        $orderId = $params['invoice'];
        $id = $params['id'];
        $error = $params['error'];
        
        Mage::helper('reepay')->log('Error : '.$orderId.' : '.$id.' : '.$error);
        $result = array(
            'status' => 'success',
            'redirect_url' => Mage::getUrl('checkout/onepage/failure'),
        );
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
}
