<?php

/*
	Copyright (C) 2017 by Clearcode <https://clearcode.cc>
	and associates (see AUTHORS.txt file).

	This file is part of CC-Sentry.

	CC-Sentry is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	CC-Sentry is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with CC-Sentry; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace Clearcode;

use Clearcode\Sentry\Raven;
use InvalidArgumentException;

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( __NAMESPACE__ . '\Sentry' ) ) {
	class Sentry extends Sentry\Plugin {
		protected $client = false;
		protected $option;

		public function __construct() {
			parent::__construct();
			$this->option = get_option( self::get( 'slug' ) );
			$this->client = $this->set_raven_client( $this->option );
		}

		public function activation() {
			if ( ! get_option( self::get( 'slug' ) ) ) {
				add_option( self::get( 'slug' ), array(
						'dsn'          => '',
						'error_levels' => array( 32767 )
					)
				);
			}
		}

		public function deactivation() {
			delete_option( self::get( 'slug' ) );
		}

		public function action_admin_menu() {
			add_options_page(
				self::__( 'Error Reporting Settings' ),
				self::__( 'Sentry' ),
				'manage_options',
				'cc-sentry',
				array( $this, 'settings' )
			);
		}

		public function settings() {
			echo self::get_template( 'settings-template.php' );
		}

		public function action_admin_enqueue_scripts() {
			wp_enqueue_style(
				'style',
				self::get( 'url' ) . '/css/style.css'
			);
		}

		public function action_admin_init() {
			add_settings_section(
				'section_sentry',
				'',
				array( $this, 'section_callback' ),
				'sentry'
			);

			add_settings_field(
				'field_sentry_dsn',
				self::__( 'Sentry DSN' ),
				array( $this, 'field_callback' ),
				'sentry',
				'section_sentry',
				array(
					'class'   => 'sentry_input',
					'type'    => 'text',
					'name'    => self::get( 'slug' ) . '[dsn]',
					'value'   => $this->option['dsn'],
					'checked' => false,
					'label'   => ''
				)
			);

			add_settings_field(
				'field_sentry_error_levels',
				self::__( 'Error Levels' ),
				array( $this, 'field_errors_callback' ),
				'sentry',
				'section_sentry',
				array(
					'class'   => 'sentry_error',
					'type'    => 'checkbox',
					'name'    => self::get( 'slug' ) . '[error_levels][]',
					'value'   => $this->option['error_levels'],
					'checked' => false,
					'label'   => ''
				)
			);

			add_settings_field(
				'field_sentry_test',
				self::__( 'Test' ),
				array( $this, 'field_callback' ),
				'sentry',
				'section_sentry',
				array(
					'class'   => 'sentry_test',
					'type'    => 'checkbox',
					'name'    => self::get( 'slug' ) . '[test]',
					'value'   => 'test',
					'checked' => false,
					'label'   => self::__( 'Sentry test connection' )
				)
			);

			register_setting( 'sentry', self::get( 'slug' ), array( $this, 'sanitize' ) );
		}

		public function section_callback() {
		}

		public function field_callback( $args ) {
			echo self::get_template( 'field-template.php', array(
				'class'   => $args['class'],
				'type'    => $args['type'],
				'name'    => $args['name'],
				'value'   => $args['value'],
				'checked' => $args['checked'],
				'label'   => $args['label']
			) );
		}

		public function field_errors_callback( $args ) {
			$error_levels = array(
				'E_ALL'               => 32767,
				'E_ERROR'             => 1,
				'E_WARNING'           => 2,
				'E_PARSE'             => 4,
				'E_NOTICE'            => 8,
				'E_CORE_ERROR'        => 16,
				'E_CORE_WARNING'      => 32,
				'E_COMPILE_ERROR'     => 64,
				'E_COMPILE_WARNING'   => 128,
				'E_USER_ERROR'        => 256,
				'E_USER_WARNING'      => 512,
				'E_USER_NOTICE'       => 1024,
				'E_STRICT'            => 2048,
				'E_RECOVERABLE_ERROR' => 4096,
				'E_DEPRECATED'        => 8192,
				'E_USER_DEPRECATED'   => 16384
			);

			foreach ( $error_levels as $key => $level ) {
				$checked = false;
				if ( ! empty( $args ) && $args['value'] !== array() && ! empty ( $args['value'] ) ) {
					$checked = checked( in_array( $level, $args['value'] ), true, false );
				} else if ( 'E_ALL' === $key && $args['value'] === array() ) {
					$checked = 'checked';
				}

				echo self::get_template( 'field-template.php', array(
					'class'   => $args['class'],
					'type'    => $args['type'],
					'name'    => $args['name'],
					'value'   => $level,
					'checked' => $checked,
					'label'   => $key
				) );
			}
		}

		public function sanitize( $input ) {
			if ( isset( $input['test'] ) && $this->client ) {
				$this->client->captureMessage( 'Sentry test connection' );
			}

			$output       = array( 'dsn' => '', 'error_levels' => array() );
			$input['dsn'] = filter_var( $input['dsn'], FILTER_SANITIZE_URL );
			if ( false !== filter_var( $input['dsn'], FILTER_VALIDATE_URL ) ) {
				$output['dsn'] = $input['dsn'];
			}

			if ( isset( $input['error_levels'] ) ) {
				foreach ( $input['error_levels'] as $i ) {
					$output['error_levels'][] = (int) filter_var( $i, FILTER_SANITIZE_NUMBER_INT );
				}
			}

			return $output;
		}

		private function set_raven_client( $option ) {
			if ( ! isset( $option ) || ! isset( $option['dsn'] ) || ! isset( $option['error_levels'] ) ) {
				return false;
			}
			try {
				return $client = new Raven( $option );
			} catch ( InvalidArgumentException $e ) {
				add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );

				return false;
			}
		}

		static public function admin_notices() {
			echo self::get_template( 'error-template.php', array(
					'message' => self::__( 'Invalid Sentry DSN!' )
				)
			);
		}
	}
}