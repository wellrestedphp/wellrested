<?php

namespace handlers;

require_once(dirname(__FILE__) . '/../../../Handler.inc.php');

class ArticleCollectionHandler extends \wellrested\Handler {

    protected function get() {

        $this->response->statusCode = 200;
        $this->response->setHeader('Content-type', 'text/plain');
        $this->response->body = 'A list of articles.';

    }

}

?>
