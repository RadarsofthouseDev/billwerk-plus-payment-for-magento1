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

                            $this->refund($data['invoice']);

                            $response = array(
                                'invoice' => $invoice,
                                'message' => 'This request is invoice_refund event.',
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
                    default:
                        $response = array('message' => 'This request is not invoice_settled or invoice_refund event.');
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
        $apiKey = Mage::helper('reepay/apikey')->getPrivateKey();
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
}
