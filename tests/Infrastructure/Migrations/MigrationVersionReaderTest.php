<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Migrations;

use Fundrik\WordPress\Infrastructure\Migrations\MigrationException;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationVersion;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationVersionReader;
use Fundrik\WordPress\Tests\Fixtures\Migrations\EmptyVersionMigration;
use Fundrik\WordPress\Tests\Fixtures\Migrations\InvalidVersionPrefixMigration;
use Fundrik\WordPress\Tests\Fixtures\Migrations\InvalidVersionSuffixMigration;
use Fundrik\WordPress\Tests\Fixtures\Migrations\OldMigration;
use Fundrik\WordPress\Tests\Fixtures\Migrations\UnversionedMigration;
use Fundrik\WordPress\Tests\Fixtures\Migrations\WhitespacedVersionMigration;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( MigrationVersionReader::class )]
#[UsesClass( MigrationVersion::class )]
final class MigrationVersionReaderTest extends FundrikTestCase {

	private MigrationVersionReader $reader;

	protected function setUp(): void {

		parent::setUp();

		$this->reader = new MigrationVersionReader();
	}

	#[Test]
	public function it_reads_the_version_from_a_class_with_attribute(): void {

		$version = $this->reader->get_version( OldMigration::class );

		$this->assertSame( '2000_01_16_00', $version );
	}

	#[Test]
	public function it_throws_if_attribute_is_missing(): void {

		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage( 'must declare exactly one #[MigrationVersion]' );

		$this->reader->get_version( UnversionedMigration::class );
	}

	#[Test]
	public function it_throws_if_version_is_empty(): void {

		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage( 'must follow "YYYY_MM_DD_XX"' );
		$this->expectExceptionMessage( 'Given:' );

		$this->reader->get_version( EmptyVersionMigration::class );
	}

	#[Test]
	public function it_throws_if_version_is_only_whitespace(): void {

		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage( 'must follow "YYYY_MM_DD_XX"' );
		$this->expectExceptionMessage( 'Given:' );

		$this->reader->get_version( WhitespacedVersionMigration::class );
	}

	#[Test]
	public function it_throws_if_version_has_invalid_format_due_to_prefix(): void {

		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage( 'must follow "YYYY_MM_DD_XX"' );
		$this->expectExceptionMessage( 'Given:' );

		$this->reader->get_version( InvalidVersionPrefixMigration::class );
	}

	#[Test]
	public function it_throws_if_version_has_invalid_format_due_to_suffix(): void {

		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage( 'must follow "YYYY_MM_DD_XX"' );
		$this->expectExceptionMessage( 'Given:' );

		$this->reader->get_version( InvalidVersionSuffixMigration::class );
	}
}
