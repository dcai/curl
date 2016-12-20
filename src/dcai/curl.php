<?php
namespace dcai;

/**
 * RESTful cURL class
 *
 * This is a wrapper class for curl.
 *
 * @copyright  Dongsheng Cai {@see http://dongsheng.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

if (!function_exists('curl_file_create')) {
    function curl_file_create($filename, $mimetype = '', $postname = '') {
        return "@$filename;filename="
            . ($postname ? '' : basename($filename))
            . ($mimetype ? ";type=$mimetype" : '');
    }
}

class curl {
    const DEFAULT_USERPWD = 'anonymous: anonymous@domain.com';
    /** @var bool */
    public $proxy = false;
    /** @var object */
    public $cacheInstance = null;
    /** @var array */
    public $responseHeaders = array();
    public $requestHeaders  = array();
    /** @var string */
    public $info;
    public $error;
    /** @var array */
    private $curlOptions;
    private $curlInstance;
    /** @var bool */
    private $debug = false;
    /** @var string */
    private $cookiePath = null;

    /**
     * @param array $options
     */
    public function __construct($options = array()) {
        if (!function_exists('curl_init')) {
            throw new Exception('cURL module must be enabled!');
        }
        // the options of curl should be init here.
        $this->initializeCurlOptions();
        if (!empty($options['debug'])) {
            $this->debug = true;
        }
        if(!empty($options['cookie'])) {
            if($options['cookie'] === true) {
                $this->cookiePath = 'curl_cookie.txt';
            } else {
                $this->cookiePath = $options['cookie'];
            }
        }
        if (!empty($options['cache'])) {
            if (class_exists('curl_cache')) {
                $this->cacheInstance = new curl_cache();
            }
        }
    }

    /**
     * HTTP GET method
     *
     * @param string $url
     * @param array $params
     * @param array $curlOptions
     * @return bool
     */
    public function get($url, $params = array(), $curlOptions = array()) {
        $curlOptions['CURLOPT_HTTPGET'] = 1;

        if (!empty($params)){
            $url .= (stripos($url, '?') !== false) ? '&' : '?';
            $url .= http_build_query($params, '', '&');
        }
        return $this->request($url, $curlOptions);
    }

    /**
     * HTTP POST method
     *
     * @param string $url
     * @param array|string $params
     * @param array $curlOptions
     * @return bool
     */
    public function post($url, $params = '', $curlOptions = array()) {
        if (is_array($params)) {
            $params = $this->makePostFields($params);
        }
        $curlOptions['CURLOPT_POST'] = 1;
        $curlOptions['CURLOPT_POSTFIELDS'] = $params;
        return $this->request($url, $curlOptions);
    }

    /**
     * HTTP PUT method
     *
     * @param string $url
     * @param array $params
     * @param array $curlOptions
     * @return bool
     */
    public function put($url, $params = array(), $curlOptions = array()) {
        $file = $params['file'];
        if (!is_file($file)){
            return null;
        }
        $fp   = fopen($file, 'r');
        $size = filesize($file);
        $curlOptions['CURLOPT_PUT']        = 1;
        $curlOptions['CURLOPT_INFILESIZE'] = $size;
        $curlOptions['CURLOPT_INFILE']     = $fp;
        if (!isset($this->curlOptions['CURLOPT_USERPWD'])){
            $curlOptions['CURLOPT_USERPWD'] = self::DEFAULT_USERPWD;
        }
        $ret = $this->request($url, $curlOptions);
        fclose($fp);
        return $ret;
    }

    /**
     * HTTP DELETE method
     *
     * @param string $url
     * @param array $params
     * @param array $curlOptions
     * @return bool
     */
    public function delete($url, $param = array(), $curlOptions = array()) {
        $curlOptions['CURLOPT_CUSTOMREQUEST'] = 'DELETE';
        if (!isset($curlOptions['CURLOPT_USERPWD'])) {
            $curlOptions['CURLOPT_USERPWD'] = self::DEFAULT_USERPWD;
        }
        $ret = $this->request($url, $curlOptions);
        return $ret;
    }
    /**
     * HTTP TRACE method
     *
     * @param string $url
     * @param array $curlOptions
     * @return bool
     */
    public function trace($url, $curlOptions = array()) {
        $curlOptions['CURLOPT_CUSTOMREQUEST'] = 'TRACE';
        $ret = $this->request($url, $curlOptions);
        return $ret;
    }
    /**
     * HTTP OPTIONS method
     *
     * @param string $url
     * @param array $curlOptions
     * @return bool
     */
    public function options($url, $curlOptions = array()) {
        $curlOptions['CURLOPT_CUSTOMREQUEST'] = 'OPTIONS';
        $ret = $this->request($url, $curlOptions);
        return $ret;
    }

    /**
     * HTTP HEAD method
     *
     * @see request()
     *
     * @param string $url
     * @param array $options
     * @return bool
     */
    public function head($url, $options = array()) {
        $curlOptions['CURLOPT_HTTPGET'] = 0;
        $curlOptions['CURLOPT_HEADER']  = 1;
        $curlOptions['CURLOPT_NOBODY']  = 1;
        return $this->request($url, $curlOptions);
    }

    /**
     * Download multiple files in parallel
     *
     * Calls {@link multi()} with specific download headers
     *
     * <code>
     * $c = new \dcai\curl;
     * $c->download(array(
     *              array('url'=>'http://localhost/', 'file'=>fopen('a', 'wb')),
     *              array('url'=>'http://localhost/20/', 'file'=>fopen('b', 'wb'))
     *              ));
     * </code>
     *
     * @param array $requests An array of files to request
     * @param array $options An array of options to set
     * @return array An array of results
     */
    public function download($requests, $options = array()) {
        $options['CURLOPT_BINARYTRANSFER'] = 1;
        $options['RETURNTRANSFER'] = false;
        return $this->multi($requests, $options);
    }

    /**
     * Reset Cookie
     */
    public function purgeCookies() {
        if (!empty($this->cookiePath)) {
            if (is_file($this->cookiePath)) {
                $fp = fopen($this->cookiePath, 'w');
                if (!empty($fp)) {
                    fwrite($fp, '');
                    fclose($fp);
                }
            }
        }
    }

    /**
     * Set curl option
     *
     * @param string $name
     * @param string $value
     */
    public function AddCurlOption($name, $value) {
        if (stripos($name, 'CURLOPT_') === false) {
            $name = strtoupper('CURLOPT_' . $name);
        }
        $this->curlOptions[$name] = $value;
    }

    /**
     * Set curl options
     *
     * @param array $curlOptions If array is null, this function will
     *                       reset the options to default value.
     */
    public function addCurlOptions($curlOptions = array()) {
        if (is_array($curlOptions)) {
            foreach($curlOptions as $name => $val){
                $this->addCurlOption($name, $val);
            }
        }
    }
    /**
     * Reset http method
     *
     */
    public function resetCurlOptions() {
        unset($this->curlOptions['CURLOPT_HTTPGET']);
        unset($this->curlOptions['CURLOPT_POST']);
        unset($this->curlOptions['CURLOPT_POSTFIELDS']);
        unset($this->curlOptions['CURLOPT_PUT']);
        unset($this->curlOptions['CURLOPT_INFILE']);
        unset($this->curlOptions['CURLOPT_INFILESIZE']);
        unset($this->curlOptions['CURLOPT_CUSTOMREQUEST']);
    }

    public function appendRequestHeader($key, $value) {
        $this->requestHeaders[] = ["$key", "$value"];
    }
    /**
     * Set HTTP Request Header
     *
     * @param array $headers
     */
    public function appendRequestHeaders(array $headers) {
        foreach ($headers as $header) {
            $this->appendRequestHeader($header[0], $header[1]);
        }
    }

    public function setRequestHeaders(array $headers) {
        $this->requestHeaders = $headers;
    }

    /**
     * Set HTTP Response Header
     */
    public function getResponseHeaders() {
        return $this->responseHeaders;
    }

    /*
     * Mulit HTTP Requests
     * This function could run multi-requests in parallel.
     *
     * @param array $requests An array of files to request
     * @param array $options An array of options to set
     * @return array An array of results
     */
    protected function multi($requests, $options = array()) {
        $count   = count($requests);
        $handles = array();
        $results = array();
        $main    = curl_multi_init();
        for ($i = 0; $i < $count; $i++) {
            $url = $requests[$i];
            foreach($url as $n=>$v){
                $options[$n] = $url[$n];
            }
            $handles[$i] = curl_init($url['url']);
            // Clean up
            $this->resetCurlOptions();
            $this->prepareRequest($handles[$i], $options);
            curl_multi_add_handle($main, $handles[$i]);
        }
        $running = 0;
        do {
            curl_multi_exec($main, $running);
        } while($running > 0);
        for ($i = 0; $i < $count; $i++) {
            if (!empty($options['CURLOPT_RETURNTRANSFER'])) {
                $results[] = true;
            } else {
                $results[] = curl_multi_getcontent($handles[$i]);
            }
            curl_multi_remove_handle($main, $handles[$i]);
        }
        curl_multi_close($main);
        return $results;
    }

    /**
     * Single HTTP Request
     *
     * @param string $url The URL to request
     * @param array $options
     * @return bool
     */
    protected function request($url, $curlOptions = array()) {
        // create curl instance
        $curl = curl_init($url);
        $curlOptions['url'] = $url;
        $this->resetCurlOptions();
        $this->prepareRequest($curl, $curlOptions);
        if ($this->cacheInstance && $httpbody = $this->cacheInstance->get($this->curlOptions)) {
            return $httpbody;
        } else {
            $httpbody = curl_exec($curl);
            if ($this->cacheInstance) {
                $this->cacheInstance->set($this->curlOptions, $httpbody);
            }
        }

        $this->info  = curl_getinfo($curl);
        $this->error = curl_error($curl);

        if ($this->debug){
            var_dump($this->info);
            var_dump($this->error);
        }

        curl_close($curl);

        $response = new CurlHttpResponse($this->info['http_code'], $this->responseHeaders, $httpbody);

        if (!empty($this->error)) {
            throw new Exception($this->error);
        }
        return $response;
    }

    /**
     * Transform a PHP array into POST parameter
     *
     * @param array $postdata
     * @return array containing all POST parameters  (1 row = 1 POST parameter)
     */
    public function makePostFields($postdata) {
        if (is_object($postdata) && !self::isCurlFile($postdata)) {
            $postdata = (array)$postdata;
        }
        $postFields = array();
        foreach ($postdata as $name => $value) {
            $name = urlencode($name);
            if (is_object($value) && !self::isCurlFile($value)) {
                $value = (array)$value;
            }
            if (is_array($value) && !self::isCurlFile($value)) {
                $postFields = $this->makeArrayField($name, $value, $postFields);
            } else {
                $postFields[$name] = $value;
            }
        }
        return $postFields;
    }

    public function getInfo() {
        return $this->info;
    }

    public static function makeUploadFile($filepath, $filename = '', $mimetype = '') {
        return curl_file_create($filepath, $filename, $mimetype);
    }

    /**
     * Resets the CURL options that have already been set
     */
    private function initializeCurlOptions() {
        $this->curlOptions = [
            'CURLOPT_USERAGENT' => 'cURL',
            // True to include the header in the output
            'CURLOPT_HEADER' => 0,
            // True to Exclude the body from the output
            'CURLOPT_NOBODY' => 0,
            // TRUE to follow any "Location: " header that the server
            // sends as part of the HTTP header (note this is recursive,
            // PHP will follow as many "Location: " headers that it is sent,
            // unless CURLOPT_MAXREDIRS is set).
            //$this->curlOptions['CURLOPT_FOLLOWLOCATION'] = 1;
            'CURLOPT_MAXREDIRS' => 10,
            'CURLOPT_ENCODING' => '',
            // TRUE to return the transfer as a string of the return
            // value of curl_exec() instead of outputting it out directly.
            'CURLOPT_RETURNTRANSFER' => 1,
            'CURLOPT_BINARYTRANSFER' => 0,
            'CURLOPT_SSL_VERIFYPEER' => 0,
            'CURLOPT_SSL_VERIFYHOST' => 2,
            'CURLOPT_CONNECTTIMEOUT' => 30,
        ];
    }

    /**
     * Recursive function formating an array in POST parameter
     *
     * @param array $arraydata - the array that we are going to format and add into &$data array
     * @param string $currentdata - a row of the final postdata array at instant T
     *                when finish, it's assign to $data under this format: name[keyname][][]...[]='value'
     * @param array $data - the final data array containing all POST parameters : 1 row = 1 parameter
     */
    private function makeArrayField($fieldname, $arrayData, $postFields) {
        foreach ($arrayData as $key => $value) {
            $key = urlencode($key);
            if (is_object($value)) {
                $value = (array)$value;
            }
            if (is_array($value)) { //the value is an array, call the function recursively
                $newfieldname = $fieldname . "[$key]";
                $postFields = $this->makeArrayField($newfieldname, $value, $postFields);
            } else {
                $postFields[] = $fieldname . "[$key]=" . urlencode($value);
            }
        }
        return $postFields;
    }

    /**
     * private callback function
     * Formatting HTTP Response Header
     *
     * @param mixed $ch Apparently not used
     * @param string $header
     * @return int The strlen of the header
     */
    private function handleResponseHeaders($ch, $header) {
        //$this->count++;
        if (strlen($header) > 2) {
            list($key, $value) = explode(" ", rtrim($header, "\r\n"), 2);
            $key = rtrim($key, ':');
            if (!empty($this->responseHeaders[$key])) {
                if (is_array($this->responseHeaders[$key])){
                    $this->responseHeaders[$key][] = $value;
                } else {
                    $tmp = $this->responseHeaders[$key];
                    $this->responseHeaders[$key] = array();
                    $this->responseHeaders[$key][] = $tmp;
                    $this->responseHeaders[$key][] = $value;

                }
            } else {
                $this->responseHeaders[$key] = $value;
            }
        }
        return strlen($header);
    }

    /**
     * Set options for individual curl instance
     *
     * @param object $curl A curl handle
     * @param array $options
     * @return object The curl handle
     */
    private function prepareRequest($curl, $curlOptions) {
        // set cookie
        if (!empty($this->cookiePath) || !empty($curlOptions['cookie'])) {
            $this->addCurlOption('cookiejar', $this->cookiePath);
            $this->addCurlOption('cookiefile', $this->cookiePath);
        }

        // set proxy
        if (!empty($this->proxy) || !empty($curlOptions['proxy'])) {
            $this->addCurlOptions($this->proxy);
        }

        $this->addCurlOptions($curlOptions);
        // set headers
        if (empty($this->requestHeaders)){
            $this->appendRequestHeaders(array(
                ['User-Agent', $this->curlOptions['CURLOPT_USERAGENT']],
                ['Accept-Charset', 'UTF-8']
            ));
        }

        self::applyCurlOption($curl, $this->curlOptions);
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, array(&$this, 'handleResponseHeaders'));
        curl_setopt($curl, CURLOPT_HTTPHEADER, self::prepareRequestHeaders($this->requestHeaders));

        if ($this->debug){
            var_dump($this->curlOptions);
            var_dump($this->requestHeaders);
        }
        return $curl;
    }

    private static function applyCurlOption($curl, $curlOptions) {
        // Apply curl options
        foreach($curlOptions as $name => $value) {
            if (is_string($name)) {
                curl_setopt($curl, constant(strtoupper($name)), $value);
            }
        }
    }

    private static function prepareRequestHeaders($headers) {
        $processedHeaders = array();
        foreach ($headers as $header) {
            $processedHeaders[] = urlencode($header[0]) . ': ' . urlencode($header[1]);
        }
        return $processedHeaders;
    }

    private static function isCurlFile($field) {
        return is_object($field) ? get_class($field) === 'CURLFile' : false;
    }

}

