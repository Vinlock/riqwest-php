<?php
/**
 * Created by PhpStorm.
 * User: dak
 * Date: 10/3/17
 * Time: 4:50 PM
 */

namespace NCWest\Riqwest;


use NCWest\Riqwest\Exceptions\IncorrectNumberOfMiddlewareParameters;
use NCWest\Riqwest\Exceptions\InvalidResponseClass;

class Request {

    /**
     * Default timeout length
     *
     * @var int
     */
    protected static $TIMEOUT = 30;

    /**
     * HTTP Protocol
     */
    const HTTP = 'http://';

    /**
     * HTTPS Protocol
     */
    const HTTPS = 'https://';

    /**
     * Request Instance Host
     *
     * @var string
     */
    protected $host;

    /**
     * Request Instance Port
     *
     * @var int
     */
    protected $port;

    /**
     * Middleware Payload Mutator Functions
     *
     * Functions in this array will accept the payload as a parameter and return the newly modified payload.
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * Curl Instance
     */
    protected $curl;

    /**
     * Retrieve the cURL instance.
     *
     * @return mixed
     */
    public function retrieveCurl() {
        return $this->curl;
    }

    /**
     * Response Class
     *
     * @var string
     */
    protected $responseClass = Response::class;

    /**
     * Set Request Instance Response Class
     *
     * @param $class Subclass of \NCWest\Riqwest\Request::class
     * @throws InvalidResponseClass
     */
    public function setResponseClass($class) {
        if (!is_subclass_of($class, Response::class)) {
            throw new InvalidResponseClass();
        }
        $this->responseClass = $class;
    }

    /**
     * Request Instance Headers
     *
     * @var array
     */
    private $headers = [
        'Content-Type' => 'application/json',
        'accept-encoding' => 'identity',
        'User-Agent' => 'Apache-HttpClient/4.2 (java 1.5)'
    ];

    /**
     * NCRequest constructor.
     *
     * @param $host
     * @param null $port
     * @param array $headers
     */
    public function __construct($host, $port = NULL, $headers = []) {
        // Host
        $this->host = $host;

        // Port
        if (is_null($port)) {
            if (Helpers::endsWith($host, self::HTTPS)) {
                $this->port = 443;
            } else {
                $this->port = 80;
            }
        } else {
            $this->port = $port;
        }

        // Headers
        $this->addHeaders($headers);
    }

    /**
     * Add a single header to the Request Instance
     *
     * @param $key
     * @param $value
     */
    public function addHeader($key, $value) {
        $current_headers = array_change_key_case($this->headers, CASE_LOWER);
        $current_headers[strtolower($key)] = $value;
        $this->headers = $current_headers;
    }

    /**
     * Add an array of headers to the Request Instance
     *
     * @param array $headers
     */
    public function addHeaders(array $headers) {
        foreach ($headers as $key => $header) {
            $this->addHeader($key, $header);
        }
    }

    /**
     * Add middleware to mutate the payload
     *
     * Callback must accept the payload and return the new payload.
     *
     * @param callable $callback
     * @throws IncorrectNumberOfMiddlewareParameters
     */
    public function addMiddleware(callable $callback) {
        $method = new \ReflectionMethod($callback);
        if ($method->getNumberOfParameters() < 1) {
            throw new IncorrectNumberOfMiddlewareParameters();
        }
        $this->middleware[] = $callback;
    }

    /**
     * Mutate the payload with the existing Middleware Mutators in $this->middleware
     *
     * @param $payload
     * @param $route
     * @return mixed
     */
    protected function mutateWithMiddleware(&$payload, &$route) {
        foreach ($this->middleware as $middleware) {
            $payload = $middleware($payload, $route);
        }
        return $payload;
    }

    /**
     * GET Request Method
     *
     * @param string $route
     * @param null $payload
     * @param array $headers
     * @return Response
     */
    public function get($route = '', $payload = NULL, $headers = []) {
        return $this->request(Method::GET, $route, $payload, $headers);
    }

    /**
     * POST Request Method
     *
     * @param string $route
     * @param null $payload
     * @param array $headers
     * @return Response
     */
    public function post($route = '', $payload = NULL, $headers = []) {
        return $this->request(Method::POST, $route, $payload, $headers);
    }

