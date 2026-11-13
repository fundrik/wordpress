<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\PostTypes;

use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaField;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaFieldDefinition;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaFieldReader;
use Fundrik\WordPress\Integration\WpSchemaType;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\InvalidMetaFieldDefaultPostTypeConfig;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\InvalidMetaFieldKeyPostTypeConfig;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\MetaFieldsPostTypeConfig;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\PostTypeConfigFixture;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\UnsupportedMetaFieldDefaultPostTypeConfig;
use Fundrik\WordPress\Tests\FundrikTestCase;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( PostTypeMetaFieldReader::class )]
#[UsesClass( PostTypeMetaField::class )]
#[UsesClass( PostTypeMetaFieldDefinition::class )]
final class PostTypeMetaFieldReaderTest extends FundrikTestCase {

	private PostTypeMetaFieldReader $reader;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->reader = new PostTypeMetaFieldReader();
	}

	#[Test]
	public function get_meta_fields_returns_declared_definitions(): void {

		self::assertEquals(
			[
				MetaFieldsPostTypeConfig::META_ENABLED => new PostTypeMetaFieldDefinition(
					MetaFieldsPostTypeConfig::META_ENABLED,
					WpSchemaType::Boolean,
					false,
				),
				MetaFieldsPostTypeConfig::META_AMOUNT => new PostTypeMetaFieldDefinition(
					MetaFieldsPostTypeConfig::META_AMOUNT,
					WpSchemaType::Integer,
					0,
				),
				MetaFieldsPostTypeConfig::META_CURRENCY => new PostTypeMetaFieldDefinition(
					MetaFieldsPostTypeConfig::META_CURRENCY,
					WpSchemaType::String,
					'RUB',
				),
				MetaFieldsPostTypeConfig::META_OPTIONAL => new PostTypeMetaFieldDefinition(
					MetaFieldsPostTypeConfig::META_OPTIONAL,
					WpSchemaType::Boolean,
				),
			],
			$this->reader->get_meta_fields( new MetaFieldsPostTypeConfig() ),
		);
	}

	#[Test]
	public function get_meta_fields_returns_empty_array_when_no_fields_are_declared(): void {

		self::assertSame(
			[],
			$this->reader->get_meta_fields( new PostTypeConfigFixture() ),
		);
	}

	#[Test]
	public function get_meta_fields_throws_when_meta_key_is_not_a_string(): void {

		$this->expectException( InvalidArgumentException::class );

		$this->reader->get_meta_fields( new InvalidMetaFieldKeyPostTypeConfig() );
	}

	#[Test]
	public function get_meta_fields_throws_when_default_does_not_match_declared_type(): void {

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage(
			'Post meta default for "fixture_invalid_default" in "'
			. InvalidMetaFieldDefaultPostTypeConfig::class
			. '" must match "integer". Given: bool.',
		);

		$this->reader->get_meta_fields( new InvalidMetaFieldDefaultPostTypeConfig() );
	}

	#[Test]
	public function get_meta_fields_throws_when_default_is_declared_for_unsupported_type(): void {

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage(
			'Post meta default for "fixture_unsupported_default" in "'
			. UnsupportedMetaFieldDefaultPostTypeConfig::class
			. '" must match "number". Given: int.',
		);

		$this->reader->get_meta_fields( new UnsupportedMetaFieldDefaultPostTypeConfig() );
	}

	#[Test]
	public function get_meta_default_by_config_class_returns_declared_default(): void {

		self::assertFalse(
			$this->reader->get_meta_default_by_config_class(
				MetaFieldsPostTypeConfig::class,
				MetaFieldsPostTypeConfig::META_ENABLED,
			),
		);
	}

	#[Test]
	public function get_meta_default_by_config_class_returns_null_when_field_has_no_default(): void {

		self::assertNull(
			$this->reader->get_meta_default_by_config_class(
				MetaFieldsPostTypeConfig::class,
				MetaFieldsPostTypeConfig::META_OPTIONAL,
			),
		);
	}

	#[Test]
	public function get_meta_default_by_config_class_returns_null_when_field_is_not_declared(): void {

		self::assertNull(
			$this->reader->get_meta_default_by_config_class(
				MetaFieldsPostTypeConfig::class,
				'fixture_unknown',
			),
		);
	}
}
