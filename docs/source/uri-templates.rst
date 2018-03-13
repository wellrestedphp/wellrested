URI Templates
=============

WellRESTed allows you to register handlers with a router using URI Templates, based on the URI Templates defined in `RFC 6570`_. These templates include variables (enclosed in curly braces) which are extracted and made available to the dispatched middleware.

Reading Variables
^^^^^^^^^^^^^^^^^

Basic Usage
-----------

Register a handler with a URI Template by providing a path that include at least one section enclosed in curly braces. The curly braces define variables for the template.

.. code-block:: php

    $router->register("GET", "/widgets/{id}", $widgetHandler);

The router will match requests for paths like ``/widgets/12`` and ``/widgets/mega-widget`` and dispatch ``$widgetHandler`` with the extracted variables made available as request attributes.

To read a path variable, router inspects the request attribute named ``"id"``, since ``id`` is what appears inside curly braces in the URI template.

.. code-block:: php

    // For a request to /widgets/12
    $id = $request->getAttribute("id");
    // 12

    // For a request to /widgets/mega-widget
    $id = $request->getAttribute("id");
    // mega-widget

.. note::

    Request attributes are a feature of the ``ServerRequestInterface`` provided by PSR-7_.

Multiple Variables
------------------

The example above included one variable, but URI Templates may include multiple variables. Each variable will be provided as a request attribute, so be sure to give your variables unique names.

Here's an example with a handful of variables. Suppose we have a template describing the path for a user's avatar image. The image is identified by a username and the image dimensions.

.. code-block:: php

    $router->register("GET", "/avatars/{username}-{width}x{height}.jpg", $avatarHandlers);

A request for ``GET /avatars/zoidberg-100x150.jpg`` will provide these request attributes:

.. code-block:: php

    // Read the variables extracted form the path.
    $username = $request->getAttribute("username");
    // "zoidberg"
    $width = $request->getAttribute("width");
    // "100"
    $height = $request->getAttribute("height");
    // "150"

Arrays
------

You may also match a comma-separated series of values as an array using a URI Template by providing a ``*`` at the end of the variable name.

.. code-block:: php

    $router->register("GET", "/favorite-colors/{colors*}", $colorsHandler);

A request for ``GET /favorite-colors/red,green,blue`` will provide an array as the value for the ``"colors"`` request attribute.

.. code-block:: php

    $colorsHandler = function ($request, $response, $next) {
        // Read the variable extracted form the path.
        $colorsList = $request->getAttribute("colors");
        /*  Array
            (
                [0] => red
                [1] => green
                [2] => blue
            )
        */
    };

Matching Characters
^^^^^^^^^^^^^^^^^^^

Unreserved Characters
---------------------

By default, URI Template variables will match only "unreserved" characters. `RFC 3968 Section 2.3`_ defines unreserved characters as alphanumeric characters,  ``-``, ``.``, ``_``, and ``~``. All other characters must be percent encoded to be matched by a default template variable.

.. note::

    Percent-encoded characters matched by template variables are automatically decoded when provided as request attributes.

Given the template ``/users/{user}``, the following paths provide these values for ``getAttribute("user")``:

.. list-table:: Paths and Values for the Template ``/users/{user}``
    :header-rows: 1

    *   - Path
        - Value
    *   - /users/123
        - "123"
    *   - /users/zoidberg
        - "zoidberg"
    *   - /users/zoidberg%40planetexpress.com
        - "zoidberg@planetexpress.com"

A request for ``GET /uses/zoidberg@planetexpress.com`` will **not** match this template, because ``@`` is a reserved character and is not percent encoded.

Reserved Characters
-------------------

If you need to match a non-percent-encoded reserved character like ``@`` or ``/``, use the ``+`` operator at the beginning of the variable name.

Using the template ``/users/{+user}``, we can match all of the paths above, plus ``/users/zoidberg@planetexpress.com``.

Reserved matching also allows matching unencoded slashes (``/``). For example, given this template:

.. code-block:: php

    $router->register("GET", "/my-favorite-path{+path}", $pathHandler);

The router will dispatch ``$pathHandler`` with for a request to ``GET /my-favorite-path/has/a/few/slashes.jpg``

.. code-block:: php

    $path = $request->getAttribute("path");
    // "/has/a/few/slashes.jpg"

.. note::

    Combine the ``+`` operator and ``*`` modifier to match reserved characters as an array. For example, the template ``/{+vars*}`` will match the path ``/c@t,d*g``, providing the  array ``["c@t", "d*g"]``.

.. _RFC 3968 Section 2.3: https://tools.ietf.org/html/rfc3986#section-2.3
.. _PSR-7: http://www.php-fig.org/psr/psr-7/
.. _RFC 6570: https://tools.ietf.org/html/rfc6570
.. _RFC 6570 Section 3.2.7: https://tools.ietf.org/html/rfc6570#section-3.2.7
