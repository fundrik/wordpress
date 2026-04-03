<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\PostTypes;

use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaField;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaFieldDefinition;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaFieldReader;
use Fundrik\WordPress\Integration\WpSchemaType;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\AlphaPostTypeConfig;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\BetaPostTypeConfig;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\GammaPostTypeConfig;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\InvalidDefaultTypePostTypeConfig;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\StringMetaWithOptionalDefaultPostTypeConfig;
use Fundrik\WordPress\Tests\FundrikTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( PostTypeMetaFieldReader::class )]
#[UsesClass( PostTypeMetaField::class )]
#[UsesClass( PostTypeMetaFieldDefinition::class )]
final class PostTypeMetaFieldReaderTest extends FundrikTestCase {

	#[Test]
	public function get_meta_fields_returns_declared_meta_fields_from_constants(): void {

		$reader = new PostTypeMetaFieldReader();
		$config = new AlphaPostTypeConfig();

		self::assertEquals(
			[
				AlphaPostTypeConfig::META_HAS_NESTED => new PostTypeMetaFieldDefinition(
					AlphaPostTypeConfig::META_HAS_NESTED,
					WpSchemaType::Boolean,
					true,
				),
			],
			$reader->get_meta_fields( $config ),
		);
	}

	#[Test]
	public function get_meta_fields_returns_empty_array_when_no_meta_fields_declared(): void {

		$reader = new PostTypeMetaFieldReader();
		$config = new BetaPostTypeConfig();

		self::assertSame( [], $reader->get_meta_fields( $config ) );
	}

	#[Test]
	public function get_meta_default_by_config_class_returns_declared_default_value(): void {

		$reader = new PostTypeMetaFieldReader();

		self::assertTrue(
			$reader->get_meta_default_by_config_class(
				AlphaPostTypeConfig::class,
				AlphaPostTypeConfig::META_HAS_NESTED,
			),
		);
	}

	#[Test]
	public function get_meta_default_by_config_class_returns_null_when_meta_key_is_not_declared(): void {

		$reader = new PostTypeMetaFieldReader();

		self::assertNull(
			$reader->get_meta_default_by_config_class(
				BetaPostTypeConfig::class,
				'beta_unknown_meta_key',
			),
		);
	}

	#[Test]
	public function get_meta_default_by_config_class_returns_declared_integer_default_value(): void {

		$reader = new PostTypeMetaFieldReader();

		self::assertSame(
			0,
			$reader->get_meta_default_by_config_class(
				GammaPostTypeConfig::class,
				GammaPostTypeConfig::META_AMOUNT,
			),
		);
	}

	#[Test]
	public function get_meta_default_by_config_class_throws_when_default_type_does_not_match_declared_type(): void {

		$reader = new PostTypeMetaFieldReader();
		$config = new InvalidDefaultTypePostTypeConfig();

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'must match "integer". Given: bool.' );

		$reader->get_meta_default_by_config_class( $config::class, InvalidDefaultTypePostTypeConfig::META_BAD_DEFAULT );
	}

	#[Test]
	public function get_meta_fields_throws_when_default_type_does_not_match_declared_type(): void {

		$reader = new PostTypeMetaFieldReader();
		$config = new InvalidDefaultTypePostTypeConfig();

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'must match "integer". Given: bool.' );

		$reader->get_meta_fields( $config );
	}

	#[Test]
	public function get_meta_fields_handles_string_defaults_and_fields_without_defaults(): void {

		$reader = new PostTypeMetaFieldReader();
		$config = new StringMetaWithOptionalDefaultPostTypeConfig();

		self::assertEquals(
			[
				StringMetaWithOptionalDefaultPostTypeConfig::META_TARGET_CURRENCY => new PostTypeMetaFieldDefinition(
					StringMetaWithOptionalDefaultPostTypeConfig::META_TARGET_CURRENCY,
					WpSchemaType::String,
					'RUB',
				),
				StringMetaWithOptionalDefaultPostTypeConfig::META_HAS_TARGET => new PostTypeMetaFieldDefinition(
					StringMetaWithOptionalDefaultPostTypeConfig::META_HAS_TARGET,
					WpSchemaType::Boolean,
				),
			],
			$reader->get_meta_fields( $config ),
		);
	}

	#[Test]
	public function get_meta_default_by_config_class_returns_null_when_meta_field_has_no_default(): void {

		$reader = new PostTypeMetaFieldReader();

		self::assertNull(
			$reader->get_meta_default_by_config_class(
				StringMetaWithOptionalDefaultPostTypeConfig::class,
				StringMetaWithOptionalDefaultPostTypeConfig::META_HAS_TARGET,
			),
		);
	}
}
