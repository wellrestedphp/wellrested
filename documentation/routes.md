# Routes

WellRESTed comes with a few route classes:

- [`StaticRoute`](../src/pjdietz/WellRESTed/Routes/StaticRoute.php): Matches request paths exactly
- [`TemplateRoute`](../src/pjdietz/WellRESTed/Routes/TemplateRoute.php): Matches URI templates
- [`RegexRoute`](../src/pjdietz/WellRESTed/Routes/RegexRoute.php): Matches a custom regular expression

Each works basically the same way: It first checks to see if it is a match for the request. If it's a match, it instantiates a specific class implementing the [`HandlerInterface`](../src/pjdietz/WellRESTed/Interfaces/HandlerInterface.php) (autoloading the class, if needed). Finally, it uses the handler class to provide a response.

## StaticRoute

Use a [`StaticRoute`](../src/pjdietz/WellRESTed/Routes/StaticRoute.php) when you know the exact path you want to handle. This route will match only requests to `/cats/`.

```php
$route = new StaticRoute("/cats/", "CatHandler");
```

You can also make a [`StaticRoute`](../src/pjdietz/WellRESTed/Routes/StaticRoute.php) that matches multiple exact paths. For example, suppose you have a multi-use `AnimalHandler` that you want to invoke to handle requests to `/cats/`, `/dogs/`, and `/birds/`. You can make this by passing an array instead of a string as the first parameter.

```php
$route = new StaticRoute(array("/cats/", "/dogs/", "/birds/"), "AnimalHandler");
```

## TemplateRoute

[`StaticRoute`](../src/pjdietz/WellRESTed/Routes/StaticRoute.php) is the best choice if you know the exact path up front. But, what if you want to handle a path that includes a variable? That's where the [`TemplateRoute`](../src/pjdietz/WellRESTed/Routes/TemplateRoute.php) comes in.

Here's a route that will match a request to a specific cat by ID and send it to a `CatItemHandler`.

```php
$route = new TemplateRoute("/cats/{id}", "CatItemHandler");
```

A [`TemplateRoute`](../src/pjdietz/WellRESTed/Routes/TemplateRoute.php) use a URI template to match a request. To include a variable in your template, enclose it in `{}`. The variable will be extracted and made available for the handler in the handler's `args` member.

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

With this [`TemplateRoute`](../src/pjdietz/WellRESTed/Routes/TemplateRoute.php)...

```php
$route = new TemplateRoute("/cats/{catId}/{dogId}", "CatItemHandler");
```

...the handler will have access to `$this->args["catId"]` and `$this->args["dogId"]`.


### Default Variable Pattern

By default, the [`TemplateRoute`](../src/pjdietz/WellRESTed/Routes/TemplateRoute.php) will accept for a variable any value consisting of numbers, letters, underscores, and hyphens. You can change this behavior by passing a pattern to use as the third parameter of the constructor. Here we'll restrict the template to match only numeric values.

```php
$route = new TemplateRoute("/cats/{id}", "CatItemHandler", TemplateRoute::RE_NUM);
```

The [`TemplateRoute`](../src/pjdietz/WellRESTed/Routes/TemplateRoute.php) includes constants for some common situations. The value of each constant is a partial regular expression. You can use one of the constants, or provide your own partial regular expression.

### Pattern Constants

| Constant   | Pattern           | Description |
| ---------  | ----------------- | ----------- |
| `RE_SLUG`  | `[0-9a-zA-Z\-_]+` | "URL-friendly" characters such as numbers, letters, underscores, and hyphens |
| `RE_NUM`   | `[0-9]+` | Digits only |
| `RE_ALPHA` | `[a-zA-Z]+` | Letters only |
| `RE_ALPHANUM` | `[0-9a-zA-Z]+` | Letters and digits |

### Variable Patterns Array

You can also set a different pattern for each variable. To do this, pass an array to the [`TemplateRoute`](../src/pjdietz/WellRESTed/Routes/TemplateRoute.php) constructor as the fourth parameter. The array must have variable names as keys and patterns as values.

```php
$patterns = array(
    "id" => TemplateRoute::RE_NUM,
    "name" => TemplateRoute::RE_ALPHA
);
$route = new TemplateRoute(
    "/cats/{id}/{name}/{more}",
    "CatItemHandler",
    TemplateRoute::RE_SLUG,
    $patterns);
```

Here, `{id}` will need to match digits and `{name}` must be all letters. Since `{more}` is not explicitly provided in the `$patterns` array, it uses the default `TemplateRoute::RE_SLUG` passed as the third parameter.

### RegexRoute

If [`TemplateRoute`](../src/pjdietz/WellRESTed/Routes/TemplateRoute.php) doesn't give you enough control, you can make a route that matches a regular expression using a [`RegexRoute`](../src/pjdietz/WellRESTed/Routes/RegexRoute.php).

```php
$route = new RegexRoute("~/cat/[0-9]+~", "CatHandler")
```

This will match `/cat/102` or `/cat/999` or what have you. To make this more useful, we can add a capture group. The captures are made available to the handler as the `$args` member, as with the URI template variables for the [`TemplateRoute`](../src/pjdietz/WellRESTed/Routes/TemplateRoute.php)

Note that the entire matched path will always be the `0` item, and captured groups will begin at `1`.

So this route...

```php
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
