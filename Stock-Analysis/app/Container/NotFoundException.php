<?php

namespace App\Container;

use Psr\Container\NotFoundExceptionInterface;
use Exception;

/**
 * Exception thrown when a container entry is not found
 * 
 * PSR-11 compliant Not Found exception
 */
class NotFoundException extends Exception implements NotFoundExceptionInterface
{
}
