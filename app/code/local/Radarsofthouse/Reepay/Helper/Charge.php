<?php
/**
 * Charge
 */

class Radarsofthouse_Reepay_Helper_Charge extends Mage_Core_Helper_Abstract
{
    const ENDPOINT = 'charge';

    /**
     * List charge
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
            'param' => array(
                'page' => $page,
                'size' => $size,
                'search' => $search,
                'sort' => $sort,
            ),
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
     * Get charge by invoice id or handle
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
     * Create charge
     *
     * @param string $apiKey
     * @param string $charge
     * @return bool|array
     */
    public function create($apiKey, $charge)
    {
        $log = array(
            'method' => __METHOD__,
            'apiKey' => $apiKey,
            'param' => array('charge' => $charge),
        );
        $response = Mage::helper('reepay/client')->post($apiKey, self::ENDPOINT, $charge);
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
     * Create charge with create new customer.
     *
     * @param string $apiKey
     * @param string $handle
     * @param string $source
     * @param array $customer
     * @param array $option
     * @return bool|array
     */
    public function createWithNewCustomer($apiKey, $handle, $source, $customer, $option = array())
    {
        $option['handle'] = $handle;
        $option['source'] = $source;
        $option['customer'] = $customer;

        return $this->create($apiKey, $option);
    }

    /**
     * Create charge with exist customer.
     *
     * @param string $apiKey
     * @param string $handle
     * @param string $source
     * @param string $customerHandle
     * @param array $option
     * @return bool|array
     */
    public function createWithExistCustomer($apiKey, $handle, $source, $customerHandle, $option = array())
    {
        $option['handle'] = $handle;
        $option['source'] = $source;
        $option['customer_handle'] = $customerHandle;

        return $this->create($apiKey, $option);
    }

    /**
     * Prepare charge
     * A charge can be prepared in Reepay without a payment attempt.
     * The charge can subsequently be attempted paid with a call to create charge.
     * A prepare charge operation is basically a create charge operation without payment source.
     * A prepared charge operation will result in an invoice with state created.
     * The prepare charge operation can be used to create an order before determining how to pay for the invoice.
     *
     * @param string $apiKey
     * @param array $prepare
     * @return bool|array
     */
    public function prepare($apiKey, $prepare)
    {
        $log = array(
            'method' => __METHOD__,
            'apiKey' => $apiKey,
            'param' => array('prepare' => $prepare),
        );
        $response = Mage::helper('reepay/client')->post($apiKey, self::ENDPOINT . '/prepare', $prepare);
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
     * Prepare charge with create new customer.
     *
     * @param string $apiKey
     * @param string $handle
     * @param array $customer
     * @param array $option
     * @return bool|array
     */
    public function prepareWithNewCustomer($apiKey, $handle, $customer, $option = array())
    {
        $option['handle'] = $handle;
        $option['customer'] = $customer;

        return $this->create($apiKey, $option);
    }

    /**
     * Prepare charge with exist customer.
     *
     * @param string $apiKey
     * @param string $handle
     * @param string $customerHandle
     * @param array $option
     * @return bool|array
     */
    public function prepareWithExistCustomer($apiKey, $handle, $customerHandle, $option = array())
    {
        $option['handle'] = $handle;
        $option['customer_handle'] = $customerHandle;

        return $this->create($apiKey, $option);
    }

    /**
     * Settle charge
     * Settle an authorized charge.
     * This is the second step in a two-step payment process with an authorization and subsequent settle.
     * Optionally the amount and order lines of the charge can be adjusted.
     *
     * @param string $apiKey
     * @param string $handle
     * @param array $settle
     * @return bool|array
     */
    public function settle($apiKey, $handle, $settle = array('key' => ''))
    {
        $log = array(
            'method' => __METHOD__,
            'apiKey' => $apiKey,
            'param' => array('handle' => $handle, 'settle' => $settle),
        );

        Mage::helper('reepay')->log("ADMIN settle", Zend_Log::INFO, true);
        Mage::helper('reepay')->log($settle, Zend_Log::INFO, true);

        $response = Mage::helper('reepay/client')->post($apiKey, self::ENDPOINT . "/{$handle}/settle", $settle);
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
     * Cancel charge
     * Cancel an authorized charge. A void of reserved money will be attempted.
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
}
