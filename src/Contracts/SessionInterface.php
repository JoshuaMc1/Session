<?php

namespace JoshuaMc1\Session\Contracts;

interface SessionInterface
{
    /**
     * Starts the session if it has not already been started.
     */
    public function start();

    /**
     * Destroys the session.
     */
    public function destroy($session_id = null);

    /**
     * Closes the session.
     */
    public function close();

    /**
     * Returns the session ID.
     * 
     * @return string|null
     */
    public function getId();

    /**
     * Sets the session ID.
     */
    public function setId($id);

    /**
     * Regenerates the session ID.
     */
    public function regenerateId();

    /**
     * Writes and closes the session.
     */
    public function writeClose();

    /**
     * Returns the value of a session variable.
     * 
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Sets the value of a session variable.
     * 
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Sets the value of a session variable.
     * 
     * @return void
     */
    public function set(string $key, $value);

    /**
     * Removes a session variable.
     * 
     * @return void
     */
    public function remove(string $key);

    /**
     * Flashes a session variable.
     * 
     * @return void
     */
    public function flash(string $key, $value);

    /**
     * Returns the value of a flashed session variable.
     * 
     * @return mixed
     */
    public function getFlash(string $key, $default = null);

    /**
     * Clears all session variables.
     * 
     * @return void
     */
    public function clear();

    /**
     * Returns the number of session variables.
     * 
     * @return int
     */
    public function count(): int;
}
