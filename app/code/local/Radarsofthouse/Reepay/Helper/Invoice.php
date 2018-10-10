<?php

class Radarsofthouse_Reepay_Helper_Invoice extends Mage_Core_Helper_Abstract
{
    const ENDPOINT = 'invoice';

    /**
     * List invoices.
     *
     * @param string $apiKey
     * @param int $page
     * @param int $size
     * @param string $search
     * @param string $sort
     * @return bool|array
     */
    public function lists($apiKey, $page = 0, $size = 100, $search = '', $sort = '-created')
    {
        $log = array(
            'method' => __METHOD__,
            'apiKey' => $apiKey,
            'param' => array('page' => $page, 'size' => $size, 'search' => $search, 'sort' => $sort),
        );
        $data = array(
            'size' => $size,
            'sort' => $sort,
        );
        if ($page) {
            $data['page'] = $page;
        }

        if (!empty($search)) {
            $data['search'] = $search;
        }

        $response = Mage::helper('reepay/client')->get($apiKey, self::ENDPOINT, $data);
        if (Mage::helper('reepay/client')->success()) {
            $log['response'] = $response;
            Mage::helper('reepay')->log(json_encode($log), Zend_Log::INFO, true);

            return $response;
        } else {
            $log['http_errors'] = Mage::helper('reepay/client')->getHttpError();
            $log['response_errors'] = Mage::helper('reepay/client')->getErrors();
            Mage::helper('reepay')->log(json_encode($log), Zend_Log::ERR, true);

            return false;
        }
    }

    /**
     * Get invoice by ID or handle
     *
     * @param string $apiKey
     * @param string $handle
     * @return bool|array
     */
    public function get($apiKey, $handle)
    {
        $log = array(
            'method' => __METHOD__,
            'apiKey' => $apiKey,
            'param' => array('handle' => $handle),
        );
        $response = Mage::helper('reepay/client')->get($apiKey, self::ENDPOINT . "/{$handle}");
        if (Mage::helper('reepay/client')->success()) {
            $log['response'] = $response;
            Mage::helper('reepay')->log(json_encode($log), Zend_Log::INFO, true);

            return $response;
        } else {
            $log['http_errors'] = Mage::helper('reepay/client')->getHttpError();
            $log['response_errors'] = Mage::helper('reepay/client')->getErrors();
            Mage::helper('reepay')->log(json_encode($log), Zend_Log::ERR, true);

            return false;
        }
    }

    /**
     * Cancel invoice.
     * An invoice with all transactions with no or only failed transaction can be cancelled.
     * No further attempts to fulfill the invoice will be made.
     * If the invoice is dunning the dunning process will be cancelled.
     *
     * @param string $apiKey
     * @param string $handle
     * @return bool|array
     */
    public function cancel($apiKey, $handle)
    {
        $log = array(
            'method' => __METHOD__,
            'apiKey' => $apiKey,
            'param' => array('handle' => $handle),
        );
        $response = Mage::helper('reepay/client')->post($apiKey, self::ENDPOINT . "/{$handle}/cancel");
        if (Mage::helper('reepay/client')->success()) {
            $log['response'] = $response;
            Mage::helper('reepay')->log(json_encode($log), Zend_Log::INFO, true);

            return $response;
        } else {
            $log['http_errors'] = Mage::helper('reepay/client')->getHttpError();
            $log['response_errors'] = Mage::helper('reepay/client')->getErrors();
            Mage::helper('reepay')->log(json_encode($log), Zend_Log::ERR, true);

            return false;
        }
    }

    /**
     *  Get transaction by ID
     *
     * @param string $apiKey
     * @param string $handle
     * @param string $transaction
     * @return bool|array
     */
    public function getTransaction($apiKey, $handle, $transaction)
    {
        $log = array(
            'method' => __METHOD__,
            'apiKey' => $apiKey,
            'param' => array('handle' => $handle, 'transaction' => $transaction),
        );
        $response = Mage::helper('reepay/client')->get(
            $apiKey,
            self::ENDPOINT . "/{$handle}/transaction/{$transaction}"
        );
        if (Mage::helper('reepay/client')->success()) {
            $log['response'] = $response;
            Mage::helper('reepay')->log(json_encode($log), Zend_Log::INFO, true);

            return $response;
        } else {
            $log['http_errors'] = Mage::helper('reepay/client')->getHttpError();
            $log['response_errors'] = Mage::helper('reepay/client')->getErrors();
            Mage::helper('reepay')->log(json_encode($log), Zend_Log::ERR, true);

            return false;
        }
    }

    /**
     * Get transaction details.
     *
     * @param string $apiKey
     * @param string $handle
     * @param string $transaction
     * @return bool|array
     */
    public function getTransactionDetails($apiKey, $handle, $transaction)
    {
        $log = array(
            'method' => __METHOD__,
            'apiKey' => $apiKey,
            'param' => array('handle' => $handle, 'transaction' => $transaction),
        );
        $response = Mage::helper('reepay/client')->get(
            $apiKey,
            self::ENDPOINT . "/{$handle}/transaction/{$transaction}/details"
        );
        if (Mage::helper('reepay/client')->success()) {
            $log['response'] = $response;
            Mage::helper('reepay')->log(json_encode($log), Zend_Log::INFO, true);

            return $response;
        } else {
            $log['http_errors'] = Mage::helper('reepay/client')->getHttpError();
            $log['response_errors'] = Mage::helper('reepay/client')->getErrors();
            Mage::helper('reepay')->log(json_encode($log), Zend_Log::ERR, true);

            return false;
        }
    }

