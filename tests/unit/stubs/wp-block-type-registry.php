<?php

declare(strict_types=1);

if ( ! class_exists( 'WP_Block_Type_Registry', false ) ) {

	// phpcs:ignore
	class WP_Block_Type_Registry {

		private static ?self $instance = null;

		public static function get_instance(): self {

			return self::$instance ??= new self();
		}

		public static function set_instance( self $instance ): void {

			self::$instance = $instance;
		}

		public function get_all_registered(): array {

			return [];
		}
	}
}
