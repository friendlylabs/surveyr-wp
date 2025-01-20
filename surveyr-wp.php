<?php

declare(strict_types=1);

namespace SurveyrWP;

/**
 * Plugin Name: Surveyr WP
 * Plugin URI: https://github.com/friendlylabs/surveyr-wp
 * Description: A survey plugin for WordPress.
 * Version: 1.0
 * Author: Abdulbasit Rubeya <FriendlyLabs>
 * Author URI: https://github.com/ibnsultan
 * License: GPL2
 */

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    http_response_code(403);
    exit('Forbidden');
}

// Check if the class already exists to prevent conflicts
if (!class_exists(SurveyrWP::class)) {
    
    final class SurveyrWP
    {
        public const PLUGIN_DIR = __DIR__;
        public const INCLUDES_DIR = self::PLUGIN_DIR . '/includes';

        /**
         * Constructor.
         */
        public function __construct()
        {
            // Load dependencies and utility functions
            $this->loadDependencies();
        }

        /**
         * Load necessary dependencies.
         */
        private function loadDependencies(): void
        {
            if (file_exists(self::PLUGIN_DIR . '/vendor/autoload.php')) {
                require_once self::PLUGIN_DIR . '/vendor/autoload.php';
            }

            $this->includeFile(self::INCLUDES_DIR . '/utilities.php');
        }

        /**
         * Initialize the plugin.
         */
        public function initialize(): void
        {
            $this->includeFile(self::INCLUDES_DIR . '/options.php');
            $this->includeFile(self::INCLUDES_DIR . '/handler.php');
        }

        /**
         * Safely include a file.
         *
         * @param string $filePath Path to the file to include.
         */
        private function includeFile(string $filePath): void
        {
            if (file_exists($filePath)) {
                include_once $filePath;
            } else {
                error_log("Failed to include file: {$filePath}");
            }
        }
    }

    // Instantiate and initialize the plugin
    $surveyr = new SurveyrWP();
    $surveyr->initialize();

    return;
}

/**
 * Prevent Conflict
 * 
 * If the Surveyr WP class is already defined, display an error message.
 */

$reflector = new \ReflectionClass(SurveyrWP::class);
$fileName = $reflector->getFileName();

\add_action('admin_notices', function () use ($fileName) {
    echo "<div class='notice notice-error is-dismissible'>
        <p><strong>Error:</strong> The Surveyr WP plugin class is already defined in <code>{$fileName}</code>.</p>
        <p>Please update, deactivate, or delete the conflicting plugin to resolve this issue.</p>
    </div>";
});

return;
