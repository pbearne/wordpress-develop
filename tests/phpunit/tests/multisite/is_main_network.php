<?php
if ( is_multisite() ) :
	/**
	 * Tests for the is_main_network function when  a multi site.
	 *
	 * @group Functions
	 * @group ms-site
	 *
	 * @covers ::is_main_network
	 */
	class Tests_MultiSite_is_main_network extends WP_UnitTestCase {

		protected static $different_network_id;
		protected static $different_site_ids = array();

		public function tear_down() {
			global $current_site;
			$current_site->id = 1;
			parent::tear_down();
		}

		public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
			self::$different_network_id = $factory->network->create(
				array(
					'domain' => 'wordpress.org',
					'path'   => '/',
				)
			);

			$sites = array(
				array(
					'domain'     => 'wordpress.org',
					'path'       => '/',
					'network_id' => self::$different_network_id,
				),
				array(
					'domain'     => 'wordpress.org',
					'path'       => '/foo/',
					'network_id' => self::$different_network_id,
				),
				array(
					'domain'     => 'wordpress.org',
					'path'       => '/bar/',
					'network_id' => self::$different_network_id,
				),
			);

			foreach ( $sites as $site ) {
				self::$different_site_ids[] = $factory->blog->create( $site );
			}
		}

		public static function wpTearDownAfterClass() {
			global $wpdb;

			foreach ( self::$different_site_ids as $id ) {
				wp_delete_site( $id );
			}

			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->sitemeta} WHERE site_id = %d", self::$different_network_id ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->site} WHERE id= %d", self::$different_network_id ) );

			wp_update_network_site_counts();
		}

		/**
		 * @ticket 59981
		 */
		public function test_is_main_network() {
			$this->assertTrue( is_main_network() );
		}

		/**
		 * @ticket 59981
		 */
		public function test_is_not_main_network() {
			$this->assertFalse( is_main_network( 99 ) );
		}

		/**
		 * @ticket 59981
		 *
		 */
		public function test_is_main_network_ms_site() {

			$this->assertFalse( is_main_network( self::$different_site_ids[0] ) );
		}

		/**
		 * @ticket 59981
		 *
		 * @group ms-site
		 * @group multisite
		 */
		public function test_is_not_main_network_when_multi() {
			switch_to_blog( self::$different_site_ids[2] );

			$this->assertTrue( is_main_network() );
			$this->assertFalse( is_main_network( self::$different_site_ids[2] ) );
		}
	}
endif;
