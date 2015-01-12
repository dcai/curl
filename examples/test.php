<?php
/**
 * @copyright  Dongsheng Cai {@see http://dongsheng.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once('../curl.class.php');

$curl = new dcai\curl();

$r = $curl->get('http://google.com');

echo $r;
var_dump($r);
