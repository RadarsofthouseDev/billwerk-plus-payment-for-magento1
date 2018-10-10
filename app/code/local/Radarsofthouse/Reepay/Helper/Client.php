<?php

class Radarsofthouse_Reepay_Helper_Client extends Mage_Core_Helper_Abstract
{
    /** @var string */
    private $_privateKey;

    const TIMEOUT = 10;

    protected $_requestSuccessful = false;
    protected $_httpError = '';
    protected $_errors = array();
    protected $_lastResponse = array();
    protected $_lastRequest = array();

    /**
     * Get last error
     * @return bool
     */
    public function getHttpError()
    {
        return $this->_httpError ?: false;
    }

    /**
     * Get array of errors
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * Get last response
     *
     * @return mixed
     */
    public function getLastResponse()
    {
        return $this->_lastResponse;
    }

    /**
     * Get last request
     *
     * @return mixed
     */
    public function getLastRequest()
    {
        return $this->_lastRequest;
    }

    /**
     * Get last request status
     *
     * @return bool
     */
    public function success()
    {
        return $this->_requestSuccessful;
    }

    /**
     * Perform a DELETE request
     * @param string $apiKey
     * @param string $endpoint
     * @param array $args
     * @param int $timeout
     * @param bool $isCheckout
     * @return mixed
     * @throws Exception
     */
    public function delete($apiKey, $endpoint, $args = array(), $isCheckout = false, $timeout = self::TIMEOUT)
    {
        $this->_privateKey = $apiKey;

        return $this->request('delete', $endpoint, $args, $isCheckout, $timeout);
    }

    /**
     * Perform a GET request
     * @param string $apiKey
     * @param string $endpoint
     * @param array $args
     * @param int $timeout
     * @param bool $isCheckout
     * @return mixed
     * @throws Exception
     */
    public function get($apiKey, $endpoint, $args = array(), $isCheckout = false, $timeout = self::TIMEOUT)
    {
        $this->_privateKey = $apiKey;

        return $this->request('get', $endpoint, $args, $isCheckout, $timeout);
    }

    /**
     * Perform a PATCH request
     * @param string $apiKey
     * @param string $endpoint
     * @param array $args
     * @param int $timeout
     * @param bool $isCheckout
     * @return mixed
     * @throws Exception
     */
    public function patch($apiKey, $endpoint, $args = array(), $isCheckout = false, $timeout = self::TIMEOUT)
    {
        $this->_privateKey = $apiKey;

        return $this->request('patch', $endpoint, $args, $isCheckout, $timeout);
    }

    /**
     * Perform a POST request
     * @param string $apiKey
     * @param string $endpoint
     * @param array $args
     * @param int $timeout
     * @param bool $isCheckout
     * @return mixed
     * @throws Exception
     */
    public function post($apiKey, $endpoint, $args = array(), $isCheckout = false, $timeout = self::TIMEOUT)
    {
        $this->_privateKey = $apiKey;

        return $this->request('post', $endpoint, $args, $isCheckout, $timeout);
    }

    /**
     * Perform a PUT request
     * @param string $apiKey
     * @param string $endpoint
     * @param array $args
     * @param int $timeout
     * @param bool $isCheckout
     * @return mixed
     * @throws Exception
     */
    public function put($apiKey, $endpoint, $args = array(), $isCheckout = false, $timeout = self::TIMEOUT)
    {
        $this->_privateKey = $apiKey;

        return $this->request('put', $endpoint, $args, $isCheckout, $timeout);
    }

