<?php

class Radarsofthouse_Reepay_Helper_Refund extends Mage_Core_Helper_Abstract
{
    const ENDPOINT = 'refund';

    /**
     * Get refund by ID
     *
     * @param string $apiKey
     * @param string $handle
     * @return array|bool
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
     * Create refund
     *
     * @param string $apiKey
     * @param array $refund
     * @return bool|array
     */
    public function create($apiKey, $refund)
    {
        $log = array(
            'method' => __METHOD__,
            'apiKey' => $apiKey,
            'param' => array('refund' => $refund),
        );
        $response = Mage::helper('reepay/client')->post($apiKey, self::ENDPOINT, $refund);
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
}
