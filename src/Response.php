<?php
/**
 * Created by PhpStorm.
 * User: dak
 * Date: 10/3/17
 * Time: 4:50 PM
 */

namespace NCWest\Riqwest;


use NCWest\Riqwest\Exceptions\IsNotRedirectException;
use Vinlock\RESTCodes\REST;

class Response {

    protected static $error_handlers = [];

    protected $code;

    protected $body;

    protected $info;

    public function __construct(Request $request) {
        // Logging
        $loggables = $request::getLoggablesCallback();
        $log_name = 'RESPONSE';
        $log = [];

        // Response Body
        $this->body = curl_exec($request->retrieveCurl());

        // Check for cURL errors
        $error = curl_error($request->retrieveCurl());
        if ($error) {
            $log['Response Body'] = $this->body;
            $log['cURL Error'] = $error . ' on URL ' . $this->info['url'];
            $loggables($log_name, $log);
            throw new ApplicationException("cURL Error: {$error} to URL {$this->info['url']}");
        }

        $this->info = curl_getinfo($request->retrieveCurl());

        $this->code = $this->info['http_code'];

        $log['Response Code'] = $this->code;
        $log['Response Body'] = $this->body;
        $log['Response Info'] = $this->info;
        $loggables($log_name, $log);

        $this->checkForErrors();
    }

    /**
     * Checks the body for Errors
     */
    protected function checkForErrors() {
        // Check Body
        foreach (static::$error_handlers as $handler) {
            /** @var AbstractErrorHandler $error_handler */
            $error_handler = new $handler($this);
            $error_handler->handle();
        }
    }

    /**
     * Get Response Info
     *
     * @return mixed
     */
    public function getInfo() {
        return $this->info;
    }

    /**
     * Check for Redirect
     *
     * @return bool
     */
    public function isRedirect() {
        if (REST::isRedirect($this->code)) {
            if (array_key_exists('redirect_url', $this->info)) {
                return TRUE;
            }
            return FALSE;
        }
        return FALSE;
    }

    /**
     * Get Redirect URL
     *
     * @return Response
     * @throws IsNotRedirectException
     */
    public function getRedirectURL() {
        if ($this->isRedirect()) {
            return $this->info['redirect_url'];
        }
        throw new IsNotRedirectException('is_not_redirect');
    }

    /**
     * Get Code
     *
     * @return int|null
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Get JSON Response Body
     *
     * @param bool $toArray
     * @return mixed|string
     */
    public function getBody($toArray = FALSE) {
        if (Helpers::is_json($this->body)) {
            return json_decode($this->body, $toArray);
        }
        return $this->body;
    }

    /**
     * Get RAW JSON Body
     *
     * @return mixed
     */
    public function getRawBody() {
        return $this->body;
    }

}