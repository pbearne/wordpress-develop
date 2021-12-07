<?php

namespace xwp\conditional_options;

class conditional_options {
	/*
	 * hold the current context
	 */
	/**
	 * @var string
	 */
	private static $context;
	/*
	 * hold the current options
	 * @var array
	 */
	private static $options;
	/*
	 * holds the list option that have updated
	 * @var array
	 */
	private static $stale_list;
	/*
	 * do we have a cache miss?
	 * @var bool
	 */
	private static $has_miss = false;

	private static $running = false;


	public function __construct() {
		add_action( 'init', array( __CLASS__, 'set_context' ) );
		add_action( 'shutdown', array( __CLASS__, 'save_options_cache' ) );
		add_action( 'update_option', array( __CLASS__, 'update_option' ) );
	}


	/**
	 * @return string
	 */
	public static function get_context() {
		if ( ! self::$context ) {
			self::$context    = self::set_context();
			self::$stale_list = get_option( 'conditional_options_stale_list', array() );
		}

		return self::$context;
	}

	public static function conditional_options_preload( $force_cache ) {
		self::$running = true;

		$context    = self::get_context();
		$alloptions = get_option( 'conditional_options_' . $context );

		if ( false === $alloptions ) {
			if ( ! is_multisite() ) {
				$alloptions = wp_cache_get( 'alloptions', 'options', $force_cache );
			} else {
				$alloptions = false;
			}
		}
		self::$running = false;

		return $alloptions;
	}

	/**
	 * @param $option_name
	 * @param $default
	 *
	 * @return false|mixed|void
	 */
	public static function get_option( $option_name, $default = false ) {
		self::$running = true;
		$context       = self::get_context();
		if ( is_array( self::$options ) && array_key_exists( $option_name, self::$options ) && ! in_array( $option_name, self::$stale_list, true ) ) {
			self::$running = false;
			return self::$options[ $option_name ];
		}

		$cache = wp_cache_get( $context, 'conditional_options' );

		$alloptions = ( false !== $cache ) ? $cache : get_option( 'conditional_options_' . $context );

		if ( is_array( $alloptions ) && array_key_exists( $option_name, $alloptions ) && ! in_array( $option_name, self::$stale_list, true ) ) {
			return $alloptions[ $option_name ];
		} else {
			$option_value                  = get_option( $option_name, $default );
			self::$options[ $option_name ] = $option_value;
			self::$has_miss                = true;

			return $option_value;
		}
	}

	/**
	 * @return bool
	 */
	public static function runing() {
		return self::$running;
	}

	/**
	 * @return void
	 */
	public static function save_options_cache() {
		var_dump( self::$has_miss );
		if ( self::$has_miss ) {
			$context = self::get_context();
			wp_cache_set( $context, self::$options, 'conditional_options', HOUR_IN_SECONDS );
			update_option( 'conditional_options_' . $context, self::$options, false );
		}

		wp_cache_set( 'stale_list', self::$stale_list, 'conditional_options', HOUR_IN_SECONDS );
		update_option( 'conditional_options_stale_list', self::$stale_list, false );

	}
	/**
	 * @return void
	 */
	public static function update_option( $option_name ) {
		if ( ! in_array( $option_name, self::$stale_list, true ) ) {
			self::$stale_list[] = $option_name;
		}

	}

	/**
	 * lets work out the context
	 * TODO: expand as needed
	 *
	 * @return string
	 */
	public static function set_context() {
		global $wp_query;
		$queryied_name = '';
		if ( null !== $wp_query ) {
			$queryied_object = $wp_query->get_queried_object();
			if ( null !== $queryied_object ) {
				$queryied_name = get_class( $queryied_object );
			}
		} else {
			$queryied_name = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
		}

		switch ( $queryied_name ) {
			case 'WP_Term':
				$context = ' term_' . $queryied_object->term_id;
				break;
			case 'WP_Post_Type':
				$context = ' post_type_' . $queryied_object->name;
				break;
			case 'WP_Post':
				$context = ' post_' . $queryied_object->ID;
				break;
			case 'WP_User':
				$context = ' user_' . $queryied_object->ID;
				break;
			case 'wp_admin':
				$context = 'wp_admin';
				break;
			default:
				if ( is_admin() ) {
					$context = ' wp_admin';
				} else {
					$context = ' wp_front';
				}
				break;
		}

		return $context;
	}
}