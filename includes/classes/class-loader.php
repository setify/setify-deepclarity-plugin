<?php
/**
 * Loader class for registering all actions and filters
 *
 * @package DeepClarity
 */

namespace DeepClarity;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Loader
 *
 * Maintains a list of all hooks that are registered throughout
 * the plugin, and registers them with the WordPress API.
 */
class Loader {

    /**
     * The array of actions registered with WordPress.
     *
     * @var array
     */
    protected $actions = array();

    /**
     * The array of filters registered with WordPress.
     *
     * @var array
     */
    protected $filters = array();

    /**
     * Constructor
     */
    public function __construct() {
        // Initialize default hooks
        $this->define_hooks();
    }

    /**
     * Define default hooks
     */
    private function define_hooks() {
        // Add default actions and filters here
    }

    /**
     * Add a new action to the collection to be registered with WordPress.
     *
     * @param string $hook          The name of the WordPress action.
     * @param object $component     A reference to the instance of the object.
     * @param string $callback      The name of the function definition.
     * @param int    $priority      Optional. The priority. Default is 10.
     * @param int    $accepted_args Optional. The number of accepted arguments. Default is 1.
     */
    public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
    }

    /**
     * Add a new filter to the collection to be registered with WordPress.
     *
     * @param string $hook          The name of the WordPress filter.
     * @param object $component     A reference to the instance of the object.
     * @param string $callback      The name of the function definition.
     * @param int    $priority      Optional. The priority. Default is 10.
     * @param int    $accepted_args Optional. The number of accepted arguments. Default is 1.
     */
    public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
    }

    /**
     * A utility function that is used to register the actions and hooks into a single collection.
     *
     * @param array  $hooks         The collection of hooks.
     * @param string $hook          The name of the WordPress filter.
     * @param object $component     A reference to the instance of the object.
     * @param string $callback      The name of the function definition.
     * @param int    $priority      The priority.
     * @param int    $accepted_args The number of accepted arguments.
     * @return array The collection of actions and filters registered with WordPress.
     */
    private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        );

        return $hooks;
    }

    /**
     * Register the filters and actions with WordPress.
     */
    public function run() {
        foreach ( $this->filters as $hook ) {
            add_filter(
                $hook['hook'],
                array( $hook['component'], $hook['callback'] ),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        foreach ( $this->actions as $hook ) {
            add_action(
                $hook['hook'],
                array( $hook['component'], $hook['callback'] ),
                $hook['priority'],
                $hook['accepted_args']
            );
        }
    }
}
