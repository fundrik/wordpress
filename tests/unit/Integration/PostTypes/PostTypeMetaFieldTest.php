<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\PostTypes;

use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaField;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaFieldDefinition;
use Fundrik\WordPress\Integration\WpSchemaType;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( PostTypeMetaField::class )]
#[UsesClass( PostTypeMetaFieldDefinition::class )]
final class PostTypeMetaFieldTest extends FundrikTestCase {

	#[Test]
	public function constructor_sets_type_and_default(): void {

		$attribute = new PostTypeMetaField( WpSchemaType::Boolean, true );

		self::assertSame( WpSchemaType::Boolean, $attribute->type );
		self::assertTrue( $attribute->default );
	}

	#[Test]
	public function to_definition_returns_definition_without_default_when_default_is_null(): void {

		$attribute = new PostTypeMetaField( WpSchemaType::Number );
		$definition = $attribute->to_definition( 'fundrik_amount' );

		self::assertSame( 'fundrik_amount', $definition->meta_key );
		self::assertSame( WpSchemaType::Number, $definition->type );
		self::assertNull( $definition->default_value );
	}

	#[Test]
	public function to_definition_includes_default_when_provided(): void {

		$attribute = new PostTypeMetaField( WpSchemaType::Boolean, true );
		$definition = $attribute->to_definition( 'fundrik_enabled' );

		self::assertSame( 'fundrik_enabled', $definition->meta_key );
		self::assertSame( WpSchemaType::Boolean, $definition->type );
		self::assertTrue( $definition->default_value );
	}
}
