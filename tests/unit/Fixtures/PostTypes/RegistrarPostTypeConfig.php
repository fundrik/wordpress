<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\PostTypes;

use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaField;
use Fundrik\WordPress\Integration\WpSchemaType;
use Override;

/**
 * Provides post type registration data for tests.
 */
final class RegistrarPostTypeConfig extends PostTypeConfigFixture {

	#[PostTypeMetaField( type: WpSchemaType::Boolean, default: false )]
	public const string META_ENABLED = 'fixture_campaign_enabled';

	#[Override]
	public function get_id(): string {

		return 'fixture_campaign';
	}

	#[Override]
	public function get_slug(): string {

		return 'fixture-campaigns';
	}

	#[Override]
	public function get_block_template(): array {

		return [ [ 'fixture/campaign' ] ];
	}

	#[Override]
	public function get_labels(): array {

		return [
			'name' => 'Fixture campaigns',
			'singular_name' => 'Fixture campaign',
		];
	}
}
