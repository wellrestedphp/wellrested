Changes from Version 3
======================

If your project uses WellRESTed version 3, you can most likely upgrade to to version 4 without making any changes to your code. However, there are a few changes that may affect some users.

Server Configuration
^^^^^^^^^^^^^^^^^^^^

Version 4 allows for easier customization of the server than version 3. Previously, to customize the Server, you would need to subclass Server and override protected methods that provided a default request, response, transmitter, etc. The Server in version 4 now provides the following setters for providing custom behavior:

- ``setAttributes(array $attributes)``
- ``setDispatcher(DispatcherInterface $dispatcher)``
- ``setPathVariablesAttributeName(string $name)``
- ``setRequest(ServerRequestInterface $request)``
- ``setResponse(ResponseInterface $response)``
- ``setTransmitter(TransmitterInterface $transmitter)``
