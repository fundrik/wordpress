<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Gateways;

use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsReader;
use Fundrik\WordPress\Integration\AdminSettings\Groups\CheckoutSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Checkout\CheckoutCancelUrlSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Checkout\CheckoutSuccessUrlSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\General\SelectedGatewaySetting;
use Fundrik\WordPress\Integration\Gateways\GatewayInterface;
use Fundrik\WordPress\Integration\Gateways\GatewayResolutionException;
use Fundrik\WordPress\Integration\Gateways\GatewayResolver;
use Fundrik\WordPress\Integration\Helpers\OptionReader;
use Fundrik\WordPress\Integration\Helpers\SettingOptionName;
use Fundrik\WordPress\Tests\Fixtures\FakeStorage;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( GatewayResolutionException::class )]
#[CoversClass( GatewayResolver::class )]
#[UsesClass( AdminSettingsReader::class )]
#[UsesClass( CheckoutCancelUrlSetting::class )]
#[UsesClass( CheckoutSettingsGroup::class )]
#[UsesClass( CheckoutSuccessUrlSetting::class )]
#[UsesClass( OptionReader::class )]
#[UsesClass( SelectedGatewaySetting::class )]
#[UsesClass( SettingOptionName::class )]
final class GatewayResolverTest extends MockeryTestCase {

	#[Test]
	public function resolve_active_gateway_returns_selected_enabled_gateway(): void {

		$first_gateway = $this->make_gateway( 'first_gateway' );
		$selected_gateway = $this->make_gateway( 'selected_gateway' );
		$resolver = $this->make_resolver(
			new FakeStorage(
				[
					'fundrik_checkout_selected_gateway_setting' => 'selected_gateway',
					'fundrik_selected_gateway_enabled_setting' => true,
				],
			),
			$first_gateway,
			$selected_gateway,
		);

		self::assertSame( $selected_gateway, $resolver->resolve_active_gateway() );
	}

	#[Test]
	public function resolve_active_gateway_returns_first_enabled_gateway_when_selection_is_missing(): void {

		$first_gateway = $this->make_gateway( 'first_gateway' );
		$resolver = $this->make_resolver(
			new FakeStorage(
				[
					'fundrik_first_gateway_enabled_setting' => true,
				],
			),
			$first_gateway,
			$this->make_gateway( 'second_gateway' ),
		);

		self::assertSame( $first_gateway, $resolver->resolve_active_gateway() );
	}

	#[Test]
	#[DataProvider( 'unavailable_gateway_option_cases' )]
	public function resolve_active_gateway_throws_when_selected_gateway_is_not_enabled( array $stored_values ): void {

		$resolver = $this->make_resolver(
			new FakeStorage( $stored_values ),
			$this->make_gateway( 'selected_gateway' ),
		);

		$this->expectException( GatewayResolutionException::class );
		$this->expectExceptionMessage( 'Cannot resolve gateway "selected_gateway": gateway is disabled.' );

		$resolver->resolve_active_gateway();
	}

	#[Test]
	public function resolve_active_gateway_throws_when_selected_gateway_is_not_registered(): void {

		$resolver = $this->make_resolver(
			new FakeStorage(
				[
					'fundrik_checkout_selected_gateway_setting' => 'unknown_gateway',
				],
			),
			$this->make_gateway( 'registered_gateway' ),
		);

		$this->expectException( GatewayResolutionException::class );
		$this->expectExceptionMessage( 'Cannot resolve gateway "unknown_gateway": gateway is not registered.' );

		$resolver->resolve_active_gateway();
	}

	/**
	 * Returns stored option cases in which the selected gateway is unavailable.
	 *
	 * @return iterable<string, array{array<string, mixed>}> Stored option cases.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public static function unavailable_gateway_option_cases(): iterable {

		yield 'disabled' => [
			[
				'fundrik_checkout_selected_gateway_setting' => 'selected_gateway',
				'fundrik_selected_gateway_enabled_setting' => false,
			],
		];

		yield 'enabled option missing' => [
			[
				'fundrik_checkout_selected_gateway_setting' => 'selected_gateway',
			],
		];

		yield 'enabled option has invalid type' => [
			[
				'fundrik_checkout_selected_gateway_setting' => 'selected_gateway',
				'fundrik_selected_gateway_enabled_setting' => [],
			],
		];
	}

	private function make_gateway( string $id ): GatewayInterface&MockInterface {

		$gateway = Mockery::mock( GatewayInterface::class );
		$gateway->shouldReceive( 'get_id' )->andReturn( $id );

		return $gateway;
	}

	private function make_resolver( FakeStorage $storage, GatewayInterface ...$gateways ): GatewayResolver {

		$option_reader = new OptionReader( $storage );

		return new GatewayResolver(
			new AdminSettingsReader(
				$option_reader,
				new CheckoutSettingsGroup(
					new SelectedGatewaySetting( ...$gateways ),
					new CheckoutSuccessUrlSetting(),
					new CheckoutCancelUrlSetting(),
				),
			),
			$option_reader,
			...$gateways,
		);
	}
}
