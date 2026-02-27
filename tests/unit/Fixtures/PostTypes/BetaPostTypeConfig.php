<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\PostTypes;

use Fundrik\WordPress\Integration\PostTypes\PostTypeConfigInterface;

final class BetaPostTypeConfig implements PostTypeConfigInterface {

	public function get_id(): string {

		return 'beta';
	}

	public function get_slug(): string {

		return 'beta';
	}

	public function get_block_template(): array {

		return [];
	}

	public function get_specific_blocks(): array {

		return [];
	}

	public function get_labels(): array {

		return [];
	}
}
