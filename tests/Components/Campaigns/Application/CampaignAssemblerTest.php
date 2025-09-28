<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Components\Campaigns\Application;

use Fundrik\Core\Components\Campaigns\Application\CampaignAssembler as CoreCampaignAssembler;
use Fundrik\Core\Components\Campaigns\Application\CampaignDtoFactory as CoreCampaignDtoFactory;
use Fundrik\Core\Components\Campaigns\Application\Exceptions\CampaignAssemblerException as CoreCampaignAssemblerException;
use Fundrik\Core\Components\Campaigns\Application\Exceptions\CampaignDtoFactoryException as CoreCampaignDtoFactoryException;
use Fundrik\WordPress\Components\Campaigns\Application\CampaignAssembler;
use Fundrik\WordPress\Components\Campaigns\Application\CampaignDto;
use Fundrik\WordPress\Components\Campaigns\Application\Exceptions\CampaignAssemblerException;
use Fundrik\WordPress\Components\Campaigns\Domain\Campaign;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignSlug;
use Fundrik\WordPress\Components\Campaigns\Domain\Exceptions\InvalidCampaignSlugException;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CampaignAssembler::class )]
#[UsesClass( CampaignDto::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignSlug::class )]
final class CampaignAssemblerTest extends FundrikTestCase {

	private CampaignAssembler $assembler;

	protected function setUp(): void {

		parent::setUp();

		$this->assembler = new CampaignAssembler(
			new CoreCampaignDtoFactory(),
			new CoreCampaignAssembler(),
		);
	}

	#[Test]
	public function it_creates_campaign_from_valid_dto(): void {

		$dto = $this->make_campaign_dto();
		$campaign = $this->assembler->from_dto( $dto );

		$this->assertInstanceOf( Campaign::class, $campaign );
		$this->assert_campaign_equals_dto( $campaign, $dto );
	}

	#[Test]
	public function it_throws_on_invalid_dto_data(): void {

		$this->expectException( CampaignAssemblerException::class );
		$this->expectExceptionMessageMatches( '/^Failed to assemble Campaign from DTO: /' );

		$this->assembler->from_dto(
			$this->make_campaign_dto(
				id: -1, // Negative ID is invalid — EntityId must be a positive integer.
			),
		);
	}

	#[Test]
	public function it_throws_on_invalid_core_assembler_data(): void {

		$this->expectException( CampaignAssemblerException::class );
		$this->expectExceptionMessageMatches( '/^Failed to assemble Campaign from DTO: /' );

		$this->assembler->from_dto(
			$this->make_campaign_dto( title: '  ' ),
		);
	}

	#[Test]
	public function it_throws_on_invalid_slug(): void {

		$this->expectException( CampaignAssemblerException::class );
		$this->expectExceptionMessageMatches( '/^Failed to assemble Campaign from DTO: /' );

		$this->assembler->from_dto(
			$this->make_campaign_dto( slug: '' ),
		);
	}

	#[Test]
	public function it_includes_original_exception_message(): void {

		try {
			$this->assembler->from_dto(
				$this->make_campaign_dto( id: -1 ),
			);

			$this->fail( 'Expected CampaignAssemblerException was not thrown.' );

		} catch ( CampaignAssemblerException $e ) {

			$this->assertStringStartsWith(
				'Failed to assemble Campaign from DTO:',
				$e->getMessage(),
				'Expected the message to start with the assembler-level prefix.',
			);

			$previous = $e->getPrevious();
			$this->assertNotNull( $previous, 'Expected wrapped (previous) exception to be present.' );

			$this->assertThat(
				$previous,
				$this->logicalOr(
					$this->isInstanceOf( CoreCampaignDtoFactoryException::class ),
					$this->isInstanceOf( CoreCampaignAssemblerException::class ),
					$this->isInstanceOf( InvalidCampaignSlugException::class ),
				),
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
