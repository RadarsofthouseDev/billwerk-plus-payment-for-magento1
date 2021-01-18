<?php
class Radarsofthouse_Reepay_Adminhtml_ReepaywebhooksController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Return some checking result
     *
     * @return void
     */
    public function updateAction()
    {
        $lastUpdateTime = Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s');
        $result = ['success' => false, 'time' => $lastUpdateTime];
        $urls = null;
        $urlsTest = null;
        try{
            $storeId = Mage::app()->getStore()->getId();
            $apiKey = Mage::getStoreConfig('payment/reepay/private_key', $storeId);
            $apiKeyTest = Mage::getStoreConfig('payment/reepay/private_key_test', $storeId);
            $log = ['store_id'=>$storeId, 'api_key'=>$apiKey, 'api_key_test'=>$apiKeyTest];
            Mage::helper('reepay')->log(json_encode($log), Zend_Log::INFO, false);
            if(!empty($apiKey)){
                $urls = Mage::helper('reepay/webhook')->updateUrl($apiKey);
            }
            if(!empty($apiKeyTest)){
                $urlsTest = Mage::helper('reepay/webhook')->updateUrl($apiKeyTest);
            }
            $log = ['urls'=>$urls, 'urls_test'=>$urlsTest];
            Mage::helper('reepay')->log(json_encode($log), Zend_Log::INFO, false);
            if(($urls !== false && $urls !== null) || ($urlsTest !== false && $urlsTest !== null)){
                $result = ['success' => true, 'urls'=>$urls, 'urls_test'=>$urlsTest, 'time' => $lastUpdateTime];
            }
        }catch (\Exception $exception){
            $log = ['error'=> $exception->getMessage()];
            Mage::helper('reepay')->log(json_encode($log), Zend_Log::ERR, false);
        }
        Mage::app()->getResponse()->setHeader('Content-type','application/json',true)->setBody(json_encode($result));
    }
}