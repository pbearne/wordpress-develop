<?php


class CacheOptionPerPage {

	private static $conditional_options_context;
	private static $alloptions_names = array();
	private static $alloptions_used  = array();

	private static $current_context;

	public static function init() {
		if ( ! isset( $_REQUEST['dco'] ) ) {
			add_filter(
				'pre_wp_load_alloptions',
				array(
					__CLASS__,
					'performance_conditional_options_options_preload',
				),
				1,
				2
			);
		}

		add_filter( 'pre_option', array( __CLASS__, 'performance_conditional_options_get_option' ), 1, 2 );
		add_action( 'shutdown', array( __CLASS__, 'performance_conditional_options_save_options_cache' ) );
		if ( ! isset( $_REQUEST['hide_stats'] ) ) {
		//	add_action( 'shutdown', array( __CLASS__, 'performance_conditional_options_stats' ), 99 );
		}
	}


	/**
	 * looks in the cache for ids for the current contaxt and if found returns the options needed
	 * Reurn null i
	 *
	 * @return array
	 */
	public static function performance_conditional_options_options_preload( $pre, $force_cache ) {
		global $wpdb;

		if ( ! wp_installing() || ! is_multisite() ) {
			$alloptions = wp_cache_get( 'alloptions', 'options', $force_cache );
			if ( false !== $alloptions ) {
				return $alloptions;
			}
		}

		if ( ! empty( self::$alloptions_names ) ) {

			return $pre;
		}

		if ( self::performance_conditional_has_persistent_caching() ) {
			foreach ( self::performance_conditional_options_get_context() as $context ) {
				$maybe_option_ids = wp_cache_get( $context, 'wp_conditional_options' );

				if ( ! empty( $maybe_option_ids ) ) {
					break;
				}
			}
		} else {
			$context          = "'" . implode( "','", self::performance_conditional_options_get_context() ) . "'";
			$maybe_option_ids = $wpdb->get_results( "SELECT option_value, option_name FROM wp_options where option_name in ( $context ) ORDER BY FIELD( option_name, $context ) LIMIT 1" );
		}

		if ( empty( $maybe_option_ids ) ) {

			return $pre;
		}

		$key_string            = $maybe_option_ids[0]->option_value;
		self::$current_context = $maybe_option_ids[0]->option_name;

		//var_dump( $wpdb->prepare( "SELECT option_name, option_value FROM `$wpdb->options` WHERE option_id IN ( %s )" , $key_string ) );
		$alloptions_db = $wpdb->get_results( str_replace( array( "('", "')" ), array( '(', ')' ), $wpdb->prepare( "SELECT option_name, option_value FROM `$wpdb->options` WHERE option_id IN (%s)", $key_string ) ) );

		$alloptions = array();
		foreach ( (array) $alloptions_db as $o ) {
			$alloptions[ $o->option_name ] = $o->option_value;
			self::$alloptions_names[]      = $o->option_name;
		}

		if ( ! empty( $alloptions ) ) {
			if ( ! is_array( self::$alloptions_used ) ) {
				self::$alloptions_used = array();
			}
			wp_cache_add( 'alloptions', $alloptions, 'options' );

			return $alloptions;
		}

		return $pre;
	}


	/**
	 * @param $option_name
	 * @param $default
	 *
	 * @return false|mixed|void
	 */
	public static function performance_conditional_options_get_option( $pre, $option_name ) {

		if ( ! in_array( $option_name, self::$alloptions_used, true ) && self::performance_conditional_options_get_context() !== $option_name ) {
			self::$alloptions_used[] = $option_name;
		}

		return $pre;
	}


