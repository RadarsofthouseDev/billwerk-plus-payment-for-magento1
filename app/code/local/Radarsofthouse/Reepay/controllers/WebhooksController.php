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
                            $response = $this->refund($data);
                        }

                        $log['response'] = $response;

                        break;
                    case 'invoice_settled':
                        if (array_key_exists('subscription', $data)) {
                            $response = array(
                                'message' => 'This request is not charge refund.',
                            );
                        } else {
                            $response = $this->settled($data);
                        }

                        $log['response'] = $response;

                        break;
                    case 'invoice_cancelled':
                        if (array_key_exists('subscription', $data)) {
                            $response = array(
                                'message' => 'This request is not charge cancel.',
                            );
                        } else {
                            $response = $this->cancel($data['invoice']);
                        }

                        $log['response'] = $response;

                        break;
                    case 'invoice_authorized':
                        if (array_key_exists('subscription', $data)) {
                            $response = array(
                                'message' => 'This request is not authorize.',
                            );
                        } else {
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
     * @param array $data
     * @return void
     */
    protected function settled($data)
    {
        $orderId = $data['invoice'];
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        Mage::helper('reepay')->log('webhook settled : '.$orderId);

        try {
            $apiKey = Mage::helper('reepay/apikey')->getPrivateKey($order->getStoreId());
            $reepayTransactionData = Mage::helper('reepay/invoice')->getTransaction($apiKey, $orderId, $data['transaction']);

            if (!empty($reepayTransactionData['id']) && $reepayTransactionData['type'] == "settle") {
                
                // check the transaction has been created
                $magentoTransaction = Mage::getModel('sales/order_payment_transaction')->getCollection()
                    ->addAttributeToFilter('order_id', array('eq' => $order->getId()))
                    ->addAttributeToFilter('txn_id', array('eq' => $reepayTransactionData['id'] ));
                if (count($magentoTransaction) > 0) {
                    Mage::helper('reepay')->log("Magento have created the transaction '".$reepayTransactionData['id']."' already.");

                    return array(
                        'invoice' => $orderId,
                        'message' => "Magento have created the transaction '".$reepayTransactionData['id']."' already.",
                    );
                }

                // create refund transaction
                $settledAmount = Mage::helper('reepay')->convertAmount($reepayTransactionData['amount']);
                $transactionID = Mage::helper('reepay')->addCaptureTransactionToOrder($order, $reepayTransactionData);

                // add order history
                $orderStore = Mage::getModel('core/store')->load($order->getStoreId());
                $settledAmountFormat = Mage::helper('core')->currencyByStore($settledAmount, $orderStore, true, false);

                $afterPaymentPaidStatus = Mage::helper('reepay')->getConfig('order_status_after_payment', $order->getStoreId());
                Mage::helper('reepay')->log('$afterPaymentPaidStatus :'.$afterPaymentPaidStatus);
                $order->addStatusHistoryComment('Reepay : Captured amount of '.$settledAmountFormat.' by Reepay webhook. Transaction ID: "'.$reepayTransactionData['id'].'". ', $afterPaymentPaidStatus);
                $order->save();

                Mage::helper('reepay')->log('Settled order #'.$orderId." , transaction ID : ".$transactionID." , Settled amount : ".$settledAmount);

                return array(
                    'invoice' => $orderId,
                    'message' => 'Settled order #'.$orderId." , transaction ID : ".$transactionID." , Settled amount : ".$settledAmount,
                );
            } else {
                Mage::helper('reepay')->log('Cannot get transaction data from Reepay : transaction ID = '.$data['transaction']);

                return array(
                    'invoice' => $orderId,
                    'message' => 'Cannot get transaction data from Reepay : transaction ID = '.$data['transaction'],
                );
            }
        } catch (Mage_Core_Exception $e) {
            Mage::helper('reepay')->log('webhook settled exception : '.$e->getMessage(), Zend_Log::ERR);

            return array(
                'invoice' => $orderId,
                'message' => 'webhook settled exception : '.$e->getMessage(),
            );
        }
    }

    /**
     * Refund from Reepay
     *
     * @param array $data
     * @return array
     */
    protected function refund($data)
    {
        $orderId = $data['invoice'];
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        Mage::helper('reepay')->log('webhook refund : '.$orderId);
        
        try {
            $apiKey = Mage::helper('reepay/apikey')->getPrivateKey($order->getStoreId());
            $refundData = Mage::helper('reepay/invoice')->getTransaction($apiKey, $orderId, $data['transaction']);

            if (!empty($refundData['id']) && $refundData['state'] == "refunded") {
                // check the transaction has been created
                $magentoTransaction = Mage::getModel('sales/order_payment_transaction')->getCollection()
                    ->addAttributeToFilter('order_id', array('eq' => $order->getId()))
                    ->addAttributeToFilter('txn_id', array('eq' => $refundData['id'] ));
                if (count($magentoTransaction) > 0) {
                    Mage::helper('reepay')->log("Magento have created the transaction '".$refundData['id']."' already.");

                    return array(
                        'invoice' => $orderId,
                        'message' => "Magento have created the transaction '".$refundData['id']."' already.",
                    );
                }

                // create refund transaction
                $refundAmount = Mage::helper('reepay')->convertAmount($refundData['amount']);
                $transactionID = Mage::helper('reepay')->addRefundTransactionToOrder($order, $refundData);

                // add order history
                $orderStore = Mage::getModel('core/store')->load($order->getStoreId());
                $refundAmountFormat = Mage::helper('core')->currencyByStore($refundAmount, $orderStore, true, false);
                $order->getStatusHistoryCollection(true);
                $order->addStatusHistoryComment('Reepay : Refunded amount of '.$refundAmountFormat.' by Reepay webhook. Transaction ID: "'.$refundData['id'].'". ');
                $order->save();

                return array(
                    'invoice' => $orderId,
                    'message' => 'Refunded order #'.$orderId." , transaction ID : ".$transactionID." , amount : ".$refundAmount,
                );
            } else {
                Mage::helper('reepay')->log('Cannot get refund transaction data from Reepay : transaction ID = '.$data['transaction']);

                return array(
                    'invoice' => $orderId,
                    'message' => 'Cannot get refund transaction data from Reepay : transaction ID = '.$data['transaction'],
                );
            }
        } catch (Mage_Core_Exception $e) {
            Mage::helper('reepay')->log('webhook refund exception : '.$e->getMessage(), Zend_Log::ERR);

            return array(
                'invoice' => $orderId,
                'message' => 'webhook refund exception : '.$e->getMessage(),
            );
        }
    }

    /**
     * Cancel from Reepay
     *
     * @param string $orderId (order increment ID)
     * @return array
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

                Mage::helper('reepay')->log('canceled order #'.$orderId);
                return array(
                    'invoice' => $orderId,
                    'message' => 'canceled order #'.$orderId,
                );
            } catch (Exception $e) {
                Mage::helper('reepay')->log('webhook cancel exception : '.$e->getMessage(), Zend_Log::ERR);
                return array(
                    'invoice' => $orderId,
                    'message' => 'Cannot cancel order #'.$orderId.' : '.$e->getMessage(),
                );
            }
        } else {
            Mage::helper('reepay')->log('Cannot cancel order #'.$orderId);
            return array(
                'invoice' => $orderId,
                'message' => 'Cannot cancel order #'.$orderId,
            );
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
            $apiKey = Mage::helper('reepay/apikey')->getPrivateKey($order->getStoreId());
            $reepayTransactionData = Mage::helper('reepay/invoice')->getTransaction($apiKey, $orderId, $data['transaction']);

            /*
            // check if has reepay status row for the order, That means the order has been authorized
            $reepayStatus = Mage::getModel('reepay/status')->getCollection()->addFieldToFilter('order_id', $orderId);
            if ($reepayStatus->getSize() > 0) {
                Mage::helper('reepay')->log('order #'.$orderId.' has been authorized already');
                return array(
                    'invoice' => $orderId,
                    'message' => 'order #'.$orderId.' has been authorized already',
                );
            }
            */

            // check the transaction has been created
            $magentoTransaction = Mage::getModel('sales/order_payment_transaction')->getCollection()
                ->addAttributeToFilter('order_id', array('eq' => $order->getId()))
                ->addAttributeToFilter('txn_id', array('eq' => $reepayTransactionData['id'] ));
            if (count($magentoTransaction) > 0) {
                Mage::helper('reepay')->log("Magento have created the transaction '".$reepayTransactionData['id']."' already.");

                return array(
                    'invoice' => $orderId,
                    'message' => "Magento have created the transaction '".$reepayTransactionData['id']."' already.",
                );
            }

            
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
                if ($order->getEmailSent()) {
                } else {
                    $order->setEmailSent(true);
                    $order->sendNewOrderEmail();
                    $order->save();
                    Mage::helper('reepay')->log('send_email_after_payment');
                }
            }

            Mage::helper('reepay')->log('order #'.$orderId.' has been authorized by Reepay webhook');

            return array(
                'invoice' => $orderId,
                'message' => 'order #'.$orderId.' has been authorized by Reepay webhook',
            );
        } catch (Exception $e) {
            Mage::helper('reepay')->log('webhook authorize exception : '.$e->getMessage(), Zend_Log::ERR);
            Mage::logException($e);
            return array(
                'invoice' => $orderId,
                'message' => 'webhook authorize error : '.$e->getMessage(),
            );
        }
    }
}
