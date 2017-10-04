<?php
/**
 * Created by PhpStorm.
 * User: dak
 * Date: 10/3/17
 * Time: 4:56 PM
 */

namespace NCWest\Riqwest\Exceptions;


class InvalidResponseClass extends RiqwestException {

    protected $message = 'Invalid Response Class. Class must be a child of \NCWest\Riqwest\Response::class.';

}