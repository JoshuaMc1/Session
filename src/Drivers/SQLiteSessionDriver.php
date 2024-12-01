<?php

namespace JoshuaMc1\Session\Drivers;

use JoshuaMc1\Session\Contracts\SessionInterface;
use PDO;

class SQLiteSessionDriver implements SessionInterface
{
    private $pdo;
    private $config;
    private $table;

    public function __construct(array $config)
    {
        try {
            $this->config = $config;
            $this->table = $config['table'] ?? 'sessions';

            $dsn = "sqlite:{$config['database']}";
            $this->pdo = new PDO($dsn);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $this->ensureTableExists();
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }
    }

    public function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_save_handler(
                [$this, 'open'],
                [$this, 'close'],
                [$this, 'read'],
                [$this, 'write'],
                [$this, 'destroy'],
                [$this, 'gc']
            );
            session_start();
        }
    }

    public function open($savePath, $sessionName)
    {
        return $this->pdo instanceof PDO;
    }

    public function close()
    {
        $this->pdo = null;
        return true;
    }

    public function read($id)
    {
        $stmt = $this->pdo->prepare("SELECT data FROM {$this->escapeIdentifier($this->table)} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->decrypt($row['data']) : '';
    }

    public function write($session_id, $session_data)
    {
        $stmt = $this->pdo->prepare(
            "REPLACE INTO {$this->escapeIdentifier($this->table)} (id, data, last_activity) VALUES (:id, :data, :last_activity)"
        );

        return $stmt->execute([
            'id' => $session_id,
            'data' => $this->encrypt($session_data),
            'last_activity' => time(),
        ]);
    }

    public function destroy($session_id = null)
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->escapeIdentifier($this->table)} WHERE id = :id");
        return $stmt->execute(['id' => $session_id]);
    }

    public function gc($max_lifetime)
    {
        $lifetime = $this->config['lifetime'] ?? $max_lifetime;
        $stmt = $this->pdo->prepare("DELETE FROM {$this->escapeIdentifier($this->table)} WHERE last_activity < :last_activity");
        return $stmt->execute(['last_activity' => time() - $lifetime]);
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

    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function remove(string $key)
    {
        unset($_SESSION[$key]);
    }

    public function clear()
    {
        $_SESSION = [];
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

    public function count(): int
    {
        return count($_SESSION);
    }

    private function ensureTableExists()
    {
        $query = "
            CREATE TABLE IF NOT EXISTS {$this->escapeIdentifier($this->table)} (
                id TEXT PRIMARY KEY,
                data TEXT NOT NULL,
                last_activity INTEGER NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ";

        $this->pdo->exec($query);
    }

    private function escapeIdentifier($name)
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }

    private function encrypt($data)
    {
        $key = $this->config['encryption_key'];
        return openssl_encrypt($data, 'AES-256-CBC', $key, 0, $key);
    }

    private function decrypt($data)
    {
        $key = $this->config['encryption_key'];
        return openssl_decrypt($data, 'AES-256-CBC', $key, 0, $key);
    }
}