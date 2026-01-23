<?php

namespace Hametuha\Hamelp\Pattern;

/**
 * Singleton pattern.
 *
 * @package hamelp
 */
abstract class Singleton {

	/**
	 * Instance holder.
	 *
	 * @var static[]
	 */
	protected static $instances = [];

	/**
	 * Singleton constructor.
	 */
	final protected function __construct() {
		$this->init();
	}

	/**
	 * Do something in constructor.
	 */
	protected function init() {
		// Do something.
	}

	/**
	 * Get instance.
	 *
	 * @return static
	 */
	public static function get() {
		$class_name = get_called_class();
		if ( ! isset( self::$instances[ $class_name ] ) ) {
			self::$instances[ $class_name ] = new $class_name();
		}
		return self::$instances[ $class_name ];
	}
}
