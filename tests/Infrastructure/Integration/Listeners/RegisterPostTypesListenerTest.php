<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Integration\Listeners;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Fundrik\WordPress\Bootstrap\Container\ContainerInterface;
use Fundrik\WordPress\Infrastructure\Integration\Events\RegisterPostTypesEvent;
use Fundrik\WordPress\Infrastructure\Integration\Listeners\RegisterPostTypesListener;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeBlockTemplate;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeBlockTemplateReader;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeId;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeIdReader;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeMetaField;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeMetaFieldReader;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeSlug;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeSlugReader;
use Fundrik\WordPress\Infrastructure\Integration\WordPressContext\WordPressContextInterface;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\AlphaPostType;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;
use stdClass;

#[CoversClass( RegisterPostTypesListener::class )]
#[UsesClass( RegisterPostTypesEvent::class )]
#[UsesClass( PostTypeBlockTemplate::class )]
#[UsesClass( PostTypeBlockTemplateReader::class )]
#[UsesClass( PostTypeId::class )]
#[UsesClass( PostTypeIdReader::class )]
#[UsesClass( PostTypeMetaField::class )]
#[UsesClass( PostTypeMetaFieldReader::class )]
#[UsesClass( PostTypeSlug::class )]
#[UsesClass( PostTypeSlugReader::class )]
final class RegisterPostTypesListenerTest extends WordPressTestCase {

	private ContainerInterface&MockInterface $container;
	private WordPressContextInterface&MockInterface $context;

	private PostTypeIdReader $id_reader;
	private PostTypeSlugReader $slug_reader;
	private PostTypeBlockTemplateReader $template_reader;
	private RegisterPostTypesListener $listener;

	protected function setUp(): void {

		parent::setUp();

		$this->container = Mockery::mock( ContainerInterface::class );
		$this->context = Mockery::mock( WordPressContextInterface::class );

		$this->id_reader = new PostTypeIdReader();
		$this->slug_reader = new PostTypeSlugReader();
		$this->template_reader = new PostTypeBlockTemplateReader();

		$this->listener = new RegisterPostTypesListener(
			$this->container,
			$this->id_reader,
			$this->slug_reader,
			$this->template_reader,
			new PostTypeMetaFieldReader(),
		);
	}

	#[Test]
	public function handle_registers_declared_post_type_and_meta_fields(): void {

		$class_name = AlphaPostType::class;

		$this->context
			->shouldReceive( 'get_declared_post_type_classes' )
			->once()
			->andReturn( [ $class_name ] );

		$post_type = new AlphaPostType();

		$this->container
			->shouldReceive( 'make' )
			->once()
			->with( $class_name )
			->andReturn( $post_type );

		$id = $this->id_reader->get_id( $class_name );
		$labels = $post_type->get_labels();
		$slug = $this->slug_reader->get_slug( $class_name );

		Filters\expectApplied( "fundrik_{$id}_post_type_labels" )
			->once()
			->with( $labels )
			->andReturnFirstArg();

		Filters\expectApplied( "fundrik_{$id}_post_type_slug" )
			->once()
			->with( $slug )
			->andReturnFirstArg();

		Functions\expect( 'register_post_type' )
			->once()
			->with(
				$id,
				Mockery::subset(
					[
						'labels' => $labels,
						'public' => true,
						'menu_icon' => 'dashicons-heart',
						'supports' => [ 'title', 'editor', 'custom-fields' ],
						'has_archive' => true,
						'rewrite' => [ 'slug' => $slug ],
						'show_in_rest' => true,
						'template' => $this->template_reader->get_template( $class_name ),
					],
				),
			);

		Functions\expect( 'register_post_meta' )
			->once()
			->with(
				$id,
				AlphaPostType::META_HAS_NESTED,
				[
					'type' => 'boolean',
					'default' => true,
					'show_in_rest' => true,
					'single' => true,
				],
			);

		$event = new RegisterPostTypesEvent( $this->context );

		$this->listener->handle( $event );
	}

	#[Test]
	public function handle_throws_when_container_returns_non_post_type(): void {

		$class_name = AlphaPostType::class;

		$this->context
			->shouldReceive( 'get_declared_post_type_classes' )
			->once()
			->andReturn( [ $class_name ] );

		$this->container
			->shouldReceive( 'make' )
			->once()
			->with( $class_name )
			->andReturn( new stdClass() );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage(
			'Post type class must implement PostTypeInterface. Given: ' . $class_name . '.',
		);

		$event = new RegisterPostTypesEvent( $this->context );

		$this->listener->handle( $event );
	}
}
