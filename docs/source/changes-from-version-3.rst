Changes from Version 3
======================

If your project uses WellRESTed version 3, you can most likely upgrade to to version 4 without making any changes to your code. However, there are a few changes that may affect some users.

Unhandled Requests
^^^^^^^^^^^^^^^^^^

In version 3, when a router fails to match the route for a request, the router returns a response with a 404 status code and stops delegating to upstream middleware. Version 4 changes this to allow for multiple routers. In verson 4, when a router fails to match a route, it sends the request up to the next middleware to give it a change to handle the request.

The server now provides the mechanism for responding with a 404 error when no handlers handle the request. This occurs when a request is dispatched all through through the server's middleware stack.

For most applications, this should not cause a problem. However, if your application uses "double pass" middleware—such as legacy ``WellRESTed\MiddlewareInterface`` implementations—and your handlers call ``$next`` after assembling the handled response, you will need to make adjustments. Return the response without calling ``$next`` in these handlers to avoid returning a 404 response.

Server Configuration
^^^^^^^^^^^^^^^^^^^^

Version 4 allows for easier customization of the server than version 3. Previously, to customize the Server, you would need subclass Server and override protected methods that provided a default request, response, transmitter, etc. The Server in version 4 now provides the following setters for providing custom behaviour:

- ``setAttributes(array $attributes)``
- ``setDispatcher(DispatcherInterface $dispatcher)``
- ``setPathVariablesAttributeName(string $name)``
- ``setRequest(ServerRequestInterface $request)``
- ``setResponse(ResponseInterface $response)``
- ``setTransmitter(TransmitterInterface $transmitter)``
- ``setUnhandledResponse(ResponseInterface $response)``