    /**
     * Cancel transaction.
     *
     * @param string $apiKey
     * @param string $handle
     * @param string $transaction
     * @return bool|array
     */
    public function cancelTransaction($apiKey, $handle, $transaction)
    {
        $log = array(
            'method' => __METHOD__,
            'apiKey' => $apiKey,
            'param' => array('handle' => $handle, 'transaction' => $transaction),
        );
        $response = Mage::helper('reepay/client')->post(
            self::ENDPOINT . "/{$handle}/transaction/{$transaction}/cancel"
        );
        if (Mage::helper('reepay/client')->success()) {
            $log['response'] = $response;
            Mage::helper('reepay')->log(json_encode($log), Zend_Log::INFO, true);

            return $response;
        } else {
            $log['http_errors'] = Mage::helper('reepay/client')->getHttpError();
            $log['response_errors'] = Mage::helper('reepay/client')->getErrors();
            Mage::helper('reepay')->log(json_encode($log), Zend_Log::ERR, true);

            return false;
        }
    }

    /**
     * Offline manual settle.
     * A non-settled invoice can be settled using an offline manual transfer.
     * An offline manual transfer could for example be a cash or bank transfer not handled automatically by Reepay.
     * The invoice will be instantly settled and a receipt email is sent to the customer.
     *
     * @param string $apiKey
     * @param string $handle
     * @param string $method
     * @param string $paymentDate
     * @param string $comment
     * @param string $reference
     * @return bool|array
     */
    public function offlineManualSettle($apiKey, $handle, $method, $paymentDate, $comment = '', $reference = '')
    {
        $log = array(
            'method' => __METHOD__,
            'apiKey' => $apiKey,
            'param' => array(
                'handle' => $handle,
                'method' => $method,
                'payment_date' => $paymentDate,
                'comment' => $comment,
                'reference' => $reference,
            ),
        );
        $settle = array(
            'method' => $method,
            'payment_date' => $paymentDate,
            'comment' => $comment,
            'reference' => $reference,
        );
        $response = Mage::helper('reepay/client')->post(self::ENDPOINT . "/{$id}/manual_settle", $settle);
        if (Mage::helper('reepay/client')->success()) {
            $log['response'] = $response;
            Mage::helper('reepay')->log(json_encode($log), Zend_Log::INFO, true);

            return $response;
        } else {
            $log['http_errors'] = Mage::helper('reepay/client')->getHttpError();
            $log['response_errors'] = Mage::helper('reepay/client')->getErrors();
            Mage::helper('reepay')->log(json_encode($log), Zend_Log::ERR, true);

            return false;
        }
    }

    /**
    * Invoice reactivate.
    * A failed or cancelled invoice can be put back to state pending for processing.
    * The invoice will potentially enter a new dunning process if it is a subscription invoice.
    *
    * @param string $apiKey
    * @param string $handle
    * @return bool|array
    */
    public function reactivate($apiKey, $handle)
    {
        $log = array(
            'method' => __METHOD__,
            'apiKey' => $apiKey,
            'param' => array('handle' => $handle),
        );
        $response = Mage::helper('reepay/client')->post(self::ENDPOINT . "/{$id}/reactivate");
        if (Mage::helper('reepay/client')->success()) {
            $log['response'] = $response;
            Mage::helper('reepay')->log(json_encode($log), Zend_Log::INFO, true);

            return $response;
        } else {
            $log['http_errors'] = Mage::helper('reepay/client')->getHttpError();
            $log['response_errors'] = Mage::helper('reepay/client')->getErrors();
            Mage::helper('reepay')->log(json_encode($log), Zend_Log::ERR, true);

            return false;
        }
    }

    /**
    * Cancel settle later.
    * Scheduled settle later can be cancelled for at pending customer invoice.
    *
    * @param string $apiKey
    * @param string $handle
    * @return bool|array
    */
    public function cancelSettleLater($apiKey, $handle)
    {
        $log = array(
            'method' => __METHOD__,
            'apiKey' => $apiKey,
            'param' => array('handle' => $handle),
        );
        $response = Mage::helper('reepay/client')->post(self::ENDPOINT . "/{$id}/settle/cancel");
        if (Mage::helper('reepay/client')->success()) {
            $log['response'] = $response;
            Mage::helper('reepay')->log(json_encode($log), Zend_Log::INFO, true);

            return $response;
        } else {
            $log['http_errors'] = Mage::helper('reepay/client')->getHttpError();
            $log['response_errors'] = Mage::helper('reepay/client')->getErrors();
            Mage::helper('reepay')->log(json_encode($log), Zend_Log::ERR, true);

            return false;
        }
    }
}
