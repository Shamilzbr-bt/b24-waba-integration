<?php

namespace App\Config;

use Dotenv\Dotenv;

/**
 * Configuration Manager
 * 
 * Handles loading and accessing configuration values from .env file
 */
class ConfigManager
{
    private static $instance = null;
    private $config = [];
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
        $this->loadConfig();
    }
    
    /**
     * Get singleton instance
     * 
     * @return ConfigManager
     */
    public static function getInstance(): ConfigManager
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Load configuration from .env file
     */
    private function loadConfig(): void
    {
        // Load .env file from config directory
        $dotenv = Dotenv::createImmutable(dirname(__DIR__, 2) . '/config');
        $dotenv->safeLoad();
        
        // Store all environment variables in config array
        foreach ($_ENV as $key => $value) {
            $this->config[$key] = $value;
        }
    }
    
    /**
     * Get configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value
     */
    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
    
    /**
     * Check if configuration key exists
     * 
     * @param string $key Configuration key
     * @return bool True if key exists
     */
    public function has(string $key): bool
    {
        return isset($this->config[$key]);
    }
    
    /**
     * Get all configuration values
     * 
     * @return array All configuration values
     */
    public function all(): array
    {
        return $this->config;
    }
}
