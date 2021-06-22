<?php

namespace Yoast\WP\SEO\Presenters\Twitter;

use Yoast\WP\SEO\Presentations\Indexable_Presentation;
use Yoast\WP\SEO\Presenters\Abstract_Cached_Indexable_Tag_Presenter;

/**
 * Presenter class for the Twitter image.
 */
class Image_Presenter extends Abstract_Cached_Indexable_Tag_Presenter {

	const KEY = 'twitter:image';

	/**
	 * The method of escaping to use.
	 *
	 * @var string
	 */
	protected $escaping = 'attribute';

	/**
	 * Run the Twitter image value through the `wpseo_twitter_image` filter.
	 *
	 * @return string The filtered Twitter image.
	 */
	public function refresh() {
		/**
		 * Filter: 'wpseo_twitter_image' - Allow changing the Twitter Card image.
		 *
		 * @param Indexable_Presentation $presentation The presentation of an indexable.
		 *
		 * @api string $twitter_image Image URL string.
		 */
		return (string) \apply_filters( 'wpseo_twitter_image', $this->presentation->twitter_image, $this->presentation );
	}
}
