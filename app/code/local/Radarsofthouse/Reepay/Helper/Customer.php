<?php

class Radarsofthouse_Reepay_Helper_Customer extends Mage_Core_Helper_Abstract
{
    const ENDPOINT = 'customer';


    /**
     * Get customer by email.
     *
     * @param $apiKey
     * @param $email
     * @return false|string
     */
    public function search($apiKey, $email){
        $log = ['param' => ['email' => $email]];
        $param = [
            'page'=> 1,
            'size'=> 20,
            'search'=> "email:{$email}",
        ];
        if(empty($email)){
            $log['input_error'] = 'empty email.';
            Mage::helper('reepay')->log(json_encode($log), Zend_Log::INFO, true);
            return false;
        }
        try {
            $response = Mage::helper('reepay/client')->get($apiKey, self::ENDPOINT,$param);
            $log ['response'] = $response;
            Mage::helper('reepay')->log(json_encode($log), Zend_Log::INFO, true);
            if (Mage::helper('reepay/client')->success() && array_key_exists('count', $response) && (int)$response['count'] > 0) {
                foreach ($response['content'] as $index => $item) {
                    if(!array_key_exists('deleted', $item) || empty($item['deleted'])) {
                        return $item['handle'];
                    }
                }
            }
        } catch (\Exception $e) {
            $log['exception_error'] = $e->getMessage();
            $log['http_errors'] = Mage::helper('reepay/client')->getHttpError();
            $log['response_errors'] = Mage::helper('reepay/client')->getErrors();
            Mage::helper('reepay')->log(json_encode($log), Zend_Log::ERR, true);
        }
        return false;
    }

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

}
