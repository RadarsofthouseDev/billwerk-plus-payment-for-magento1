<?php

class Radarsofthouse_Reepay_Helper_Session extends Mage_Core_Helper_Abstract
{
    const ENDPOINT = 'session';

    /**
     * Create charge session
     *
     * @param string $apiKey
     * @param array $session
     * @return bool|array
     */
    public function chargeCreate($apiKey, $session)
    {
        $log = array(
            'method' => __METHOD__,
            'apiKey' => $apiKey,
            'param' => array('session' => $session),
        );
        $response = Mage::helper('reepay/client')->post($apiKey, self::ENDPOINT . '/charge', $session, true);
        if (Mage::helper('reepay/client')->success()) {
            $log ['response'] = $response;
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
     * Create session charge with create exist invoice.
     *
     * @param string $apiKey
     * @param string $invoice
     * @param bool $settle
     * @param array $paymentMethods
     * @param array $option
     * @return bool|array
     */
    public function chargeCreateWithExistInvoice($apiKey, $invoice, $paymentMethods, $settle, $option = array())
    {
        $option['invoice'] = $invoice;
        $option['settle'] = $settle;
        $option['payment_methods'] = $paymentMethods;

        return $this->chargeCreate($apiKey, $option);
    }

    /**
     * Create session charge with create exist customer.
     *
     * @param string $apiKey
     * @param string $customerHandle
     * @param array $order
     * @param array $paymentMethods
     * @param bool $settle
     * @param array $option
     * @return bool|array
     */
    public function chargeCreateWithExistCustomer($apiKey, $customerHandle, $order, $paymentMethods, $settle, $option = array())
    {
        $order['customer_handle'] = $customerHandle;
        $order['settle'] = $settle;
        $option['order'] = $order;
        $option['settle'] = $settle;
        $option['payment_methods'] = $paymentMethods;

        return $this->chargeCreate($apiKey, $option);
    }

    /**
     * Create session charge with create new customer.
     *
     * @param string $apiKey
     * @param array $customer
     * @param array $order
     * @param bool $settle
     * @param array $paymentMethods
     * @param array $option
     * @return bool|array
     */
    public function chargeCreateWithNewCustomer($apiKey, $customer, $order, $paymentMethods, $settle, $option = array())
    {
        $order['customer'] = $customer;
        $order['settle'] = $settle;
        $option['order'] = $order;
        $option['settle'] = $settle;
        $option['payment_methods'] = $paymentMethods;

        return $this->chargeCreate($apiKey, $option);
    }


    /**
     * Delete session
     *
     * @param string $apiKey
     * @param string $id
     * @return bool|array
     */
    public function delete($apiKey, $id)
    {
        $log = array(
            'method' => __METHOD__,
            'apiKey' => $apiKey,
            'param' => array('id' => $id),
        );
        $response = Mage::helper('reepay/client')->delete($apiKey, self::ENDPOINT . "/{$id}", array(), true);
        if (Mage::helper('reepay/client')->success()) {
            $log['response'] = $response;
            Mage::helper('reepay')->log(json_encode($log), Zend_Log::INFO, true);

            return true;
        } else {
            $log['http_errors'] = Mage::helper('reepay/client')->getHttpError();
            $log['response_errors'] = Mage::helper('reepay/client')->getErrors();
            Mage::helper('reepay')->log(json_encode($log), Zend_Log::ERR, true);

            return false;
        }
    }
}
