<?php

namespace JoshuaMc1\Session\Drivers;

use JoshuaMc1\Session\Contracts\SessionInterface;

class FileSessionDriver implements SessionInterface
{
    private $filePath;

    public function __construct(array $config)
    {
        $this->filePath = $config['path'];
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
        return isset($_SESSION[$key]);
    }

    public function get(string $key, $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function clear(): void
    {
        $_SESSION = [];
    }

    public function count(): int
    {
        return count($_SESSION);
    }

    public function flash(string $key, $value)
    {
        $_SESSION['flash'][$key] = $value;
    }

    public function getFlash(string $key, $default = null)
    {
        if (isset($_SESSION['flash'][$key])) {
            $value = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $value;
        }

        return $default;
    }
}
