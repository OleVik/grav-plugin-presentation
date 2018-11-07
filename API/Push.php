<?php
/**
 * @package Storage
 *
 * @license MIT License
 */

namespace Grav\Plugin\PresentationPlugin\API;

/**
 * Simple REST API for communicating commands between pages
 *
 * @package Grav\Plugins
 */
class Push
{
    /**
     * Initiate Push Storage
     *
     * @param string $directory Path to directory.
     * @param string $file      Filename.
     */
    public function __construct($directory, $file)
    {
        $this->directory = $directory;
        $this->file = $file;
        $this->DS = DIRECTORY_SEPARATOR;
    }

    /**
     * Set Push Command
     *
     * @param string $command Command to execute.
     * 
     * @throws Exception Errors from file operations.
     * 
     * @return bool State of execution.
     */
    public function set($command)
    {
        try {
            if (!is_writable($this->directory)) {
                try {
                    mkdir($this->directory, 0755, true);
                } catch(\Exception $e) {
                    throw new \Exception($e);
                }
            }
            try {
                $data = json_encode($command);
                file_put_contents($this->directory . $this->DS . $this->file, $data);
                echo $data;
                return true;
            } catch(\Exception $e) {
                throw new \Exception($e);
            }
            return false;
        } catch(\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Get Push Command
     *
     * @throws Exception Errors from file operations.
     * 
     * @return bool Command to execute.
     */
    public function get()
    {
        try {
            $target = $this->directory . $this->DS . $this->file;
            if (file_exists($target)) {
                $data = file_get_contents($target);
                echo $data;
                return true;
            }
        } catch(\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Remove Push Command
     *
     * @throws Exception Errors from file operations.
     * 
     * @return bool State of execution.
     */
    public function remove()
    {
        try {
            $target = $this->directory . $this->DS . $this->file;
            unlink($target);
            echo 'removed ' . $this->file;
            return true;
        } catch(\Exception $e) {
            throw new \Exception($e);
        }
        return false;
    }
}