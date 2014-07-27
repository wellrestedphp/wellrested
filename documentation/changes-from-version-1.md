# Changes from Version 1.x

WellRESTed 2 brings a streamlined API and extra flexibility. In order to make the kinds of improvements I wanted to make, I had to take a sledge hammer to backwards compatabiliy. If you're project is using a 1.x version, please be sure to set you Composer file to use 1.x until you are ready to migrate.

```json
{
    "require": {
        "pjdietz/wellrested": "1.*"
    }
}
```

This is not a comprehensive list, but here I'll outline some of the major departures from version 1 to help you port to the new version.

## Routes

Routes are redesigned for version 2.

### URI Templates

In version 1, `Route` included a static method for creating a route using a URI template. Version 2 has a specific class for URI template routes.

**Version 1**
```php
$route = Route::newFromUriTemplate('/things/', 'ThingsHandler');
```

**Version 2**
```php
$route = new TemplateRoute('/things/', 'ThingsHandler');
```

### Regular Expressions

Version 1's `Route` expected you to use regular expressions. To do this in version 2, use the `RegexRoute`.

**Version 1**
```php
$route = new Route('/^\/cat\//', 'CatHandler');
```

**Version 2**
```php
$route = new RegexRoute('/^\/cat\//', 'CatHandler');
```

Version 2 also includes the `StaticRoute` class for when you want to match on an exact path.

```php
$route = new StaticRoute('/cat/', 'CatHandler');
```

See [Routes](routes.md) for more information.

## Interfaces

I whittled the number of interfaces down to three:
- [`HandlerInterface`](../src/pjdietz/WellRESTed/Interfaces/HanderInterface.php)
- [`RequestInterface`](../src/pjdietz/WellRESTed/Interfaces/RequestInterface.php)
- [`ResponseInterface`](../src/pjdietz/WellRESTed/Interfaces/ResponseInterface.php)

(`RoutableInterface`, `RouteInterface`, `RouteTargetInterface`,  `RouterInterface` are all removed.)

Version 2's design is centered around [`HandlerInterface`](../src/pjdietz/WellRESTed/Interfaces/HanderInterface.php). This new approach both simplifies the API, but also adds a great deal of flexibility.

See [HandlerInterface](handler-interface.md) to learn more.

## No Magic Accessors

I removed the magic property methods so that I could make better use of interfaces. This means you'll need to use accessors where you previously could have used properties.

**Version 1**
```php
$request->statusCode = 200;
```

**Version 2**
```php
$request->setStatusCode(200);
```

## Making Requests

I moved the cURL functionality that allows you to make a request out of the `Request` class and into [`Client`](../src/pjdietz/WellRESTed/Client.php).

**Version 1**
```php
// Prepare a request.
$rqst = new Request('http://my.api.local/resources/');

// Make the request.
$resp = $rqst->request();
```

**Version 2**
```php
// Prepare a request.
$rqst = new Request('http://my.api.local/resources/');

// Make the request.
$client = new Client();
$resp = $client->request(rqst);
```
