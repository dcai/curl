<?php
/**
 * @copyright  Dongsheng Cai {@see http://dongsheng.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once(dirname(__DIR__) . '/src/dcai/curl.php');

$curl = new dcai\curl();

echo $curl::VERSION;
