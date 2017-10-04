<?php
/**
 * Created by PhpStorm.
 * User: dak
 * Date: 10/4/17
 * Time: 11:04 AM
 */

namespace NCWest\Riqwest;


abstract class AbstractErrorHandler {

    /**
     * Response
     *
     * @var Response
     */
    protected $response;

    /**
     * AbstractErrorHandler constructor.
     * @param $response Response
     */
    public function __construct($response) {
        $this->response = $response;
    }

    /**
     * Handle Error
     *
     * @return mixed
     */
    abstract public function handle();

}