<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration;

use Fundrik\WordPress\Integration\Database\WpdbDatabase;
use Fundrik\WordPress\Integration\Database\WpdbDuplicateKeyException;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use wpdb;

#[CoversClass( WpdbDatabase::class )]
#[CoversClass( WpdbDuplicateKeyException::class )]
final class WpdbDatabaseDuplicateInsertTest extends MockeryTestCase {

	private wpdb&MockInterface $wpdb;
	private WpdbDatabase $db;

	protected function setUp(): void {

		parent::setUp();

		$this->wpdb = Mockery::mock( 'wpdb' );
		$this->wpdb->last_error = '';
		$this->wpdb->prefix = 'wp_';

		// phpcs:ignore SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable, WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['wpdb'] = $this->wpdb;

		$this->db = new WpdbDatabase();
	}

	#[Test]
	public function insert_throws_duplicate_key_exception_when_last_error_indicates_duplicate_key(): void {

		$table = 'wp_table';
		$data = [ 'title' => 'A' ];

		$this->wpdb
			->shouldReceive( 'insert' )
			->once()
			->with( $table, $data )
			->andReturn( false );

		$this->wpdb->last_error = 'Duplicate entry "A" for key "PRIMARY"';

		$this->expectException( WpdbDuplicateKeyException::class );
		$this->expectExceptionMessage( 'Cannot insert row into table "wp_table": duplicate key.' );

		$this->db->insert( $table, $data );
	}
}
