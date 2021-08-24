<?php

namespace Yoast\WP\SEO\Integrations;

use Yoast\WP\Lib\Model;
/**
 * Adds cleanup hooks.
 */
class Cleanup_Integration implements Integration_Interface {

	const CURRENT_TASK_OPTION = "wpseo-cleanup-current-task";

	/**
	 * Returns the conditionals based in which this loadable should be active.
	 *
	 * @return array The array of conditionals.
	 */
	public static function get_conditionals() {
		return [];
	}

	private function get_cleanup_tasks() {
		return [
			"clean_indexables_by_object_sub_type_shop-order" => function( $limit ) {
				return $this->clean_indexables_with_object_type( "post", "shop-order", $limit );
			},
			"clean_indexables_by_post_status_auto-draft" => function( $limit ) {
				return $this->clean_indexables_with_post_status( "auto-draft", $limit );
			},
			/* These should always be the last one to be called */
			"clean_orphaned_content_indexable_hierarchy" => function( $limit ) {
				return $this->cleanup_orphaned_from_table( 'Indexable_Hierarchy', 'indexable_id', $limit );
			},
			"clean_orphaned_content_seo_links_indexable_id" => function( $limit ) {
				return $this->cleanup_orphaned_from_table( 'SEO_Links', 'indexable_id', $limit );
			},
			"clean_orphaned_content_seo_links_target_indexable_id" => function( $limit ) {
				return $this->cleanup_orphaned_from_table( 'SEO_Links', 'target_indexable_id', $limit );
			},
		];
	}

	public function start_cleanup() {
		$this->reset_cleanup();

		$cleanups = $this->get_cleanup_tasks();

		foreach ( $cleanups as $name => $action ) {
			$limit = \apply_filters( 'wpseo_cron_query_limit_size', 1000 );

			$items_cleaned = $action( $limit );
			if ( $items_cleaned < $limit ) {
				continue;
			}
			// There are more items to delete for the current cleanup job, start a cronjob at the specified job.
			$this->start_cron_job( $name );
			return;
		}
	}

	private function reset_cleanup() {
		\delete_option( self::CURRENT_TASK_OPTION );
		\wp_unschedule_hook( 'wpseo_cleanup_cron' );
	}

	private function start_cron_job( $job_name ) {
		\add_option( self::CURRENT_TASK_OPTION, $job_name );
		\wp_schedule_event(
			time(),
			'hourly',
			'wpseo_cleanup_cron',
		);
	}

	public function _run_cleanup_cron() {
		$current_task_name = \get_option( self::CURRENT_TASK_OPTION );
		$limit             = \apply_filters( 'wpseo_cron_query_limit_size', 1000 );
		$tasks             = $this->get_cleanup_tasks();

		
		while ( $current_task = \current( $tasks ) ) {
			// Skip the tasks that have already been done.
			if ( \key( $tasks ) !== $current_task_name ) {
				\next( $tasks );
				continue;
			}

			$items_cleaned = $current_task( $limit );

			if( $items_cleaned === 0 ) {
				// Check if we are finshed with all tasks.
				if ( \next( $tasks ) === false ) {
					$this->reset_cleanup();
					return;
				}
				// Continue with the next task next time.
				\update_option( self::CURRENT_TASK_OPTION, \key( $tasks ) );
				return;
			}
		}
	}

	/**
	 * Initializes the integration.
	 *
	 * This is the place to register hooks and filters.
	 *
	 * @return void
	 */
	public function register_hooks() {
		\add_action( 'wpseo_cleanup_cron', [ $this, '_run_cleanup_cron' ] );
		\add_action( 'wpseo_deactivate', [ $this, 'unschedule_cron' ] );
	}

	/**
	 * Cleans rows from the indexable table and unregisters the cron if no deletions.
	 *
	 * @param string $object_type     The object type to query.
	 * @param string $object_sub_type The object subtype to query.
	 *
	 * @return void
	 */
	public function cleanup_obsolete_indexables( $object_type, $object_sub_type ) {
		$number_of_deletions = $this->clean_indexables_with_object_type( $object_type, $object_sub_type, 1000 );

		if ( empty( $number_of_deletions ) ) {
			$this->unschedule_cron( 'wpseo_cleanup_indexables' );
		}
	}

