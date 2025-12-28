<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Migrations;

use Attribute;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationVersion;
use Fundrik\WordPress\Tests\Fixtures\Migrations\OldMigration;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;

#[CoversClass( MigrationVersion::class )]
final class MigrationVersionTest extends FundrikTestCase {

	#[Test]
	public function it_can_be_instantiated_with_version(): void {

		$attribute = new MigrationVersion( '2025_08_04_01' );

		$this->assertSame( '2025_08_04_01', $attribute->value );
	}

	#[Test]
	public function it_is_configured_as_class_only_attribute(): void {

		$reflection = new ReflectionClass( MigrationVersion::class );

		$attributes = $reflection->getAttributes( Attribute::class );

		$this->assertCount( 1, $attributes );

		$attr_instance = $attributes[0]->newInstance();

		$this->assertSame( Attribute::TARGET_CLASS, $attr_instance->flags );
	}

	#[Test]
	public function it_can_be_extracted_from_target_class(): void {

		$reflection = new ReflectionClass( OldMigration::class );

		$attributes = $reflection->getAttributes( MigrationVersion::class );

		$this->assertCount( 1, $attributes );

		$instance = $attributes[0]->newInstance();

		$this->assertSame( '2000_01_16_00', $instance->value );
	}
}
