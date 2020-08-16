<?php

namespace WellRESTed\Message;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class ServerRequestMarshaller
{
    /**
     * Read the request as sent from the client and construct a ServerRequest
     * representation.
     *
     * @return ServerRequestInterface
     * @internal
     */
    public function getServerRequest(): ServerRequestInterface
    {
        $method = self::parseMethod($_SERVER);
        $uri = self::readUri($_SERVER);
        $headers = self::parseHeaders($_SERVER);
        $body = self::readBody();

        $request = (new ServerRequest($method, $uri, $headers, $body, $_SERVER))
            ->withProtocolVersion(self::parseProtocolVersion($_SERVER))
            ->withUploadedFiles(self::readUploadedFiles($_FILES))
            ->withCookieParams($_COOKIE)
            ->withQueryParams(self::parseQuery($_SERVER));

        if (self::isForm($request)) {
            $request = $request->withParsedBody($_POST);
        }

        return $request;
    }

    private static function parseQuery(array $serverParams): array
    {
        $queryParams = [];
        if (isset($serverParams['QUERY_STRING'])) {
            parse_str($serverParams['QUERY_STRING'], $queryParams);
        }
        return $queryParams;
    }

    private static function parseProtocolVersion(array $serverParams): string
    {
        if (isset($serverParams['SERVER_PROTOCOL'])
            && $serverParams['SERVER_PROTOCOL'] === 'HTTP/1.0') {
            return '1.0';
        }
        return '1.1';
    }

    private static function parseHeaders(array $serverParams): array
    {
        // http://www.php.net/manual/en/function.getallheaders.php#84262
        $headers = [];
        foreach ($serverParams as $name => $value) {
            if (substr($name, 0, 5) === 'HTTP_') {
                $name = self::normalizeHeaderName(substr($name, 5));
                $headers[$name] = trim($value);
            } elseif (self::isContentHeader($name) && !empty(trim($value))) {
                $name = self::normalizeHeaderName($name);
                $headers[$name] = trim($value);
            }
        }
        return $headers;
    }

    private static function normalizeHeaderName(string $name): string
    {
        $name = ucwords(strtolower(str_replace('_', ' ', $name)));
        return str_replace(' ', '-', $name);
    }

    private static function isContentHeader(string $name): bool
    {
        return $name === 'CONTENT_LENGTH' || $name === 'CONTENT_TYPE';
    }

    private static function parseMethod(array $serverParams): string
    {
        return $serverParams['REQUEST_METHOD'] ?? 'GET';
    }

    private static function readBody(): StreamInterface
    {
        $input = fopen('php://input', 'rb');
        $temp = fopen('php://temp', 'wb+');
        stream_copy_to_stream($input, $temp);
        rewind($temp);
        return new Stream($temp);
    }

    private static function readUri(array $serverParams): UriInterface
    {
        $uri = '';

        $scheme = 'http';
        if (isset($serverParams['HTTPS']) && $serverParams['HTTPS'] && $serverParams['HTTPS'] !== 'off') {
            $scheme = 'https';
        }

        if (isset($serverParams['HTTP_HOST'])) {
            $authority = $serverParams['HTTP_HOST'];
            $uri .= "$scheme://$authority";
        }

        // Path and query string
        if (isset($serverParams['REQUEST_URI'])) {
            $uri .= $serverParams['REQUEST_URI'];
        }

        return new Uri($uri);
    }

    private static function isForm(ServerRequestInterface $request): bool
    {
        $contentType = $request->getHeaderLine('Content-type');
        return (strpos($contentType, 'application/x-www-form-urlencoded') !== false)
            || (strpos($contentType, 'multipart/form-data') !== false);
    }

    private static function readUploadedFiles(array $input): array
    {
        $uploadedFiles = [];
        foreach ($input as $name => $value) {
            self::addUploadedFilesToBranch($uploadedFiles, $name, $value);
        }
        return $uploadedFiles;
    }

    private static function addUploadedFilesToBranch(
        array &$branch,
        string $name,
        array $value
    ): void {
        if (self::isUploadedFile($value)) {
            if (self::isUploadedFileList($value)) {
                $files = [];
                $keys = array_keys($value['name']);
                foreach ($keys as $key) {
                    $files[$key] = new UploadedFile(
                        $value['name'][$key],
                        $value['type'][$key],
                        $value['size'][$key],
                        $value['tmp_name'][$key],
                        $value['error'][$key]
                    );
                }
                $branch[$name] = $files;
            } else {
                // Single uploaded file
                $uploadedFile = new UploadedFile(
                    $value['name'],
                    $value['type'],
                    $value['size'],
                    $value['tmp_name'],
                    $value['error']
                );
                $branch[$name] = $uploadedFile;
            }
        } else {
            // Add another branch
            $nextBranch = [];
            foreach ($value as $nextName => $nextValue) {
                self::addUploadedFilesToBranch($nextBranch, $nextName, $nextValue);
            }
            $branch[$name] = $nextBranch;
        }
    }

    private static function isUploadedFile(array $value): bool
    {
        // Check for each of the expected keys. If all are present, this is a
        // a file. It may be a single file, or a list of files.
        return isset($value['name'], $value['type'], $value['tmp_name'], $value['error'], $value['size']);
    }

    private static function isUploadedFileList(array $value): bool
    {
        // When each item is an array, this is a list of uploaded files.
        return is_array($value['name'])
            && is_array($value['type'])
            && is_array($value['tmp_name'])
            && is_array($value['error'])
            && is_array($value['size']);
    }
}
