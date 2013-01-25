<?php

namespace ApiSample;

use pjdietz\WellRESTed\Router;
use pjdietz\WellRESTed\Route;

// TODO Revise with autoload required.

/**
 * Loads and instantiates handlers based on URI.
 */
class ApiSampleRouter extends Router
{
    public function __construct()
    {
        parent::__construct();

        $this->addTemplate(
            '/articles/',
            'ArticleCollectionHandler'
        );
        $this->addTemplate(
            '/articles/{id}',
            'ArticleItemHandler',
            array('id' => Route::RE_NUM)
        );
        $this->addTemplate(
            '/articles/{slug}',
            'ArticleItemHandler',
            array('slug' => Route::RE_SLUG)
        );
    }

    public function addTemplate($template, $handlerClassName, $variables = null)
    {
        // Customize as needed based on your server.
        $template = '/wellrested/samples/apisample' . $template;
        $handlerClassName = '\apisample\handlers\\' . $handlerClassName;

        $this->addRoute(
            Route::newFromUriTemplate(
                $template,
                $handlerClassName,
                $variables
            )
        );
    }

}
