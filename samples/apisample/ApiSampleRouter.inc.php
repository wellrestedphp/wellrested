<?php

namespace apisample;

require_once(dirname(__FILE__) . '/../../Router.inc.php');

/**
 * Loads and instantiates handlers based on URI.
 */
class ApiSampleRouter extends \wellrested\Router {

    public function __construct() {

        parent::__construct();

        $this->addTemplate('/articles/',
                'ArticleCollectionHandler',
                'ArticleCollectionHandler.inc.php');

        $this->addTemplate('/articles/{id}',
                'ArticleItemHandler',
                'ArticleItemHandler.inc.php',
                array('id' => \wellrested\Route::RE_NUM));

        $this->addTemplate('/articles/{slug}',
                'ArticleItemHandler',
                'ArticleItemHandler.inc.php',
                array('slug' => \wellrested\Route::RE_SLUG));

    }

    public function addTemplate($template, $handlerClassName, $handlerFilePath, $variables=null) {

        // Customize as needed based on your server.
        $template = '/wellrested/samples/apisample' . $template;
        $handlerClassName = '\apisample\handlers\\' . $handlerClassName;
        $handlerFilePath = dirname(__FILE__) . '/handlers/' . $handlerFilePath;

        $this->addRoute(\wellrested\Route::newFromUriTemplate(
                $template, $handlerClassName, $handlerFilePath, $variables));

    }

}

?>