<?php

namespace Yoast\WP\SEO\Integrations;

use WPSEO_Admin_Asset_Manager;
use WPSEO_Options;
use Yoast\WP\SEO\Conditionals\New_Settings_Ui_Conditional;
use Yoast\WP\SEO\Conditionals\Premium_Active_Conditional;

/**
 * Class Settings_Integration.
 */
class Settings_Integration implements Integration_Interface {

	protected $asset_manager;

	/**
	 * Constructs Settings_Integration.
	 *
	 * @param WPSEO_Admin_Asset_Manager $asset_manager The WPSEO_Admin_Asset_Manager.
	 */
	public function __construct( WPSEO_Admin_Asset_Manager $asset_manager ) {
		$this->asset_manager = $asset_manager;
	}

	/**
	 * Returns the conditionals based on which this loadable should be active.
	 *
	 * @return array
	 */
	public static function get_conditionals() {
		return [ New_Settings_Ui_Conditional::class, Premium_Active_Conditional::class ];
	}

	/**
	 * Initializes the integration.
	 *
	 * This is the place to register hooks and filters.
	 *
	 * @return void
	 */
	public function register_hooks() {
		\add_filter( 'wpseo_submenu_pages', [ $this, 'add_page' ] );
		\add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Adds the page.
	 *
	 * @param array $pages The pages.
	 *
	 * @return array The pages.
	 */
	public function add_page( $pages ) {
		\array_unshift(
			$pages,
			[
				'wpseo_dashboard',
				'',
				sprintf(
				// translators: %1$s expands to the opening span tag (styling). %2$s expands to the closing span tag.
					__( 'Settings %1$sBeta%2$s', 'wordpress-seo' ),
					'<span class="yoast-badge yoast-beta-badge">',
					'</span>'
				),
				'wpseo_manage_options',
				'wpseo_settings',
				[ $this, 'display_page' ],
			]
		);

		return $pages;
	}

	/**
	 * Displays the page.
	 */
	public function display_page() {
		echo '<div id="yoast-seo-settings"></div>';
	}

	/**
	 * Enqueues the assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		$this->asset_manager->enqueue_script( 'new-settings' );
		$this->asset_manager->enqueue_style( 'tailwind' );
		$this->asset_manager->localize_script( 'new-settings', 'wpseoScriptData', $this->get_script_data() );
	}

	/**
	 * Creates the script data.
	 *
	 * @return array The script data.
	 */
	protected function get_script_data() {
		$data = [
			'options' => [],
			'nonces'  => [],
		];

		foreach ( WPSEO_Options::$options as $name => $instance ) {
			$data['options'][ $name ] = WPSEO_Options::get_option( $name );
			$data['nonces'][ $name ]  = \wp_create_nonce( "yoast_${name}_options-options" );
		}

		return $data;
	}
}
