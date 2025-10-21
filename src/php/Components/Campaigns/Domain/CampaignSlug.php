<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Campaigns\Domain;

use Fundrik\WordPress\Components\Campaigns\Domain\Exceptions\InvalidCampaignSlugException;

/**
 * Represents the slug of a fundraising campaign.
 *
 * Validates that the slug is non-empty and trimmed.
 *
 * @since 1.0.0
 */
final readonly class CampaignSlug {

	/**
	 * Private constructor, use factory method.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value The validated campaign slug.
	 */
	private function __construct(
		private string $value,
	) {}

	/**
	 * Creates a validated slug value object.
	 *
	 * Trims the input and throws if it is empty or only whitespace.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value The raw input slug.
	 *
	 * @return self The campaign slug value object.
	 *
	 * @throws InvalidCampaignSlugException When the slug is empty or whitespace.
	 */
	public static function create( string $value ): self {

		$value = trim( $value );

		if ( $value === '' ) {
			throw new InvalidCampaignSlugException( 'Campaign slug must not be empty or whitespace.' );
		}

		return new self( $value );
	}

	/**
	 * Returns the validated slug string.
	 *
	 * @since 1.0.0
	 *
	 * @return string The campaign slug.
	 */
	public function get_value(): string {

		return $this->value;
	}

	/**
	 * Checks whether this slug is equal to another.
	 *
	 * @since 1.0.0
	 *
	 * @param self $other The slug to compare with.
	 *
	 * @return bool True if the two slug objects are equal.
	 */
	public function equals( self $other ): bool {

		return $this->value === $other->value;
	}
}