class CurlHttpResponse {
    public $headers = array();
    public $statusCode;
    public $text = '';

    public function __construct($statusCode, $headers, $text) {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->text = $text;
    }

    public function __toString() {
        return $this->text;
    }

    public function text() {
        return $this->text;
    }

    public function json() {
        return json_decode($this->text);
    }
}

/**
 * This class is used by cURL class, use case:
 *
 * $c = new curl(array('cache'=>true), 'module_cache'=>'repository');
 * $ret = $c->get('http://www.google.com');
 *
 * @copyright  Dongsheng Cai {@see http://dongsheng.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
class curl_cache {
    /** @var string */
    public $dir = '';
    /**
     *
     * @param string @module which module is using curl_cache
     *
     */
    function __construct() {
        $this->dir = '/tmp/';
        if (!file_exists($this->dir)) {
            mkdir($this->dir, 0700, true);
        }
        $this->ttl = 1200;
    }

    /**
     * Get cached value
     *
     * @param mixed $param
     * @return bool|string
     */
    public function get($param) {
        $this->cleanup($this->ttl);
        $filename = 'u_'.md5(serialize($param));
        if(file_exists($this->dir.$filename)) {
            $lasttime = filemtime($this->dir.$filename);
            if(time()-$lasttime > $this->ttl)
            {
                return false;
            } else {
                $fp = fopen($this->dir.$filename, 'r');
                $size = filesize($this->dir.$filename);
                $content = fread($fp, $size);
                return unserialize($content);
            }
        }
        return false;
    }

    /**
     * Set cache value
     *
     * @param mixed $param
     * @param mixed $val
     */
    public function set($param, $val) {
        $filename = 'u_'.md5(serialize($param));
        $fp = fopen($this->dir.$filename, 'w');
        fwrite($fp, serialize($val));
        fclose($fp);
    }

    /**
     * Remove cache files
     *
     * @param int $expire The number os seconds before expiry
     */
    public function cleanup($expire) {
        if($dir = opendir($this->dir)){
            while (false !== ($file = readdir($dir))) {
                if(!is_dir($file) && $file != '.' && $file != '..') {
                    $lasttime = @filemtime($this->dir.$file);
                    if(time() - $lasttime > $expire){
                        @unlink($this->dir.$file);
                    }
                }
            }
        }
    }
    /**
     * delete current user's cache file
     *
     */
    public function refresh() {
        if($dir = opendir($this->dir)){
            while (false !== ($file = readdir($dir))) {
                if(!is_dir($file) && $file != '.' && $file != '..') {
                    if(strpos($file, 'u_')!==false){
                        @unlink($this->dir.$file);
                    }
                }
            }
        }
    }
}
