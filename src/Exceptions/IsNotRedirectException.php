<?php
/**
 * Created by PhpStorm.
 * User: dak
 * Date: 10/4/17
 * Time: 11:08 AM
 */

namespace NCWest\Riqwest\Exceptions;


class IsNotRedirectException extends RiqwestException {

    protected $message = 'The response is not a redirect.';

}