    /**
     * PUT Request Method
     *
     * @param string $route
     * @param null $payload
     * @param array $headers
     * @return Response
     */
    public function put($route = '', $payload = NULL, $headers = []) {
        return $this->request(Method::PUT, $route, $payload, $headers);
    }

    /**
     * DELETE Request Method
     *
     * @param string $route
     * @param null $payload
     * @param array $headers
     * @return Response
     */
    public function delete($route = '', $payload = NULL, $headers = []) {
        return $this->request(Method::DELETE, $route, $payload, $headers);
    }

    /**
     * @param $method
     * @param $route
     * @param null $payload
     * @param array $headers
     * @return Response
     */
    protected function request($method, $route, $payload = NULL, $headers = []) {
        if (!Helpers::startsWith($route, '/')) {
            $route = '/'.$route;
        }
        $payload = $this->mutateWithMiddleware($payload, $route);

        // Create Query if needed.
        $query = $this->generateQuery($method, $payload);

        // Add additional headers to the request.
        $this->addHeaders($headers);

        $protocol = '';

        // Create URL
        $url = "{$protocol}{$this->host}{$route}{$query}";

        $this->curl = $this->createCurlInstance($url, $method, $payload, $route);

        // Get Response
        $response = new $this->responseClass($this);

        // Handle Response
        return $response;
    }

    /**
     * Generate HTTP Query String
     * Used for GET and DELETE requests.
     *
     * @param $method
     * @param $payload
     * @return string
     */
    protected function generateQuery($method, $payload) {
        if (in_array($method, [Method::GET, Method::DELETE]) && !empty($payload)) {
            $query = http_build_query($payload);
            return "?{$query}";
        }
        return '';
    }

    /**
     * Create a cURL instance
     *
     * @param $url
     * @param $method
     * @param $payload
     * @param $route
     * @return resource
     */
    protected function createCurlInstance($url, $method, $payload, $route) {
        $curl = curl_init($url);
//        dd($url);

        $headers = [];
        foreach ($this->headers as $key => $header) {
            $headers[] = "{$key}: {$header}";
        }

        $curl_options = [
            CURLOPT_PORT => $this->port,
            CURLOPT_POST => in_array($method, [
                self::POST, self::PUT
            ]),
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_SSL_VERIFYHOST => FALSE,
            CURLOPT_SSL_VERIFYPEER => FALSE,
            CURLOPT_SSLVERSION => 'CURL_SSLVERSION_SSLv3',
            CURLOPT_VERBOSE => TRUE,
            CURLINFO_HEADER_OUT => TRUE,
            CURLOPT_ENCODING => 'gzip, deflate',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => self::DEFAULT_TIMEOUT_SECONDS
        ];

        if (in_array($method, [self::PUT, self::DELETE])) {
            $curl_options[CURLOPT_CUSTOMREQUEST] = $method;
        }

        // Encode Payload
        if (!is_null($payload) && (is_object($payload) || is_array($payload))) {
            $payload = json_encode($payload);
        }

        // Logging
        $loggables = self::loggables;
        if (!is_null($loggables) && is_callable($loggables)) {
            $loggables('REQUEST OUT', [
                'Method' => $method,
                'Host' => $this->host,
                'Port' => $this->port,
                'Route' => $route,
                'Headers' => $this->headers,
                'Payload' => $payload
            ]);
        }

        // Set POSTFIELDS if POST method is used.
        if (in_array($method, [self::PUT, self::POST]) && !is_null($payload)) {
            $curl_options[CURLOPT_POSTFIELDS] = $payload;
        }

        curl_setopt_array($curl, $curl_options);

        return $curl;
    }

    /**
     * Callback for adding to log
     *
     * @var callable
     */
    protected static $loggables;

    /**
     * Set Loggbles Callback
     *
     * @param callable $loggables
     */
    public static function setLoggablesCallback(callable $loggables) {
        self::$loggables = $loggables;
    }

    /**
     * Get Loggables Callback
     *
     * @return callable
     */
    public static function getLoggablesCallback() {
        return self::$loggables;
    }




}