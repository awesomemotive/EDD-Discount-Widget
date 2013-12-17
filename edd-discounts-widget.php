<?php
/*
 * Plugin Name: Easy Digital Downloads - Discount Widget
 * Description: Allow third-party sites to display your current downloads through a simple widget!
 * Author: Daniel J Griffiths
 * Author URI: http://www.ghost1227.com
 * Version: 1.0.0
 */

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


/**
 * Discount Widget
 *
 * @access		public
 * @author		Daniel J Griffiths
 * @since		1.0.0
 */
class EDD_Discount_Widget extends WP_Widget {
	function edd_discount_widget() {
		$widget_ops = array( 'classname' => 'edd_discount_widget', 'description' => __( 'Display current discounts from any EDD powered site!', 'edd-discount-widget' ) );
		$control_ops = array( 'id_base' => 'edd_discount_widget' );
		$this->WP_Widget( 'edd_discount_widget', __( 'Discounts', 'edd-discount-widget' ), $widget_ops, $control_ops );
	}


	function widget( $args, $instance ) {
		extract( $args, EXTR_SKIP );
		$title			= apply_filters( 'widget_title', $instance['title'] );
		$site_url		= $instance['site_url'];
		$api_key		= $instance['api_key'];
		$api_token		= $instance['api_token'];
		$max_discounts	= ( is_numeric( $instance['max_discounts'] ) ? $instance['max_discounts'] : '5' );
		$hide_exp		= $instance['hide_exp'];
		$count = 0;

		echo $before_widget;

		if( $title ) echo $before_title . $title . $after_title;

		$discounts = $this->get_discounts( $site_url, $api_key, $api_token, $max_discounts );
		$discounts = json_decode( $discounts, true );
		$discounts = $discounts['discounts'];

		echo '<ul>';

		foreach( $discounts as $discount ) {
			if( $discount['status'] == 'active' && $count < $max_discounts ) {
				echo '<li class="edd-discount">';
				echo '<div class="edd-discount-title">' . $discount['name'] . '</div>';
				echo '<div class="edd-discount-code">' . __( 'Discount Code: ', 'edd-discount-widget' ) . $discount['code'] . '</div>';
				echo '<div class="edd-discount-value">' . __( 'Value: ', 'edd-discount-widget' ) . ( $discount['type'] == 'flat' ? '$' . $discount['amount'] . ' ' . __( 'off', 'edd-discount-widget' ) : $discount['amount'] . '% ' . __( 'off', 'edd_discount_widget' ) ) . '</div>';
				if( isset( $discount['exp_date'] ) && !empty( $discount['exp_date'] ) && !$hide_exp ) {
					echo '<div class="edd-discount-exp">' . __( 'Expires: ', 'edd-discount-widget' ) . date( get_option( 'date_format' ), strtotime( $discount['exp_date'] ) ) . '</div>';
				}
				echo '</li>';

				$count++;
			}
		}

		echo '</ul>';

		echo $after_widget;
	}


	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title']			= strip_tags( $new_instance['title'] );
		$instance['site_url']		= strip_tags( $new_instance['site_url'] );
		$instance['api_key']		= strip_tags( $new_instance['api_key'] );
		$instance['api_token']		= strip_tags( $new_instance['api_token'] );
		$instance['max_discounts']	= strip_tags( $new_instance['max_discounts'] );
		$instance['hide_exp']		= $new_instance['hide_exp'];

		return $instance;
	}


	function form( $instance ) {
		$defaults = array(
			'title'			=> __( 'Discounts', 'edd-discounts-widget' ),
			'max_discounts'	=> '5'
		);

		$instance = wp_parse_args( (array)$instance, $defaults );

		echo '<p><label for="' . $this->get_field_id( 'title' ) . '">' . __( 'Title', 'edd-discount-widget' ) . ':' .
			 '<input id="' . $this->get_field_id( 'title' ) . '" name="' . $this->get_field_name( 'title' ) . '" value="' . $instance['title'] . '" type="text" class="widefat" />' .
			 '</label></p>';

		echo '<p><label for="' . $this->get_field_id( 'site_url' ) . '">' . __( 'Site URL', 'edd-discount-widget' ) . ':' .
			 '<input id="' . $this->get_field_id( 'site_url' ) . '" name="' . $this->get_field_name( 'site_url' ) . '" value="' . $instance['site_url'] . '" type="text" class="widefat" />' .
			 '</label></p>';

		echo '<p><label for="' . $this->get_field_id( 'api_key' ) . '">' . __( 'API Key', 'edd-discount-widget' ) . ':' .
			 '<input id="' . $this->get_field_id( 'api_key' ) . '" name="' . $this->get_field_name( 'api_key' ) . '" value="' . $instance['api_key'] . '" type="text" class="widefat" />' .
			 '</label></p>';

		echo '<p><label for="' . $this->get_field_id( 'api_token' ) . '">' . __( 'API Token', 'edd-discount-widget' ) . ':' .
			 '<input id="' . $this->get_field_id( 'api_token' ) . '" name="' . $this->get_field_name( 'api_token' ) . '" value="' . $instance['api_token'] . '" type="text" class="widefat" />' .
			 '</label></p>';

		echo '<p><label for="' . $this->get_field_id( 'max_discounts' ) . '">' . __( 'Max Discounts', 'edd-discount-widget' ) . ':' .
			 '<input id="' . $this->get_field_id( 'max_discounts' ) . '" name="' . $this->get_field_name( 'max_discounts' ) . '" value="' . $instance['max_discounts'] . '" type="text" class="widefat" />' .
			 '</label></p>';
		echo '<p><label for="' . $this->get_field_id( 'hide_exp' ) . '">' . __( 'Hide Expiration', 'edd-discount-widget' ) . ': ' .
			 '<input id="' . $this->get_field_id( 'hide_exp' ) . '" name="' . $this->get_field_name( 'hide_exp' ) . '" type="checkbox" value="1" ' . checked( $instance['hide_exp'], 1, false ) . ' />' .
			 '</label></p>';

	}


	function get_discounts( $site_url, $api_key, $api_token, $max_discounts ) {
		$options = array(
			'timeout'	=> 5
		);

		$temp_url = parse_url( $site_url );
		if( !$temp_url['scheme'] == 'http' && !$temp_url['scheme'] == 'https' )
			$site_url = 'http://' . $site_url;

		$discounts = wp_remote_get( $site_url . '/edd-api/discounts?key=' . rawurlencode( $api_key ) . '&token=' . rawurlencode( $api_token ) . '&format=json', $options );
		$discounts = $discounts['body'];

		if( !$discounts || is_wp_error( $discounts ) ) $discounts = __( 'An unknown error occurred!', 'edd-discounts-widget' );

		return $discounts;
	}
}


/**
 * Register Discount Widget
 *
 * @access		public
 * @author		Daniel J Griffiths
 * @since		1.0.0
 * @return		void
 */
function edd_register_discount_widget() {
	register_widget( 'edd_discount_widget' );
}
add_action( 'widgets_init', 'edd_register_discount_widget' );
