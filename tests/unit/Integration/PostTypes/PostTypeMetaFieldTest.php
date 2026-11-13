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
	public function to_definition_returns_definition_without_default(): void {

		$field = new PostTypeMetaField( WpSchemaType::Integer );

		self::assertEquals(
			new PostTypeMetaFieldDefinition(
				'fundrik_campaign_target_amount',
				WpSchemaType::Integer,
			),
			$field->to_definition( 'fundrik_campaign_target_amount' ),
		);
	}

	#[Test]
	public function to_definition_preserves_configured_default(): void {

		$field = new PostTypeMetaField( WpSchemaType::Boolean, false );

		self::assertEquals(
			new PostTypeMetaFieldDefinition(
				'fundrik_campaign_accepts_donations',
				WpSchemaType::Boolean,
				false,
			),
			$field->to_definition( 'fundrik_campaign_accepts_donations' ),
		);
	}
}
