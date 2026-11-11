<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures;

use Fundrik\WordPress\Integration\PostTypes\PostTypeConfigInterface;
use Override;

/**
 * Provides configurable post type metadata for tests.
 */
final readonly class TestPostTypeConfig implements PostTypeConfigInterface {

	/**
	 * Constructor.
	 *
	 * @param string $id Post type ID.
	 * @param list<string> $specific_blocks Blocks restricted to this post type.
	 *
	 * @phpstan-param non-empty-lowercase-string $id
	 */
	public function __construct(
		private string $id,
		private array $specific_blocks,
	) {}

	#[Override]
	public function get_id(): string {

		return $this->id;
	}

	#[Override]
	public function get_slug(): string {

		return $this->id;
	}

	#[Override]
	public function get_block_template(): array {

		return [];
	}

	#[Override]
	public function get_specific_blocks(): array {

		return $this->specific_blocks;
	}

	#[Override]
	public function get_labels(): array {

		return [];
	}
}