	/**
	 * Cleans orphaned rows from the yoast tables and unregisters the cron if no deletions.
	 *
	 * @return void
	 */
	public function cleanup_orphaned_indexables() {
		$deleted_orphans  = $this->cleanup_orphaned_from_table( 'Indexable_Hierarchy', 'indexable_id', 1000 );
		$deleted_orphans += $this->cleanup_orphaned_from_table( 'SEO_Links', 'indexable_id', 1000 );
		$deleted_orphans += $this->cleanup_orphaned_from_table( 'SEO_Links', 'target_indexable_id', 1000 );

		$deleted_orphans = \apply_filters( 'wpseo_cleanup_orphaned', $deleted_orphans );

		if ( empty( $deleted_orphans ) ) {
			$this->unschedule_cron( 'wpseo_cleanup_orphaned_indexables' );
		}
	}

	/**
	 * Cleans orphaned rows from a yoast table.
	 *
	 * @param string $table  The table to cleanup.
	 * @param string $column The table column the cleanup will rely on.
	 * @param int    $limit  The limit we'll apply to the queries.
	 *
	 * @return int The number of deleted rows.
	 */
	public function cleanup_orphaned_from_table( $table, $column, $limit = 1000 ) {
		global $wpdb;

		$table           = Model::get_table_name( $table );
		$indexable_table = Model::get_table_name( 'Indexable' );
		$limit           = \apply_filters( 'wpseo_cron_query_limit_size', $limit );

		// Sanitize the $limit.
		$limit = ! is_int( $limit ) ? 1000 : $limit;
		$limit = ( $limit > 5000 ) ? 5000 : ( ( $limit <= 0 ) ? 1000 : $limit );

		// Warning: If this query is changed, make sure to update the query in cleanup_orphaned_from_table in Premium as well.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: There is no unescaped user input.
		$query = $wpdb->prepare(
			"
			SELECT table_to_clean.{$column}
			FROM {$table} table_to_clean
			LEFT JOIN {$indexable_table} AS indexable_table
			ON table_to_clean.{$column} = indexable_table.id
			WHERE indexable_table.id IS NULL
			AND table_to_clean.{$column} IS NOT NULL
			LIMIT %d",
			$limit
		);
		// phpcs:enable

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: Already prepared.
		$orphans = $wpdb->get_col( $query );

		if ( empty( $orphans ) ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: Already prepared.
		return intval( $wpdb->query( "DELETE FROM $table WHERE {$column} IN( " . implode( ',', $orphans ) . ' ) ' ) );
	}

	/**
	 * Deletes rows from the indexable table depending on the object_type and object_sub_type.
	 *
	 * @param string $object_type     The object type to query.
	 * @param string $object_sub_type The object subtype to query.
	 * @param int    $limit           The limit we'll apply to the delete query.
	 *
	 * @return int|bool
	 */
	public function clean_indexables_with_object_type( $object_type, $object_sub_type, $limit = 1000 ) {
		global $wpdb;

		$limit           = \apply_filters( 'wpseo_upgrade_query_limit_size', $limit );
		$indexable_table = Model::get_table_name( 'Indexable' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: There is no unescaped user input.
		$sql = $wpdb->prepare( "DELETE FROM $indexable_table WHERE object_type = %s AND object_sub_type = %s ORDER BY id LIMIT %d", $object_type, $object_sub_type, $limit );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: Already prepared.
		return $wpdb->query( $sql );
	}

	public function clean_indexables_with_post_status( $post_status ) {

	}

	/**
	 * Unschedules the WP-Cron jobs to cleanup indexables and orphaned rows.
	 *
	 * @param string|null $hook The hook to unregister.
	 * @return void
	 */
	public function unschedule_cron( $hook = null ) {
		if ( is_string( $hook ) && ! empty( $hook ) ) {
			\wp_unschedule_hook( $hook );
		}
		else {
			\wp_unschedule_hook( 'wpseo_cleanup_indexables' );
			\wp_unschedule_hook( 'wpseo_cleanup_orphaned_indexables' );
		}
	}
}
