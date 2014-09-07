<?php

class HMES_Post_Type extends HMES_Base_Type {

	var $name             = 'post';
	var $index_hooks      = array( 'edit_post', 'save_post', 'publish_post' );
	var $delete_hooks     = array( 'delete_post' );
	var $mappable_hooks   = array(
		'added_post_meta'   => 'update_post_meta_callback',
		'updated_post_meta' => 'update_post_meta_callback',
		'deleted_post_meta' => 'update_post_meta_callback',
		'set_object_terms'  => 'set_object_terms_callback',
		'edited_term'       => 'edited_term_callback',
	);

	/**
	 * Called when a term is edited - triggers re-indexing of all posts in the term
	 *
	 * @param $term_id
	 * @param $tt_id
	 * @param $taxonomy
	 */
	function edited_term_callback( $term_id, $tt_id, $taxonomy ) {

		$this->queue_action( 'index_all_in_term', $term_id, $args = array( 'taxonomy' => $taxonomy  ) );
	}

	/**
	 * Called when post meta is added/deleted/edited - triggers re-indexing of the applicable post
	 *
	 * @param $meta_id
	 * @param $post_id
	 */
	function update_post_meta_callback( $meta_id, $post_id ) {

		$this->index_callback( $post_id );
	}

	/**
	 * Called when a post has it's terms modified - triggers re-indexing of applicable post
	 *
	 * @param $post_id
	 */
	function set_object_terms_callback( $post_id ) {

		$this->index_callback( $post_id );
	}

	/**
	 * Queue the indexing of an item - called when a post is modified or added to the database
	 *
	 * @param $post_id
	 * @param array $args
	 */
	function index_callback( $post_id, $args = array()  ) {

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
	function delete_callback( $post_id, $args = array()  ) {

		$this->queue_action( 'delete_item', $post_id );
	}

	/**
	 * Gets all posts in a specified term -  used to get all posts which apply to a recently edited term for reindexing
	 *
	 * @param $term_id
	 * @param $args
	 * @return array
	 */
	function get_all_in_term( $term_id, $args ) {

		$items = $this->search( "taxonomies." . $args['taxonomy'] . '.term_id:' . $term_id );

		$post_ids = array();

		if ( empty( $items['hits']['hits'] ) ) {

			return array();
		}

		foreach ( $items['hits']['hits'] as $hit ) {

			$post_ids[] = $hit['_id'];
		}

		//Get full objects to avoid single queries for each post
		$posts = get_posts( array(
			'post_type'       => 'any',
			'posts_per_page'  => -1,
			'post__in'        => $post_ids,
		) );

		return $posts;
	}

	/**
	 * Re-indexes all posts which are assigned to a given term - used to update all applicable posts when a term is modified
	 *
	 * @param $term_id
	 * @param $args
	 */
	function index_all_in_term( $term_id, $args ) {

		$this->index_items( $this->get_all_in_term( $term_id, $args ) );
	}

	/**
	 * Parse an item for indexing, accepts post ID or post object
	 *
	 * @param $item
	 * @param array $args
	 * @return array|bool
	 */
	function parse_item_for_index( $item, $args = array() ) {

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

			if ( $terms = wp_get_object_terms( $item['ID'], $tax ) ) {
				$item['taxonomies'][$tax] = $terms;
			}
		};

		return $item;
	}

	/**
	 * Get paginated comments for use by index_all base class method
	 *
	 * @param $page
	 * @param $per_page
	 * @return array
	 */
	function get_items( $page, $per_page ) {

		$posts = get_posts( array(
			'post_type'       => 'any',
			'post_status'     => 'publish',
			'posts_per_page'  => $per_page,
			'paged'           => $page
		) );

		return $posts;
	}

}