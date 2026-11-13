<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\PostTypes;

use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaField;
use Fundrik\WordPress\Integration\WpSchemaType;

/**
 * Declares a post meta field with a non-string key for tests.
 */
final class InvalidMetaFieldKeyPostTypeConfig extends PostTypeConfigFixture {

	#[PostTypeMetaField( type: WpSchemaType::Boolean )]
	public const int META_INVALID = 42;
}
