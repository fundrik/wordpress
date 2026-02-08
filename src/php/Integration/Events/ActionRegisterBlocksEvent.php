<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Events;

use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventInterface;
use Fundrik\WordPress\Integration\WordPressContext\WordPressContextInterface;

/**
 * Signals that it is time to register editor blocks.
 *
 * Triggered by the WordPress 'init' action via the integration bridge.
 *
 * @since 1.0.0
 */
final readonly class ActionRegisterBlocksEvent implements InfrastructureEventInterface {

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
