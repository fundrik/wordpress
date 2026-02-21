<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\HookDispatchers;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Creates hook dispatcher instances.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class HookDispatcherFactory {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param LoggerInterface $logger Writes structured log entries for hook operations.
	 */
	public function __construct(
		private LoggerInterface $logger,
	) {}

	/**
	 * Creates a hook dispatcher instance by class name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The hook dispatcher class.
	 *
	 * @phpstan-param class-string<HookDispatcherInterface> $class_name
	 *
	 * @return HookDispatcherInterface The created hook dispatcher.
	 *
	 * @throws InvalidArgumentException When the class does not exist or does not implement HookDispatcherInterface.
	 */
	public function create( string $class_name ): HookDispatcherInterface {

		if ( ! class_exists( $class_name ) ) {

			throw new InvalidArgumentException(
				sprintf(
					'Cannot create the hook dispatcher: the class must exist. Given: %s.',
					$class_name,
				),
			);
		}

		if ( ! is_subclass_of( $class_name, HookDispatcherInterface::class ) ) {

			throw new InvalidArgumentException(
				sprintf(
					'Cannot create the hook dispatcher: the class must implement %s. Given: %s.',
					HookDispatcherInterface::class,
					$class_name,
				),
			);
		}

		return new $class_name( new HookDispatcherLogger( $this->logger ) );
	}
}
