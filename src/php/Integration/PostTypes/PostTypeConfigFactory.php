<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\PostTypes;

use InvalidArgumentException;

/**
 * Creates post type config instances.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class PostTypeConfigFactory {

	/**
	 * Creates the post type config by the given class name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The post type config class name.
	 *
	 * @return PostTypeConfigInterface The post type config instance.
	 *
	 * @throws InvalidArgumentException When the class does not exist or does not implement PostTypeConfigInterface.
	 */
	public function create( string $class_name ): PostTypeConfigInterface {

		if ( ! class_exists( $class_name ) ) {

			throw new InvalidArgumentException(
				sprintf(
					'Cannot create the post type configuration: the class must exist. Given: %s.',
					$class_name,
				),
			);
		}

		if ( ! is_subclass_of( $class_name, PostTypeConfigInterface::class ) ) {

			throw new InvalidArgumentException(
				sprintf(
					'Cannot create the post type configuration: the class must implement %s. Given: %s.',
					PostTypeConfigInterface::class,
					$class_name,
				),
			);
		}

		return new $class_name();
	}
}
