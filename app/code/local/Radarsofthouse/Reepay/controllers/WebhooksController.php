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
class Radarsofthouse_Reepay_WebhooksController extends Mage_Core_Controller_Front_Action
{
    /**
     * Reepay webhooks (callback from Reepay)
     */
    public function indexAction()
    {
        $request = $this->getRequest()->getRawBody();
        $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json');
        $log = array(
            'method' => __METHOD__,
            'request' => array('request' => json_decode($request, true)),
        );

        try {
            if (empty($request) || $request === null) {
                $this->getResponse()->setHeader('HTTP/1.0', 400, true);
                $response = array(
                    'error_code' => '400',
                    'message' => 'Empty request data',
                );
                $log['response_error'] = $response;
                Mage::helper('reepay')->log(json_encode($log), Zend_Log::ERR, true);
            } else {
                $data = json_decode($request, true);
                switch ($data['event_type']) {
                    case 'invoice_refund':
                        if (array_key_exists('subscription', $data)) {
                            $response = array(
                                'message' => 'This request is not charge invoice.',
                            );
                        } else {
                            $invoice = $this->getInvoice($data['invoice']);

                            // $this->refund($data['invoice']);

                            $response = array(
                                'invoice' => $invoice,
                                'message' => 'This request is invoice_refund event. (blocked by Magento)',
                            );
                        }

                        $log['response'] = $response;

                        break;
                    case 'invoice_settled':
                        if (array_key_exists('subscription', $data)) {
                            $response = array(
                                'message' => 'This request is not charge refund.',
                            );
                        } else {
                            $invoice = $this->getInvoice($data['invoice']);

                            $this->settled($data['invoice']);

                            $response = array(
                                'invoice' => $invoice,
                                'message' => 'This request is invoice_settled event.',
                            );
                        }

                        $log['response'] = $response;

                        break;
                    case 'invoice_cancelled':
                        if (array_key_exists('subscription', $data)) {
                            $response = array(
                                'message' => 'This request is not charge cancel.',
                            );
                        } else {
                            $invoice = $this->getInvoice($data['invoice']);
                            
                            $this->cancel($data['invoice']);

                            $response = array(
                                'invoice' => $invoice,
                                'message' => 'This request is invoice_cancelled event.',
                            );
                        }

                        $log['response'] = $response;

                        break;
                    case 'invoice_authorized':
                        if (array_key_exists('subscription', $data)) {
                            $response = array(
                                'message' => 'This request is not authorize.',
                            );
                        } else {
                            $invoice = $this->getInvoice($data['invoice']);
                            
                            $response = $this->authorize($data);
                        }

                        $log['response'] = $response;

                        break;
                    default:
                        $response = array('message' => 'The '.$data['event_type'].' event has been ignored by Magento.');
                        $log['response'] = $response;

                        break;
                }

                Mage::helper('reepay')->log(json_encode($log), Zend_Log::INFO, true);
            }
        } catch (\Exception $e) {
            $response = array(
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            );
            $log['response_error'] = $response;
            $log['response_error']['trace'] = $e->getTrace();
            Mage::helper('reepay')->log(json_encode($log), Zend_Log::ERR, true);
            $this->getResponse()->setHeader('HTTP/1.0', 500, true);
        }

        $this->getResponse()->setBody(json_encode($response), 'response');
    }

    /**
     * Get invoice
     *
     * @param $handle
     * @return $invoice response from API
     */
    protected function getInvoice($handle)
    {
        $order = Mage::getModel('sales/order')->loadByIncrementId($handle);
        $apiKey = Mage::helper('reepay/apikey')->getPrivateKey($order->getStoreId());
        $invoice = Mage::helper('reepay/invoice')->get($apiKey, $handle);
        if (!$invoice) {
            Mage::throwException("Invoice {$handle} not found in {$apiKey}");
        }

        return $invoice;
    }

