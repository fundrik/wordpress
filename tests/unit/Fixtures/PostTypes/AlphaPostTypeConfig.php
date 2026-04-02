<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\PostTypes;

use Fundrik\WordPress\Integration\WpSchemaType;
use Fundrik\WordPress\Integration\PostTypes\PostTypeConfigInterface;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaField;

final class AlphaPostTypeConfig implements PostTypeConfigInterface {

	#[PostTypeMetaField( type: WpSchemaType::Boolean, default: true )]
	public const string META_HAS_NESTED = 'alpha_has_nested';

	public function get_id(): string {

		return 'alpha';
	}

	public function get_slug(): string {

		return 'alpha';
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
