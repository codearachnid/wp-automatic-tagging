<?php

/*

API Access to query tags from http://www.tagthe.net please use responsibly.

*/


// Don't load directly
if ( !defined('ABSPATH') ) { die('-1'); }

if( !class_exists('wp_auto_tagging_tagthenet') ) {
	class api_tagthenet extends wp_auto_tagging_api {
		const API_URL	= 'http://tagthe.net/api/';
		const METHOD 	= 'text';

		public function request(){
			return self::API_URL;
		}

	}
}