<?php

namespace HMES\Types;

class Post extends Base {

	public $name             = 'post';
	public $index_hooks      = array( 'edit_post', 'save_post', 'publish_post' );
	public $delete_hooks     = array( 'delete_post' );
	public $mappable_hooks   = array(
		'added_post_meta'   => 'update_post_meta_callback',
		'updated_post_meta' => 'update_post_meta_callback',
		'deleted_post_meta' => 'update_post_meta_callback',
		'set_object_terms'  => 'set_object_terms_callback',
	);

	/**
	 * Called when post meta is added/deleted/edited - triggers re-indexing of the applicable post
	 *
	 * @param $meta_id
	 * @param $post_id
	 */
	public function update_post_meta_callback( $meta_id, $post_id ) {

		$this->index_callback( $post_id );
	}

	/**
	 * Called when a post has it's terms modified - triggers re-indexing of applicable post
	 *
	 * @param $post_id
	 */
	public function set_object_terms_callback( $post_id ) {

		$this->index_callback( $post_id );
	}

	/**
	 * Queue the indexing of an item - called when a post is modified or added to the database
	 *
	 * @param $post_id
	 * @param array $args
	 */
	public function index_callback( $post_id, $args = array()  ) {

		$post = (array) get_post( $post_id );

		if ( ! $post ) {
			return;
		}

		$this->queue_action( 'index_item', $post_id );
	}

	/**
	 * Queue the deletion of an item - called when a post is deleted from the database
	 *
	 * @param $post_id
	 * @param array $args
	 */
	public function delete_callback( $post_id, $args = array()  ) {

		$this->queue_action( 'delete_item', $post_id );
	}

	/**
	 * Parse an item for indexing, accepts post ID or post object
	 *
	 * @param $item
	 * @param array $args
	 * @return array|bool
	 */
	public function parse_item_for_index( $item, $args = array() ) {

		//get a valid post object as array (populate if only id is supplied)
		if ( is_numeric( $item ) ) {
			$item = (array) get_post( $item );
		} else {
			$item = (array) $item;
		}

		if ( empty( $item['ID'] ) ) {
			return false;
		}

		$item['meta'] = get_metadata( 'post', (int) $item['ID'], '', true );

		foreach ( $item['meta'] as $meta_key => $meta_array ) {

			$item['meta'][$meta_key] = reset( $meta_array );
		}

		$item['taxonomies'] = array();

		foreach ( get_taxonomies() as $tax ) {

			$terms = get_the_terms( $item['ID'], $tax );

			if ( $terms && is_array( $terms ) ) {
				$item['taxonomies'][$tax] = array_map( function( $term ) {
					return $term->term_id;
				}, array_values( $terms ) );
			}
		};

		$item['meta'] = array();

		return $item;
	}

	/**
	 * Get paginated comments for use by index_all base class method
	 *
	 * @param $page
	 * @param $per_page
	 * @return array
	 */
	public function get_items( $page, $per_page ) {

		$posts = get_posts( array(
			'post_type'       => 'any',
			'post_status'     => 'publish',
			'posts_per_page'  => $per_page,
			'paged'           => $page
		) );

		return $posts;
	}

}