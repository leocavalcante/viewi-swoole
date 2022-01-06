<?php

require __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/viewi-app/viewi.php';

$request_handler = static function (\Swoole\Http\Request $request, \Swoole\Http\Response $response): void {
    $url = $request->server['request_uri'];
    $method = $request->server['request_method'];
    $params = ($request->get ?? []) + ($request->post ?? []);

    /** @var \Viewi\WebComponents\Response|string $viewi_response */
    $viewi_response = \Viewi\Routing\Router::handle($url, $method, $params);

    if (is_string($viewi_response)) {
        $response->end($viewi_response);
        return;
    }

    if (! $viewi_response instanceof \Viewi\WebComponents\Response) {
        $response->setHeader('Content-type', 'application/json');
        $response->end(json_encode($viewi_response, JSON_THROW_ON_ERROR));
        return;
    }

    $response->setStatusCode($viewi_response->StatusCode);
    foreach ($viewi_response->Headers as $header_name => $header_value) {
        $response->setHeader($header_name, $header_value);
    }
    $response->end($viewi_response->Content);
};

$server = new \Swoole\Http\Server('0.0.0.0', 9501);
$server->on(\Swoole\Constant::EVENT_REQUEST, $request_handler);
$server->set([
    \Swoole\Constant::OPTION_ENABLE_STATIC_HANDLER => true,
    \Swoole\Constant::OPTION_DOCUMENT_ROOT => PUBLIC_FOLDER, # viewi-app/config.php
]);

echo "Listening at {$server->port}\n";
$server->start();