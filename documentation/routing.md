# Routers and Routes

Let's take a look at how to build a simple Router with WellRESTed.

The first step is to build a `Router` instance.

```php
$router = new Router();
```

This router doesn't know how to route anything yet. To tell it how to route, we need to add some `Route` instances. WellRESTed comes with a few `Route` classes you can use. The simplest of these is a `StaticRoute`.

## StaticRoute

Use a `StaticRoute` when you know the exact path you want to handle.

```php
$router = new Router();
$router->addRoute(new StaticRoute("/", "RootHandler"));
$router->addRoute(new StaticRoute("/cats/", "CatCollectionHandler"));
$myRouter->respond();
```

This `Router` will now use a `RootHandler` for requests for the path `/` and `CatCollectionHandler` for requests to `/cats/`. The router doesn't know about any other paths, so any other requests will result in a 404 Not Found response.

You can also make a `StaticRoute` that matches multiple exact paths. For example, suppose you have a multi-use `AnimalHandler` that you want to invoke to handle requests to `/cats/`, `/dogs`, and `/birds`. You can make this by passing an array instead of a string as the first parameter.

```php
$route = new StaticRoute(array("/cats/", "/dogs/", "/birds"), "AnimalHandler");
```

## TemplateRoute

`StaticRoutes` are the best choice if you know the exact path up front. But, what if you want to handle a path that expects an ID or other variable? That's where the `TemplateRoute` comes in.

Here's a route that will match a request to a specific cat by ID and send it to a `CatItemHandler`.

```php
$route = new TemplateRoute("/cats/{id}", "CatItemHandler");
```

TemplateRoutes use URI templates to match requests to handlers. To include a variable in your template, enclose it in `{}`. The variable will be extracted and made available for the handler in the handler's `args` member.

```php
class CatItemHandlder extends \pjdietz\WellRESTed\Handler
{
    protected function get()
    {
        // Access the {id} variable from the $this->args member.
        $id = $this->args["id"];
        // ...Do something with the {id}.
    }
}
```

Your template may have multiple variables. Be sure to give each a unique name.

With this `TemplateRoute`...

```php
$route = new TemplateRoute("/cats/{catId}/{dogId}", "CatItemHandler");
```

...the handler will have access to `$this->args["catId"]` and `$this->args["dogId"]`.


### Default Variable Pattern

By default, the `TemplateRoute` will accept for a variable any value consisting of numbers, letters, underscores, and hyphens. You can change this behavior by passing a pattern to use as the third parameter of the constructor. Here we'll restrict the template to only match numeric values.

```php
$route = new TemplateRoute("/cats/{id}", "CatItemHandler", TemplateRoute::RE_NUM);
```

The `TemplateRoute` includes constants for some common situations. The value of each constant is a partial regular expression. You can use one of the constants, or provide your own partial regular expression.

### Pattern Constants

| Constant   | Pattern           | Description |
| ---------  | ----------------- | ----------- |
| `RE_SLUG`  | `[0-9a-zA-Z\-_]+` | "URL-friendly" characters such as numbers, letters, underscores, and hyphens |
| `RE_NUM`   | `[0-9]+` | Digits only |
| `RE_ALPHA` | `[a-zA-Z]+` | Letters only |
| `RE_ALPHANUM` | `[0-9a-zA-Z]+` | Letters and digits |

### Variable Patterns Array

You can also set a different pattern for each variable. To do this, pass an array to the `TemplateRoute` constructor as the fourth paramter. The array must have variable names as keys and patterns as values.

```php
$patterns = array(
    "id" => TemplateRoute::RE_NUM,
    "name" => TemplateRoute::RE_ALPHA,
);
$route = new TemplateRoute(
    "/cats/{id}/{name}/{more}",
    "CatItemHandler",
    TemplateRoute::RE_SLUG,
    $patterns);
```

Here, `{id}` will need to match digits, `{name}` must be all letters, and since `{more}` is not explicitly provided in the `$patterns` array, it uses the default `TemplateRoute::RE_SLUG` passed as the thrid parameter.

### RegexRoute

If `TemplateRoute` doesn't give you enough control, you can make a route that matches a regular expression.

```php
$route = new RegexRoute("~/cat/[0-9]+~", "CatHandler")
```

This will match `/cat/102` or `/cat/999` or what have you. To make this more useful, we can add a capture group. The captures are made available to the `Handler` as the `$this->args` member, as with the URI template variables for the `TemplateRoute`

Note the entire matched path will always be the `0` item, and captured groups will begin at `1`.

So this route...

```php
<?php
$route = new RegexRoute("~/cat/([0-9]+)~", "CatHandler")
```

...with the path `/cat/99` creates this array of matches:

```
Array
(
    [0] => /cat/99
    [1] => 99
)
```

You can also used named capture groups like this;


```php
<?php
$route = new RegexRoute("~/cat/(?<id>[0-9]+)~", "CatHandler")
```

...with the path `/cat/99` creates this array or matches:

```
Array
(
    [0] => /cat/99
    [1] => 99
    [id] => 99
)
```
