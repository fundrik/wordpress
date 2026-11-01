<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests;

use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationStatus;
use Fundrik\Core\Components\Donations\Domain\PaymentId;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionProperty;

abstract class FundrikTestCase extends PHPUnitTestCase {

	/**
	 * Returns a valid campaign for tests with optional field overrides.
	 */
	protected function make_campaign(
		int|string $id = 7,
		int $version = 1,
		string $title = 'Campaign title',
		bool $accepts_donations = false,
		string $currency_code = 'RUB',
		?int $target_amount = 123,
	): Campaign {

		return new Campaign(
			id: EntityId::create( $id ),
			version: EntityVersion::create( $version ),
			title: CampaignTitle::create( $title ),
			accepts_donations: $accepts_donations,
			target: CampaignTarget::create( $currency_code, $target_amount ),
		);
	}

	/**
	 * Returns a valid campaign persistence row for tests with optional field overrides.
	 *
	 * @return array<string,string|null> Campaign persistence row.
	 */
	protected function make_campaign_row(
		int $id = 7,
		?int $version = 1,
		string $title = 'Campaign title',
		bool $accepts_donations = false,
		string $currency_code = 'RUB',
		?int $target_amount = 123,
		int $collected_amount = 0,
		int $donations_count = 0,
		string $created_at = '2026-03-21 10:00:00',
		?string $updated_at = null,
	): array {

		return [
			'id' => (string) $id,
			'version' => $version === null ? null : (string) $version,
			'title' => $title,
			'accepts_donations' => $accepts_donations ? '1' : '0',
			'currency_code' => $currency_code,
			'target_amount' => $target_amount === null ? null : (string) $target_amount,
			'collected_amount' => (string) $collected_amount,
			'donations_count' => (string) $donations_count,
			'created_at' => $created_at,
			'updated_at' => $updated_at,
		];
	}

	/**
	 * Returns a valid created donation for tests with optional field overrides.
	 */
	protected function make_created_donation(
		int|string $id = 5_001,
		int|string|EntityId $campaign_id = 901,
		int $amount = 1_000,
		string $currency = 'RUB',
	): Donation {

		return new Donation(
			id: EntityId::create( $id ),
			version: EntityVersion::initial(),
			campaign_id: $campaign_id instanceof EntityId ? $campaign_id : EntityId::create( $campaign_id ),
			money: Money::create( $amount, $currency ),
			status: DonationStatus::Created,
			payment_id: null,
		);
	}

	/**
	 * Returns a valid pending donation for tests with optional field overrides.
	 */
	protected function make_pending_donation(
		int|string $id = 5_001,
		int|string|EntityId $campaign_id = 901,
		int $amount = 1_000,
		string $currency = 'RUB',
		string $payment_id = 'pay_5001',
		int $version = 1,
	): Donation {

		return new Donation(
			id: EntityId::create( $id ),
			version: EntityVersion::create( $version ),
			campaign_id: $campaign_id instanceof EntityId ? $campaign_id : EntityId::create( $campaign_id ),
			money: Money::create( $amount, $currency ),
			status: DonationStatus::Pending,
			payment_id: PaymentId::create( $payment_id ),
		);
	}

	/**
	 * Returns a valid succeeded donation for tests with optional field overrides.
	 */
	protected function make_succeeded_donation(
		int|string $id = 5_001,
		int|string|EntityId $campaign_id = 901,
		int $amount = 1_000,
		string $currency = 'RUB',
		string $payment_id = 'pay_5001',
		int $version = 1,
	): Donation {

		return new Donation(
			id: EntityId::create( $id ),
			version: EntityVersion::create( $version ),
			campaign_id: $campaign_id instanceof EntityId ? $campaign_id : EntityId::create( $campaign_id ),
			money: Money::create( $amount, $currency ),
			status: DonationStatus::Succeeded,
			payment_id: PaymentId::create( $payment_id ),
		);
	}

