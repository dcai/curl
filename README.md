# cURL wrapper class for PHP

This is a PHP class to make cURL library easier to use

## How to use
Following code shows how to use this class

```php
$c = new curl;
// enable cache
$c = new curl(array('cache'=>true));
// enable cookie
$c = new curl(array('cookie'=>true));
// enable proxy
$c = new curl(array('proxy'=>true));

// HTTP GET Method
$html = $c->get('http://example.com');
// HTTP POST Method
$html = $c->post('http://example.com/', array('q'=>'words', 'name'=>'moodle'));
// HTTP PUT Method
$html = $c->put('http://example.com/', array('file'=>'/var/www/test.txt');
```

[![Build Status](https://travis-ci.org/dcai/cURL.png)](https://travis-ci.org/dcai/cURL)