    /**
     * Capture from Reepay
     *
     * @param string $orderId (order increment ID)
     * @return void
     */
    protected function settled($orderId)
    {
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        Mage::helper('reepay')->log('webhook settled : '.$orderId);

        try {
            if (!$order->canInvoice()) {
                Mage::helper('reepay')->log('Cannot create an invoice.');
                Mage::throwException(Mage::helper('core')->__('Cannot create an invoice.'));
            }

            $invoice = $order->prepareInvoice();
            $invoice->register();
            $transaction = Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder());
            $transaction->save();
            
            $invoice->capture();
            $invoice->save();

            $order->addStatusToHistory($order->getStatus(), 'Reepay : Transaction has been captured.');
            $order->save();

            $_payment = $order->getPayment();
            Mage::helper('reepay')->setReepayPaymentState($_payment, 'settled');
            $order->save();
            
            Mage::helper('reepay')->log('create invoice and capture');
        } catch (Mage_Core_Exception $e) {
            Mage::helper('reepay')->log('webhook settled exception : '.$e->getMessage());
        }
    }

    /**
     * Refund from Reepay
     *
     * @param string $orderId (order increment ID)
     * @return void
     */
    protected function refund($orderId)
    {
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        Mage::helper('reepay')->log('webhook refund : '.$orderId);

        try {
            $invoiceCollection = $order->getInvoiceCollection();
            foreach ($invoiceCollection as $invoice) {
                $service = Mage::getModel('sales/service_order', $order);
                $creditmemo = $service->prepareInvoiceCreditmemo($invoice);
                $creditmemo->register();
                $creditmemo->save();
                Mage::helper('reepay')->log('creditmemo : '.$invoice->getIncrementId());
            }

            $_payment = $order->getPayment();
            Mage::helper('reepay')->setReepayPaymentState($_payment, 'refunded');
            $order->save();
        } catch (Mage_Core_Exception $e) {
            Mage::helper('reepay')->log('webhook refund exception : '.$e->getMessage());
        }
    }

    /**
     * Cancel from Reepay
     *
     * @param string $orderId (order increment ID)
     * @return void
     */
    protected function cancel($orderId)
    {
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        Mage::helper('reepay')->log('webhook cancel : '.$orderId);

        if ($order->canCancel()) {
            try {
                $order->cancel();
                $order->getStatusHistoryCollection(true);
                $order->addStatusHistoryComment('Reepay : order have been cancelled by Reepay webhook');
                $order->save();

                $_payment = $order->getPayment();
                Mage::helper('reepay')->setReepayPaymentState($_payment, 'cancelled');
                $order->save();

                Mage::helper('reepay')->log('cancel order');
            } catch (Exception $e) {
                Mage::helper('reepay')->log('webhook cancel exception : '.$e->getMessage());
                Mage::logException($e);
            }
        }
    }

    /**
     * Create authorize transaction if have no the transaction
     *
     * @param array $data
     * @return array
     */
    protected function authorize($data)
    {
        $orderId = $data['invoice'];
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        Mage::helper('reepay')->log('webhook authorize : '.$orderId);

        try {
            // check if has reepay status row for the order, That means the order has been authorized
            $reepayStatus = Mage::getModel('reepay/status')->getCollection()->addFieldToFilter('order_id', $orderId);
            if ($reepayStatus->getSize() > 0) {
                Mage::helper('reepay')->log('order : '.$orderId.' has been authorized already');
                return array(
                    'invoice' => $orderId,
                    'message' => 'order : '.$orderId.' has been authorized already',
                );
            }

            $apiKey = Mage::helper('reepay/apikey')->getPrivateKey($order->getStoreId());
            $charge = Mage::helper('reepay/charge')->get($apiKey, $orderId);

            $data = array(
                'order_id' => $orderId,
                'first_name' => $order->getBillingAddress()->getFirstname(),
                'last_name' => $order->getBillingAddress()->getLastname(),
                'email' => $order->getCustomerEmail(),
                // 'token' => $params['id'],
                'token' => "",
                'masked_card_number' => $charge['source']['masked_card'],
                'fingerprint' => $charge['source']['fingerprint'],
                'card_type' => $charge['source']['card_type'],
                'status' => $charge['state'],
            );

            $reepayOrderStatus = Mage::getModel('reepay/status');
            $reepayOrderStatus->setData($data);
            $reepayOrderStatus->save();
            Mage::helper('reepay')->log('save Model:reepay/status');

            Mage::helper('reepay')->addTransactionToOrder($order, $charge);

            // delete reepay session

            // have no token id form invoice_authorized webhook
            // $res = Mage::helper('reepay/session')->delete($apiKey, $id);
            // Mage::helper('reepay')->log('delete reepay session : '.$id);

            $order->getStatusHistoryCollection(true);
            $order->addStatusHistoryComment('Reepay : order has been authorized by Reepay webhook');
            $order->save();

            $sendEmailAfterPayment = Mage::helper('reepay')->getConfig('send_email_after_payment', $order->getStoreId());
            if ($sendEmailAfterPayment) {
                $order->setEmailSent(true);
                $order->sendNewOrderEmail();
                $order->save();
                Mage::helper('reepay')->log('send_email_after_payment');
            }

            return array(
                'invoice' => $orderId,
                'message' => 'order : '.$orderId.' has been authorized by Reepay webhook',
            );

        } catch (Exception $e) {
            Mage::helper('reepay')->log('webhook authorize exception : '.$e->getMessage());
            Mage::logException($e);
            return array(
                'invoice' => $orderId,
                'message' => 'webhook authorize error : '.$e->getMessage(),
            );
        }
        
    }
}
