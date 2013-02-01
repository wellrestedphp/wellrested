WellRESTed
==========

WellRESted provides classes to help you create RESTful APIs and work with HTTP requests and responses.



Requirements
------------

- PHP 5.3
- [Composer](http://getcomposer.org/) for autoloading
- [PHP cURL](http://php.net/manual/en/book.curl.php) for making requests



Install
-------

Include the following to your composer.json file's **require** property.

```json
    "require": {
        "pjdietz/wellrested": "dev-master"
    }
```

Use Composer to download and install WellRESTed.

```bash
$ curl -s https://getcomposer.org/installer | php
$ php composer.phar install
```


Examples
--------

### Responses

Build a response and use it to send output.

```php
$resp = new \pjdietz\WellRESTed\Response();
$resp->statusCode = 200;
$resp->setHeader('Content-type', 'text/plain');
$resp->body = 'Hello world!';
$resp->respond();
exit;
```

### Requests

You can access a Request singleton instance that represents the request sent to the current script.

```php
$rqst = \pjdietz\WellRESTed\Request::getRequest();

if ($rqst->method === 'PUT') {
    $obj = json_decode($rqst->body);
    // Do something with the JSON sent as the message body.
}
```

The Request class can also make a request to another server and provide the response as a Respones object. (This feature requires [PHP's cURL](http://php.net/manual/en/book.curl.php).)

```php
// Prepare a request.
$rqst = new \pjdietz\WellRESTed\Request();
$rqst->uri = 'http://my.api.local/resources/';
$rqst->method = 'POST';
$rqst->body = json_encode($newResource);

// Make the request.
$resp = $rqst->request();

// Read the response.
if ($resp->statusCode === 201) {
    // The new resource was created.
    $newResource = json_decode($resp->body);
}
```

### URIs and Routing

WellRESTed also provides several classes to facilitate working with resource-based URIs. You can create your own regular expressions to match the URIs, or you can use URI templates.

Here's an example of a Router subclass which examines the request URI, compares it against a series of URI templates, and matches the request to a particular Handler class.

For more information on URI templates, see [RFC 6570](http://tools.ietf.org/html/rfc6570).

```php
/**
 * Loads and instantiates handlers based on URI.
 */
class MyRouter extends \pjdietz\WellRESTed\Router
{
    public function __construct()
    {
        parent::__construct();

        // Match any request to the URI "/things/"
        // Send it to a handler to collections of thing objects.
        $this->addRoute(Route::newFromUriTemplate('/things/', 'ThingCollectionHandler'));

        // Match any request to "/things/" followed by a variable.
        // Send the request to a handler for one thing object.
        // The ThingItemHandler will receive an array containing an "id" key
        // and the value from the URI.
        $this->addRoute(Route::newFromUriTemplate('/things/{id}', 'ThingItemHandler'));

        // ...Add as manu routes as you like here...
    }
}

```

More explamples
---------------

For more examples, see the project [pjdietz/wellRrested-samples](https://github.com/pjdietz/wellrested-samples).

