<?php
/*
Plugin Name: Automatic Tagging
Description: An automatic tagging plugin with bolt on APIs for automatic querying tags from your content when they are created, updated or all posts that have no tags associated (supports custom post types). TagThe.Net API included
Version: 0.1
Author: Timothy Wood @codearachnid
Author URI: http://www.codearachnid.com
Text Domain: wp-tagthenet
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
if ( !defined('ABSPATH') ) { die('-1'); }

if( !class_exists('wp_auto_tagging') ) {
	class wp_auto_tagging {
		private function __construct() {
			if ( is_admin() ) {

				// enable this class to run only in the admin


			}
		}
		public function retrieve_content( $args ) {
			$defaults = array(
				'post_type'			=> 'post',
				'posts_per_page'	=> -1
				// 'meta_query' 		=> array(
				// 							array(
				// 								'key' 		=> '_wp_auto_tagged',
				// 								'value' 	=> '',
				// 								'compare' 	=> 'NOT LIKE'
				// 							)
				// 						)
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
		public function load_api() {
			
		}
	}
	$wp_auto_tagging = new wp_auto_tagging;
}

if( !class_exists('wp_auto_tagging_api') ) {
	class wp_auto_tagging_api {
		
	}
}