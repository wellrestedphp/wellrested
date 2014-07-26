<?php

// http://www.php.net/manual/en/function.getallheaders.php#84262
$headers = [];
foreach ($_SERVER as $name => $value) {
    if (substr($name, 0, 5) === 'HTTP_') {
        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
    }
}
print json_encode($headers);
