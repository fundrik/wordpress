<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\PostTypes;

use Fundrik\WordPress\Integration\MetaFieldType;
use Fundrik\WordPress\Integration\PostTypes\PostTypeConfigInterface;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaField;

final class GammaPostTypeConfig implements PostTypeConfigInterface {

	#[PostTypeMetaField( type: MetaFieldType::Boolean, default: true )]
	public const string META_IS_OPEN = 'gamma_is_open';

	#[PostTypeMetaField( type: MetaFieldType::Number, default: 0 )]
	public const string META_AMOUNT = 'gamma_amount';

	public function get_id(): string {

		return 'gamma';
	}

	public function get_slug(): string {

		return 'gamma';
	}

	public function get_block_template(): array {

		return [ [ 'gamma/block' ] ];
	}

	public function get_specific_blocks(): array {

		return [ 'gamma/block' ];
	}

	public function get_labels(): array {

		return [ 'name' => 'Gamma' ];
	}
}
