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

        Mage::helper('reepay')->log('cancel order observer : '.$order->getIncrementId());

        $apiKey = Mage::helper('reepay/apikey')->getPrivateKey($order->getStoreId());
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
            $order->getPayment()->getMethodInstance()->getCode() == 'reepay_viabill'
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
                    $order->getBillingAddress()->getEmail(),
                    $order->getBillingAddress()->getFirstname() . ' ' . $order->getBillingAddress()->getLastname(),
                    $vars,
                    $order->getStoreId()
                        );


                if (!empty(Mage::helper('reepay')->getConfig('order_status_before_payment'))) {
                    $order->setState(
                        Mage::helper('reepay')->getConfig('order_status_before_payment'),
                        true,
                        'Reepay : Order status before the payment is made',
                        null
                                    );
                    $order->save();
                }
                        
                Mage::log('onCheckoutSubmitAllAfter', null, 'reepay-observer.log');
            } catch (Exception $e) {
                Mage::log('onCheckoutSubmitAllAfter() exception: '.$e->getMessage(), null, 'reepay-observer.log');
                Mage::throwException('Error: '.$e->getMessage());
            }
        }
    }
}
