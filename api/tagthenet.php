<?php

// Don't load directly
if ( !defined('ABSPATH') ) { die('-1'); }

// API Access to query tags from http://www.tagthe.net please use responsibly
if( !class_exists('wp_auto_tagging_tagthenet') ) {
	class wp_auto_tag_api_tagthenet extends wp_auto_tagging_api {
		const API_URL	= 'http://tagthe.net/api/?view=json';
		const METHOD 	= 'text';
		function __construct(){
			parent::__construct();
		}
		private function update_tag( $tag ){
			self::$tags[] = $tag;
		}
		static function request( $content ){
			self::$content = $content;
			$response = wp_remote_get( self::API_URL . '&' . self::METHOD . '=' . urlencode(self::$content) );
			if( is_wp_error( $response ) ) {
				echo 'Something went wrong!';
			} else {
				$memes = json_decode( $response['body'] );
				$memes = $memes->memes;
				if( !empty($memes)) {
					foreach( $memes as $meme) {
						foreach( $meme->dimensions->topic as $tag ) {
							self::update_tag( $tag );
						}
					}
				}
			}
		}
	}
}