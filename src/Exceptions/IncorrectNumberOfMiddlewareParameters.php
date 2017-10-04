<?php
/**
 * Created by PhpStorm.
 * User: dak
 * Date: 10/3/17
 * Time: 5:07 PM
 */

namespace NCWest\Riqwest\Exceptions;


class IncorrectNumberOfMiddlewareParameters extends RiqwestException {

    protected $message = 'Middleware must accept at least one parameter, the payload.';

}