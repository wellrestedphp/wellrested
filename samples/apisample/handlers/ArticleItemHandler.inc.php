<?php

namespace handlers;

require_once(dirname(__FILE__) . '/../../../Handler.inc.php');

class ArticleItemHandler extends \wellrested\Handler {

    protected function get() {

        $this->response->statusCode = 200;
        $this->response->setHeader('Content-type', 'text/plain');
        $this->response->body = 'One article';
        $this->response->body = print_r($this->args, true);

    }

}

?>
