<?php
if ( is_multisite() ) :
	/**
	 * Tests for the is_main_network function when  a multi site.
	 *
	 * @group Functions
	 * @group ms-site
	 * @group multisite
	 *
	 * @covers ::is_main_network
	 */
	class Tests_MultiSite_is_main_network extends WP_UnitTestCase {

		protected static $network_ids;
		protected static $site_ids;

		public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
			self::$network_ids = array(
				'make.wordpress.org/' => array(
					'domain' => 'make.wordpress.org',
					'path'   => '/',
				),
				'site_2/'             => array(
					'domain' => '2.wordpress.org',
					'path'   => '/',
				),
			);

			foreach ( self::$network_ids as &$id ) {
				$id = $factory->network->create( $id );

//				self::$site_ids = array(
//					'make.wordpress.org/' . $id     => array(
//						'domain'     => 'make.wordpress.org',
//						'path'       => '/',
//						'network_id' => self::$network_ids[ $id ],
//					),
//					'make.wordpress.org/foo/' . $id => array(
//						'domain'     => 'make.wordpress.org',
//						'path'       => '/foo/',
//						'network_id' => self::$network_ids[ $id ],
//					),
//				);
//
//				foreach ( self::$site_ids as &$Site_id ) {
//					$id = $factory->blog->create( $Site_id );
//				}
//				unset( $Site_id );
			}
			unset( $id );

			var_dump( self::$network_ids );
			var_dump( self::$site_ids );
		}

		public static function wpTearDownAfterClass() {
			global $wpdb;

			foreach ( self::$site_ids as $id ) {
				wp_delete_site( $id );
			}

			foreach ( self::$network_ids as $id ) {
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->sitemeta} WHERE site_id = %d", $id ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->site} WHERE id= %d", $id ) );
			}

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
			$this->assertTrue( is_main_network( self::$site_ids[0] ) );
		}

		/**
		 * @ticket 59981
		 *
		 * @group ms-site
		 * @group multisite
		 */
		public function test_is_not_main_network_when_multi() {
			switch_to_site( $site_ids[3] );
			$this->assertFalse( is_main_network( self::$site_ids[3] ) );
		}
	}
endif;
