<?php
/**
 * @copyright  Dongsheng Cai {@see http://dongsheng.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once('../curl.class.php');

/**
 * Simple XMLRPC client
 *
 * @copyright  Dongsheng Cai {@see http://dongsheng.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
class xmlrpc_client {
    private $url;
    function __construct($url, $autoload=true) {
        $this->url = $url;
        $this->connection = new curl(array('debug'=>true));
        $this->methods = array();
        if ($autoload) {
            $resp = $this->call('system.listMethods', null);
            $this->methods = $resp;
            print_r($resp);
        }
    }
    public function call($method, $params = null) {
        $post = xmlrpc_encode_request($method, $params);
        return xmlrpc_decode($this->connection->post($this->url, $post));
    }
}

header('Content-Type: text/plain');
$rpc = 'http://log.dongsheng.org/xmlrpc.php';
$client = new xmlrpc_client($rpc, true);
//$resp = $client->call('methodname', array());
//print_r($resp);
