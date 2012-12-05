<?php

namespace apisample\data;

/**
 * Simple class for reading and writing articles to a text file.
 */
class ArticlesControler {

    public $data;
    protected $path;

    public function __construct() {
        $this->path = dirname(__FILE__) . '/articles.json';
        $this->load();
    }

    public function load() {

        if (file_exists($this->path)) {
            $data = file_get_contents($this->path);
            $this->data = json_decode($data, true);
        }

    }

    public function save() {

        if (is_writable($this->path)) {
            $data = json_encode($this->data);
            return file_put_contents($this->path, $data);
        }

        return false;

    }

    public function getArticleById($id) {

        foreach ($this->data as $article) {
            if ($article['articleId'] == $id) {
                return $article;
            }
        }
        return false;

    }

    public function getArticleBySlug($slug) {

        foreach ($this->data as $article) {
            if ($article['slug'] == $slug) {
                return $article;
            }
        }
        return false;

    }

    public function addArticle($newArticle) {

        $validatedArticle = array(
            'articleId' => $this->getNewId(),
            'slug' => $newArticle['slug'],
            'title' => $newArticle['title'],
            'excerpt' => $newArticle['excerpt']
        );

        $this->data[] = $validatedArticle;

        return $validatedArticle;

    }

    public function updateArticle($newArticle) {

        foreach ($this->data as &$oldArticle) {

            if ($oldArticle['articleId'] == $newArticle['articleId']) {
                $oldArticle['slug'] = $newArticle['slug'];
                $oldArticle['title'] = $newArticle['title'];
                $oldArticle['excerpt'] = $newArticle['excerpt'];
                return $newArticle;
            }

        }

        return false;

    }

    public function removeArticle($id) {

        foreach ($this->data as $index => $article) {
            if ($article['articleId'] == $id) {
                unset($this->data[$index]);
                return true;
            }
        }

        return false;

    }


    protected function getNewId() {

        $maxId = 0;

        foreach ($this->data as $article) {
            $maxId = max($maxId, $article['articleId']);
        }

        return $maxId + 1;

    }

}

?>