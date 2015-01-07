<?php

namespace HMES;

use HMES\Types\Base;

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

		self::init_cron();
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
	protected static function set_hooks( Base $class ) {

		if ( ! Configuration::get_is_indexing_enabled() ) {
			return;
		}

		foreach ( $class->index_hooks as $hook ) {

			add_action( $hook, array( $class, 'index_callback' ), 10, 5 );
		}

		foreach ( $class->delete_hooks as $hook ) {

			add_action( $hook, array( $class, 'delete_callback' ), 10, 5 );
		}

		foreach ( $class->mappable_hooks as $hook => $function ) {

			add_action( $hook, array( $class, $function ), 10, 5 );
		}

		add_action( 'shutdown',  array( $class, 'save_actions' ) );
	}

	protected static function init_cron() {

		if ( ! Configuration::get_is_indexing_enabled() ) {
			return;
		}

		add_action( 'hmes_update_index_cron', array( 'HMES\Type_Manager', 'execute_index_cron' ), 10, 5 );

		if ( wp_next_scheduled( 'hmes_update_index_cron' ) ) {
			return;
		}

		wp_schedule_event( time(), 'minutes_10', 'hmes_update_index_cron' );
	}

	public static function execute_index_cron() {

		if ( ! Configuration::get_is_indexing_enabled() ) {
			return;
		}

		foreach ( self::get_types() as $type ) {

			$type->execute_queued_actions();
		}

	}
}