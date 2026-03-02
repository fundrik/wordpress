<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\PostTypes;

use Fundrik\WordPress\Integration\PostTypes\PostTypeConfigInterface;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaField;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaFieldType;

final class StringMetaWithOptionalDefaultPostTypeConfig implements PostTypeConfigInterface {

	#[PostTypeMetaField( type: PostTypeMetaFieldType::String, default: 'RUB' )]
	public const string META_TARGET_CURRENCY = 'fixture_target_currency';

	#[PostTypeMetaField( type: PostTypeMetaFieldType::Boolean )]
	public const string META_HAS_TARGET = 'fixture_has_target';

	public function get_id(): string {

		return 'string-meta';
	}

	public function get_slug(): string {

		return 'string-meta';
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
