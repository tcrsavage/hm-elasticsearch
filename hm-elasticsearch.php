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
include_dir( __DIR__ . '/lib/elasticsearch/src' );
include_dir( __DIR__ . '/classes' );

/**
 * Init ell HMES type classes on plugins_loaded hook
 */
function init_types() {

	if ( ! Configuration::get_is_indexing_enabled() ) {
		return;
	}

	Type_Manager::init_types();
}

add_action( 'plugins_loaded', '\\HMES\\init_types' );

/**
 * Get the list of HMES type classes by name
 *
 * @return array
 */
function get_type_class_names() {
	return array(
		'post'      => '\\HMES\\Types\Post',
		'user'      => '\\HMES\\Types\User',
		'comment'   => '\\HMES\\Types\Comment',
		'term'      => '\\HMES\\Types\Term'
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

	$es->disable_logging();

	if ( ! $es->is_connection_available() ) {
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

	$es->disable_logging();

	if ( ! $es->is_connection_available() ) {
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

add_action( 'wp_cron_hmes_reindex_types', '\\HMES\\reindex_types', 10 );

function reindex_types( $type_names ) {

	foreach ( $type_names as $type_name ) {

		$type = Type_Manager::get_type( $type_name );

		if ( $type ) {

			$type->index_all();
		}
	}
}

add_action( 'init', function() {

//	foreach( Type_Manager::get_types() as $type ) {
//
//		var_dump( $type->get_status() );
//	}
//	exit;

//
//	$r = $type->search( array(
//		"query" => array(
//			'bool' => array(
//				'must' => array(
//					array(
//						'term' => array(
//							'post_type' => 'post',
//						),
//					),
//				),
//			)
//		)
//	) );
//
//	echo '<pre>';
//	print_r( $r );
//	echo '</pre>';
//	exit;

	$str1 = "{\"index\":{\"_id\":39,\"_index\":\"hmes\",\"_type\":\"post\"}}\n{\"ID\":39,\"post_author\":\"1\",\"post_date\":\"2014-09-06 03:27:54\",\"post_date_gmt\":\"2014-09-06 03:27:54\",\"post_content\":\"wt\",\"post_title\":\"werw\",\"post_excerpt\":\"\",\"post_status\":\"publish\",\"comment_status\":\"open\",\"ping_status\":\"open\",\"post_password\":\"\",\"post_name\":\"werw\",\"to_ping\":\"\",\"pinged\":\"\",\"post_modified\":\"2014-09-11 11:12:26\",\"post_modified_gmt\":\"2014-09-11 11:12:26\",\"post_content_filtered\":\"\",\"post_parent\":0,\"guid\":\"http:\\\/\\\/ceh.dev\\\/?p=39\",\"menu_order\":0,\"post_type\":\"post\",\"post_mime_type\":\"\",\"comment_count\":\"0\",\"filter\":\"raw\",\"meta\":[],\"taxonomies\":{\"category\":[1]}}\n{\"index\":{\"_id\":9,\"_index\":\"hmes\",\"_type\":\"post\"}}\n{\"ID\":9,\"post_author\":\"1\",\"post_date\":\"2014-09-05 10:06:16\",\"post_date_gmt\":\"2014-09-05 10:06:16\",\"post_content\":\"Post title\",\"post_title\":\"Test 1\",\"post_excerpt\":\"\",\"post_status\":\"publish\",\"comment_status\":\"open\",\"ping_status\":\"open\",\"post_password\":\"\",\"post_name\":\"test-post-1\",\"to_ping\":\"\",\"pinged\":\"\",\"post_modified\":\"2014-09-11 07:27:25\",\"post_modified_gmt\":\"2014-09-11 07:27:25\",\"post_content_filtered\":\"\",\"post_parent\":0,\"guid\":\"http:\\\/\\\/ceh.dev\\\/?p=9\",\"menu_order\":0,\"post_type\":\"post\",\"post_mime_type\":\"\",\"comment_count\":\"0\",\"filter\":\"raw\",\"meta\":[],\"taxonomies\":{\"category\":[1]}}\n{\"index\":{\"_id\":4,\"_index\":\"hmes\",\"_type\":\"post\"}}\n{\"ID\":4,\"post_author\":\"1\",\"post_date\":\"2014-07-30 11:05:52\",\"post_date_gmt\":\"2014-07-30 11:05:52\",\"post_content\":\"Content krhwer\",\"post_title\":\"Post title\",\"post_excerpt\":\"\",\"post_status\":\"publish\",\"comment_status\":\"open\",\"ping_status\":\"open\",\"post_password\":\"\",\"post_name\":\"test\",\"to_ping\":\"\",\"pinged\":\"\",\"post_modified\":\"2014-09-05 10:48:46\",\"post_modified_gmt\":\"2014-09-05 10:48:46\",\"post_content_filtered\":\"\",\"post_parent\":0,\"guid\":\"http:\\\/\\\/ceh.dev\\\/?p=4\",\"menu_order\":0,\"post_type\":\"post\",\"post_mime_type\":\"\",\"comment_count\":\"0\",\"filter\":\"raw\",\"meta\":[],\"taxonomies\":{\"category\":[1]}}\n{\"index\":{\"_id\":1,\"_index\":\"hmes\",\"_type\":\"post\"}}\n{\"ID\":1,\"post_author\":\"1\",\"post_date\":\"2014-02-26 05:47:40\",\"post_date_gmt\":\"2014-02-26 05:47:40\",\"post_content\":\"Welcome to WordPress. This is your first post. Edit or delete it, then start blogging!\",\"post_title\":\"Hello world!\",\"post_excerpt\":\"\",\"post_status\":\"publish\",\"comment_status\":\"open\",\"ping_status\":\"open\",\"post_password\":\"\",\"post_name\":\"hello-world\",\"to_ping\":\"\",\"pinged\":\"\",\"post_modified\":\"2014-09-05 08:37:17\",\"post_modified_gmt\":\"2014-09-05 08:37:17\",\"post_content_filtered\":\"\",\"post_parent\":0,\"guid\":\"http:\\\/\\\/ceh.dev\\\/?p=1\",\"menu_order\":0,\"post_type\":\"post\",\"post_mime_type\":\"\",\"comment_count\":\"1\",\"filter\":\"raw\",\"meta\":[],\"taxonomies\":{\"category\":[1],\"post_tag\":[3,4]}}\n{\"index\":{\"_id\":2,\"_index\":\"hmes\",\"_type\":\"post\"}}\n{\"ID\":2,\"post_author\":\"1\",\"post_date\":\"2014-02-26 05:47:40\",\"post_date_gmt\":\"2014-02-26 05:47:40\",\"post_content\":\"This is an example page. It's different from a blog post because it will stay in one place and will show up in your site navigation (in most themes). Most people start with an About page that introduces them to potential site visitors. It might say something like this:\\n\\n<blockquote>Hi there! I'm a bike messenger by day, aspiring actor by night, and this is my blog. I live in Los Angeles, have a great dog named Jack, and I like pi&#241;a coladas. (And gettin' caught in the rain.)<\\\/blockquote>\\n\\n...or something like this:\\n\\n<blockquote>The XYZ Doohickey Company was founded in 1971, and has been providing quality doohickeys to the public ever since. Located in Gotham City, XYZ employs over 2,000 people and does all kinds of awesome things for the Gotham community.<\\\/blockquote>\\n\\nAs a new WordPress user, you should go to <a href=\\\"http:\\\/\\\/ceh.dev\\\/wordpress\\\/wp-admin\\\/\\\">your dashboard<\\\/a> to delete this page and create new pages for your content. Have fun!\",\"post_title\":\"Sample Page\",\"post_excerpt\":\"\",\"post_status\":\"publish\",\"comment_status\":\"open\",\"ping_status\":\"open\",\"post_password\":\"\",\"post_name\":\"sample-page\",\"to_ping\":\"\",\"pinged\":\"\",\"post_modified\":\"2014-02-26 05:47:40\",\"post_modified_gmt\":\"2014-02-26 05:47:40\",\"post_content_filtered\":\"\",\"post_parent\":0,\"guid\":\"http:\\\/\\\/ceh.dev\\\/?page_id=2\",\"menu_order\":0,\"post_type\":\"page\",\"post_mime_type\":\"\",\"comment_count\":\"0\",\"filter\":\"raw\",\"meta\":[],\"taxonomies\":[]}\n";
	$str2 = "{\"index\":{\"_id\":39,\"_index\":\"hmes\",\"_type\":\"post\"}}\n{\"ID\":39,\"post_author\":\"1\",\"post_date\":\"2014-09-06 03:27:54\",\"post_date_gmt\":\"2014-09-06 03:27:54\",\"post_content\":\"wt\",\"post_title\":\"werw\",\"post_excerpt\":\"\",\"post_status\":\"publish\",\"comment_status\":\"open\",\"ping_status\":\"open\",\"post_password\":\"\",\"post_name\":\"werw\",\"to_ping\":\"\",\"pinged\":\"\",\"post_modified\":\"2014-09-11 11:12:26\",\"post_modified_gmt\":\"2014-09-11 11:12:26\",\"post_content_filtered\":\"\",\"post_parent\":0,\"guid\":\"http:\\\/\\\/ceh.dev\\\/?p=39\",\"menu_order\":0,\"post_type\":\"post\",\"post_mime_type\":\"\",\"comment_count\":\"0\",\"filter\":\"raw\",\"meta\":[],\"taxonomies\":{\"category\":[1]}}\n{\"index\":{\"_id\":9,\"_index\":\"hmes\",\"_type\":\"post\"}}\n{\"ID\":9,\"post_author\":\"1\",\"post_date\":\"2014-09-05 10:06:16\",\"post_date_gmt\":\"2014-09-05 10:06:16\",\"post_content\":\"Post title\",\"post_title\":\"Test 1\",\"post_excerpt\":\"\",\"post_status\":\"publish\",\"comment_status\":\"open\",\"ping_status\":\"open\",\"post_password\":\"\",\"post_name\":\"test-post-1\",\"to_ping\":\"\",\"pinged\":\"\",\"post_modified\":\"2014-09-11 07:27:25\",\"post_modified_gmt\":\"2014-09-11 07:27:25\",\"post_content_filtered\":\"\",\"post_parent\":0,\"guid\":\"http:\\\/\\\/ceh.dev\\\/?p=9\",\"menu_order\":0,\"post_type\":\"post\",\"post_mime_type\":\"\",\"comment_count\":\"0\",\"filter\":\"raw\",\"meta\":[],\"taxonomies\":{\"category\":[1]}}\n{\"index\":{\"_id\":4,\"_index\":\"hmes\",\"_type\":\"post\"}}\n{\"ID\":4,\"post_author\":\"1\",\"post_date\":\"2014-07-30 11:05:52\",\"post_date_gmt\":\"2014-07-30 11:05:52\",\"post_content\":\"Content krhwer\",\"post_title\":\"Post title\",\"post_excerpt\":\"\",\"post_status\":\"publish\",\"comment_status\":\"open\",\"ping_status\":\"open\",\"post_password\":\"\",\"post_name\":\"test\",\"to_ping\":\"\",\"pinged\":\"\",\"post_modified\":\"2014-09-05 10:48:46\",\"post_modified_gmt\":\"2014-09-05 10:48:46\",\"post_content_filtered\":\"\",\"post_parent\":0,\"guid\":\"http:\\\/\\\/ceh.dev\\\/?p=4\",\"menu_order\":0,\"post_type\":\"post\",\"post_mime_type\":\"\",\"comment_count\":\"0\",\"filter\":\"raw\",\"meta\":[],\"taxonomies\":{\"category\":[1]}}\n{\"index\":{\"_id\":1,\"_index\":\"hmes\",\"_type\":\"post\"}}\n{\"ID\":1,\"post_author\":\"1\",\"post_date\":\"2014-02-26 05:47:40\",\"post_date_gmt\":\"2014-02-26 05:47:40\",\"post_content\":\"Welcome to WordPress. This is your first post. Edit or delete it, then start blogging!\",\"post_title\":\"Hello world!\",\"post_excerpt\":\"\",\"post_status\":\"publish\",\"comment_status\":\"open\",\"ping_status\":\"open\",\"post_password\":\"\",\"post_name\":\"hello-world\",\"to_ping\":\"\",\"pinged\":\"\",\"post_modified\":\"2014-09-05 08:37:17\",\"post_modified_gmt\":\"2014-09-05 08:37:17\",\"post_content_filtered\":\"\",\"post_parent\":0,\"guid\":\"http:\\\/\\\/ceh.dev\\\/?p=1\",\"menu_order\":0,\"post_type\":\"post\",\"post_mime_type\":\"\",\"comment_count\":\"1\",\"filter\":\"raw\",\"meta\":[],\"taxonomies\":{\"category\":[1],\"post_tag\":[3,4]}}\n{\"index\":{\"_id\":2,\"_index\":\"hmes\",\"_type\":\"post\"}}\n{\"ID\":2,\"post_author\":\"1\",\"post_date\":\"2014-02-26 05:47:40\",\"post_date_gmt\":\"2014-02-26 05:47:40\",\"post_content\":\"This is an example page. It's different from a blog post because it will stay in one place and will show up in your site navigation (in most themes). Most people start with an About page that introduces them to potential site visitors. It might say something like this:\\n\\n<blockquote>Hi there! I'm a bike messenger by day, aspiring actor by night, and this is my blog. I live in Los Angeles, have a great dog named Jack, and I like pi&#241;a coladas. (And gettin' caught in the rain.)<\\\/blockquote>\\n\\n...or something like this:\\n\\n<blockquote>The XYZ Doohickey Company was founded in 1971, and has been providing quality doohickeys to the public ever since. Located in Gotham City, XYZ employs over 2,000 people and does all kinds of awesome things for the Gotham community.<\\\/blockquote>\\n\\nAs a new WordPress user, you should go to <a href=\\\"http:\\\/\\\/ceh.dev\\\/wordpress\\\/wp-admin\\\/\\\">your dashboard<\\\/a> to delete this page and create new pages for your content. Have fun!\",\"post_title\":\"Sample Page\",\"post_excerpt\":\"\",\"post_status\":\"publish\",\"comment_status\":\"open\",\"ping_status\":\"open\",\"post_password\":\"\",\"post_name\":\"sample-page\",\"to_ping\":\"\",\"pinged\":\"\",\"post_modified\":\"2014-02-26 05:47:40\",\"post_modified_gmt\":\"2014-02-26 05:47:40\",\"post_content_filtered\":\"\",\"post_parent\":0,\"guid\":\"http:\\\/\\\/ceh.dev\\\/?page_id=2\",\"menu_order\":0,\"post_type\":\"page\",\"post_mime_type\":\"\",\"comment_count\":\"0\",\"filter\":\"raw\",\"meta\":[],\"taxonomies\":[]}\n";

} );