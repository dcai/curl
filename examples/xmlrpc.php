<?php
namespace dcai;
/**
 * @copyright  Dongsheng Cai {@see http://dongsheng.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once(dirname(__DIR__) . '/vendor/autoload.php');

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
            $resp = $this->call('flickr.test.echo', null);
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
$rpc = 'https://api.flickr.com/services/xmlrpc/';
$client = new xmlrpc_client($rpc, true);
//$resp = $client->call('methodname', array());
//print_r($resp);
