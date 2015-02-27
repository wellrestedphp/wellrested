Routes
======

WellRESTed provides a number of types of routes to match a request's path to a handler_ that will provide a response.

`Static Routes`_
    Match request paths exactly
`Prefix Routes`_
    Match beginnings of paths
`Template Routes`_
    Match against URI templates
`Regex Routes`_
    Match against regular expressions

Static Routes
^^^^^^^^^^^^^

A static route maps to an exact path. The router will always check for a static route first before trying any other route types.

The following will match all requests to ``/cats/``.

.. code-block:: php

    $router->add("/cats/", $catHandler);

A router can only have one static route for a given path, so adding a duplicate static route will replace the first.

.. code-block:: php

    $router->add("/cats/", $catHandler);
    $router->add("/cats/", $dogHandler);
    // Requests for /cats/ will now be dispatched to $dogHandler.

Prefix Routes
^^^^^^^^^^^^^

You can also create prefix routes which will match any path that *begins* with a given prefix. To create a prefix route, include an asterisk (``*``) at the end of the path. The following will match all requests beginning with ``/cats/``, including ``/cats/``, ``/cats/maine-coon``, ``/cats/persian``, etc.

.. code-block:: php

    $router->add("/cats/*", $catHandler));

Prefix routes are evaluated after static routes. So, given this router…

.. code-block:: php

    $router->add("/cats/",  $catRootHandler);
    $router->add("/cats/*", $catSubHandler);

…requests for ``/cats/`` will be dispatched to ``$catRootHandler``, while ``/cats/maine-coon``, ``/cats/persian``, etc. will be dispatched to ``$catSubHandler``.

Finding the Best Match
----------------------

In the event that multiple prefix routes match the request, the router uses the longest match to determine the best match. Give this router…

.. code-block:: php

    $router->add("/dogs/*", $dogHandler);
    $router->add("/dogs/sporting/*", $sportingHandler);

…requests to ``/dogs/sporting/flat-coated-retriever`` will be dispatched to ``$sportingHandler``, while requests to ``/dogs/herding/boarder-collie`` will be dispatched to ``$dogHandler``.

Template Routes
^^^^^^^^^^^^^^^

Template routes allow the router to extract parts of the path and make them available to the handler_. To create a template route, include one or more variables in the path.

.. code-block:: php

    $router->add("/dogs/{group}/{breed}", $dogHandler);

When the router gets a request for ``/dogs/herding/australian-shepherd``, it will dispatch ``$dogHandler`` and pass this array:

.. code-block:: php

    [
        "group" => "herding",
        "breed" => "australian-shepherd"
    ]

The name of each variable (the part between the curly braces) becomes an array key while the extracted text becomes the corresponding value.

Default Variable Pattern
------------------------

By default, the variables will match digits, upper- and lowercase letters, hyphens, and underscores. To change this, pass a partial regular expression to ``Router::add`` as the third parameter.

The following will restrict the route to match only when the variable matches digits.

.. code-block:: php

    $router->add("/cats/{catId}", $catHandler, "[0-9]+");

Variable Pattern Map
--------------------

For more specificity, you can provide an array mapping variables to their patterns as the fourth parameter. (The third parameter will server as the default for any variables not included in the map).

This will restrict ``{dogId}`` to be digits, while leaving ``{group}`` and ``{breed}`` to be matched by the default.

.. code-block:: php

    $router->add("/dogs/{group}/{breed}/{dogId}", $dogHandler, null, [
            "dogId" => "[0-9]+"
        ]);

Pattern Constants
-----------------

The ``TemplateRoute`` class provides a few of these patterns as class constants.

=============== ===================== ===========
Constant        Regex                 Description
=============== ===================== ===========
``RE_SLUG``     ``[0-9a-zA-Z\-_]+``   **(Default)** "URL-friendly" characters
``RE_NUM``      ``[0-9]+``            Digits only
``RE_ALPHA``    ``[a-zA-Z]+``         Letters only
``RE_ALPHANUM`` ``[0-9a-zA-Z]+``      Letters and digits
=============== ===================== ===========

You can use the class constants or provide your own strings.

This will restrict ``{dogId}`` to be digits, and restrict ``{group}`` and ``{breed}`` to lowercase letters and hyphens.

.. code-block:: php

    $router->add("/dogs/{group}/{breed}/{dogId}", $dogHandler, "[a-z\-]", [
            "dogId" => TemplateRoute::RE_NUM,
        ]);


Regex Routes
^^^^^^^^^^^^

If template routes don't give you enough control, you can make a route that matches a regular expression.

.. code-block:: php

    $router->add("~/cat/[0-9]+~", $catHandler);

This will match ``/cat/102``, ``/cat/999``, etc.

Capture Groups
--------------

To make this more useful, add a capture group. A regex route makes captures available to the dispatched handler the same way template route makes matched variables available.

So this route…

.. code-block:: php

    $router->add("~/cat/([0-9]+)~", $catHandler);

…with the path ``/cat/99``, creates this array of matches:

.. code-block:: php

    [
        0 => "/cat/99",
        1 => "99"
    ]

(Note that the entire matched path will always be the ``0`` item, and captured groups will begin at ``1``.)

You can also used named capture groups like this:

.. code-block:: php

    $router->add("~/cat/(?<catId>[0-9]+)~", $catHandler);

The path ``/cat/99`` creates this array of matches:

.. code-block:: php

    [
        0 => "/cat/99",
        1 => "99",
        "catId" => "99"
    ]

Delimiter
---------

To prevent the route from interpreting your regular expression as a really weird path, use a character other than `/` as the regular expression delimiter_. For example, `~` or `#`.

.. _delimiter: http://php.net/manual/en/regexp.reference.delimiters.php
.. _handler: Handlers_
.. _Handlers: handlers.html
