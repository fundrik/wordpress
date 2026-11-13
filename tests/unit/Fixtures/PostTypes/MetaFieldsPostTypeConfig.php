<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\PostTypes;

use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaField;
use Fundrik\WordPress\Integration\WpSchemaType;

/**
 * Declares representative post meta fields for tests.
 */
final class MetaFieldsPostTypeConfig extends PostTypeConfigFixture {

	#[PostTypeMetaField( type: WpSchemaType::Boolean, default: false )]
	public const string META_ENABLED = 'fixture_enabled';

	#[PostTypeMetaField( type: WpSchemaType::Integer, default: 0 )]
	public const string META_AMOUNT = 'fixture_amount';

	#[PostTypeMetaField( type: WpSchemaType::String, default: 'RUB' )]
	public const string META_CURRENCY = 'fixture_currency';

	#[PostTypeMetaField( type: WpSchemaType::Boolean )]
	public const string META_OPTIONAL = 'fixture_optional';
}
