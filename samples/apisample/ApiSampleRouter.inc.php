<?php

require_once(dirname(__FILE__) . '/../../Router.inc.php');

class ApiSampleRouter extends \wellrested\Router {

    public function __construct() {

        parent::__construct();

        $this->addTemplate('/articles/',
                '\handlers\ArticleCollectionHandler',
                'ArticleCollectionHandler.inc.php');

        $this->addTemplate('/articles/{id}',
                '\handlers\ArticleItemHandler',
                'ArticleItemHandler.inc.php',
                array('id' => \wellrested\Route::RE_NUM));

        $this->addTemplate('/articles/{slug}',
                '\handlers\ArticleItemHandler',
                'ArticleItemHandler.inc.php',
                array('slug' => \wellrested\Route::RE_SLUG));

    }

    public function addTemplate($template, $handlerClassName, $handlerFilePath, $variables=null) {

        // Customize as needed based on your server.
        $template = '/wellrested/samples/apisample' . $template;
        $handlerFilePath = dirname(__FILE__) . '/handlers/' . $handlerFilePath;

        $this->addRoute(\wellrested\Route::newFromUriTemplate(
                $template, $handlerClassName, $handlerFilePath, $variables));

    }

}

?>