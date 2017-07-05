<?php
/**
 * @author Masterton
 * @version 0.0.1
 * @date 2017-7-5
 * @time 14:13:00
 *
 */

namespace Spider\Server;

/**
* Base
*/
class Base {
    public $container;

    public function __construct(\Pimple\Container $container) {
        $this->container = $container;
    }

    // 构造(获取)请求(PSR7)
    public static function get_request() {
        $request = \Zend\Diactoros\ServerRequestFactory::fromGlobals(
            $_SERVER,
            $_GET,
            $_POST,
            $_COOKIE,
            $_FILES
        );
        return $request;
    }

    // 构造响应(PSR7)
    public static function get_response() {
        // Workerman\Protocols\Http::header('HTTP/1.1 400 Bad Request');
        // $connection->close('<h1>400 Bad Request</h1>');
        $response = new \Zend\Diactoros\Response();
        return $response;
    }

    // (从PSR7)构造 workerman 的(http)响应
    public static function fromPSR7($request, $response) {
        $uri = $request->getUri();
        $scheme = $uri->getScheme();
        $version = $response->getProtocolVersion();
        $status = $response->getStatusCode();
        $phrase = $response->getReasonPhrase();
        $resp_body = $response->getBody();
        if(!$response->hasHeader('Connection')) {
            $response = $response->withHeader('Connection', 'keep-alive');
        }
        if(!$response->hasHeader('Content-Type')) {
            $response = $response->withHeader('Content-Type', 'text/html;charset=utf-8');
        }
        if(!$response->hasHeader('Content-Length')) {
            $size = $resp_body->getSize();
            $response = $response->withHeader('Content-Length', strval($size));
        }
        if(!$response->hasHeader('Server')) {
            $size = $resp_body->getSize();
            $response = $response->withHeader('Server', 'kz-spider-server');
        }
        // $response = $response->withStatus(200);
        /*
        $path = $uri->getPath();
        // $url = $request->getRequestTarget();
        return $content;
        */
        $output = sprintf(
            '%s/%s %s %s',
            $scheme,
            $version,
            $status,
            $phrase
        );
        $output .= PHP_EOL;
        foreach ($response->getHeaders() as $name => $values) {
            $output .= sprintf('%s: %s', $name, $response->getHeaderLine($name)) . PHP_EOL;
        }
        $output .= PHP_EOL;
        $output .= (string)$resp_body;

        return $output;
    }
}