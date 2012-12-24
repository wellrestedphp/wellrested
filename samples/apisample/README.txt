The apisample directory contains a mini API project that demonstrates the
main features of Well RESTEd.

Resources
---------

For this sample project, the only resources
are "articles", which are kind of like little mini blog posts or news feed
items. Each article contains the following fields:

    articleId: Numeric unique identifier for the article
    slug: A human readable unique identifier for the article
    title: Text title describing the article
    excerpt: A short portion of the article's content

In JSON, an article resource looks like this:

    {
        "articleId": 1,
        "slug": "good-movie",
        "title": "Reports Of Movie Being Good Reach Area Man",
        "excerpt": "Local resident Daniel Paxson has reportedly heard dozens of accounts from numerous friendly sources in the past two weeks confirming that the new James Bond film is pretty good. According to persons with knowledge of the situation, an unnamed friend of Paxson’s coworker Wendy Mathers watched the movie on opening weekend and found it to be “decent enough.”"
    }


URIs
----

The API exposes both the collection of articles and each article individually.

/articles/
    Represents the collection of articles.

    GET
        Display the full list of articles.
    POST
        Add a new article. Provide the new article in JSON format as the
        request body.
    PUT
        Not allowed
    DELETE
        Not allowed

/articles/{id}
/articles/{slug}
    Represents one specific article identified by the numberic ID {id} or by the
    alpha-numeric slug {slug}.

    GET
        Display one specific article.
    POST
        Not allowed
    PUT
        Replace the article with the new article. Provide the new article in
        JSON format as the request body.
    DELETE
        Remove the article.
