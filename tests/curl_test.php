<?php
require_once(dirname(dirname(__FILE__)) . '/vendor/autoload.php');

class CurlTest extends PHPUnit_Framework_TestCase {
    public function testGet() {
        $http = new \dcai\curl;
        $response = $http->get('http://httpbin.org/get', array('test'=>1));
        $json = $response->json();
        $this->assertEquals($json->args->test, 1);
    }
    public function testPost() {
        $http = new \dcai\curl;
        $response = $http->post('http://httpbin.org/post', array('test'=>2));
        $json = $response->json();
        $this->assertEquals($json->form->test, 2);
    }
    public function testUpload() {
        $http = new \dcai\curl();
        $postData = array(
            'afile' =>
                \dcai\curl::makeUploadFile(realpath(__DIR__ . '/assets/uploadtest.txt'))
        );
        $response = $http->post('http://httpbin.org/post', $postData);
        $json = $response->json();
        $this->assertEquals(trim($json->files->afile), 'upload test');
    }
    public function testHeaders() {
        $http = new \dcai\curl();
        $http->appendRequestHeader('oauthtoken', 'supersecret');
        $response = $http->get('http://httpbin.org/headers');
        $this->assertEquals(trim($response->json()->headers->Oauthtoken), 'supersecret');
    }
    public function testPostData() {
        $http = new \dcai\curl;
        $processed = $http->makePostFields(array(
            'hello' => 'world',
            'afile' => '@' . realpath(__DIR__ . '/assets/uploadtest.txt'),
            'nestedlist' => array(
                'name2' => array(
                    1,
                    2,
                    3
                ),
            ),
            'coollist' => array(
                'xbox one',
                'ps4',
                'wii',
            ),
        ));
        //$this->assertEquals($json->form->test, 2);
    }
}