	/**
	 * @return void
	 */
	public static function performance_conditional_options_save_options_cache() {
		global $wpdb;

		if ( null === self::$alloptions_used ) {
			self::$alloptions_used = array();
		}

		if ( empty( self::$current_context ) ) {
			$context               = self::performance_conditional_options_get_context();
			self::$current_context = array_pop( $context ); // get root URL context
		}
//		var_dump( self::performance_conditional_options_get_context() );
		if ( array_diff( self::$alloptions_used, self::$alloptions_names ) !== array() ) {

			$key_string = "'" . implode( "','", self::$alloptions_used ) . "'";

			$db_ids = $wpdb->get_results( "select option_id from $wpdb->options where option_name IN  ( $key_string ) order by option_id", ARRAY_A );
			$ids    = implode( ',', wp_list_pluck( $db_ids, 'option_id' ) );


			//          check that this is not the same as a lower URL path value.
			foreach ( array_reverse( self::performance_conditional_options_get_context() ) as $key ) {
				$test[$key] = $wpdb->get_results( $wpdb->prepare( "select option_value from $wpdb->options where option_name = '%s'", $key ), ARRAY_A );

				if ( ! empty( $test[$key] ) && $ids === $test[$key][0]['option_value'] ) {
//					self::$current_context = $key;
//					break;
					return;
				}

				self::$current_context = $key;
			}

			if ( self::performance_conditional_has_persistent_caching() ) {
				wp_cache_add( self::$current_context, $ids, 'wp_conditional_options', DAY_IN_SECONDS );
				delete_option( self::$current_context );
			} else {
				$saved = $wpdb->replace(
					$wpdb->options,
					array(
						'option_name'  => self::$current_context,
						'option_value' => $ids,
						'autoload'     => 'no',
					),
					array( '%s', '%s', '%s' )
				);
			}

			// used to test for persistent cache
			wp_cache_add( 'persistent_test', 'cache_active', 'wp_conditional_options', DAY_IN_SECONDS );
		}
	}


	public static function performance_conditional_options_stats() {
		global $wpdb;

		$keys_count = count( self::$alloptions_used );
		$alloptions = $wpdb->get_results( "SELECT option_name FROM $wpdb->options WHERE autoload = 'yes'" );
		$serialized = serialize( $alloptions );
		$all_size   = self::get_size( $serialized );

		$options_keys = array();
		foreach ( $alloptions as $value ) {
			$options_keys[] = $value->option_name;
		}

		$diff_count = count( array_diff( self::$alloptions_names, $options_keys ) );

		$options_count = count( $alloptions );

		$options = wp_cache_get( 'alloptions', 'options' );

		$serialized = serialize( $options );
		$used_size  = self::get_size( $serialized );
		//      var_dump( $used_size );
		$saving_bytes = $all_size - $used_size;

		echo "<div style='text-align: center'>$keys_count options loaded/used instead of an all options count of $options_count</div>";
		echo "<div style='text-align: center'>Pluss the $keys_count options included $diff_count option(s) that were not set to be autoload or where not in/found options </div>";
		echo "<div style='text-align: center'>We used $used_size bytes saving $saving_bytes bytes from the autoload of $all_size bytes</div>";
		echo "<div style='text-align: center'>If we have options used (and maybe bytes saved is negative) and not in the autoloaded we saved calls to DB to load the extra data</div>";
	}


	/**
	 * lets work out the context
	 * TODO: expand as needed
	 *
	 * @return array
	 */
	public static function performance_conditional_options_get_context() {

		// use global value to shortcut function
		if ( self::$conditional_options_context ) {
			return self::$conditional_options_context;
		}
		// find out if logged in
		// this is too early to use WP functions
		$logined = 'false';
		foreach ( $_COOKIE as $key => $val ) {
			if ( false !== strpos( $key, 'wordpress_logged_in_' ) ) {
				$logined = 'user'; //$val; // this have to just true not the user ID
				break;
			}
		}
		$path = 'root' . parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );

		foreach ( explode( '/', trim( $path, '/' )  ) as $path_fragment ) {

//			self::$conditional_options_context[] = 'opc_' . md5( $path_fragment . $logined );
			self::$conditional_options_context[] = 'opc_' . $path_fragment . '_' .  $logined;
		}
		// reverse the array
		self::$conditional_options_context = array_reverse( self::$conditional_options_context );

		return self::$conditional_options_context;
	}

	public static function performance_conditional_has_persistent_caching() {
		$cached_value = wp_cache_get( 'persistent_test', 'wp_conditional_options' );

		if ( 'cache_active' === $cached_value ) {

			return true;
		}

		return false;
	}

	/**
	 * @param string $serialized
	 *
	 * @return false|int
	 */
	private static function get_size( string $serialized ) {
		if ( function_exists( 'mb_strlen' ) ) {
			$used_size = mb_strlen( $serialized, '8bit' );
		} else {
			$used_size = strlen( $serialized );
		}

		return $used_size;
	}
}

CacheOptionPerPage::init();
