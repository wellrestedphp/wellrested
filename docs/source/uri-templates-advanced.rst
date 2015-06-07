URI Templates (Advanced)
========================

In `URI Templates`_, we looked at the most common ways to use URI Templates. In this chapter, we'll look at some of the extended syntaxes that URI Templates provide.

Path Components
^^^^^^^^^^^^^^^

To match a path component, include a slash ``/`` at the beginning of the variable expression. This instructs the template to match the variable if it:

- Begins with ``/``
- Contains only unreserved and percent-encoded characters

You may also use the explode (``*``) modifier to match a variable number of path components and provide them as an array. When using the explode (``*``) modifier to match paths components, the ``/`` character serves as the delimiter instead of a comma.

.. list-table:: Matching path components
    :header-rows: 1

    *   - Template
        - Path
        - Match?
        - Attributes
    *   - {/path}
        - /hello.html
        - Yes
        - :path: ``"hello.html"``
    *   - {/path}
        - /too/many/parts.jpg
        - No
        -
    *   - {/one}{/two}{/three}
        - /just/enough/parts.jpg
        - Yes
        - :one: ``"just"``
          :two: ``"enough"``
          :three: ``"parts.jpg"``
    *   - {/path*}
        - /any/number/of/parts.jpg
        - Yes
        - :path: ``["any", "number", "of", "parts.jpg"]``
    *   - /image{/image*}.jpg
        - /image/with/any/path.jpg
        - Yes
        - :image: ``["with", "any", "path"]``

.. note::

    The template ``{/path}`` fails to match the path ``/too/many/parts.jpg``. Although the path does begin with a slash, the subsequent slashes are reserved characters, and therefore the match fails. To match a variable number of path components, use the explode ``*`` modifier (e.g, ``{/paths*}``), or use the reserved (``+``) operator (e.g., ``/{+paths}``).

Dot Prefixes
^^^^^^^^^^^^

Dot prefixes work similarly to matching path components, but a dot ``.`` is the prefix character in place of a slash. This may be useful for file extensions, etc.

Including a dot ``.`` at the beginning of the variable expression instructs the template to match the variable if it:

- Begins with ``.``
- Contains only unreserved (including ``.``) and percent-encoded characters

You may also use the explode (``*``) modifier to match a variable number of dot-prefixed segments and store them to an array. When using the explode (``*``) modifier to match paths components, the ``.`` character serves as the delimiter instead of a comma.

.. list-table:: Matching dot prefixes
    :header-rows: 1

    *   - Template
        - Path
        - Match?
        - Attributes
    *   - /file{.ext}
        - /file.jpg
        - Yes
        - :ext: ``"jpg"``
    *   - /file{.ext}
        - /file.tar.gz
        - Yes
        - :ext: ``"tar.gz"``
    *   - /file{.ext1}{.ext2}
        - /file.tar.gz
        - Yes
        - :ext1: ``"tar"``
          :ext2: ``"gz"``
    *   - /file{.ext*}
        - /file.tar.gz
        - Yes
        - :ext: ``["tar", "gz"]``

.. note::

    Because ``.`` is an unreserved character, the template ``/file{.ext}`` matches the path ``/file.tar.gz`` and provides the value ``"tar.gz"``. This is different from the behavior of the slash prefix, where an unexpected slash causes the match to fail.

Multiple-variable Expressions
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

An expression in a URI template may contain more than one variable. For example, the template ``/aliases/{one},{two},{three}`` can be written as ``/aliases/{one,two,three}``.

The delimiter between the matched variables is the same as when matching with the explode (``*``) modifier:

.. list-table::
    :header-rows: 1

    *   - Type
        - Delimiter
    *   - Simple String
        - Comma ``,``
    *   - Reserved
        - Comma ``,``
    *   - Path Components
        - Slash ``/``
    *   - Dot Prefix
        - Dot ``.``

.. list-table:: Multiple-variable expressions
    :header-rows: 1

    *   - Template
        - Path
        - Attributes
    *   - /{one,two,three}
        - /fry,leela,bender
        - :one: ``"fry"``
          :two: ``"leela"``
          :three: ``"bender"``
    *   - /{one,two,three}
        - /fry,leela,Nixon%27s%20head
        - :one: ``"fry"``
          :two: ``"leela"``
          :three: ``"Nixon's head"``
    *   - /{+one,two,three}
        - /fry,leela,Nixon's+head
        - :one: ``"fry"``
          :two: ``"leela"``
          :three: ``"Nixon's head"``
    *   - /{/one,two,three}
        - /fry/leela/bender
        - :one: ``"fry"``
          :two: ``"leela"``
          :three: ``"bender"``
    *   - /file{.one,two,three}
        - /file.fry.leela.bender
        - :one: ``"fry"``
          :two: ``"leela"``
          :three: ``"bender"``

.. _URI Templates: uri-templates.html
