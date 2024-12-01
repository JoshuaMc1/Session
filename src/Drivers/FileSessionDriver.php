<?php

namespace JoshuaMc1\Session\Drivers;

use JoshuaMc1\Session\Contracts\SessionInterface;

class FileSessionDriver implements SessionInterface
{
    private $filePath;

    public function __construct(array $config)
    {
        $this->filePath = $config['path'];

        if (!file_exists($this->filePath)) {
            mkdir($this->filePath, 0777, true);
        }

        if (!is_writable($this->filePath)) {
            throw new \RuntimeException(sprintf('The session directory "%s" is not writable.', $this->filePath));
        }

        if (!is_readable($this->filePath)) {
            throw new \RuntimeException(sprintf('The session directory "%s" is not readable.', $this->filePath));
        }

        if (!is_dir($this->filePath)) {
            throw new \RuntimeException(sprintf('The session directory "%s" is not a directory.', $this->filePath));
        }
    }

    public function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_save_path($this->filePath);
            session_start();
        }
    }

    public function destroy($session_id = null)
    {
        session_destroy();
    }

    public function close()
    {
        session_write_close();
    }

    public function getId()
    {
        return session_id();
    }

    public function setId($id)
    {
        session_id($id);
    }

    public function regenerateId()
    {
        session_regenerate_id(true);
    }

    public function writeClose()
    {
        session_write_close();
    }

    public function has(string $key): bool
    {
        $this->ifNotStarted();
        return isset($_SESSION[$key]);
    }

    public function get(string $key, $default = null): mixed
    {
        $this->ifNotStarted();
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $this->ifNotStarted();
        $_SESSION[$key] = $value;
    }

    public function remove(string $key): void
    {
        $this->ifNotStarted();
        unset($_SESSION[$key]);
    }

    public function clear(): void
    {
        $this->ifNotStarted();
        $_SESSION = [];
    }

    public function count(): int
    {
        $this->ifNotStarted();
        return count($_SESSION);
    }

    public function flash(string $key, $value)
    {
        $this->ifNotStarted();
        $_SESSION['flash'][$key] = $value;
    }

    public function getFlash(string $key, $default = null)
    {
        $this->ifNotStarted();
        if (isset($_SESSION['flash'][$key])) {
            $value = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $value;
        }

        return $default;
    }

    private function ifNotStarted()
    {
        if (!isset($_SESSION)) {
            $this->start();
        }
    }
}
