<?php

namespace JoshuaMc1\Session\Drivers;

use JoshuaMc1\Session\Contracts\SessionInterface;
use PDO;

class SQLiteSessionDriver implements SessionInterface
{
    private $pdo;
    private $config;
    private $table;
    private $cache = [];

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
        if (isset($this->cache[$id])) {
            return $this->cache[$id];
        }

        $stmt = $this->pdo->prepare("SELECT data FROM {$this->escapeIdentifier($this->table)} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->cache[$id] = $row ? $this->decrypt($row['data']) : '';
        return $this->cache[$id];
    }

    public function write($session_id, $session_data)
    {
        try {
            $stmt = $this->pdo->prepare(
                "REPLACE INTO {$this->escapeIdentifier($this->table)} (id, data, last_activity) VALUES (:id, :data, :last_activity)"
            );

            $stmt->execute([
                'id' => $session_id,
                'data' => $this->encrypt($session_data),
                'last_activity' => time(),
            ]);

            $this->cache[$session_id] = $session_data;

            return true;
        } catch (\Exception $e) {
            error_log('Session write failed: ' . $e->getMessage());
            return false;
        }
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
        $this->ifNotStarted();
        return isset($_SESSION[$key]) || isset($_SESSION['flash'][$key]);
    }

    public function get(string $key, $default = null)
    {
        $this->ifNotStarted();
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, $value)
    {
        $this->ifNotStarted();
        $_SESSION[$key] = $value;
        $this->write(session_id(), session_encode());
    }

    public function remove(string $key)
    {
        $this->ifNotStarted();
        unset($_SESSION[$key]);
        $this->write(session_id(), session_encode());
    }

    public function clear()
    {
        $this->ifNotStarted();
        $_SESSION = [];
        $this->write(session_id(), session_encode());
    }

    public function flash(string $key, $value)
    {
        $this->ifNotStarted();
        $_SESSION['flash'][$key] = $value;
        $this->write(session_id(), session_encode());
    }

    public function getFlash(string $key, $default = null)
    {
        $this->ifNotStarted();
        if (isset($_SESSION['flash'][$key])) {
            $value = $_SESSION['flash'][$key];

            unset($_SESSION['flash'][$key]);

            if (empty($_SESSION['flash'])) {
                unset($_SESSION['flash']);
            }

            $this->write(session_id(), session_encode());
            return $value;
        }

        return $default;
    }

    public function count(): int
    {
        $this->ifNotStarted();
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

        if (strlen($key) !== 32) {
            throw new \RuntimeException('Encryption key must be exactly 256 bits (32 characters).');
        }

        $cipher = 'AES-256-CBC';
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));

        $encrypted = openssl_encrypt($data, $cipher, $key, 0, $iv);

        if ($encrypted === false) {
            throw new \RuntimeException('Encryption failed.');
        }

        return base64_encode($iv . '::' . $encrypted);
    }

    private function decrypt($data)
    {
        $key = $this->config['encryption_key'];

        if (strlen($key) !== 32) {
            throw new \RuntimeException('Encryption key must be exactly 256 bits (32 characters).');
        }

        $cipher = 'AES-256-CBC';
        $data = base64_decode($data);

        [$iv, $encryptedData] = explode('::', $data, 2);

        $decrypted = openssl_decrypt($encryptedData, $cipher, $key, 0, $iv);

        if ($decrypted === false) {
            throw new \RuntimeException('Decryption failed.');
        }

        return $decrypted;
    }

    private function ifNotStarted()
    {
        if (!isset($_SESSION)) {
            $this->start();
        }
    }
}
