<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\PostTypes;

use Fundrik\WordPress\Integration\MetaFieldType;
use Fundrik\WordPress\Integration\PostTypes\PostTypeConfigInterface;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaField;

final class InvalidDefaultTypePostTypeConfig implements PostTypeConfigInterface {

	#[PostTypeMetaField( type: MetaFieldType::Integer, default: true )]
	public const string META_BAD_DEFAULT = 'invalid_default';

	public function get_id(): string {

		return 'invalid-default';
	}

	public function get_slug(): string {

		return 'invalid-default';
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
