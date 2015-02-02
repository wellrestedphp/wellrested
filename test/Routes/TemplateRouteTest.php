<?php

namespace pjdietz\WellRESTed\Test;

use pjdietz\WellRESTed\Interfaces\HandlerInterface;
use pjdietz\WellRESTed\Interfaces\RequestInterface;
use pjdietz\WellRESTed\Response;
use pjdietz\WellRESTed\Routes\TemplateRoute;

class TemplateRouteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider matchingTemplateProvider
     */
    public function testMatchTemplate($template, $default, $vars, $path, $testName, $expected)
    {
        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($path));

        $route = new TemplateRoute($template, __NAMESPACE__ . '\TemplateRouteTestMockHandler', $default, $vars);
        $resp = $route->getResponse($mockRequest);
        $args = json_decode($resp->getBody(), true);
        $this->assertEquals($expected, $args[$testName]);
    }

    public function matchingTemplateProvider()
    {
        return [
            ["/cat/{id}", TemplateRoute::RE_NUM, null, "/cat/12", "id", "12"],
            ["/cat/{catId}/{dogId}", TemplateRoute::RE_SLUG, null, "/cat/molly/bear", "dogId", "bear"],
            ["/cat/{catId}/{dogId}", TemplateRoute::RE_NUM, [
                "catId" => TemplateRoute::RE_SLUG,
                "dogId" => TemplateRoute::RE_SLUG],
                "/cat/molly/bear", "dogId", "bear"],
            ["/cat/{catId}/{dogId}", TemplateRoute::RE_NUM, (object) [
                "catId" => TemplateRoute::RE_SLUG,
                "dogId" => TemplateRoute::RE_SLUG],
                "/cat/molly/bear", "dogId", "bear"],
            ["/cat/{id}/*", null, null, "/cat/12/molly", "id", "12"],
            ["/cat/{id}-{width}x{height}.jpg", TemplateRoute::RE_NUM, null, "/cat/17-100x100.jpg", "id", "17"],
            ["/cat/{path}", ".*", null, "/cat/this/section/has/slashes", "path", "this/section/has/slashes"]
        ];
    }

    /**
     * @dataProvider nonmatchingTemplateProvider
     */
    public function testSkipNonmatchingTemplate($template, $default, $vars, $path)
    {
        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($path));

        $route = new TemplateRoute($template, "NoClass", $default, $vars);
        $resp = $route->getResponse($mockRequest);
        $this->assertNull($resp);
    }

    public function nonmatchingTemplateProvider()
    {
        return array(
            array("/cat/{id}", TemplateRoute::RE_NUM, null, "/cat/molly"),
            array("/cat/{catId}/{dogId}", TemplateRoute::RE_ALPHA, null, "/cat/12/13"),
            array("/cat/{catId}/{dogId}", TemplateRoute::RE_NUM, array(
                "catId" => TemplateRoute::RE_ALPHA,
                "dogId" => TemplateRoute::RE_ALPHA),
                "/cat/12/13")
        );
    }

}

/**
 * Mini Handler class that allways returns a 200 status code Response.
 */
class TemplateRouteTestMockHandler implements HandlerInterface
{
    public function getResponse(RequestInterface $request, array $args = null)
    {
        $resp = new Response();
        $resp->setStatusCode(200);
        $resp->setBody(json_encode($args));
        return $resp;
    }
}
