<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\PostTypes;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\PostTypes\Exceptions\PostMetaRegistrationException;
use Fundrik\WordPress\Integration\PostTypes\Exceptions\PostTypeRegistrationException;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaField;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaFieldReader;
use Fundrik\WordPress\Integration\PostTypes\PostTypeRegistrar;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\GammaPostTypeConfig;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use WP_Error;

#[CoversClass( PostTypeRegistrar::class )]
#[UsesClass( PostTypeMetaField::class )]
#[UsesClass( PostTypeMetaFieldReader::class )]
final class PostTypeRegistrarTest extends WordPressTestCase {

	private PostTypeRegistrar $registrar;

	protected function setUp(): void {

		parent::setUp();

		$this->registrar = new PostTypeRegistrar( new PostTypeMetaFieldReader() );
	}

	#[Test]
	public function it_registers_post_type_and_then_registers_all_meta_fields(): void {

		$config = new GammaPostTypeConfig();

		Filters\expectApplied( 'fundrik_gamma_post_type_labels' )
			->once()
			->with( [ 'name' => 'Gamma' ] )
			->andReturn( [ 'name' => 'Gamma Filtered' ] );

		Filters\expectApplied( 'fundrik_gamma_post_type_slug' )
			->once()
			->with( 'gamma' )
			->andReturn( 'gamma-filtered' );

		Functions\expect( 'register_post_type' )
			->once()
			->with(
				'gamma',
				Mockery::on(
					static fn ( array $args ): bool => $args['labels'] === [ 'name' => 'Gamma Filtered' ]
						&& $args['rewrite'] === [ 'slug' => 'gamma-filtered' ]
						&& $args['template'] === [ [ 'gamma/block' ] ]
						&& $args['public'] === true
						&& $args['menu_icon'] === 'dashicons-heart'
						&& $args['supports'] === [ 'title', 'editor', 'custom-fields' ]
						&& $args['show_in_menu'] === AdminPageDefinitions::ROOT_MENU_SLUG
						&& $args['has_archive'] === true
						&& $args['show_in_rest'] === true,
				),
			)
			->andReturn( true );

		Functions\expect( 'register_post_meta' )
			->once()
			->with(
				'gamma',
				'gamma_is_open',
				[
					'type' => 'boolean',
					'default' => true,
					'show_in_rest' => true,
					'single' => true,
				],
			)
			->andReturn( true );

		Functions\expect( 'register_post_meta' )
			->once()
			->with(
				'gamma',
				'gamma_amount',
				[
					'type' => 'integer',
					'default' => 0,
					'show_in_rest' => true,
					'single' => true,
				],
			)
			->andReturn( true );

		$this->registrar->register( $config );

		self::assertTrue( true );
	}

	#[Test]
	public function it_throws_when_post_type_registration_returns_wp_error(): void {

		$config = new GammaPostTypeConfig();

		Filters\expectApplied( 'fundrik_gamma_post_type_labels' )
			->once()
			->with( [ 'name' => 'Gamma' ] )
			->andReturn( [ 'name' => 'Gamma' ] );

		Filters\expectApplied( 'fundrik_gamma_post_type_slug' )
			->once()
			->with( 'gamma' )
			->andReturn( 'gamma' );

		$wp_error = Mockery::mock( WP_Error::class );
		$wp_error->shouldReceive( 'get_error_message' )->once()->andReturn( 'Boom' );

		Functions\expect( 'register_post_type' )
			->once()
			->with( 'gamma', Mockery::type( 'array' ) )
			->andReturn( $wp_error );

		Functions\expect( 'register_post_meta' )->never();

		$this->expectException( PostTypeRegistrationException::class );
		$this->expectExceptionMessage( 'Cannot register post type "gamma": Boom.' );

		$this->registrar->register( $config );
	}

	#[Test]
	public function it_throws_when_any_meta_field_registration_fails(): void {

		$config = new GammaPostTypeConfig();

		Filters\expectApplied( 'fundrik_gamma_post_type_labels' )
			->once()
			->with( [ 'name' => 'Gamma' ] )
			->andReturn( [ 'name' => 'Gamma' ] );

		Filters\expectApplied( 'fundrik_gamma_post_type_slug' )
			->once()
			->with( 'gamma' )
			->andReturn( 'gamma' );

		Functions\expect( 'register_post_type' )
			->once()
			->with( 'gamma', Mockery::type( 'array' ) )
			->andReturn( true );

		// Fail on the first meta registration. The second must not happen.
		Functions\expect( 'register_post_meta' )
			->once()
			->with(
				'gamma',
				'gamma_is_open',
				[
					'type' => 'boolean',
					'default' => true,
					'show_in_rest' => true,
					'single' => true,
				],
			)
			->andReturn( false );

		$this->expectException( PostMetaRegistrationException::class );
		$this->expectExceptionMessage( 'Failed to register post meta "gamma_is_open" for post type "gamma".' );

		$this->registrar->register( $config );
	}
}
