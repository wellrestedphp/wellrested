<?php

namespace apisample\handlers;

require_once(dirname(__FILE__) . '/../../../Handler.inc.php');
require_once(dirname(__FILE__) . '/../ArticlesController.inc.php');

/**
 * Handler class for a list of articles.
 */
class ArticleCollectionHandler extends \pjdietz\WellRESTed\Handler {

    /**
     * Respond to a GET request.
     */
    protected function get() {

        // Display the list of articles.
        $articles = new \apisample\ArticlesController();

        if (isset($articles->data)) {

            $this->response->statusCode = 200;
            $this->response->setHeader('Content-type', 'application/json');
            $this->response->body = json_encode($articles->data);

        } else {

            $this->response->statusCode = 500;
            $this->response->setHeader('Content-type', 'text/plain');
            $this->response->body = 'Unable to read the articles.';

        }

    }

    /**
     * Respond to a POST request.
     */
    protected function post() {

        // Read the request body, and ensure it is in the proper format.
        $article = json_decode($this->request->body, true);

        // Ensure the JSON is well-formed.
        if (!$article) {
            $this->response->statusCode = 400;
            $this->response->setHeader('Content-type', 'text/plain');
            $this->response->body = 'Unable to parse JSON from request body.';
            return;
        }

        // Ensure requied fields are present.
        if (!isset($article['slug']) || $article['slug'] === '') {
            $this->response->statusCode = 400;
            $this->response->setHeader('Content-type', 'text/plain');
            $this->response->body = 'Request body missing slug.';
            return;
        }

        if (!isset($article['title'])) {
            $this->response->statusCode = 400;
            $this->response->setHeader('Content-type', 'text/plain');
            $this->response->body = 'Request body missing title.';
            return;
        }

        if (!isset($article['excerpt'])) {
            $this->response->statusCode = 400;
            $this->response->setHeader('Content-type', 'text/plain');
            $this->response->body = 'Request body missing excerpt.';
            return;
        }

        // Ensure slug is not a duplicate.
        $articles = new \apisample\ArticlesController();
        if ($articles->getArticleBySlug($article['slug']) !== false) {
            $this->response->statusCode = 409;
            $this->response->setHeader('Content-type', 'text/plain');
            $this->response->body = 'Unable to store article. Slug "' . $article['slug'] . '" is already in use.';
            return;
        }

        // All looks good! Add this to the articles and save!
        $article = $articles->addArticle($article);

        if ($articles->save() === false) {
            $this->response->statusCode = 500;
            $this->response->setHeader('Content-type', 'text/plain');
            $this->response->body = 'Unable to write to file. Make sure permissions are set properly.';
            return;
        }

        // Ok!
        $this->response->statusCode = 201;
        $this->response->setHeader('Content-type', 'application/json');
        $this->response->body = json_encode($article);
        return;

    }

}

?>
