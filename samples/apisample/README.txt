The apisample directory contains a mini API project that demonstrates all of the
main features of Well RESTEd.

/articles/
    Represents a list of articles.

    GET
        Displays a list of articles
    POST
        Add a new article
    PUT
        Not allowed
    DELETE
        Not allowed

/articles/{id}
/articles/{slug}
    Represents one specific article identified by the numberic ID {id} or by the
    alpha-numeric slug.

    GET
        Displays one specific article
    POST
        Not allowed
    PUT
        Replace the article with the given contents
    DELETE
        Remove the article
