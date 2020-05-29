<?php
/**
 * Reindexation action for indexables.
 *
 * @package Yoast\WP\SEO\Actions\Indexation
 */

namespace Yoast\WP\SEO\Actions\Indexation;

use wpdb;
use Yoast\WP\Lib\Model;
use Yoast\WP\SEO\Helpers\Post_Type_Helper;
use Yoast\WP\SEO\Repositories\Indexable_Repository;

/**
 * Post_Link_Indexing_Action class.
 */
class Post_Link_Indexing_Action implements Indexation_Action_Interface {

	/**
	 * The post type helper.
	 *
	 * @var Post_Type_Helper
	 */
	protected $post_type_helper;

	/**
	 * The indexable repository.
	 *
	 * @var Indexable_Repository
	 */
	protected $repository;

	/**
	 * The WordPress database instance.
	 *
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Indexable_Post_Indexing_Action constructor
	 *
	 * @param Post_Type_Helper     $post_type_helper The post type helper.
	 * @param Indexable_Repository $repository       The indexable repository.
	 * @param wpdb                 $wpdb             The WordPress database instance.
	 */
	public function __construct( Post_Type_Helper $post_type_helper, Indexable_Repository $repository, wpdb $wpdb ) {
		$this->post_type_helper = $post_type_helper;
		$this->repository       = $repository;
		$this->wpdb             = $wpdb;
	}

	/**
	 * @inheritDoc
	 */
	public function get_total_unindexed() {
		$query = $this->get_query( true );

		$result = $this->wpdb->get_var( $query );

		if ( \is_null( $result ) ) {
			return false;
		}
		return (int) $result;
	}

	/**
	 * Creates indexables for unindexed posts.
	 *
	 * @return Indexable[] The created indexables.
	 */
	public function index() {
		/**
		 * Filter 'wpseo_link_indexing_limit' - Allow filtering the amount of posts indexed during each indexing pass.
		 *
		 * @api int The maximum number of posts indexed.
		 */
		$limit = \apply_filters( 'wpseo_post_link_indexing_limit', 5 );

		$query = $this->get_query( false, $limit );

		$posts = $this->wpdb->get_results( $query );
	}

	/**
	 * Queries the database for unindexed term IDs.
	 *
	 * @param bool $count Whether or not it should be a count query.
	 * @param int  $limit The maximum amount of term IDs to return.
	 *
	 * @return string The query.
	 */
	protected function get_query( $count, $limit = 1 ) {
		$public_post_types = $this->post_type_helper->get_public_post_types();
		$placeholders      = \implode( ', ', \array_fill( 0, \count( $public_post_types ), '%s' ) );
		$seo_meta_table    = Model::get_table_name( 'SEO_Meta' );
		$replacements      = $public_post_types;

		$select = 'ID, post_content';
		if ( $count ) {
			$select = 'COUNT(ID)';
		}
		$limit_query = '';
		if ( ! $count ) {
			$limit_query    = 'LIMIT %d';
			$replacements[] = $limit;
		}

		return $this->wpdb->prepare( "
			SELECT $select
			FROM {$this->wpdb->posts}
			WHERE ID NOT IN (SELECT object_id FROM $seo_meta_table) AND post_status = 'publish' AND post_type IN ($placeholders)
			$limit_query
        ", $replacements );
	}
}
