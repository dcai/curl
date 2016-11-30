# curl wrapper class for PHP

A PHP wrapper class to simplify [PHP cURL Library](http://php.net/manual/en/book.curl.php).

## How to use
Following code shows how to use this class

```php
$http = new dcai\curl;
// enable cache
$http = new dcai\curl(array('cache'=>true));
// enable cookie
$http = new dcai\curl(array('cookie'=>true));
// enable proxy
$http = new dcai\curl(array('proxy'=>true));

// HTTP GET
$response = $http->get('http://example.com');
// HTTP POST
$response = $http->post('http://example.com/', array('q'=>'words', 'name'=>'moodle'));
// POST RAW
$xml = '<action>perform</action>';
$response = $http->post('http://example.com/', $xml);
// HTTP PUT
$response = $http->put('http://example.com/', array('file'=>'/var/www/test.txt');
```

[![Build Status](https://travis-ci.org/dcai/curl.png)](https://travis-ci.org/dcai/curl)
