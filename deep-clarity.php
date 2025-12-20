<?php

/**
 * Plugin Name: Deep Clarity 2
 * Plugin URI: https://example.com/deep-clarity
 * Description: A powerful analytics and visualization plugin for WordPress.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: deep-clarity
 * Domain Path: /languages
 *
 * @package DeepClarity
 */

// Prevent direct access
if (! defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('DEEP_CLARITY_VERSION', '1.0.0');
define('DEEP_CLARITY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DEEP_CLARITY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DEEP_CLARITY_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader for classes
spl_autoload_register(function ($class) {
    $prefix = 'DeepClarity\\';
    $base_dir = DEEP_CLARITY_PLUGIN_DIR . 'includes/classes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . 'class-' . strtolower(str_replace('\\', '-', $relative_class)) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Include core files
require_once DEEP_CLARITY_PLUGIN_DIR . 'includes/classes/class-loader.php';
require_once DEEP_CLARITY_PLUGIN_DIR . 'includes/classes/class-assets.php';
require_once DEEP_CLARITY_PLUGIN_DIR . 'includes/classes/class-admin.php';
require_once DEEP_CLARITY_PLUGIN_DIR . 'includes/classes/class-sessions.php';
require_once DEEP_CLARITY_PLUGIN_DIR . 'includes/classes/class-shortcodes.php';
require_once DEEP_CLARITY_PLUGIN_DIR . 'includes/classes/class-helpers.php';

/**
 * Main plugin class
 */
final class Deep_Clarity
{

    /**
     * Single instance of the class
     *
     * @var Deep_Clarity
     */
    private static $instance = null;

    /**
     * Loader instance
     *
     * @var DeepClarity\Loader
     */
    public $loader;

    /**
     * Assets instance
     *
     * @var DeepClarity\Assets
     */
    public $assets;

    /**
     * Admin instance
     *
     * @var DeepClarity\Admin
     */
    public $admin;

    /**
     * Sessions instance
     *
     * @var DeepClarity\Sessions
     */
    public $sessions;

    /**
     * Shortcodes instance
     *
     * @var DeepClarity\Shortcodes
     */
    public $shortcodes;

    /**
     * Helpers instance
     *
     * @var DeepClarity\Helpers
     */
    public $helpers;

    /**
     * Get single instance of the class
     *
     * @return Deep_Clarity
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->init();
    }

    /**
     * Initialize the plugin
     */
    private function init()
    {
        // Initialize classes
        $this->loader     = new DeepClarity\Loader();
        $this->assets     = new DeepClarity\Assets();
        $this->admin      = new DeepClarity\Admin();
        $this->sessions   = new DeepClarity\Sessions();
        $this->shortcodes = new DeepClarity\Shortcodes();
        $this->helpers    = new DeepClarity\Helpers();

        // Load textdomain
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Register activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Run the loader
        $this->loader->run();
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain()
    {
        load_plugin_textdomain(
            'deep-clarity',
            false,
            dirname(DEEP_CLARITY_PLUGIN_BASENAME) . '/languages/'
        );
    }

    /**
     * Plugin activation
     */
    public function activate()
    {
        // Activation logic here
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate()
    {
        // Deactivation logic here
        flush_rewrite_rules();
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserializing
     */
    public function __wakeup()
    {
        throw new \Exception('Cannot unserialize singleton');
    }
}

/**
 * Returns the main instance of Deep_Clarity
 *
 * @return Deep_Clarity
 */
function deep_clarity()
{
    return Deep_Clarity::instance();
}

// Initialize the plugin
deep_clarity();
