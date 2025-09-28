<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Components\Campaigns\Application;

use Fundrik\Core\Support\Exceptions\ArrayExtractionException;
use Fundrik\WordPress\Components\Campaigns\Application\CampaignDto;
use Fundrik\WordPress\Components\Campaigns\Application\CampaignDtoFactory;
use Fundrik\WordPress\Components\Campaigns\Application\Exceptions\CampaignDtoFactoryException;
use Fundrik\WordPress\Components\Campaigns\Domain\Campaign;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignSlug;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CampaignDtoFactory::class )]
#[UsesClass( CampaignDto::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignSlug::class )]
final class CampaignDtoFactoryTest extends FundrikTestCase {

	private CampaignDtoFactory $dto_factory;

	protected function setUp(): void {

		$this->dto_factory = new CampaignDtoFactory();
	}

	#[Test]
	public function creates_dto_from_array(): void {

		$dto = $this->dto_factory->from_array( $this->make_data_array() );

		$this->assertInstanceOf( CampaignDto::class, $dto );
		$this->assertSame( 1, $dto->id );
		$this->assertSame( 'Test Campaign', $dto->title );
		$this->assertSame( 'test-campaign', $dto->slug );
		$this->assertTrue( $dto->is_active );
		$this->assertTrue( $dto->is_open );
		$this->assertTrue( $dto->has_target );
		$this->assertSame( 100, $dto->target_amount );
	}

	#[Test]
	public function throws_when_field_has_wrong_type(): void {

		$this->expectException( CampaignDtoFactoryException::class );
		$this->expectExceptionMessage( 'Failed to create CampaignDto from array:' );

		$this->dto_factory->from_array(
			$this->make_data_array(
				[
					'slug' => false, // Invalid type.
				],
			),
		);
	}

	#[Test]
	public function throws_when_required_field_is_missing(): void {

		$data = $this->make_data_array();
		unset( $data['target_amount'] ); // 'target_amount' is missing.

		$this->expectException( CampaignDtoFactoryException::class );
		$this->expectExceptionMessage( 'Failed to create CampaignDto from array:' );

		$this->dto_factory->from_array( $data );
	}

	#[Test]
	public function creates_dto_from_campaign(): void {

		$campaign = $this->make_campaign();
		$dto = $this->dto_factory->from_campaign( $campaign );

		$this->assertInstanceOf( CampaignDto::class, $dto );
		$this->assert_campaign_equals_dto( $campaign, $dto );
	}

	private function make_data_array( array $overrides = [] ): array {

		return array_merge(
			[
				'id' => 1,
				'title' => 'Test Campaign',
				'slug' => 'test-campaign',
				'is_active' => true,
				'is_open' => true,
				'has_target' => true,
				'target_amount' => 100,
			],
			$overrides,
		);
	}

	#[Test]
	public function it_includes_original_array_extraction_exception_message(): void {

		try {
			$this->dto_factory->from_array(
				$this->make_data_array( [ 'id' => 'not an int' ] ),
			);

			$this->fail( 'Expected CampaignDtoFactoryException was not thrown.' );

		} catch ( CampaignDtoFactoryException $e ) {

			$this->assertStringStartsWith(
				'Failed to create CampaignDto from array:',
				$e->getMessage(),
				'Expected message to start with the factory-level prefix.',
			);

			$previous = $e->getPrevious();
			$this->assertNotNull( $previous, 'Expected wrapped (previous) exception to be present.' );

			$this->assertInstanceOf(
				ArrayExtractionException::class,
				$previous,
				'Unexpected previous exception type.',
			);

			$this->assertNotSame(
				'',
				(string) $previous->getMessage(),
				'Expected previous exception to contain a message.',
			);
		}
	}
}
