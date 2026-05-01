<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Kernel\Container;

use Illuminate\Contracts\Container\Container as LaravelContainerInterface;
use LogicException;

/**
 * Stores the initialized runtime container exposed across WordPress entry points.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class RuntimeContainer {

	/**
	 * The initialized dependency injection container.
	 *
	 * @since 1.0.0
	 */
	private static ?LaravelContainerInterface $container = null;

	/**
	 * Stores the initialized dependency injection container.
	 *
	 * @since 1.0.0
	 *
	 * @param LaravelContainerInterface $container Initialized container.
	 */
	public static function set( LaravelContainerInterface $container ): void {

		self::$container = $container;
	}

	/**
	 * Returns the initialized runtime container.
	 *
	 * @since 1.0.0
	 *
	 * @return LaravelContainerInterface Initialized container.
	 *
	 * @throws LogicException Runtime container is not available.
	 */
	public static function get(): LaravelContainerInterface {

		if ( self::$container === null ) {
			throw new LogicException( 'Fundrik runtime container is not available.' );
		}

		return self::$container;
	}

	/**
	 * Clears the stored runtime container.
	 *
	 * @since 1.0.0
	 */
	public static function reset(): void {

		self::$container = null;
	}
}
