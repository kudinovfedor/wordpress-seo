<?php

namespace Yoast\WP\SEO\Generators\Schema\Third_Party;

use Yoast\WP\SEO\Generators\Schema\Author;

/**
 * Returns schema Author data for the CoAuthor Plus assigned user on a post.
 */
class CoAuthor extends Author {
	/**
	 * @var int $user_id
	 */
	private $user_id;

	/**
	 * Determine whether we should return Person schema.
	 *
	 * @return bool
	 */
	public function is_needed() {
		return true;
	}

	/**
	 * Returns Person Schema data.
	 *
	 * @return bool|array Person data on success, false on failure.
	 */
	public function generate() {
		$user_id = $this->determine_user_id();
		if ( ! $user_id ) {
			return false;
		}

		$data = $this->build_person_data( $user_id );

		$data['@type'] = 'Person';
		unset( $data['logo'] );

		// If this is a post and the author archives are enabled, set the author archive url as the author url.
		if ( $this->helpers->options->get( 'disable-author' ) !== true ) {
			$data['url'] = $this->helpers->user->get_the_author_posts_url( $user_id );
		}

		return $data;
	}

	/**
	 * Generate the Person data given a user ID.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return array|bool
	 */
	public function generate_from_user_id( $user_id ) {
		$this->user_id = $user_id;

		return $this->generate();
	}

	/**
	 * Determines a User ID for the Person data.
	 *
	 * @return bool|int User ID or false upon return.
	 */
	protected function determine_user_id() {
		return $this->user_id;
	}

	/**
	 * An author should not have an image from options, this only applies to persons.
	 *
	 * @param array  $data      The Person schema.
	 * @param string $schema_id The string used in the `@id` for the schema.
	 *
	 * @return array The Person schema.
	 */
	protected function set_image_from_options( $data, $schema_id ) {
		if ( $this->site_represents_current_author() ) {
			return parent::set_image_from_options( $data, $schema_id );
		}

		return $data;
	}
}
