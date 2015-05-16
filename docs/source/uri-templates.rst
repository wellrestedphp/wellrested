URI Templates
=============

WellRESTed allows you to register middleware with a router using URI Templates. These templates include variables (enclosed in curly braces) which are extracted and made availble to the disptached middleware.

The URI Template syntax is based on the URI Templates defined in `RFC 6570`_.

Example
-------

Register middleware with a URI Template by providing a path that include at least one section enclosed in curly braces. The curly braces define variables for the template.

.. code-block:: php

    $router->register("GET", "/widgets/{id}", $widgetHandler);

The router will match requests for paths like ``/widgets/12`` and ``/widgets/mega-widget`` and dispatch ``$widgetHandler`` with the extracted variables made available as request attributes.

To read a path variable, the ``$widgetHandler`` middleware inspects the request attribute named "id".

.. code-block:: php

    $widgetHandler = function ($request, $response, $next) {

        // Read the variable extracted form the path.
        $id = $request->getAttribute("id");
        // "12"

    };

Syntax
------

To illustrate the syntax for various matches, we'll start by providing table of variable names and values that will be used throughout the examples.

.. class:: code-table
.. list-table:: Variable names and values
    :header-rows: 1

    *   - Name
        - Value
    *   - empty
        - ""
    *   - half
        - "50%"
    *   - hello
        - "Hello, World!"
    *   - path
        - "/foo/bar"
    *   - var
        - "value"
    *   - who
        - "fred"
    *   - x
        - "1024"
    *   - y
        - "768"
    *   - count
        - ["one", "two", "three"]
    *   - list
        - ["red", "green", "blue"]

Simple Strings
##############

Variables by default match any unreserved characters. This includes all alpha-numeric characters, plus ``-``, ``.``, ``_``, and ``~``. All other characers MUST be percent encoded.

.. class:: code-table
.. list-table:: Simple Strings
    :header-rows: 1

    *   - URI Template
        - Request Path
    *   - /{var}
        - /value
    *   - /{hello}
        - /Hello%20World%21
    *   - /{x,hello,y}
        - /1024,Hello%20World%21,768

Reserver Charaters
##################

To match reserved character, begin the expression with a ``+``.

.. class:: code-table
.. list-table:: Reserved Characters
    :header-rows: 1

    *   - URI Template
        - Request Path
    *   - /{+var}
        - /value
    *   - /{+hello}
        - /Hello%20World!
    *   - {+path}/here
        - /foo/bar/here

Dot Prefix
##########

Expressions beginning with ``.`` indicate that each variable matched begins with ``.``.

.. class:: code-table
.. list-table:: Dot Prefix
    :header-rows: 1

    *   - URI Template
        - Request Path
    *   - /{.who}
        - /.fred
    *   - /{.half,who}
        - /.50%25.fred
    *   - /X{.empty}
        - /X.

Path Segments
#############

Expressions beginning with ``/`` indicate that each variable matched beings with ``/``.

.. class:: code-table
.. list-table:: Path Segments
    :header-rows: 1

    *   - URI Template
        - Request Path
    *   - {/who}
        - /fred
    *   - {/half,who}
        - /50%25/fred
    *   - {/var,empty}
        - /value/

Explosion
#########

The explosion operator (``*``) at the end of an expression indicates that the extracted value is a list, and will be provided to the middleware as an array.

.. class:: code-table
.. list-table:: Explosion
    :header-rows: 1

    *   - URI Template
        - Request Path
    *   - /{count*}
        - /one,two,three
    *   - {/count*}
        - /one/two/three
    *   - X{.list*}
        - X.red.green.blue

Limitations
-----------

While WellRESTed's URI templates are modeled after `RFC 6570`_, there are some parts of the RFC that WellRESTed does not implement. Some of these are because WellRESTed's uses a URI template and a given path to extract variables, whereas `RFC 6570`_ describes using a URI template and variables to create a path. Other parts are just not implemented yet, but may be in future releases.

Query and Fragment
##################

Anything relating to the query or fragment is omitted. This is because routing is based only on the path component of the request's URI and does not make use of the query or fragment for routing purposes. Furthur, a request for a valid resource with an invalid query **should** generally result in a ``400 Bad Request`` response, not a ``404 Not Found`` response, but taking the query into account for routing would make the ``404`` response happen automatically. A developer may have reason to respond ``404`` based on the query, but this should not be the library's default behavior.

Path-Style Parameter Expansion
##############################

`RFC 6570 Section 3.2.7`_ describes "path-style parameter expansion" where semi-colon-prefixed expressions (e.g., ``{;x,y}``) expand to key-value pairs (e.g., ``;x=1024;y=768``). This is not currently implemented in WellRESTed, although later releases may provide this functionality.

Variable Names
##############

Variable names MUST contain only alphanumeric characters and ``_``.

Unique Variable Names
#####################

Variables names within a given URI Template MUST be unique.

Although some examples in `RFC 6570`_ include the same variable name multiple times, this is not supported by WellRESTed.

.. _RFC 6570: https://tools.ietf.org/html/rfc6570
.. _RFC 6570 Section 3.2.7: https://tools.ietf.org/html/rfc6570#section-3.2.7
