<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\Events;

use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventInterface;
use Fundrik\WordPress\Infrastructure\Integration\WordPressContext\WordPressContextInterface;

/**
 * Signals that it is time to register custom post types.
 *
 * Triggered by the WordPress 'init' action via the integration bridge.
 *
 * @since 1.0.0
 */
final readonly class ActionRegisterPostTypesEvent implements InfrastructureEventInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param WordPressContextInterface $context The WordPress-specific plugin context.
	 */
	public function __construct(
		public WordPressContextInterface $context,
	) {}
}
