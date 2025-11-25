<?php
/**
 * Custom Authentication Exceptions
 * These exceptions allow pages to handle authentication failures gracefully
 */

namespace App\Auth;

class AuthenticationException extends \Exception {
    protected $redirectUrl;
    protected $shouldRedirect;
    
    public function __construct($message = "", $redirectUrl = null, $shouldRedirect = true, $code = 0, ?\Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->redirectUrl = $redirectUrl;
        $this->shouldRedirect = $shouldRedirect;
    }
    
    public function getRedirectUrl() {
        return $this->redirectUrl;
    }
    
    public function shouldRedirect() {
        return $this->shouldRedirect;
    }
}

class LoginRequiredException extends AuthenticationException {
    public function __construct($redirectUrl = 'login.php', $message = "Login required") {
        parent::__construct($message, $redirectUrl, true);
    }
}

class AdminRequiredException extends AuthenticationException {
    public function __construct($message = "Administrator privileges required") {
        parent::__construct($message, null, false, 403);
    }
}

class SessionException extends AuthenticationException {
    public function __construct($message = "Session error", $redirectUrl = 'login.php') {
        parent::__construct($message, $redirectUrl, true);
    }
}
?>
