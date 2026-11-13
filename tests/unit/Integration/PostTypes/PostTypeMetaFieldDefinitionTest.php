<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\PostTypes;

use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaFieldDefinition;
use Fundrik\WordPress\Integration\WpSchemaType;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( PostTypeMetaFieldDefinition::class )]
final class PostTypeMetaFieldDefinitionTest extends FundrikTestCase {

	#[Test]
	public function to_wp_args_omits_default_when_not_configured(): void {

		$definition = new PostTypeMetaFieldDefinition( 'fundrik_campaign_target_amount', WpSchemaType::Integer );

		self::assertSame( [ 'type' => 'integer' ], $definition->to_wp_args() );
	}

	#[Test]
	public function to_wp_args_includes_false_default(): void {

		$definition = new PostTypeMetaFieldDefinition(
			'fundrik_campaign_accepts_donations',
			WpSchemaType::Boolean,
			false,
		);

		self::assertSame(
			[
				'type' => 'boolean',
				'default' => false,
			],
			$definition->to_wp_args(),
		);
	}
}
