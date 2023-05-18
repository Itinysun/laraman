<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Itinysun\Laraman\Http;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpFoundation\ParameterBag;
use Workerman\Protocols\Http\Request as WorkmanRequest;

/**
 * Class Request
 * @package Webman\Http
 */
class Request extends \Symfony\Component\HttpFoundation\Request
{
    protected WorkmanRequest $workmanRequest;
    private function __construct()
    {

    }
    public static function createFromWorkmanRequest(WorkmanRequest $workmanRequest): static
    {
        $instance = new static();
        $instance->request = new InputBagLazy($workmanRequest,'post');
        $instance->query = new InputBagLazy($workmanRequest,'get');
        $instance->cookies  = new InputBagLazy($workmanRequest,'cookie');
        $instance->files = new InputBagLazy($workmanRequest,'file');
        $instance->server = new InputBagLazy($workmanRequest,'server');
        $instance->headers = new InputBagLazy($workmanRequest,'header');
        $instance->attributes = new ParameterBag([]);

        $instance->content = $workmanRequest->rawBody();
        $instance->languages = null;
        $instance->charsets = null;
        $instance->encodings = null;
        $instance->acceptableContentTypes = null;
        $instance->pathInfo = null;
        $instance->requestUri = null;
        $instance->baseUrl = null;
        $instance->basePath = null;
        $instance->method = null;
        $instance->format = null;

        $instance->workmanRequest = $workmanRequest;
        return $instance;
    }

    public function getMethod(): string
    {
        if (null !== $this->method) {
            return $this->method;
        }

        $this->method = $this->getRealMethod();

        if ('POST' !== $this->method) {
            return $this->method;
        }

        $method = $this->headers->get('X-HTTP-METHOD-OVERRIDE');

        if (!$method && self::$httpMethodParameterOverride) {
            $method = $this->request->get('_method', $this->query->get('_method', 'POST'));
        }

        if (!\is_string($method)) {
            return $this->method;
        }

        $method = strtoupper($method);

        if (\in_array($method, ['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'CONNECT', 'OPTIONS', 'PATCH', 'PURGE', 'TRACE'], true)) {
            return $this->method = $method;
        }

        if (!preg_match('/^[A-Z]++$/D', $method)) {
            throw new SuspiciousOperationException(sprintf('Invalid method override "%s".', $method));
        }

        return $this->method = $method;
    }

    public function getRealMethod(): string
    {
        return strtoupper($this->workmanRequest->method());
    }

    public function getClientIp(): ?string
    {
        return $this->workmanRequest->connection->getRemoteIp();
    }
    protected function prepareRequestUri(): string
    {
        return $this->workmanRequest->path().'?'.$this->workmanRequest->queryString();
    }

    protected function preparePathInfo(): string
    {
        return $this->workmanRequest->path();
    }
    public function getBasePath(): string{
        return '';
    }
    public function getBaseUrl(): string{
        return '';
    }
    public function getPort(): int|string|null{
        return $this->workmanRequest->connection->getLocalPort();
    }
    public function getQueryString(): ?string{
        $qs = static::normalizeQueryString($this->workmanRequest->queryString());
        return '' === $qs ? null : $qs;
    }
    public function getHost(): string{
        return $this->workmanRequest->host(true);
    }

    public function getProtocolVersion(): ?string
    {
        if ($this->isFromTrustedProxy()) {
            preg_match('~^(HTTP/)?([1-9]\.[0-9]) ~', $this->headers->get('Via') ?? '', $matches);

            if ($matches) {
                return 'HTTP/'.$matches[2];
            }
        }

        return 'HTTP/'.$this->workmanRequest->protocolVersion();
    }

    /**
     * Returns the request body content.
     *
     * @param bool $asResource If true, a resource will be returned
     *
     * @return string|resource
     *
     * @psalm-return ($asResource is true ? resource : string)
     */
    public function getContent(bool $asResource = false)
    {
        if (true === $asResource) {
            $resource = fopen('php://temp', 'r+');
            fwrite($resource, $this->content);
            rewind($resource);

            return $resource;
        }
        if (null === $this->content || false === $this->content) {
            $this->content = file_get_contents('php://input');
        }
        return $this->content;
    }

}
