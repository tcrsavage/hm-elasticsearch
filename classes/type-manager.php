<?php

namespace HMES;

class Type_Manager {

	protected static $types = array();

	/**
	 * Initialise the HMES type classes (verify setup and set hooks)
	 */
	public static function init_types() {

		foreach ( get_type_class_names() as $class_name ) {

			$class = new $class_name();

			self::verify_setup( $class );

			self::set_hooks( $class );

			self::$types[] = $class;
		}
	}

	/**
	 * Get all HMES type class instances
	 *
	 * @return Types\Base[]
	 */
	public static function get_types() {

		return self::$types;
	}

	/**
	 * Get a HMES type class instance from the type name
	 *
	 * @param $type_name
	 * @return Types\Base|bool
	 */
	public static function get_type( $type_name ) {

		foreach ( self::get_types() as $type ) {

			if ( $type->name === $type_name ) {

				return $type;
			}
		}

		return false;
	}

	/**
	 * Verify the setup of a HMES type class
	 *
	 * @param $class
	 * @throws \Exception
	 */
	protected static function verify_setup( $class ) {

		if ( ! $class->name ) {
			throw new \Exception( 'Type name must be defined ' . get_class( $class ) );
		}
	}

	/**
	 * Set the hooks of a HMES type class
	 *
	 * @param $class
	 */
	protected static function set_hooks( $class ) {

		foreach ( $class->index_hooks as $hook ) {

			add_action( $hook, array( $class, 'index_callback' ), 10, 5 );

			add_action( $hook, function() use ( $hook ) {

				error_log( 'index_callback - from: ' . $hook );
			} );
		}

		foreach ( $class->delete_hooks as $hook ) {

			add_action( $hook, array( $class, 'delete_callback' ), 10, 5 );

			add_action( $hook, function() use ( $hook ) {

				error_log( 'delete_callback - from: ' . $hook );
			} );

		}

		foreach ( $class->mappable_hooks as $hook => $function ) {

			add_action( $hook, array( $class, $function ), 10, 5 );

			add_action( $hook, function() use ( $hook ) {

				error_log( 'custom_callback - from: ' . $hook );
			} );
		}

		add_action( 'shutdown', array( $class, 'execute_queued_actions' ), 10, 5 );
	}
}