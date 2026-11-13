<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\PostTypes;

use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaField;
use Fundrik\WordPress\Integration\WpSchemaType;

/**
 * Declares a post meta field with a mismatched default type for tests.
 */
final class InvalidMetaFieldDefaultPostTypeConfig extends PostTypeConfigFixture {

	#[PostTypeMetaField( type: WpSchemaType::Integer, default: false )]
	public const string META_INVALID = 'fixture_invalid_default';
}
