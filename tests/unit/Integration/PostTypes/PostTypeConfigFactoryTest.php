<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\PostTypes;

use Fundrik\WordPress\Integration\PostTypes\PostTypeConfigFactory;
use Fundrik\WordPress\Integration\PostTypes\PostTypeConfigInterface;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\AlphaPostTypeConfig;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\NotAPostTypeConfig;
use Fundrik\WordPress\Tests\FundrikTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( PostTypeConfigFactory::class )]
final class PostTypeConfigFactoryTest extends FundrikTestCase {

	private PostTypeConfigFactory $factory;

	protected function setUp(): void {

		parent::setUp();

		$this->factory = new PostTypeConfigFactory();
	}

	#[Test]
	public function create_returns_instance_of_given_config_class(): void {

		$config = $this->factory->create( AlphaPostTypeConfig::class );

		self::assertInstanceOf( AlphaPostTypeConfig::class, $config );
		self::assertInstanceOf( PostTypeConfigInterface::class, $config );
	}

	#[Test]
	public function create_throws_when_class_does_not_exist(): void {

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage(
			'Cannot create the post type configuration: the class must exist. Given: No\\Such\\Class.',
		);

		$this->factory->create( 'No\\Such\\Class' );
	}

	#[Test]
	public function create_throws_when_class_does_not_implement_interface(): void {

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage(
			sprintf(
				'Cannot create the post type configuration: the class must implement %s. Given: %s.',
				PostTypeConfigInterface::class,
				NotAPostTypeConfig::class,
			),
		);

		$this->factory->create( NotAPostTypeConfig::class );
	}
}
