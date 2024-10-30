<?php

/*
	Plugin Name: CC-Sentry
	Plugin URI: https://wordpress.org/plugins/cc-sentry
	Description: This plugin integrates your WordPress site with Sentry error logging system.
	Version: 1.0.0
	Author: Clearcode
	Author URI: https://clearcode.cc
	Text Domain: cc-sentry
	Domain Path: /languages/
	License: GPLv3
	License URI: http://www.gnu.org/licenses/gpl-3.0.txt

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

use Clearcode\Sentry;
use Raven_Autoloader;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'get_plugin_data' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

require_once( plugin_dir_path( __FILE__ ) . 'vendor/raven/raven/lib/Raven/Autoloader.php' );
Raven_Autoloader::register();

foreach ( array( 'singleton', 'plugin', 'raven', 'sentry' ) as $class ) {
	require_once( plugin_dir_path( __FILE__ ) . sprintf( 'includes/class-%s.php', $class ) );
}

if ( ! has_action( Sentry::get( 'slug' ) ) ) {
	do_action( Sentry::get( 'slug' ), Sentry::instance() );
}

