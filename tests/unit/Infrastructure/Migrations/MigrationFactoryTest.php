<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Migrations;

use Fundrik\WordPress\Infrastructure\DatabasePort;
use Fundrik\WordPress\Infrastructure\Migrations\AbstractMigration;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationException;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationFactory;
use Fundrik\WordPress\Tests\Fixtures\Migrations\NewMigration1;
use Fundrik\WordPress\Tests\Fixtures\Migrations\NotAMigration;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( MigrationFactory::class )]
#[UsesClass( MigrationException::class )]
#[UsesClass( AbstractMigration::class )]
final class MigrationFactoryTest extends MockeryTestCase {

	private DatabasePort&MockInterface $database;
	private MigrationFactory $factory;

	protected function setUp(): void {

		parent::setUp();

		$this->database = Mockery::mock( DatabasePort::class );
		$this->factory = new MigrationFactory( $this->database );
	}

	#[Test]
	public function it_creates_a_migration_instance(): void {

		$migration = $this->factory->create( NewMigration1::class );

		$this->assertInstanceOf( AbstractMigration::class, $migration );
		$this->assertInstanceOf( NewMigration1::class, $migration );
	}

	#[Test]
	public function it_throws_when_the_class_does_not_exist(): void {

		$class_name = 'Fundrik\\WordPress\\Tests\\Fixtures\\Migrations\\MissingMigration';

		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage( 'Cannot create the migration: the class must exist.' );
		$this->expectExceptionMessage( sprintf( 'Given: %s.', $class_name ) );

		$this->factory->create( $class_name );
	}

	#[Test]
	public function it_throws_when_the_class_does_not_extend_abstract_migration(): void {

		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage( 'Cannot create the migration: the class must extend' );
		$this->expectExceptionMessage( AbstractMigration::class );
		$this->expectExceptionMessage( sprintf( 'Given: %s.', NotAMigration::class ) );

		$this->factory->create( NotAMigration::class );
	}
}
