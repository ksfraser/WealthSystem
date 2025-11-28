<?php

namespace App\Container;

use Psr\Container\ContainerExceptionInterface;
use Exception;

/**
 * Exception thrown when container encounters an error
 * 
 * PSR-11 compliant Container exception
 */
class ContainerException extends Exception implements ContainerExceptionInterface
{
}
