<?php
require_once(dirname(dirname(__FILE__)) . '/vendor/autoload.php');

class CurlTest extends PHPUnit_Framework_TestCase {
    public function testSayHello() {
        $this->assertEquals("H", strtoupper('h'));
        $this->assertEquals("A", strtoupper('a'));
    }
}
