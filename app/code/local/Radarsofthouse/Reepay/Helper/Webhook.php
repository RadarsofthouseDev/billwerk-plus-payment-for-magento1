<?php

class Radarsofthouse_Reepay_Helper_Webhook extends Mage_Core_Helper_Abstract
{
    const ENDPOINT = 'account/webhook_settings';

    /** Update webhook url.
     * @param $apiKey
     * @return bool
     */
    public function getUrl($apiKey)
    {
        $param = [];
        $log = ['param' => $param];
        try {
            $response = Mage::helper('reepay/client')->get($apiKey, self::ENDPOINT, $param);
            $log['response'] = $response;
            Mage::helper('reepay')->log(json_encode($log), Zend_Log::INFO, true);
            if (Mage::helper('reepay/client')->success()) {
                return $response['urls'];
            }
        } catch (\Exception $e) {
            $log['exception_error'] = $e->getMessage();
            $log['http_errors'] = Mage::helper('reepay/client')->getHttpError();
            $log['response_errors'] = Mage::helper('reepay/client')->getErrors();
            Mage::helper('reepay')->log(json_encode($log), Zend_Log::ERR, true);
        }
        return false;
    }

    /** Update webhook url.
     * @param $apiKey
     * @return bool
     */
    public function updateUrl($apiKey)
    {
        $DefaultStoreId = Mage::app()->getWebsite(true)->getDefaultGroup()->getDefaultStoreId();
        $url = Mage::app()->getStore($DefaultStoreId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
        $url .= substr($url, -1) === '/' ? '' : '/';
        $url .= 'reepay/webhooks/index';

        $urls = [$url];
        $currentUrls = $this->getUrl($apiKey);
        if($currentUrls !== false && !empty($currentUrls)){
            $urls = $currentUrls;
            $isExistUrl = array_search($url, $currentUrls);
            if($isExistUrl === false){
                $urls[] = $url;
            }
        }

        $param = [
            'urls' => $urls,
            'disabled' => false,
        ];
        $log = ['param' => $param];

        $response = Mage::helper('reepay/client')->put($apiKey, self::ENDPOINT, $param);
        $log['response'] = $response;
        Mage::helper('reepay')->log(json_encode($log), Zend_Log::INFO, true);
        if (Mage::helper('reepay/client')->success()) {
            return $response['urls'];
        }
        $log['http_errors'] = Mage::helper('reepay/client')->getHttpError();
        $log['response_errors'] = Mage::helper('reepay/client')->getErrors();
        Mage::helper('reepay')->log(json_encode($log), Zend_Log::ERR, true);
        return false;
    }

}
