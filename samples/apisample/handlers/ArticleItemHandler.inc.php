<?php

namespace apisample\handlers;

require_once(dirname(__FILE__) . '/../../../Handler.inc.php');
require_once(dirname(__FILE__) . '/../ArticlesController.inc.php');

/**
 * Handler class for one specific article.
 *
 * When instantiated by the Router, this class should receive an id or slug
 * argument to identify the article.
 */
class ArticleItemHandler extends \wellrested\Handler {

    /**
     * Respond to a GET request.
     */
    protected function get() {

        // Read the list of articles.
        $articles = new \apisample\ArticlesController();

        $article = false;

        // Locate the article by ID or slug
        if (isset($articles->data)) {

            if (isset($this->args['id'])) {
                $article = $articles->getArticleById($this->args['id']);
            } elseif (isset($this->args['slug'])) {
                $article = $articles->getArticleBySlug($this->args['slug']);
            }

        }

        if ($article !== false) {

            $this->response->statusCode = 200;
            $this->response->setHeader('Content-type', 'application/json');
            $this->response->body = json_encode($article);

        } else {

            $this->response->statusCode = 404;
            $this->response->setHeader('Content-type', 'text/plain');
            $this->response->body = 'Unable to locate the article.';

        }

    }

    /**
     * Respond to a PUT request.
     */
    protected function put() {

        // Read the request body, and ensure it is in the proper format.
        $article = json_decode($this->request->body, true);

        // Ensure the JSON is well-formed.
        if (!$article) {
            $this->response->statusCode = 400;
            $this->response->setHeader('Content-type', 'text/plain');
            $this->response->body = 'Unable to parse JSON from request body.';
            return;
        }

        // Ensure required fields are present.
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

        // Read the list of articles.
        $articles = new \apisample\ArticlesController();

        $oldArticle = false;

        // Locate the article by ID or slug
        if (isset($articles->data)) {

            if (isset($this->args['id'])) {
                $oldArticle = $articles->getArticleById($this->args['id']);
            } elseif (isset($this->args['slug'])) {
                $oldArticle = $articles->getArticleBySlug($this->args['slug']);
            }

        }

        // Fail if the article identified by the URI does not exist.
        if ($oldArticle === false) {

            $this->response->statusCode = 404;
            $this->response->setHeader('Content-type', 'text/plain');
            $this->response->body = 'Unable to locate the article.';
            return;

        }

        // If the user located the resource by ID and has passed a slug,
        // make sure the new slug is not already in use.
        if (isset($this->args['id'])) {

            $slugArticle = $articles->getArticleBySlug($article['slug']);

            if ($slugArticle && $slugArticle['articleId'] != $article['articleId']) {
                $this->response->statusCode = 409;
                $this->response->setHeader('Content-type', 'text/plain');
                $this->response->body = 'Unable to store article. Slug "' . $article['slug'] . '" is already in use.';
                return;
            }

        }

        // Update the article.

        // First, ensure the articleId is set.
        // It must match the existing article found earlier.
        $article['articleId'] = $oldArticle['articleId'];

        // Keep the results from the update for the response.
        $article = $articles->updateArticle($article);

        if ($articles->save() === false) {
            $this->response->statusCode = 500;
            $this->response->setHeader('Content-type', 'text/plain');
            $this->response->body = 'Unable to write to file. Make sure permissions are set properly.';
            return;
        }

        // Ok!
        $this->response->statusCode = 200;
        $this->response->setHeader('Content-type', 'application/json');
        $this->response->body = json_encode($article);
        return;

    }

    /**
     * Respond to a DELETE request.
     */
    protected function delete() {

        // Read the list of articles.
        $articles = new \apisample\ArticlesController();

        $article = false;

        // Locate the article by ID or slug
        if (isset($articles->data)) {

            if (isset($this->args['id'])) {
                $article = $articles->getArticleById($this->args['id']);
            } elseif (isset($this->args['slug'])) {
                $article = $articles->getArticleBySlug($this->args['slug']);
            }

        }

        // Ensure the article exists.
        if ($article === false) {
            $this->response->statusCode = 404;
            $this->response->setHeader('Content-type', 'text/plain');
            $this->response->body = 'Unable to locate the article.';
            return;
        }

        // Remove the article and save.
        $articles->removeArticle($article['articleId']);

        if ($articles->save() === false) {
            $this->response->statusCode = 500;
            $this->response->setHeader('Content-type', 'text/plain');
            $this->response->body = 'Unable to write to file. Make sure permissions are set properly.';
            return;
        }

        // Ok!
        $this->response->statusCode = 200;
        return;

    }

}

?>
