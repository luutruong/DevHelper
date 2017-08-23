<?php

namespace DevHelper;

class StreamWrapper
{
    const PROTOCOL = 'devhelper';

    private $_dirPath;
    private $_dirHandle;
    private $_streamPath;
    private $_streamHandle;

    /**
     * Close directory handle
     *
     * @return bool
     */
    public function dir_closedir()
    {
        if (empty($this->_dirHandle)) {
            return false;
        }

        closedir($this->_dirHandle);

        $this->_dirPath = null;
        $this->_dirHandle = null;

        return true;
    }

    /**
     * Open directory handle
     *
     * @param string $path
     * @param int $options
     * @return bool
     */
    public function dir_opendir($path, $options)
    {
        $located = static::locate($path);
        if (!file_exists($located)) {
            return false;
        }

        $this->_dirPath = $path;
        $this->_dirHandle = opendir($located);

        return true;
    }

    /**
     * Read entry from directory handle
     *
     * @return string|false
     */
    public function dir_readdir()
    {
        if (empty($this->_dirHandle)) {
            return false;
        }

        return readdir($this->_dirHandle);
    }

    /**
     * Rewind directory handle
     *
     * @return bool
     */
    public function dir_rewinddir()
    {
        if (empty($this->_dirHandle)) {
            return false;
        }

        return rewinddir($this->_dirHandle);
    }

    /**
     * Create a directory
     *
     * @param string $path
     * @param int $mode
     * @param int $options
     * @return bool
     */
    public function mkdir($path, $mode, $options)
    {
        $located = static::locate($path);
        if (!file_exists($located)) {
            $locatedDir = static::locate(dirname($path));
            if (is_dir($locatedDir)) {
                $located = $locatedDir . DIRECTORY_SEPARATOR . basename($path);
            }
        }

        return mkdir($located, $mode, $options);
    }

    /**
     * @param string $path
     * @param int $options
     * @return bool
     */
    public function rmdir($path, $options)
    {
        $located = static::locate($path);
        if (!file_exists($located)) {
            return false;
        }

        return rmdir($path);
    }

    /**
     * Retrieve the under laying resource
     *
     * @param int $castAs
     * @return resource
     */
    public function stream_cast($castAs)
    {
        if (empty($this->_streamHandle)) {
            return null;
        }

        return $this->_streamHandle;
    }

    /**
     * Close an resource
     */
    public function stream_close()
    {
        if (empty($this->_streamHandle)) {
            return;
        }

        fclose($this->_streamHandle);

        $this->_streamPath = null;
        $this->_streamHandle = null;
    }

    /**
     * Tests for end-of-file on a file pointer
     *
     * @return bool
     */
    public function stream_eof()
    {
        if (empty($this->_streamHandle)) {
            return true;
        }

        return feof($this->_streamHandle);
    }

    /**
     * Advisory file locking
     *
     * @param $operation
     * @return bool
     */
    public function stream_lock($operation)
    {
        if (empty($this->_streamHandle)) {
            return false;
        }

        return flock($this->_streamHandle, $operation);
    }

    /**
     * Change stream metadata
     *
     * @param string $path
     * @param int $option
     * @param mixed $value
     * @return bool
     */
    public function stream_metadata($path, $option, $value)
    {
        switch ($option) {
            case STREAM_META_ACCESS:
                $located = static::locate($path);
                return chmod($located, $value);
        }

        return false;
    }

    /**
     * Opens file or URL
     *
     * @param string $path
     * @param string $mode
     * @param int $options
     * @param string &$openedPath
     * @return bool
     */
    public function stream_open($path, $mode, $options, &$openedPath)
    {
        $needCreate = false;
        if (strpos($mode, 'c') !== false) {
            // modes: c, c+
            $needCreate = true;
        } elseif (strpos($mode, 'a') !== false
            || strpos($mode, '+') !== false
        ) {
            // modes: r+, w+, a, a+, x+
            $needCreate = true;
        } elseif (strpos($mode, 'w') !== false
            || strpos($mode, 'x') !== false
        ) {
            // modes: w, x
            $needCreate = true;
        }

        $located = static::locate($path);
        if ($needCreate && !file_exists($located)) {
            $locatedDir = static::locate(dirname($path));
            if (is_dir($locatedDir)) {
                $located = $locatedDir . DIRECTORY_SEPARATOR . basename($path);
            }
        }

        $this->_streamPath = $path;
        $this->_streamHandle = fopen($located, $mode);

        return true;
    }

    /**
     * Read from stream
     *
     * @param int $count
     * @return string
     */
    public function stream_read($count)
    {
        if (empty($this->_streamHandle)) {
            return '';
        }

        return fread($this->_streamHandle, $count);
    }

    public function stream_seek($offset, $whence = SEEK_SET)
    {
        if (empty($this->_streamHandle)) {
            return false;
        }

        return fseek($this->_streamHandle, $offset, $whence);
    }

    /**
     * Retrieve information about a file resource
     *
     * @return array|false
     */
    public function stream_stat()
    {
        if (empty($this->_streamHandle)) {
            return false;
        }

        return fstat($this->_streamHandle);
    }

    /**
     * Write to stream
     *
     * @param string $data
     * @return int
     */
    public function stream_write($data)
    {
        if (empty($this->_streamHandle)) {
            return 0;
        }

        $written = fwrite($this->_streamHandle, $data);

        return $written;
    }

    /**
     * Delete a file
     *
     * @param string $path
     * @return bool
     */
    public function unlink($path)
    {
        $located = static::locate($path);
        if (!file_exists($located)) {
            return false;
        }

        return unlink($located);
    }

    /**
     *  Retrieve information about a file
     *
     * @param string $path
     * @param int $flags
     * @return array|false
     */
    public function url_stat($path, $flags)
    {
        $located = static::locate($path);
        if (!file_exists($located)) {
            return false;
        }

        $stat = stat($located);

        return $stat;
    }

    /**
     * @param string $path
     * @return string
     * @throws \Exception
     */
    public static function locate($path)
    {
        $protocolLength = strlen(static::PROTOCOL);
        if (substr($path, 0, $protocolLength) !== static::PROTOCOL) {
            throw new \Exception('Unrecognized path ' . $path);
        }

        $pathWithoutProtocol = substr($path, $protocolLength + 3);
        list($xenforoDir,) = Router::getLocatePaths();
        $pathFromRoot = $xenforoDir . DIRECTORY_SEPARATOR . $pathWithoutProtocol;

        return Router::locateCached($pathFromRoot);
    }
}