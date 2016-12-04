<?php
/**
 * @copyright  Dongsheng Cai {@see http://dongsheng.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once(dirname(__DIR__) . '/vendor/autoload.php');

$curl = new dcai\curl();

$resp = $curl->get('http://httpbin.org/get');

echo $resp;
