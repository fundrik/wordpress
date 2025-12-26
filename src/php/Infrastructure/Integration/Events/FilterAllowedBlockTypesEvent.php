<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\Events;

use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventInterface;
use Fundrik\WordPress\Infrastructure\Integration\WordPressContext\WordPressContextInterface;
use WP_Block_Editor_Context;

/**
 * Signals that the list of allowed block types in the Block Editor should be filtered or overridden.
 *
 * Triggered by the WordPress 'allowed_block_types_all' filter via the integration bridge.
 *
 * @since 1.0.0
 */
final class FilterAllowedBlockTypesEvent implements InfrastructureEventInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param bool|array<string> $allowed The list of allowed block type slugs, or a boolean to allow or disallow all.
	 * @param WP_Block_Editor_Context $editor_context The current Block Editor context.
	 * @param WordPressContextInterface $context The WordPress-specific plugin context.
	 */
	public function __construct(
		public bool|array $allowed,
		public readonly WP_Block_Editor_Context $editor_context,
		public readonly WordPressContextInterface $context,
	) {}
}
