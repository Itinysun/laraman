<?php

use Itinysun\Laraman\fixes\Http;


if (! function_exists('header')) {
    /**
     * Send a raw HTTP header
     *
     * @link https://php.net/manual/en/function.header.php
     */
    function header(string $content, bool $replace = true, ?int $http_response_code = null): void
    {
        Http::header($content, $replace, $http_response_code);
    }
    /**
     * Remove previously set headers
     *
     * @param string $name  The header name to be removed. This parameter is case-insensitive.
     * @return void
     *
     * @link https://php.net/manual/en/function.header-remove.php
     */
    function header_remove(string $name): void
    {
        Http::headerRemove($name);  //TODO fix case-insensitive
    }

    /**
     * Get or Set the HTTP response code
     *
     * @param int|null $code [optional] The optional response_code will set the response code.
     * @return integer      The current response code. By default the return value is int(200).
     *
     * @link https://www.php.net/manual/en/function.http-response-code.php
     */
    function http_response_code(int $code = null): int
    { // int|bool
        return Http::responseCode($code); // Fix to return actual status when void
    }

    /**
     * Send a cookie
     *
     * @param string $name
     * @param string $value
     * @param int|array $expires
     * @param string $path
     * @param string $domain
     * @param boolean $secure
     * @param boolean $httponly
     * @return boolean
     *
     * @link https://php.net/manual/en/function.setcookie.php
     */
    function setcookie(string $name, string $value = '', int|array $expires = 0, string $path = '', string $domain = '', bool $secure = FALSE, bool $httponly = FALSE): bool
    {
        if (is_array($expires)) { // Alternative signature available as of PHP 7.3.0 (not supported with named parameters)
            $expires  = $expires['expires'] ?? 0;
            $path     = $expires['path'] ?? '';
            $domain   = $expires['domain'] ?? '';
            $secure   = $expires['secure'] ?? FALSE;
            $httponly = $expires['httponly'] ?? FALSE;
        }

        return Http::setCookie($name, $value, $expires, $path, $domain, $secure, $httponly);
    }

    /**
     * Create new session id
     *
     * @param string $prefix
     * @return string
     */
    function session_create_id(string $prefix = ''): string
    {
        return Http::sessionCreateId();  //TODO fix to use $prefix
    }

    /**
     * Get and/or set the current session id
     *
     * @param string $id
     * @return string
     *
     * @link https://www.php.net/manual/en/function.session-id.php
     */
    function session_id(string $id = ''): string
    {
        return Http::sessionId($id);   //TODO fix return session name or '' if not exists session
    }

    /**
     * Get and/or set the current session name
     *
     * @param string $name
     * @return string
     */
    function session_name(string $name = ''): string
    {
        return Http::sessionName($name);
    }

    /**
     * Get and/or set the current session save path
     *
     * @param string $path
     * @return string
     */
    function session_save_path(string $path = ''): string
    {
        return Http::sessionSavePath($path);
    }

    /**
     * Returns the current session status
     *
     * @return int
     */
    function session_status(): int
    {
        if (Http::sessionStarted() === false) {
            return PHP_SESSION_NONE;
        }
        return PHP_SESSION_ACTIVE;
    }

    /**
     * Start new or resume existing session
     *
     * @param array $options
     * @return bool
     */
    function session_start(array $options = []): bool
    {
        return Http::sessionStart();   //TODO fix $options
    }

    /**
     * Write session data and end session
     *
     * @return bool
     *
     * @link https://www.php.net/manual/en/function.session-write-close.php
     */
    function session_write_close(): bool
    {
        return Http::sessionWriteClose();
    }

    /**
     * Update the current session id with a newly generated one
     *
     * @param bool $delete_old_session
     * @return bool
     *
     * @link https://www.php.net/manual/en/function.session-regenerate-id.php
     */
    function session_regenerate_id(bool $delete_old_session = false): bool
    {
        return Http::sessionRegenerateId($delete_old_session);
    }

    /**
     * Limits the maximum execution time
     *
     * @param int $seconds
     * @return bool
     */
    function set_time_limit(int $seconds): bool
    {
        // Disable set_time_limit to not stop the worker
        // by default CLI sapi use 0 (unlimited)
        return true;
    }

    /**
     * Checks if or where headers have been sent
     *
     * @return bool
     */
    function headers_sent(): bool
    {
        return false;
    }
}



/**
 * Get cpu count
 *
 * @return int
 */


/* function exit(string $status = ''): void {  //string|int
    http::end($status);
} // exit and die are language constructors, change your code with an empty ExitException
 */