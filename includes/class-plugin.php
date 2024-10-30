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

use ReflectionClass;
use ReflectionMethod;

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( __NAMESPACE__ . '\Plugin' ) ) {
	class Plugin extends Singleton {
		static protected $plugin = null;

		static public function get( $name = null ) {
			$path = WP_PLUGIN_DIR . '/cc-sentry/';
			$file = $path . 'plugin.php';
			$dir  = basename( $path );
			$url  = plugins_url( '', $file );

			if ( null === self::$plugin ) {
				self::$plugin = get_plugin_data( $file );
			}

			switch ( strtolower( $name ) ) {
				case 'file':
					return $file;
				case 'dir':
					return $dir;
				case 'path':
					return $path;
				case 'url':
					return $url;
				case 'slug':
					return __NAMESPACE__;
				case null:
					return self::$plugin;
				default:
					if ( ! empty( self::$plugin[ $name ] ) ) {
						return self::$plugin[ $name ];
					}

					return null;
			}
		}

		public function __construct() {
			register_activation_hook(   self::get( 'file' ), array( $this, 'activation'   ) );
			register_deactivation_hook( self::get( 'file' ), array( $this, 'deactivation' ) );

			add_action( 'activated_plugin',   array( $this, 'switch_plugin_hook' ), 10, 2 );
			add_action( 'deactivated_plugin', array( $this, 'switch_plugin_hook' ), 10, 2 );

			$class = new ReflectionClass( $this );
			foreach ( $class->getMethods( ReflectionMethod::IS_PUBLIC ) as $method ) {
				if ( $this->is_hook( $method->getName() ) ) {
					$hook     = self::apply_filters( 'hook', $this->get_hook( $method->getName() ), $class, $method );
					$priority = self::apply_filters( 'priority', $this->get_priority( $method->getName() ), $class, $method );
					$args     = self::apply_filters( 'args', $method->getNumberOfParameters(), $class, $method );

					add_filter( $hook, array( $this, $method->getName() ), $priority, $args );
				}
			}
		}

		public function activation() {}

		public function deactivation() {}

		protected function get_priority( $method ) {
			$priority = substr( strrchr( $method, '_' ), 1 );

			return is_numeric( $priority ) ? (int) $priority : 10;
		}

		protected function has_priority( $method ) {
			$priority = substr( strrchr( $method, '_' ), 1 );

			return is_numeric( $priority ) ? true : false;
		}

		protected function get_hook( $method ) {
			if ( $this->has_priority( $method ) ) {
				$method = substr( $method, 0, strlen( $method ) - strlen( $this->get_priority( $method ) ) - 1 );
			}
			if ( $this->is_hook( $method ) ) {
				$method = substr( $method, 7 );
			}

			return $method;
		}

		protected function is_hook( $method ) {
			foreach ( array( 'filter_', 'action_' ) as $hook ) {
				if ( 0 === strpos( $method, $hook ) ) {
					return true;
				}
			}

			return false;
		}

		static public function __( $text ) {
			return __( $text, self::get( 'textdomain' ) );
		}

		static public function apply_filters( $tag, $value ) {
			$args    = func_get_args();
			$args[0] = self::get( 'slug' ) . '\\' . $args[0];

			return call_user_func_array( 'apply_filters', $args );
		}

		static public function get_template( $template, $vars = array() ) {
			$template = self::apply_filters( 'template', self::get( 'path' ) . '/templates/' . $template, $vars );
			if ( ! is_file( $template ) ) {
				return false;
			}

			$vars = self::apply_filters( 'vars', $vars, $template );
			if ( is_array( $vars ) ) {
				extract( $vars, EXTR_SKIP );
			}

			ob_start();
			include $template;

			return ob_get_clean();
		}

		function switch_plugin_hook( $plugin, $network_wide = null ) {
			if ( ! $network_wide ) {
				return;
			}

			list( $hook ) = explode( '_', current_filter(), 2 );
			$hook = str_replace( 'activated', 'activate_', $hook );
			$hook .= plugin_basename( self::get( 'file' ) );

			$this->call_user_func_array( 'do_action', array( $hook, false ) );
		}

		protected function call_user_func_array( $function, $args = array() ) {
			if ( is_multisite() ) {
				foreach ( get_sites( array( 'public' => 1 ) ) as $blog ) {
					switch_to_blog( $blog->blog_id );
					call_user_func_array( $function, $args );
				}
				restore_current_blog();
			} else {
				$function( $args );
			}
		}
	}
}
