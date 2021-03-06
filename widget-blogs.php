<?php
/*
Plugin Name: Blogs Widget
Plugin URI: http://premium.wpmudev.org/project/blogs-widget/
Description: Show recently updated blogs across your site, with avatars, through this handy widget
Author: WPMU DEV
Version: 1.0.9.4
Author URI: http://premium.wpmudev.org
WDP ID: 64
Network: true
Text Domain: widget_blogs
*/

/*
Copyright 2009-2014 Incsub (http://incsub.com)
Author - S H Mohanjith
Contributors - Andrew Billits

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

add_action('init', 'widget_blogs_init');

function widget_blogs_init() {
	if ( !is_multisite() )
		exit( 'The Widget Blogs plugin is only compatible with WordPress Multisite.' );
	load_plugin_textdomain('widget_blogs', false, dirname(plugin_basename(__FILE__)).'/languages');
}

//------------------------------------------------------------------------//
//---Config---------------------------------------------------------------//
//------------------------------------------------------------------------//
$blogs_widget_main_blog_only = 'no'; //Either 'yes' or 'no'
//------------------------------------------------------------------------//
//---Hook-----------------------------------------------------------------//
//------------------------------------------------------------------------//

//------------------------------------------------------------------------//
//---Functions------------------------------------------------------------//
//------------------------------------------------------------------------//
function widget_blogs_widget_init() {
	global $wpdb, $blogs_widget_main_blog_only;

	// Check for the required API functions
	if ( !function_exists('register_widget') )
		return;

	register_widget('BlogsWidget');
}

add_action('widgets_init', 'widget_blogs_widget_init');

class BlogsWidget extends WP_Widget {

    var $translation_domain = 'widget_blogs';

    public function __construct() {
	$widget_ops = array( 'description' => __('Display Blogs Pages', $this->translation_domain) );
        $control_ops = array(
		'title' => __('Blogs', $this->translation_domain),
		'display' => 'blog_name',
		'blog-name-characters' => 30,
		'public-only' => 'yes',
		'templates' => 'no',
		'order' => 'random',
		'number' => 10,
		'avatar-size' => 16
	);

        parent::__construct('blogs_widget', __('Blogs', $this->translation_domain), $widget_ops, $control_ops );
    }

    public function widget($args, $instance) {
	global $wpdb, $current_site, $post, $blogs_tree;

	extract($args);

	$defaults = array('count' => 10, 'blogname' => 'wordpress');
	$options = $instance;

	foreach ( $defaults as $key => $value )
		if ( !isset($options[$key]) )
			$options[$key] = $defaults[$key];

		$title = apply_filters('widget_title', $options['title']);
		?>
		<?php echo $before_widget; ?>
			<?php echo $before_title . __(esc_html($title), 'widget_blogs') . $after_title; ?>
            <br />
            <?php
				$public_where = "";
				if ($options['public-only'] == 'yes') {
					$public_where = "AND public = 1";
				}
				$template_where = "";
				if (class_exists( 'blog_templates' ) && $options['templates'] == 'no') {
					$template_blogs = $wpdb->get_col($wpdb->prepare("SELECT * FROM {$wpdb->base_prefix}nbt_templates WHERE network_id = %d", $wpdb->siteid));
					if ($template_blogs && is_array($template_blogs) && count($template_blogs) > 0) {
						$template_where .= " AND blog_id NOT IN ( " . join(',', $template_blogs). " ) ";
					}
				}
				//=================================================//
				if ( $options['order'] == 'most_recent' ) {
					$query = "SELECT blog_id FROM " . $wpdb->base_prefix . "blogs WHERE site_id = '" . $wpdb->siteid . "' AND spam != '1' AND archived != '1' AND deleted != '1' {$public_where} {$template_where} ORDER BY registered DESC LIMIT %d ";
				} else if ( $options['order'] == 'random' ) {
					$query = "SELECT blog_id FROM " . $wpdb->base_prefix . "blogs WHERE site_id = '" . $wpdb->siteid . "' AND spam != '1' AND archived != '1' AND deleted != '1' {$public_where} {$template_where} ORDER BY RAND() LIMIT %d ";
				}
				$blogs = $wpdb->get_results( $wpdb->prepare($query, $options['number']), ARRAY_A );
				if (count($blogs) > 0){
					if ( $options['display'] == 'blog_name' || $options['display'] == 'avatar_blog_name' ) {
						echo '<ul>';
					}
					foreach ($blogs as $blog){
						$blog_details = get_blog_details( $blog['blog_id'] );
						if ( $options['display'] == 'avatar_blog_name' && function_exists('get_blog_avatar')) {
							echo '<li>';
							echo '<a href="' . $blog_details->siteurl . '">' . get_blog_avatar( $blog['blog_id'], $options['avatar-size'], '' ) . '</a>';
							echo ' ';
							echo '<a href="' . $blog_details->siteurl . '">' . substr($blog_details->blogname, 0, $options['blog-name-characters']) . '</a>';
							echo '</li>';
						} else if ( $options['display'] == 'avatar' && function_exists('get_blog_avatar')) {
							echo '<a href="' . $blog_details->siteurl . '">' . get_blog_avatar( $blog['blog_id'], $options['avatar-size'], '' ) . '</a>';
						} else if ( $options['display'] == 'blog_name' || !function_exists('get_blog_avatar') ) {
							echo '<li>';
							echo '<a href="' . $blog_details->siteurl . '">' . substr($blog_details->blogname, 0, $options['blog-name-characters']) . '</a>';
							echo '</li>';
						}
					}
					if ( $options['display'] == 'blog_name' || $options['display'] == 'avatar_blog_name' ) {
						echo '</ul>';
					}
				}
				//=================================================//
			?>
		<?php echo $after_widget; ?>
	<?php
    }

    public function update($new_instance, $old_instance) {
	$instance = $old_instance;
        $new_instance = wp_parse_args( (array) $new_instance, array( 'title' => __('Blogs', $this->translation_domain),
		       'display' => 'blog_name', 'blog-name-characters' => 30,
		       'public-only' => 'yes', 'templates' => 'no', 'order' => 'random', 'number' => 10,
		       'avatar-size' => 16
		       ) );
	$instance['title'] = $new_instance['title'];
	$instance['display'] = $new_instance['display'];
	$instance['blog-name-characters'] = $new_instance['blog-name-characters'];
	$instance['public-only'] = $new_instance['public-only'];
	$instance['templates'] = $new_instance['templates'];
	$instance['order'] = $new_instance['order'];
	$instance['number'] = $new_instance['number'];
	$instance['avatar-size'] = $new_instance['avatar-size'];

        return $instance;
    }

    public function form($instance) {
	$instance = wp_parse_args( (array) $instance,
		array( 'title' => __('Blogs', $this->translation_domain),
		       'display' => 'blog_name', 'blog-name-characters' => 30,
		       'public-only' => 'yes', 'templates' => 'no', 'order' => 'random', 'number' => 10,
		       'avatar-size' => 16
		       ));
        $options = array('title' => strip_tags($instance['title']), 'display' => $instance['display'],
			 'blog-name-characters' => $instance['blog-name-characters'],
			 'public-only' => $instance['public-only'], 'templates' => $instance['templates'],
			 'order' => $instance['order'],
			 'number' => $instance['number'],
			 'avatar-size' => $instance['avatar-size']);

	?>
	<div style="text-align:left">
		<label for="<?php echo $this->get_field_id('title'); ?>" style="line-height:35px;display:block;"><?php _e('Title', 'widget_blogs'); ?>:<br />
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($options['title']); ?>" type="text" style="width:95%;" />
                </label>
		<?php if (function_exists('get_blog_avatar')) { ?>
			<label for="<?php echo $this->get_field_id('display'); ?>" style="line-height:35px;display:block;"><?php _e('Display', 'widget_blogs'); ?>:
			<select name="<?php echo $this->get_field_name('display'); ?>" id="<?php echo $this->get_field_id('display'); ?>" style="width:95%;">
				<option value="avatar_blog_name" <?php if ($options['display'] == 'avatar_blog_name'){ echo 'selected="selected"'; } ?> ><?php _e('Avatar + Blog Name', 'widget_blogs'); ?></option>
				<option value="avatar" <?php if ($options['display'] == 'avatar'){ echo 'selected="selected"'; } ?> ><?php _e('Avatar Only', 'widget_blogs'); ?></option>
				<option value="blog_name" <?php if ($options['display'] == 'blog_name'){ echo 'selected="selected"'; } ?> ><?php _e('Blog Name Only', 'widget_blogs'); ?></option>
			</select>
		<?php } else { ?>
			<input type="hidden" name="display" id="blogs-display" value="blog_name" />
		<?php } ?>
                </label>
		<label for="<?php echo $this->get_field_id('blog-name-characters'); ?>" style="line-height:35px;display:block;"><?php _e('Blog Name Characters', 'widget_blogs'); ?>:<br />
			<select name="<?php echo $this->get_field_name('blog-name-characters'); ?>" id="<?php echo $this->get_field_id('blog-name-characters'); ?>" style="width:95%;">
			<?php
			if ( empty($options['blog-name-characters']) ) {
				$options['blog-name-characters'] = 30;
			}
			$counter = 0;
			for ( $counter = 1; $counter <= 500; $counter += 1) {
			?>
				<option value="<?php echo $counter; ?>" <?php if ($options['blog-name-characters'] == $counter){ echo 'selected="selected"'; } ?> ><?php echo $counter; ?></option>
                        <?php
			}
			?>
			</select>
                </label>
		<label for="<?php echo $this->get_field_id('public-only'); ?>" style="line-height:35px;display:block;"><?php _e('Public Only', 'widget_blogs'); ?>:
			<select name="<?php echo $this->get_field_name('public-only'); ?>" id="<?php echo $this->get_field_id('public-only'); ?>" style="width:95%;">
				<option value="yes" <?php if ($options['public-only'] == 'yes'){ echo 'selected="selected"'; } ?> ><?php _e('Yes', 'widget_blogs'); ?></option>
				<option value="no" <?php if ($options['public-only'] == 'no'){ echo 'selected="selected"'; } ?> ><?php _e('No', 'widget_blogs'); ?></option>
			</select>
                </label>
		<?php if (class_exists( 'blog_templates' )) { ?>
		<label for="<?php echo $this->get_field_id('templates'); ?>" style="line-height:35px;display:block;"><?php _e('Include Template Blogs', 'widget_blogs'); ?>:
			<select name="<?php echo $this->get_field_name('templates'); ?>" id="<?php echo $this->get_field_id('templates'); ?>" style="width:95%;">
				<option value="yes" <?php if ($options['templates'] == 'yes'){ echo 'selected="selected"'; } ?> ><?php _e('Yes', 'widget_blogs'); ?></option>
				<option value="no" <?php if ($options['templates'] == 'no'){ echo 'selected="selected"'; } ?> ><?php _e('No', 'widget_blogs'); ?></option>
			</select>
                </label>
		<?php } ?>
		<label for="<?php echo $this->get_field_id('order'); ?>" style="line-height:35px;display:block;"><?php _e('Order', 'widget_blogs'); ?>:
			<select name="<?php echo $this->get_field_name('order'); ?>" id="<?php echo $this->get_field_id('order'); ?>" style="width:95%;">
				<option value="most_recent" <?php if ($options['order'] == 'most_recent'){ echo 'selected="selected"'; } ?> ><?php _e('Most Recent', 'widget_blogs'); ?></option>
				<option value="random" <?php if ($options['order'] == 'random'){ echo 'selected="selected"'; } ?> ><?php _e('Random', 'widget_blogs'); ?></option>
			</select>
                </label>
		<label for="<?php echo $this->get_field_id('number'); ?>" style="line-height:35px;display:block;"><?php _e('Number', 'widget_blogs'); ?>:<br />
			<select name="<?php echo $this->get_field_name('number'); ?>" id="<?php echo $this->get_field_id('number'); ?>" style="width:95%;">
			<?php
			if ( empty($options['number']) ) {
				$options['number'] = 10;
			}
			$counter = 0;
			for ( $counter = 1; $counter <= 25; $counter += 1) {
			?>
				<option value="<?php echo $counter; ?>" <?php if ($options['number'] == $counter){ echo 'selected="selected"'; } ?> ><?php echo $counter; ?></option>
			<?php
			}
			?>
			</select>
                </label>
		<?php if (function_exists('get_blog_avatar')) { ?>
		<label for="<?php echo $this->get_field_id('avatar-size'); ?>" style="line-height:35px;display:block;"><?php _e('Avatar Size', 'widget_blogs'); ?>:<br />
			<select name="<?php echo $this->get_field_name('avatar-size'); ?>" id="<?php echo $this->get_field_id('avatar-size'); ?>" style="width:95%;">
			<option value="16" <?php if ($options['avatar-size'] == '16'){ echo 'selected="selected"'; } ?> ><?php _e('16px', 'widget_blogs'); ?></option>
			<option value="32" <?php if ($options['avatar-size'] == '32'){ echo 'selected="selected"'; } ?> ><?php _e('32px', 'widget_blogs'); ?></option>
			<option value="48" <?php if ($options['avatar-size'] == '48'){ echo 'selected="selected"'; } ?> ><?php _e('48px', 'widget_blogs'); ?></option>
			<option value="96" <?php if ($options['avatar-size'] == '96'){ echo 'selected="selected"'; } ?> ><?php _e('96px', 'widget_blogs'); ?></option>
			<option value="128" <?php if ($options['avatar-size'] == '128'){ echo 'selected="selected"'; } ?> ><?php _e('128px', 'widget_blogs'); ?></option>
			</select>
		</label>
		<?php } else { ?>
			<input type="hidden" name="<?php echo $this->get_field_name('avatar-size'); ?>" id="<?php echo $this->get_field_id('avatar-size'); ?>" value="16" />
		<?php } ?>
		<input type="hidden" name="blogs-submit" id="blogs-submit" value="1" />
	</div>
	<?php
    }
}
