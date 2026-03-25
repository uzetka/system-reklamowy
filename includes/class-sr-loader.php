<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SR_Loader {

    /**
     * @var array
     */
    protected $actions = [];

    /**
     * Dodanie akcji do kolejki
     */
    public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->actions[] = [
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        ];
    }

    /**
     * Rejestracja wszystkich hooków z kolejki
     */
    public function run() {
        foreach ( $this->actions as $hook ) {
            add_action(
                $hook['hook'],
                [ $hook['component'], $hook['callback'] ],
                $hook['priority'],
                $hook['accepted_args']
            );
        }
    }
}