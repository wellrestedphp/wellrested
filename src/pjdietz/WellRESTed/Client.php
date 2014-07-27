<?php

/**
 * pjdietz\WellRESTed\Client
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2014 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed;

use pjdietz\WellRESTed\Exceptions\CurlException;
use pjdietz\WellRESTed\Interfaces\RequestInterface;
use pjdietz\WellRESTed\Interfaces\ResponseInterface;

/**
 * Class for making HTTP requests using cURL.
 */
class Client
{
    /** @var array cURL options */
    private $curlOpts;

    /**
     * Create a new client.
     *
     * You may optionally provide an array of cURL options to use by default.
     * Options passed in the requset method will override these.
     *
     * @param array $curlOpts Optional array of cURL options
     */
    public function __construct(array $curlOpts = null)
    {
        if (is_array($curlOpts)) {
            $this->curlOpts = $curlOpts;
        } else {
            $this->curlOpts = array();
        }
    }

    /**
     * Make an HTTP request and return a Response.
     *
     * @param RequestInterface $rqst
     * @param array $curlOpts Optional array of cURL options
     * @throws \pjdietz\WellRESTed\Exceptions\CurlException
     * @return ResponseInterface
     */
    public function request(RequestInterface $rqst, $curlOpts = null)
    {
        $ch = curl_init();

        $headers = array();
        foreach ($rqst->getHeaders() as $field => $value) {
            $headers[] = sprintf('%s: %s', $field, $value);
        }

        $options = $this->curlOpts;
        $options[CURLOPT_URL] = $rqst->getUri();
        $options[CURLOPT_PORT] = $rqst->getPort();
        $options[CURLOPT_HEADER] = 1;
        $options[CURLOPT_RETURNTRANSFER] = 1;
        $options[CURLOPT_HTTPHEADER] = $headers;

        // Set the method. Include the body, if needed.
        switch ($rqst->getMethod()) {
            case 'GET':
                $options[CURLOPT_HTTPGET] = 1;
                break;
            case 'POST':
                $options[CURLOPT_POST] = 1;
                $options[CURLOPT_POSTFIELDS] = $rqst->getBody();
                break;
            case 'PUT':
                $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
                $options[CURLOPT_POSTFIELDS] = $rqst->getBody();
                break;
            default:
                $options[CURLOPT_CUSTOMREQUEST] = $rqst->getMethod();
                $options[CURLOPT_POSTFIELDS] = $rqst->getBody();
                break;
        }

        // Override cURL options with the user options passed in.
        if ($curlOpts) {
            foreach ($curlOpts as $optKey => $optValue) {
                $options[$optKey] = $optValue;
            }
        }

        // Set the cURL options.
        curl_setopt_array($ch, $options);

        // Make the cURL request.
        $result = curl_exec($ch);

        // Throw an exception in the event of a cURL error.
        if ($result === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new CurlException($error, $errno);
        }

        // Make a reponse to populate and return with data obtained via cURL.
        $resp = new Response();

        $resp->setStatusCode(curl_getinfo($ch, CURLINFO_HTTP_CODE));

        // Split the result into headers and body.
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($result, 0, $headerSize);
        $body = substr($result, $headerSize);

        // Set the body. Do not auto-add the Content-length header.
        $resp->setBody($body, false);

        // Iterate over the headers line by line and add each one.
        foreach (explode("\r\n", $headers) as $header) {
            if (strpos($header, ':')) {
                list ($headerName, $headerValue) = explode(':', $header, 2);
                $resp->setHeader($headerName, ltrim($headerValue));
            }
        }

        curl_close($ch);

        return $resp;
    }
}
