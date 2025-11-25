<?php

namespace App\Auth;

/**
 * Modern Authentication Exceptions using namespaces
 */
class LoginRequiredException extends \Exception
{
    /** @var string */
    private $redirectUrl;

    public function __construct(string $redirectUrl = '/login.php', string $message = 'Login required', int $code = 401, ?\Throwable $previous = null)
    {
        $this->redirectUrl = $redirectUrl;
        parent::__construct($message, $code, $previous);
    }

    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }
}

class AdminRequiredException extends \Exception
{
    public function __construct(string $message = 'Admin access required', int $code = 403, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

class SessionException extends \Exception
{
    public function __construct(string $message = 'Session error', int $code = 500, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
