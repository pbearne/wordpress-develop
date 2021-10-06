<?php
namespace image;

require_once __DIR__ . '/base.php';

class TestWpGetWebpInfo extends \WP_Image_UnitTestCase {
	public $editor_engine = '\WP_Image_Editor_Mock';

	/**
	 * Setup test fixture
	 */
	public function set_up() {
		require_once ABSPATH . WPINC . '/class-wp-image-editor.php';

		require_once DIR_TESTDATA . '/../includes/mock-image-editor.php';

		// This needs to come after the mock image editor class is loaded.
		parent::set_up();
	}

	/**
	 * Test wp_get_webp_info.
	 *
	 * @ticket 35725
	 * @dataProvider _test_wp_get_webp_info
	 *
	 * @covers ::wp_get_webp_info
	 */
	public function test_wp_get_webp_info( $file, $expected ) {
		$editor = wp_get_image_editor( $file );

		if ( is_wp_error( $editor ) || ! $editor->supports_mime_type( 'image/webp' ) ) {
			$this->markTestSkipped( sprintf( 'No WebP support in the editor engine %s on this system.', $this->editor_engine ) );
		}

		$file_data = wp_get_webp_info( $file );
		$this->assertSame( $file_data, $expected );
	}

	/**
	 * Data provider for test_wp_get_webp_info().
	 */
	public static function _test_wp_get_webp_info() {
		return array(
			// Standard JPEG.
			'test-image.jpg' => array(
				'file_data' => DIR_TESTDATA . '/images/test-image.jpg',
				'expected'  => array(
					'width'  => false,
					'height' => false,
					'type'   => false,
				),
			),
			// Standard GIF.
			'test-image.gif' => array(
				'file_data' => DIR_TESTDATA . '/images/test-image.gif',
				'expected'  => array(
					'width'  => false,
					'height' => false,
					'type'   => false,
				),
			),
			// Animated WebP.
			'webp-animated.webp' => array(
				'file_data' => DIR_TESTDATA . '/images/webp-animated.webp',
				'expected'  => array(
					'width'  => 100,
					'height' => 100,
					'type'   => 'animated-alpha',
				),
			),
			// Lossless WebP.
			'webp-lossless.webp' => array(
				'file_data' => DIR_TESTDATA . '/images/webp-lossless.webp',
				'expected'  => array(
					'width'  => 1200,
					'height' => 675,
					'type'   => 'lossless',
				),
			),
			// Transparent WebP.
			'webp-transparent.webp' => array(
				'file_data' => DIR_TESTDATA . '/images/webp-transparent.webp',
				'expected'  => array(
					'width'  => 1200,
					'height' => 675,
					'type'   => 'animated-alpha',
				),
			),
			//  WebP.
			'animated-webp-supported.webp' => array(
				'file_data' => DIR_TESTDATA . '/images/animated-webp-supported.webp',
				'expected'  => array(
					'width'  => 400,
					'height' => 400,
					'type'   => 'animated-alpha',
				),
			),
			//VP8X  WebP.
			'bored_animation_VP8X.webp' => array(
				'file_data' => DIR_TESTDATA . '/images/bored_animation_VP8X.webp',
				'expected'  => array(
					'width'  => 279,
					'height' => 193,
					'type'   => 'animated-alpha',
				),
			),
			//  WebP.
			'breiskrednosi_VP8.webp' => array(
				'file_data' => DIR_TESTDATA . '/images/breiskrednosi_VP8.webp',
				'expected'  => array(
					'width'  => 320,
					'height' => 214,
					'type'   => 'lossy',
				),
			),
			//  WebP.
			'cell_animation_VP8X.webp' => array(
				'file_data' => DIR_TESTDATA . '/images/cell_animation_VP8X.webp',
				'expected'  => array(
					'width'  => 320,
					'height' => 240,
					'type'   => 'animated-alpha',
				),
			),
			//  WebP.
			'Freddy_Arenas_VP8X.webp' => array(
				'file_data' => DIR_TESTDATA . '/images/Freddy_Arenas_VP8X.webp',
				'expected'  => array(
					'width'  => 400,
					'height' => 492,
					'type'   => 'animated-alpha',
				),
			),
			//  WebP.
			'Olivia_When_VP8X.webp' => array(
				'file_data' => DIR_TESTDATA . '/images/Olivia_When_VP8X.webp',
				'expected'  => array(
					'width'  => 500,
					'height' => 500,
					'type'   => 'animated-alpha',
				),
			),
		);
	}
}
