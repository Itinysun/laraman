<?php

namespace Itinysun\Laraman\server;

use Illuminate\Http\Request;

class FileResponse extends \Workerman\Protocols\Http\Response
{
    /**
     * File
     * @param string $file
     * @return $this
     */
    public function file(string $file,Request $request): FileResponse
    {
//        if ($this->notModifiedSince($file,$request)) {
//            return $this->withStatus(304);
//        }
        return $this->withFile($file);
    }

    /**
     * Download
     * @param string $file
     * @param string $downloadName
     * @return $this
     */
    public function download(string $file, string $downloadName = ''): FileResponse
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
    protected function notModifiedSince(string $file,Request $request): bool
    {
        $ifModifiedSince = $request->header('if-modified-since');
        if ($ifModifiedSince === null || !($mtime = filemtime($file))) {
            return false;
        }
        return $ifModifiedSince === gmdate('D, d M Y H:i:s', $mtime) . ' GMT';
    }
}
