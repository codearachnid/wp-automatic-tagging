<?php
/*
Plugin Name: Automatic Tagging
Description: An automatic tagging plugin with bolt on APIs for automatic querying tags from your content when they are created, updated or all posts that have no tags associated (supports custom post types). TagThe.Net API included
Version: 0.3
Author: Timothy Wood @codearachnid
Author URI: http://www.codearachnid.com
Text Domain: wp-auto-tagging
License: GPLv2 or later
*/

/*
Copyright 2010-2012 by Timothy Wood @codearachnid and the contributors

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

// Don't load directly
if ( !defined('ABSPATH') ) die('-1');

if( !class_exists('wp_auto_tagging') ) {
	class wp_auto_tagging {

		protected static $instance;

		private $api;
		private $settings;
		private $tag_api;

		const DOMAIN = 'wp-auto-tagging';
		const MIN_WP_VERSION = '3.3';

		private function __construct() {
			$this->settings_obj = new wp_auto_tagging_settings;
			$this->settings = $this->get_options();
			$this->api = $this->get_apis();
			$this->autoload_api();
			add_action( 'save_post', array( $this, 'on_save_auto_tag' ), 10, 2);
			add_filter( 'wp_auto_tagging_send_data', array( $this, 'trend_data'));
		}

		public function get_options(){
			$required = array('enable_tagging','use_api');
			$settings = (array) get_option(self::DOMAIN);
			foreach( $required as $option ){
				if( ! array_key_exists($option, $settings))
					$settings[$option] = null;
			}
			return $settings;
		}
		public function get_apis(){
			return apply_filters( 'wp_auto_tag_api', array(
				'tagthenet' => array(
					'init' => trailingslashit(dirname(__FILE__)) . 'api/tagthenet.php'
					)
				));
		}

		public function on_save_auto_tag( $post_id ){
			global $post;

			//verify post is not a revision, api is set & post type is configgured
			if ( !wp_is_post_revision( $post_id ) && $this->settings['use_api'] !== '' && in_array( $post->post_type, (array) $this->settings['enable_tagging'] ) ){

				// skip if already autotagged
				if( get_post_meta( $post_id, '_wp_auto_tagged', true ) != '' )
					return $post_id;

				$content = $this->clean_content( $post->post_content );
				$this->tag_api->request( $content );
				$terms = $this->tag_api->get_tags();
				$term_ids = array();
				foreach( $terms as $term ){
					$term_insert = wp_insert_term( $term, 'post_tag' );
					if( ! is_wp_error( $term_insert ) )
						$term_ids[] = $term_insert['term_id'];
				}
				$term_attach = wp_set_object_terms( $post_id, $term_ids, 'post_tag', true );
				clean_object_term_cache($post_id, 'post_tag');
				if( is_wp_error( $term_attach ) ) {
					echo 'Something went wrong!';
				} else {
					$debug = array(
						'use_api' => $this->settings['use_api'],
						'run_at' => date('Y-m-d H:i:s')
						);
					update_post_meta($post_id, '_wp_auto_tagged', json_encode($debug) );					
				}
			}
		}

		public function retrieve_content( $args ) {
			$defaults = array(
				'post_type'			=> 'post',
				'posts_per_page'	=> -1,
				'meta_query' 		=> array(
											array(
												'key' 		=> '_wp_auto_tagged',
												'value' 	=> '',
												'compare' 	=> 'NOT LIKE'
											)
										)
				);
			$args = wp_parse_args( $args, $defaults);
			$wp_query = new WP_Query( $args );

			foreach( $wp_query->posts as $post ) {
				$title = $this->clean_content( $post->post_title );
				$content = $this->clean_content( $post->post_content );
			}
		}
		private function clean_content( $content ){

			// remove shortcodes or execute (todo)
			$content = strip_shortcodes( $content );

			// clean html away from content
			$content = wp_filter_nohtml_kses( $content );

			// all clean and ready to shine
			return $content;
		}

		public function autoload_api() {
			require_once $this->api[$this->settings['use_api']]['init'];
			$class = 'wp_auto_tag_api_' . $this->settings['use_api'];
			$this->tag_api = new $class;
		}

		public function trend_data(){
			return true;
		}

		/**
		 * Check the minimum WP version and if TribeEvents exists
		 *
		 * @static
		 * @param string $wp_version
		 * @return bool Whether the test passed
		 */
		public static function prerequisites( $wp_version = null ) {;
			$pass = TRUE;
			$pass = $pass && class_exists('TribeEvents');
			$pass = $pass && version_compare( is_null($wp_version) ? get_bloginfo('version') : $wp_version, self::MIN_WP_VERSION, '>=');
			return $pass;
		}

		public function fail_notices() {
			printf( '<div class="error"><p>%s</p></div>', 
				sprintf( __( 'Automatic Tagging requires WordPress v%2$s or higher.', 'wp-auto-tagging' ), self::MIN_WP_VERSION ));
		}

		/* Static Singleton Factory Method */
	    public static function instance() {
	      if ( !isset( self::$instance ) ) {
	        $className = __CLASS__;
	        self::$instance = new $className;
	      }
	      return self::$instance;
	    }
	}
	add_action( 'init', 'load_wp_auto_tagging', 1 );
	function load_wp_auto_tagging(){
		// enable this plugin to run only in the admin
		if ( is_admin() ) {
			if ( apply_filters( 'wp_auto_tag_pre_check', class_exists( 'wp_auto_tagging' ) && wp_auto_tagging::prerequisites() ) ) {
				require_once 'settings.php';
				register_activation_hook( __FILE__, array('wp_auto_tagging_settings', 'register_defaults'));
				add_action('init', array('wp_auto_tagging', 'instance'), -100, 0);      
			} else {
				// let the user know prerequisites weren't met
				add_action('admin_head', array('wp_auto_tagging', 'fail_notices'), 0, 0);
			}
		}
	}
}

if( !class_exists('wp_auto_tagging_api') ) {
	class wp_auto_tagging_api {
		static protected $tags = array();
		static protected $content;
		public function __construct(){}
		static function get_tags(){
			return self::$tags;
		}
		static function request( $content ){
			_doing_it_wrong( __FUNCTION__, __( 'Calling wp_auto_tagging_api::request() directly will lead to inaccurate tagging.' ), '3.3' );
			self::$content;
		}
	}
}

require_once 'presstrends.php';