	/**
	 * Returns a valid donation persistence row for tests with optional field overrides.
	 *
	 * @return array<string,string|null> Donation persistence row.
	 */
	protected function make_donation_row(
		string $id = '123e4567-e89b-42d3-a456-426614174000',
		?int $version = 1,
		int $campaign_id = 77,
		int $amount = 1_000,
		string $currency_code = 'RUB',
		string $status = 'succeeded',
		?string $payment_id = 'payment-123',
		string $created_at = '2026-01-01 10:00:00.123456',
		?string $updated_at = '2026-01-01 11:00:00.654321',
	): array {

		return [
			'id' => $id,
			'version' => $version === null ? null : (string) $version,
			'campaign_id' => (string) $campaign_id,
			'amount' => (string) $amount,
			'currency_code' => $currency_code,
			'status' => $status,
			'payment_id' => $payment_id,
			'created_at' => $created_at,
			'updated_at' => $updated_at,
		];
	}

	protected function assert_has_attribute_instance_of(
		string $class_name,
		string $target_name,
		string $attribute_class,
		?array $expected_values = null,
		string $target_type = 'property',
	): void {

		if ( $target_type === 'property' ) {
			$reflection = new ReflectionProperty( $class_name, $target_name );
		} elseif ( $target_type === 'class' ) {
			$reflection = new ReflectionClass( $class_name );
		} elseif ( $target_type === 'class_constant' ) {
			$reflection = new ReflectionClassConstant( $class_name, $target_name );
		} else {
			throw new InvalidArgumentException( 'Invalid target type. Use "property", "class", or "class_constant".' );
		}

		$attributes = $reflection->getAttributes( $attribute_class );

		Assert::assertCount(
			1,
			$attributes,
			sprintf(
				'%s "%s" of class "%s" must have the "%s" attribute.',
				ucfirst( $target_type ),
				$target_name,
				$class_name,
				$attribute_class,
			),
		);

		$instance = $attributes[0]->newInstance();

		Assert::assertInstanceOf(
			$attribute_class,
			$instance,
			sprintf(
				'Expected instance of "%s" for %s "%s", got "%s"',
				$attribute_class,
				$target_type,
				$target_name,
				$instance::class,
			),
		);

		if ( ! $expected_values ) {
			return;
		}

		foreach ( $expected_values as $property => $expected ) {
			$actual = $instance->$property ?? null;

			Assert::assertSame(
				$expected,
				$actual,
				sprintf(
					'Expected value "%s" for property "%s" on attribute "%s", got "%s".',
					$this->debug_value( $expected ),
					$property,
					$attribute_class,
					$this->debug_value( $actual ),
				),
			);
		}
	}

	protected function assert_class_has_attribute(
		string $class_name,
		string $attribute_class,
		?array $expected_values = null,
	): void {

		$this->assert_has_attribute_instance_of(
			class_name: $class_name,
			target_name: $class_name,
			attribute_class: $attribute_class,
			expected_values: $expected_values,
			target_type: 'class',
		);
	}

	protected function assert_property_has_attribute(
		string $class_name,
		string $property_name,
		string $attribute_class,
		?array $expected_values = null,
	): void {

		$this->assert_has_attribute_instance_of(
			class_name: $class_name,
			target_name: $property_name,
			attribute_class: $attribute_class,
			expected_values: $expected_values,
			target_type: 'property',
		);
	}

	protected function assert_class_constant_has_attribute(
		string $class_name,
		string $constant_name,
		string $attribute_class,
		?array $expected_values = null,
	): void {

		$this->assert_has_attribute_instance_of(
			class_name: $class_name,
			target_name: $constant_name,
			attribute_class: $attribute_class,
			expected_values: $expected_values,
			target_type: 'class_constant',
		);
	}

	/**
	 * Converts the value into a readable string for assertion messages.
	 *
	 * @param mixed $value The value to format.
	 *
	 * @return string The formatted value.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function debug_value( mixed $value ): string {

		if ( $value === null ) {
			return 'null';
		}

		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}

		if ( is_scalar( $value ) ) {
			return (string) $value;
		}

		if ( $value instanceof \BackedEnum ) {
			return sprintf( '%s::%s(%s)', $value::class, $value->name, (string) $value->value );
		}

		if ( $value instanceof \UnitEnum ) {
			return sprintf( '%s::%s', $value::class, $value->name );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
		return sprintf( '%s(%s)', get_debug_type( $value ), json_encode( $value ) ?: 'unserializable' );
	}
}
