<?php

/**
 * Test to verify PHPUnit version.
 *
 * @group phpunit
 */
class Tests_PHPUnit_Version extends WP_UnitTestCase {

	/**
	 * Test that we can detect the PHPUnit version.
	 */
	public function test_phpunit_version() {
		$version = PHPUnit\Runner\Version::id();
		$this->assertStringStartsWith('11.', $version, "Expected PHPUnit version 11, but got $version");

		// Output the exact version for informational purposes
		echo "Running PHPUnit version: $version\n";
	}
}
