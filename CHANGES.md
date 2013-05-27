### v1.2.0
2013-05-27
**Warning** Some of the changes in this update will break backward compatibility.
- Remove constructor from Handler.
- Add Interfaces: RouterInterface, RouteInterface, HandlerInterface, RequestInterface, and ResponseInterface
- Classes are updated to implement interfaces and type hint interfaces in method calls. For example, Router now implements RouteInterface and its method addRoute() now type hints the parameter as a RouteInterface instead of a Route.
- Route fields pattern and handler are now private and replaced accessors to conform to RouteInterface
- Add support for port in Request. You can now use Request::setPort() and Request::getPort(), and the port is included and extract from the URI.

### v1.1.2
2013-05-19
- Change Handler to an abstract class
- Add Handler::respondWithMethodNotAllowed() for flexibility
- Convert a number of methods from protected to private
- Bug fix: return null or false from magic __get() and __isset() methods

### v1.1.1
2013-03-29
- Bug fix: Instantiate Message->headers and Message->headerLines in constructor

### v1.1.0
2013-03-26
- Add MIT License to composer.json
- Add constructor to Request to allow setting URI and method on instantiation
- Add Response::getSuccess()
