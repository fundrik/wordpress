<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\PostTypes;

use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaField;
use Fundrik\WordPress\Integration\WpSchemaType;

/**
 * Declares a default for an unsupported post meta schema type for tests.
 */
final class UnsupportedMetaFieldDefaultPostTypeConfig extends PostTypeConfigFixture {

	#[PostTypeMetaField( type: WpSchemaType::Number, default: 1 )]
	public const string META_INVALID = 'fixture_unsupported_default';
}
