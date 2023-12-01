<?php

/**
 * Tests for the is_main_network function when not a multi site.
 *
 * @group Functions
 * @group ms-excluded
 *
 * @covers ::is_main_network
 */
class Tests_Function_is_main_network extends WP_UnitTestCase {

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
		$this->assertTrue( is_main_network( 99 ) );
	}
}

