<?php

// Don't load directly
if ( !defined('ABSPATH') ) die('-1');

if( !class_exists('wp_auto_tagging_settings')) {
	class wp_auto_tagging_settings {

		private $domain;
		private $options;

		public function __construct(){
			$this->domain = wp_auto_tagging::DOMAIN;
			$this->options = wp_auto_tagging::get_options();
			add_action('admin_menu', array( $this, 'admin_menu'));
			add_action('admin_init', array( $this, 'admin_init'));
		}
		function admin_menu(){
			// create settings page
			add_options_page( __('Automatic Tagging Settings', 'wp-auto-tagging'), __('Automatic Tagging', 'wp-auto-tagging'), 'manage_options', $this->domain, array( $this, 'options_page'));
		}
		function admin_init(){
			register_setting( $this->domain, $this->domain, array( $this, 'settings_validate') );
			add_settings_section( 'main_section', __('Main Settings', 'wp-auto-tagging'), array( $this, 'main_section'), $this->domain);
			add_settings_field( 'enable_automatic_tagging', __('Enable Automatic Tagging:', 'wp-auto-tagging'), array( $this, 'setting_enable_tagging'), $this->domain, 'main_section');
			add_settings_field( 'use_api', __('Use Tagging API:', 'wp-auto-tagging'), array( $this, 'setting_use_api'), $this->domain, 'main_section');
		}
		function main_section(){
		}
		function setting_use_api(){
			$field_name = 'use_api';
			foreach( wp_auto_tagging::get_apis() as $api => $param ) {
				$this->field_checkbox( 
					$this->domain . '[' . $field_name . ']', 
					$api,
					checked( $api, $this->options[$field_name], false ),
					isset($param['name']) ? $param['name'] : $api );
			}
		}
		function setting_enable_tagging(){
			$field_name = 'enable_tagging';
			$ptype_args = array( 'public' => true );
			// list post types to enable the plugin
			foreach( get_post_types( $ptype_args ) as $ptype ) {
				$is_tag_type = false;
				$ptype_tax_obj = get_object_taxonomies( $ptype );

				// move onto the next post type if no taxonomies are attached
				if( empty($ptype_tax_obj) )
					continue;

				// check taxonomies for hierarchical attribute
				foreach( $ptype_tax_obj as $tax_obj ) {
					if( ! is_taxonomy_hierarchical( $tax_obj ) )
						$is_tag_type = true;
				}

				// list post type if taxonomy is non-hierarchical (tag type)
				if($is_tag_type)
					$this->field_checkbox( 
						$this->domain . '[' . $field_name . '][]', 
						$ptype,  
						in_array( $ptype, (array) $this->options[$field_name] ) ? 'checked="checked"' : '',
						ucfirst(str_replace('_', ' ', str_replace('-', ' ', $ptype))));
			}
		}
		function field_checkbox( $name, $value, $label, $checked){
			printf('<p><label><input type="checkbox" name="%s" value="%s" %s /> %s</label></p>',
				$name,
				$value,
				$label,
				$checked
				);
		}
		function settings_validate( $input ){
			return $input; // return validated input
		}

		function options_page(){
?>
	<div class="wrap"><div class="icon32" id="icon-options-general"><br></div>
		<h2><?php _e('Automatic Tagging Settings', 'wp-auto-tagging'); ?></h2>
		<form action="options.php" method="post">
			<?php settings_fields( $this->domain ); ?>
			<?php do_settings_sections($this->domain); ?>
			<p class="submit"><input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Settings', 'wp-auto-tagging'); ?>" /></p>
		</form>
	</div>
<?php
		}

	}
}