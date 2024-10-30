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

namespace Clearcode\Sentry;

use Raven_ErrorHandler;

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( __NAMESPACE__ . '\Raven' ) ) {
	class Raven extends \Raven_Client {
		protected $dsn;
		protected $error_levels = array( 32767 );

		public function __construct( $option ) {
			$this->dsn = $option['dsn'];
			if ( $this->dsn == '' ) {
				return;
			}
			parent::__construct( $this->dsn, array(
				'extra' => array(
					'WordPress version' => get_bloginfo( 'version' )
				)
			) );

			if ( ! empty( $option['error_levels'] ) ) {
				$this->error_levels = $option['error_levels'];
			}

			$this->set_handlers();
		}

		private function set_handlers() {
			$error_handler = new Raven_ErrorHandler( $this );

			set_error_handler( array(
				$error_handler,
				'handleError'
			), error_reporting( array_reduce( $this->error_levels, function ( $a, $b ) {
				return $a | $b;
			} ) ) );
			set_exception_handler( array( $error_handler, 'handleException' ) );
		}
	}
}