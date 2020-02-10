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
     * Change Order Status on Invoice Generation
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function sales_order_invoice_save_after(Varien_Event_Observer $observer)
    {
        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();

        $code = $method->getCode();
        if (strpos($code, 'reepay') === false) {
            return $this;
        }

        // is Captured
        if (!$payment->getIsTransactionPending()) {
            $orderStore = Mage::getModel('core/store')->load($order->getStoreId());
            $grandTotal = Mage::helper('core')->currencyByStore($order->getGrandTotal(), $orderStore, true, false);

            // Change order status
            /** @var Radarsofthouse_Reepay_Helper_Data $helper */
            $helper = Mage::helper('reepay');

            /** @var Mage_Sales_Model_Order_Status $status */
            $status = $helper->getAssignedState($helper->getConfig('order_status_settled'));
            $order->setData('state', $status->getState());
            $order->setStatus($status->getStatus());
            $order->addStatusHistoryComment(
                Mage::helper('reepay')->__('Reepay : The settled amount is %s.', $grandTotal),
                $status->getStatus()
            );
            $order->save();
        }

        return $this;
    }

    /**
     * Clean up Pending Orders
     *
     * @return $this
     */
    public function cleanup_pending_orders()
    {
        $orders = Mage::getModel('sales/order')->getCollection();
        $orders->getSelect()->join(
            array('p' => $orders->getResource()->getTable('sales/order_payment')),
            'p.parent_id = main_table.entity_id',
            array()
        );
        $orders->addFieldToFilter('method', array('in' => array('reepay', 'reepay_mobilepay', 'reepay_viabill')));
        $orders->addFieldToFilter('status', array('in' => array('pending_payment')));
        foreach ($orders as $order) {
            /** @var $order Mage_Sales_Model_Order */
            // Check order state
            if (!$order->isCanceled() && !$order->hasInvoices()) {
                try {
                    $clean_time = -1 * Mage::helper('reepay')->getConfig('cleanup_time', $order->getStore());
                    if ($clean_time !== 0) {
                        $clean_time = strtotime($clean_time . ' minutes');
                        $order_created_time = strtotime($order->getCreatedAt());
                        if ($clean_time > $order_created_time) {
                            // Cancel order
                            $order->cancel();
                            $order->addStatusHistoryComment(Mage::helper('reepay')->__('Order has been cancelled by timeout.'));
                            $order->save();

                            Mage::helper('reepay')->log(sprintf('Pending Cleaner: Order #%s was cancelled.', $order->getIncrementId()));
                        }
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
        }

        return $this;
    }
}
