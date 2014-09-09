<?php

/*
Plugin Name: HM Elastic Search
Description: Developer's ElasticSearch integration for WordPress
Author: Theo Savage
Version: 0.1
Author URI: http://hmn.md/
*/

namespace HMES;

require_once ( __DIR__ . '/hm-elasticsearch-admin.php' );

include_dir( __DIR__ . '/classes' );
include_dir( __DIR__ . '/lib/elasticsearch/src' );

/**
 * Init ell HMES type classes on plugins_loaded hook
 */
function init_types() {

	if ( ! Configuration::get_is_enabled() ) {
		return;
	}

	Type_Manager::init_types();
}

add_action( 'plugins_loaded', 'hm_es_init_types' );

/**
 * Get the list of HMES type classes by name
 *
 * @return array
 */
function get_type_class_names() {
	return array(
		'post'      => '\HMES\Types\Post',
		'user'      => '\HMES\Types\User',
		'comment'   => '\HMES\Types\Comment',
		'term'      => '\HMES\Types\Term'
	);
}

/**
 * Init elasticsearch for given connection and index/type args
 *
 * @param array $connection_args
 * @param array $index_creation_args
 * @return array|bool
 */
function init_elastic_search_index( $connection_args = array(), $index_creation_args = array() ) {

	$es = Wrapper::get_instance( $connection_args );

	if ( ! $es->is_connection_available( array( 'log' => false ) ) ) {
		return false;
	}

	if ( ! $es->is_index_created() ) {

		return $es->create_index( $index_creation_args );
	}

	return false;
}

/**
 * Init elasticsearch for given connection and index/type args
 *
 * @param array $connection_args
 * @param array $index_deletion_args
 * @return array|bool|\Exception
 */
function delete_elastic_search_index( $connection_args = array(), $index_deletion_args = array() ) {

	$es = Wrapper::get_instance( $connection_args );

	if ( ! $es->is_connection_available( array( 'log' => false ) ) ) {
		return false;
	}

	if (  $es->is_index_created() ) {

		return $es->delete_index( $index_deletion_args );
	}

	return false;
}

/**
 * Recursively include all php files in a directory and subdirectories
 *
 * @param $dir
 * @param int $depth
 * @param int $max_scan_depth
 */
function include_dir( $dir, $depth = 0, $max_scan_depth = 5 ) {

	if ( $depth > $max_scan_depth ) {
		return;
	}

	// require all php files
	$scan = glob( $dir . '/*' );

	foreach ( $scan as $path ) {
		if ( preg_match( '/\.php$/', $path ) ) {
			require_once $path;
		} elseif ( is_dir( $path ) ) {
			include_dir( $path, $depth + 1, $max_scan_depth );
		}
	}
}