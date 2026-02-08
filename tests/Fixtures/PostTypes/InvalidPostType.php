<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\PostTypes;

use Fundrik\WordPress\Integration\PostTypes\PostTypeInterface;

final class InvalidPostType implements PostTypeInterface {

	public function get_labels(): array {

		return [];
	}
}
