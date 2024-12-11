<?php
namespace App\Consumer\Exceptions;

use App\Consumer\Errors\UserIsNotConsumerError;

/**
 * Class UserIsNotConsumerException
 * @package App\Consumer\Exceptions
 *
 * Exception is thrown when user is not consumer
 *
 * @see UserIsNotConsumerError
 */
class UserIsNotConsumerException extends Exception
{
}