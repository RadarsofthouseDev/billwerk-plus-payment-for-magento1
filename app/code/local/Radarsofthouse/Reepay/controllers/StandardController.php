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

        $order = Mage::getModel('sales/order')->loadByIncrementId($quote->getReservedOrderId());

        Mage::helper('reepay')->log('Display type : '.Mage::helper('reepay')->getConfig('display_type', $order->getStoreId()));

        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle($this->__('Reepay payment'));
        
        $sessionId = null;
        // create new Reepay session
        $sessionId = Mage::helper('reepay')->createReepaySession($order);

        $session->setReepaySessionOrder($quote->getReservedOrderId());
        $session->setReepaySessionID($sessionId);
        
        if ($order->getPayment()->getMethodInstance()->getCode() == 'reepay_viabill') {
            // force viabill into payment window always
            $this->getLayout()->getBlock('reepay_index')
                ->setPaymentSessionId($sessionId)
                ->setTemplate('reepay/window.phtml');
        } elseif (Mage::helper('reepay')->getConfig('display_type', $order->getStoreId()) == SELF::DISPLAY_EMBEDDED) {
            $this->getLayout()->getBlock('reepay_index')
                ->setPaymentSessionId($sessionId)
                ->setTemplate('reepay/embedded.phtml');
        } elseif (Mage::helper('reepay')->getConfig('display_type', $order->getStoreId()) == SELF::DISPLAY_OVERLAY) {
            $this->getLayout()->getBlock('reepay_index')
                ->setPaymentSessionId($sessionId)
                ->setTemplate('reepay/overlay.phtml');
        } elseif (Mage::helper('reepay')->getConfig('display_type', $order->getStoreId()) == SELF::DISPLAY_WINDOW) {
            $this->getLayout()->getBlock('reepay_index')
                ->setPaymentSessionId($sessionId)
                ->setTemplate('reepay/window.phtml');
        }

        $this->renderLayout();
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
        $id = "";
        if (!empty($params['id'])) {
            $id = $params['id'];
        }
        
        $_isAjax = $params['_isAjax'];
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        $reepayStatus = Mage::getModel('reepay/status')->getCollection()->addFieldToFilter('order_id', $orderId);

        $apiKey = Mage::helper('reepay/apikey')->getPrivateKey($order->getStoreId());

        // delete reepay session
        if (!empty($id)) {
            $res = Mage::helper('reepay/session')->delete($apiKey, $id);
            Mage::helper('reepay')->log('delete reepay session : '.$id);
        }

        // unset reepay session id on checkout session
        if (!empty($session->getReepaySessionID())) {
            $session->unsReepaySessionID();
        }
        if (!empty($session->getReepaySessionOrder())) {
            $session->unsReepaySessionOrder();
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

        if (!empty($params['error'])) {
            if ($params['error'] == "error.session.SESSION_DELETED") {
                $this->_redirect('checkout/cart', array('_secure' => true));

                return;
            }
        }

        if (empty($params['invoice']) || empty($params['id'])) {
            Mage::helper('reepay')->log('Not found parameters');
            $this->_redirect('checkout/cart', array('_secure' => true));

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

                $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
                if ($quote->getId()) {
                    $quote->setIsActive(1)
                        ->setReservedOrderId(null)
                        ->save();
                    $session->replaceQuote($quote);
                }
                $session->unsLastRealOrderId();

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
                'redirect_url' => Mage::getUrl('checkout/cart'),
            );
            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        } else {
            Mage::helper('reepay')->log('reepay/standard/cancel : redirect to checkout/cart');
            $this->_redirect('checkout/cart', array('_secure' => true));
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
