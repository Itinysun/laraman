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

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

use function filemtime;
use function gmdate;

/**
 * Class Response
 * @package Webman\Http
 */
class Response extends \Workerman\Protocols\Http\Response
{
    /**
     * @var Throwable
     */
    protected $exception = null;

    /**
     * File
     * @param string $file
     * @return $this
     */
    public function file(string $file): Response
    {
        if ($this->notModifiedSince($file)) {
            return $this->withStatus(304);
        }
        return $this->withFile($file);
    }

    /**
     * Download
     * @param string $file
     * @param string $downloadName
     * @return $this
     */
    public function download(string $file, string $downloadName = ''): Response
    {
        $this->withFile($file);
        if ($downloadName) {
            $this->header('Content-Disposition', "attachment; filename=\"$downloadName\"");
        }
        return $this;
    }

    /**
     * NotModifiedSince
     * @param string $file
     * @return bool
     */
    protected function notModifiedSince(string $file): bool
    {
        $ifModifiedSince = request()->header('if-modified-since');
        if ($ifModifiedSince === null || !($mtime = filemtime($file))) {
            return false;
        }
        return $ifModifiedSince === gmdate('D, d M Y H:i:s', $mtime) . ' GMT';
    }

    /**
     * Exception
     * @param Throwable|null $exception
     * @return Throwable|null
     */
    public function exception(Throwable $exception = null): ?Throwable
    {
        if ($exception) {
            $this->exception = $exception;
        }
        return $this->exception;
    }

    public static function fromLaravelResponse(mixed $response): static
    {
        $resp = new static($response->getStatusCode(),$response->headers->all(),$response->getContent());
        if($response instanceof BinaryFileResponse && null!==$response->getFile()){
            $resp->withFile($response->getFile());
        }
        return $resp;
    }
}