    /**
     * Perform an API request request
     *
     * @param $verb
     * @param $endpoint
     * @param array $args
     * @param int $timeout
     * @param bool $isCheckout
     * @return mixed
     * @throws \Exception
     */
    protected function request($verb, $endpoint, $args = array(), $isCheckout = false, $timeout = self::TIMEOUT)
    {
        if (!function_exists('curl_init') || !function_exists('curl_setopt')) {
            Mage::throwException("cURL support is required, but can't be found.");
        }

        $url = ($isCheckout ? $this->getReepayCheckoutUrl() : $this->getReepayApiUrl()) . $endpoint;

        $response = $this->prepareStateForRequest($verb, $endpoint, $url, $timeout);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($this->_privateKey),
                'X-Magento-Version: ' . Mage::getVersion(),
            )
        );

        curl_setopt($ch, CURLOPT_USERAGENT, 'magento/ba460eaaf82cf719170e3365f63094c4');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        switch ($verb) {
            case 'post':
                curl_setopt($ch, CURLOPT_POST, true);
                $this->attachRequestBody($ch, $args);

                break;
            case 'get':
                $query = http_build_query($args, '', '&');
                curl_setopt($ch, CURLOPT_URL, $url . '?' . $query);

                break;
            case 'delete':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

                break;
            case 'patch':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                $this->attachRequestBody($ch, $args);

                break;
            case 'put':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                $this->attachRequestBody($ch, $args);

                break;
        }

        $responseContent = curl_exec($ch);
        $response['headers'] = curl_getinfo($ch);
        $response = $this->setResponseState($response, $responseContent, $ch);
        $formattedResponse = $this->formatResponse($response, true);
        curl_close($ch);

        $this->isSuccessful($response, $this->formatResponse($response));

        return $formattedResponse;
    }

    /**
     * json_encode data and attach it to the request
     *
     * @param $ch
     * @param $data
     */
    protected function attachRequestBody(&$ch, $data)
    {
        $encoded = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
    }

    /**
     * Reset state prior to request
     *
     * @param $verb
     * @param $endpoint
     * @param $url
     * @param $timeout
     * @return array
     */
    protected function prepareStateForRequest($verb, $endpoint, $url, $timeout)
    {
        $this->_httpError = '';
        $this->_errors = array();

        $this->_requestSuccessful = false;

        $this->_lastResponse = array(
            'headers' => null, // array of details from curl_getinfo()
            'httpHeaders' => null, // array of HTTP headers
            'body' => null, // content of the response
        );

        $this->_lastRequest = array(
            'method' => $verb,
            'endpoint' => $endpoint,
            'url' => $url,
            'body' => '',
            'timeout' => $timeout,
        );

        return $this->_lastResponse;
    }

    /**
     * Set response state
     *
     * @param $response
     * @param $responseContent
     * @param $ch
     * @return mixed
     */
    protected function setResponseState($response, $responseContent, $ch)
    {
        if ($responseContent === false) {
            $this->_httpError = curl_error($ch);
        } else {
            $headerSize = $response['headers']['header_size'];

            $response['httpHeaders'] = $this->getHeadersAsArray(substr($responseContent, 0, $headerSize));
            $response['body'] = substr($responseContent, $headerSize);
            if (isset($response['headers']['request_header'])) {
                $this->_lastRequest['headers'] = $response['headers']['request_header'];
            }
        }

        return $response;
    }

    /**
     * Parse header string and return array of headers
     *
     * @param $headerString
     * @return array
     */
    protected function getHeadersAsArray($headerString)
    {
        $headers = array();

        foreach (explode("\r\n", $headerString) as $i => $line) {
            if ($i === 0) { // HTTP code
                continue;
            }

            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            list($key, $value) = explode(': ', $line);

            $headers[$key] = $value;
        }

        return $headers;
    }

    /**
     *  json_decode response body
     *
     * @param $response
     * @param bool $type
     * @return bool|mixed
     */
    protected function formatResponse($response, $type = false)
    {
        $this->_lastResponse = $response;

        if (!empty($response['body'])) {
            return json_decode($response['body'], $type);
        }

        return false;
    }

    /**
     * Determine if request succeeded
     * @param $response
     * @param $formattedResponse
     * @return bool
     */
    protected function isSuccessful($response, $formattedResponse)
    {
        $status = 410;

        //Get HTTP status code
        if (!empty($response['headers']) && isset($response['headers']['http_code'])) {
            $status = $response['headers']['http_code'];
        } elseif (!empty($response['body']) && isset($formattedResponse->http_status)) {
            $status = $formattedResponse->http_status;
        }

        if ($status >= 200 && $status <= 299) {
            $this->_requestSuccessful = true;

            return true;
        }

        if (isset($formattedResponse->error)) {
            $this->_httpError = sprintf('%d: %s', $formattedResponse->http_status, $formattedResponse->http_reason);
            $this->_errors = array(
                'code' => property_exists($formattedResponse, 'code') ? $formattedResponse->code : '',
                'error' => property_exists($formattedResponse, 'error') ? $formattedResponse->error : '',
                'message' => property_exists($formattedResponse, 'message') ? $formattedResponse->message : '',
            );

            return false;
        }

        return false;
    }

    /**
     * Get API url
     *
     * @return string
     */
    protected function getReepayApiUrl()
    {
        return 'https://api.reepay.com/v1/';
    }

    /**
     * Get API Checkout url
     *
     * @return string
     */
    protected function getReepayCheckoutUrl()
    {
        return 'https://checkout-api.reepay.com/v1/';
    }
}
