<?php

return [
    'httpVersion' => '1.1',
    'responseChunkSize' => 4096,
    'outputBuffering' => 'append',
    'determineRouteBeforeAppMiddleware' => false,
    'displayErrorDetails' => false,
    'addContentLengthHeader' => true,
    'autoHandleOptionsMethod' => true,
    'routerCacheFile' => false,
    'defaultContentType' => 'text/html',
    'doNotReport' => [
        'Sledium\Exceptions\HttpClientException',
    ],
];