# Handlers


[`Handler`](../src/pjdietz/WellRESTed/Handler.php) is an abstract base class for you to subclass to create controllers for generating responses given requests.

## Instance Members

Your [`Handler`](../src/pjdietz/WellRESTed/Handler.php) subclass has access to three protected members:

Member     | Type | Description
---------- | ---- | -----------
`args`     | `array` | Map of variables to supplement the request, usually path variables.
`request`  | [`RequestInterface`](../src/pjdietz/WellRESTed/Interfaces/RequestInterface.php) | The HTTP request to respond to.
`response` | [`ResponseInterface`](../src/pjdietz/WellRESTed/Interfaces/ResponseInterface.php) | The HTTP response to send based on the request.


## HTTP Verbs

Most of the action takes place inside the methods called in response to specific HTTP verbs. For example, to handle a `GET` request, implement the `get` method.

```php
class CatsCollectionHandler extends \pjdietz\WellRESTed\Handler
{
    protected function get()
    {
        // Read some cats from the database, cache, whatever.
        // ...read these an array as the variable $cats.

        // Set the values for the instance's response member. This is what the
        // Router will eventually output to the client.
        $this->response->setStatusCode(200);
        $this->response->setHeader("Content-Type", "application/json");
        $this->response->setBody(json_encode($cats));
    }
}
```

Implement the methods that you want to support. If you don't want to support `POST`, don't implement it. The default behavior is to respond with `405 Method Not Allowed` for most verbs.

The methods available to implement are:

HTTP Verb | Method    | Default behavior
--------- | --------- | ----------------------
`GET`     | `get`     | 405 Method Not Allowed
`HEAD`    | `head`    | Call `get`, then clean the response body
`POST`    | `post`    | 405 Method Not Allowed
`PUT`     | `put`     | 405 Method Not Allowed
`DELETE`  | `delete`  | 405 Method Not Allowed
`PATCH`   | `patch`   | 405 Method Not Allowed
`OPTIONS` | `options` | Add `Allow` header, if able

### `OPTIONS` requests and `Allowed` headers

An `OPTIONS` request to your endpoint should result in the API responding with an `Allow` header listing the verbs the endpoint supports. For example:

```
HTTP/1.1 200 OK
Allow: GET, HEAD, POST, OPTIONS
Content-Length: 0
```

To support `OPTIONS` requests, implement `getAllowedMethods` and return an array of the methods you support. For a handler that supports the methods in the example response, do this:

```php
protected function getAllowedMethods()
{
    return array("GET", "HEAD", "POST", "OPTIONS");
}
```

You do not need to implement `options`. `options` by default calls `getAllowedMethods`. If it gets a return value, it sets the status code to `200 OK` and adds the `Allow` header. Otherwise, it responds `405 Method Not Allowed`.

### Custom Verbs

To support custom verbs, redefine the `buildResponse`. To respond to the custom verb `SNIFF`, to this:

```php
protected function buildResponse()
{
    switch ($this->request->getMethod()) {
        case 'SNIFF':
            // Assuming you also made a sniff() method...
            $this->sniff();
            break;
        default:
            // Let the parent's method do the rest.
            self::buildResponse();
    }
}
```

## HttpExceptions

Another useful feature of the [`Handler`](../src/pjdietz/WellRESTed/Handler.php) class is that it catches exceptions deriving from [`HttpException`](../src/pjdietz/WellRESTed/Exceptions/HttpExceptions.php) and turns them into responses. [`HttpException`](../src/pjdietz/WellRESTed/Exceptions/HttpExceptions.php) and its subclasses provide the status code and description for simple error responses.

For example, you can throw a `NotFountException` if the resource the request indicates does not exist.


```php
use \pjdietz\WellRESTed\Handler;
use \pjdietz\WellRESTed\Exceptions\HttpExceptions\NotFoundException;

class CatsCollectionHandler extends Handler
{
    protected function get()
    {
        // Lookup a cat by ID.
        $cat = Cat::getById($this->args["id"]);
        if (!$cat) {
            throw new NotFoundException();
        }

        $this->response->setStatusCode(200);
        $this->response->setHeader("Content-Type", "application/json");
        $this->response->setBody(json_encode($cat));
    }
}
```

Your [`Handler`](../src/pjdietz/WellRESTed/Handler.php) will automatically turn this into a `404 Not Found` response.

Here are the available [`HttpException`](../src/pjdietz/WellRESTed/Exceptions/HttpExceptions.php)  classes:

Response Code               | Class
--------------------------- | -----------------------
`400 Bad Request`           | `BadRequestException`
`401 Unauthorized`          | `UnauthorizedException`
`403 Forbidden`             | `ForbiddenException`
`404 Not Found`             | `NotFoundException`
`409 Conflict`              | `ConflictException`
`500 Internal Server Error` | `HttpException`

You can also create your own by subclass [`HttpException`](../src/pjdietz/WellRESTed/Exceptions/HttpExceptions.php) and setting the exception's `$code` to the status code and `$messge` to a default message.

## Custom Base Handler

When building your API, you may want to subclass [`Handler`](../src/pjdietz/WellRESTed/Handler.php) with your own abstract class that adds methods for authenticaion, supports some extra verbs, presents custom errors, adds addiitonal headers, etc. Then, you can derive all of your concrete handlers from that class.

```php
<?php
abstract class MyHandler extends \pjdietz\WellRESTed\Handler
{
    protected function buildResponse()
    {
        try {
            // Add support for a custom HTTP verb.
            switch ($this->request->getMethod()) {
            case 'SNIFF':
                $this->sniff();
                break;
            default:
                self::buildResponse();
            }
        } catch (UnauthorizedException $e) {
            // Catch 401 errors and call a method to do something with them.
            $this->responseToUnauthorized($e);
        }

        // Add a header to all responses.
        $this->response->addHeader("X-Custom-Header", "Hello, world!");
    }

    abstract protected function sniff();

    protected function responseToUnauthorized(HttpException $e)
    {
        $this->response->setStatusCode($e->getCode());
        $this->response->setBody("Y U NO SEND CREDENTIALS?");
    }
}
```
