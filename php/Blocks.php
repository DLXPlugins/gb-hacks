<?php
/**
 * Set up the blocks and their attributes.
 *
 * @package GBHacks
 */

namespace DLXPlugins\GBHacks;

/**
 * Helper class for registering blocks.
 */
class Blocks {

	/**
	 * Main class runner.
	 *
	 * @return Blocks.
	 */
	public static function run() {
		$self = new self();
		add_action( 'init', array( $self, 'init' ) );
		add_action( 'rest_api_init', array( $self, 'init_rest_api' ) );
		add_filter( 'block_type_metadata', array( $self, 'add_block_metadata' ), 10, 1 );
		add_filter( 'generateblocks_typography_font_family_list', array( $self, 'add_adobe_fonts' ), 10, 1 );
		return $self;
	}


	/**
	 * Add Adobe Fonts to the list of fonts.
	 *
	 * @param array $fonts List of fonts.
	 */
	public function add_adobe_fonts( $fonts ) {
		return $fonts;
	}

	/**
	 * Set Paragraph defaults.
	 *
	 * @param array $metadata {
	 *    An array of arguments.
	 *
	 *    @type string $name       Block name.
	 *    @type array  $attributes Block attributes.
	 * }
	 */
	public function add_block_metadata( $metadata ) {
		// Check the block type.
		if ( 'generateblocks/headline' !== $metadata['name'] ) {
			return $metadata;
		}

		// Add accordion view (collapsed view).
		$metadata['attributes']['element']['default'] = 'p';

		// Return the metadata.
		return $metadata;
	}

	/**
	 * Register the rest routes needed.
	 */
	public function init_rest_api() {
		register_rest_route(
			'dlxplugins/gb-hacks/v1',
			'/process_image',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_add_remote_image' ),
				'permission_callback' => array( $this, 'rest_image_sideload_permissions' ),
			)
		);
	}

	/**
	 * Process a list of images for a plugin.
	 *
	 * @param WP_Rest $request REST request.
	 */
	public function rest_add_remote_image( $request ) {
		$image_url = filter_var( $request->get_param( 'imgUrl' ), FILTER_VALIDATE_URL );
		$image_alt = sanitize_text_field( $request->get_param( 'imgAlt' ) );

		if ( $image_url ) {
			// Check file extension.
			$extension = pathinfo( $image_url, PATHINFO_EXTENSION );

			// Strip query vars from extension.
			$extension = preg_replace( '/\?.*/', '', $extension );

			// Get current domain.
			$domain = parse_url( $image_url, PHP_URL_HOST );

			// If we're on same domain, bail successfully.
			if ( $domain === $_SERVER['HTTP_HOST'] ) {
				\wp_send_json_success(
					array(
						'attachmentId'  => 0,
						'attachmentUrl' => esc_url( $image_url ),
					)
				);
			}

			if ( ! $extension ) {
				\wp_send_json_error(
					array(
						'message' => __( 'File extension not found.', 'gb-hacks' ),
					),
					400
				);
			}
			$valid_extensions = Functions::get_supported_file_extensions();
			if ( ! in_array( $extension, $valid_extensions, true ) ) {
				\wp_send_json_error(
					array(
						'message' => __( 'Invalid file extension.', 'gb-hacks' ),
					),
					400
				);
			}

			// Save the image to the media library.
			if ( ! function_exists( 'media_sideload_image' ) ) {
				require_once ABSPATH . 'wp-admin/includes/image.php';
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/media.php';
			}
			$attachment_id = media_sideload_image( $image_url, 0, '', 'id' );

			// Add order to attachment.
			if ( ! is_wp_error( $attachment_id ) ) {

				// Get attachment URL.
				$attachment_url_src = wp_get_attachment_image_src( $attachment_id, 'full' );
				$attachment_url     = $attachment_url_src[0];

				// Update alt attribute.
				update_post_meta( $attachment_id, '_wp_attachment_image_alt', $image_alt );

				// Send success.
				\wp_send_json_success(
					array(
						'attachmentId'  => absint( $attachment_id ),
						'attachmentUrl' => esc_url( $attachment_url ),
					)
				);
			} else {
				\wp_send_json_error(
					array(
						'message' => $attachment_id->get_error_message(),
					),
					400
				);
			}
		}
		\wp_send_json_error(
			array(
				'message' => __( 'Invalid image URL.', 'gb-hacks' ),
			),
			400
		);
	}

	/**
	 * Check if user has access to REST API for retrieving and sideloading images.
	 */
	public function rest_image_sideload_permissions() {
		return current_user_can( 'publish_posts' );
	}

	/**
	 * Init action callback.
	 */
	public function init() {

		register_block_type(
			Functions::get_plugin_dir( 'build/js/blocks/pattern-importer/block.json' ),
			array(
				'render_callback' => '__return_empty_string',
			)
		);

		// Enqueue block assets.
		// add_action( 'enqueue_block_assets', array( $this, 'register_block_styles' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'register_block_editor_scripts' ) );
	}

	/**
	 * Register the block editor script with localized vars.
	 */
	public function register_block_editor_scripts() {
		$options = Options::get_options();

		wp_register_script(
			'gb-hacks-pattern-inserter-block',
			Functions::get_plugin_url( 'build/index.js' ),
			array(),
			Functions::get_plugin_version(),
			true
		);

		wp_localize_script(
			'gb-hacks-pattern-inserter-block',
			'gbHacksPatternInserter',
			array(
				'restUrl'                     => rest_url( 'dlxplugins/gb-hacks/v1' ),
				'restNonce'                   => wp_create_nonce( 'wp_rest' ),
				'allowedGoogleFonts'          => $options['allowedGoogleFonts'] ?? array(),
				'defaultHeadlineBlockEnabled' => (bool) $options['enableDefaultHeadlineBlock'] ?? false,
				'defaultHeadlineBlockElement' => $options['headlineBlockElement'] ?? '',
			)
		);
	}
}
