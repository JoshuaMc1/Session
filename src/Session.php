<?php

namespace JoshuaMc1\Session;

class Session
{
    private static $instance;
    private $config;
    private $driverInstance;

    private function __construct()
    {
        $this->config = require __DIR__ . '/../../../../config/session.php';

        $this->initializeDriver();
    }

    /**
     * Returns the singleton instance of the Session class
     *
     * if the instance doesn't exist, it will be created
     * 
     * @return self The singleton instance
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function __callStatic($name, $arguments)
    {
        $instance = self::getInstance();

        if (!method_exists($instance->driverInstance, $name)) {
            throw new \BadMethodCallException("Method '{$name}' not found.");
        }

        return call_user_func_array([$instance->driverInstance, $name], $arguments);
    }

    private function initializeDriver()
    {
        $driver = $this->config['driver'];
        $drivers = $this->config['drivers'];

        if (!isset($drivers[$driver])) {
            throw new \InvalidArgumentException("Driver '{$driver}' not found.");
        }

        $driverConfig = $drivers[$driver];

        switch ($driver) {
            case 'file':
                $this->driverInstance = new Drivers\FileSessionDriver($driverConfig);
                break;
            case 'sqlite':
                $this->driverInstance = new Drivers\SQLiteSessionDriver($driverConfig);
                break;
            case 'mysql':
                $this->driverInstance = new Drivers\MySQLSessionDriver($driverConfig);
                break;
            default:
                throw new \InvalidArgumentException("Driver '{$driver}' not supported.");
        }
    }

    public static function start()
    {
        self::getInstance()->driverInstance->start();
    }

    public static function destroy()
    {
        self::getInstance()->driverInstance->destroy();
    }

    public static function close()
    {
        self::getInstance()->driverInstance->close();
    }

    public static function getId()
    {
        self::getInstance()->driverInstance->getId();
    }

    public static function setId($id)
    {
        self::getInstance()->driverInstance->setId($id);
    }

    public static function regenerateId()
    {
        self::getInstance()->driverInstance->regenerateId();
    }

    public static function writeClose()
    {
        self::getInstance()->driverInstance->writeClose();
    }

    public static function get(string $key, $default = null)
    {
        self::getInstance()->driverInstance->get($key, $default);
    }

    public static function has(string $key)
    {
        self::getInstance()->driverInstance->has($key);
    }

    public static function set(string $key, $value)
    {
        self::getInstance()->driverInstance->set($key, $value);
    }

    public static function remove(string $key)
    {
        self::getInstance()->driverInstance->remove($key);
    }

    public static function flash(string $key, $value)
    {
        self::getInstance()->driverInstance->flash($key, $value);
    }

    public static function getFlash(string $key, $default = null)
    {
        self::getInstance()->driverInstance->getFlash($key, $default);
    }

    public static function clear()
    {
        self::getInstance()->driverInstance->clear();
    }

    public static function count()
    {
        self::getInstance()->driverInstance->count();
    }
}
