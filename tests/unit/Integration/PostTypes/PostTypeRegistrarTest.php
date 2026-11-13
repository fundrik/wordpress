<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\PostTypes;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\PostTypes\Exceptions\PostMetaRegistrationException;
use Fundrik\WordPress\Integration\PostTypes\Exceptions\PostTypeRegistrationException;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaField;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaFieldDefinition;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaFieldReader;
use Fundrik\WordPress\Integration\PostTypes\PostTypeRegistrar;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\RegistrarPostTypeConfig;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use WP_Error;

#[CoversClass( PostTypeRegistrar::class )]
#[UsesClass( AdminPageDefinitions::class )]
#[UsesClass( PostTypeMetaField::class )]
#[UsesClass( PostTypeMetaFieldDefinition::class )]
#[UsesClass( PostTypeMetaFieldReader::class )]
final class PostTypeRegistrarTest extends WordPressTestCase {

	private const string POST_TYPE_ID = 'fixture_campaign';
	private const string LABELS_FILTER = 'fundrik_fixture_campaign_post_type_labels';
	private const string SLUG_FILTER = 'fundrik_fixture_campaign_post_type_slug';

	private RegistrarPostTypeConfig $config;
	private PostTypeRegistrar $registrar;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->config = new RegistrarPostTypeConfig();
		$this->registrar = new PostTypeRegistrar( new PostTypeMetaFieldReader() );
	}

	#[Test]
	public function register_registers_filtered_post_type_and_declared_meta_fields(): void {

		$filtered_labels = [
			'name' => 'Filtered campaigns',
			'singular_name' => 'Filtered campaign',
		];

		Filters\expectApplied( self::LABELS_FILTER )
			->once()
			->with( $this->config->get_labels() )
			->andReturn( $filtered_labels );

		Filters\expectApplied( self::SLUG_FILTER )
			->once()
			->with( 'fixture-campaigns' )
			->andReturn( 'filtered-campaigns' );

		Functions\expect( 'register_post_type' )
			->once()
			->with(
				self::POST_TYPE_ID,
				$this->expected_post_type_args( $filtered_labels, 'filtered-campaigns' ),
			)
			->andReturn( true );

		$this->expect_successful_meta_registration();

		$this->registrar->register( $this->config );
	}

	#[Test]
	public function register_uses_original_labels_when_filter_result_is_invalid(): void {

		$labels = $this->config->get_labels();

		Filters\expectApplied( self::LABELS_FILTER )
			->once()
			->with( $labels )
			->andReturn( 'invalid' );

		Filters\expectApplied( self::SLUG_FILTER )
			->once()
			->with( 'fixture-campaigns' );

		Functions\expect( '_doing_it_wrong' )
			->once()
			->with( self::LABELS_FILTER, 'Filter must return an array of strings. Given: string.', '1.0.0' );

		Functions\expect( 'register_post_type' )
			->once()
			->with(
				self::POST_TYPE_ID,
				$this->expected_post_type_args( $labels, 'fixture-campaigns' ),
			)
			->andReturn( true );

		$this->expect_successful_meta_registration();

		$this->registrar->register( $this->config );
	}

	#[Test]
	public function register_uses_original_slug_when_filter_result_is_invalid(): void {

		$labels = $this->config->get_labels();

		Filters\expectApplied( self::LABELS_FILTER )
			->once()
			->with( $labels );

		Filters\expectApplied( self::SLUG_FILTER )
			->once()
			->with( 'fixture-campaigns' )
			->andReturn( 42 );

		Functions\expect( '_doing_it_wrong' )
			->once()
			->with( self::SLUG_FILTER, 'Filter must return a string. Given: int.', '1.0.0' );

		Functions\expect( 'register_post_type' )
			->once()
			->with(
				self::POST_TYPE_ID,
				$this->expected_post_type_args( $labels, 'fixture-campaigns' ),
			)
			->andReturn( true );

		$this->expect_successful_meta_registration();

		$this->registrar->register( $this->config );
	}

	#[Test]
	public function register_throws_when_post_type_registration_fails(): void {

		$labels = $this->config->get_labels();

		Filters\expectApplied( self::LABELS_FILTER )
			->once()
			->with( $labels );

		Filters\expectApplied( self::SLUG_FILTER )
			->once()
			->with( 'fixture-campaigns' );

		Functions\expect( 'register_post_type' )
			->once()
			->with(
				self::POST_TYPE_ID,
				$this->expected_post_type_args( $labels, 'fixture-campaigns' ),
			)
			->andReturn( new WP_Error( 'registration_failed', 'Database rejected the post type' ) );

		$this->expectException( PostTypeRegistrationException::class );
		$this->expectExceptionMessage(
			'Cannot register post type "fixture_campaign": Database rejected the post type.',
		);

		$this->registrar->register( $this->config );
	}

	#[Test]
	public function register_throws_when_meta_field_registration_fails(): void {

		$labels = $this->config->get_labels();

		Filters\expectApplied( self::LABELS_FILTER )
			->once()
			->with( $labels );

		Filters\expectApplied( self::SLUG_FILTER )
			->once()
			->with( 'fixture-campaigns' );

		Functions\expect( 'register_post_type' )
			->once()
			->with(
				self::POST_TYPE_ID,
				$this->expected_post_type_args( $labels, 'fixture-campaigns' ),
			)
			->andReturn( true );

		Functions\expect( 'register_post_meta' )
			->once()
			->with(
				self::POST_TYPE_ID,
				RegistrarPostTypeConfig::META_ENABLED,
				[
					'type' => 'boolean',
					'default' => false,
					'show_in_rest' => true,
					'single' => true,
				],
			)
			->andReturn( false );

		$this->expectException( PostMetaRegistrationException::class );
		$this->expectExceptionMessage(
			'Failed to register post meta "fixture_campaign_enabled" for post type "fixture_campaign".',
		);

		$this->registrar->register( $this->config );
	}

	/**
	 * Returns expected WordPress post type registration arguments.
	 *
	 * @param array<string, string> $labels Post type labels.
	 * @param string $slug Rewrite slug.
	 *
	 * @return array<string, mixed> Registration arguments.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function expected_post_type_args( array $labels, string $slug ): array {

		return [
			'labels' => $labels,
			'public' => true,
			'menu_icon' => 'dashicons-heart',
			'supports' => [ 'title', 'editor', 'thumbnail', 'custom-fields' ],
			'has_archive' => true,
			'rewrite' => [ 'slug' => $slug ],
			'show_in_menu' => AdminPageDefinitions::ROOT_MENU_SLUG,
			'show_in_rest' => true,
			'template' => [ [ 'fixture/campaign' ] ],
		];
	}

	private function expect_successful_meta_registration(): void {

		Functions\expect( 'register_post_meta' )
			->once()
			->with(
				self::POST_TYPE_ID,
				RegistrarPostTypeConfig::META_ENABLED,
				[
					'type' => 'boolean',
					'default' => false,
					'show_in_rest' => true,
					'single' => true,
				],
			)
			->andReturn( true );
	}
}
