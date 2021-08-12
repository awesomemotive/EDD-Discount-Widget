<?php
/**
 * Plugin Name:     Easy Digital Downloads - Discount Widget
 * Description:     Allow third-party sites to display your current downloads through a simple widget!
 * Version:         1.0.2
 * Author:          Sandhills Development, LLC
 * Author URI:      https://sandhillsdev.com
 * Text Domain:     edd-discounts-widget
 *
 * @package         EDD\Widgets\Discounts
 * @author          Sandhills Development, LLC
 * @copyright       Copyright © 2021 Sandhills Development, LLC
 */

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


if( !class_exists( 'EDD_Discounts_Widget' ) )  {

    /**
     * Main EDD_Discounts_Widget class
     *
     * @since       1.0.3
     */
    class EDD_Discounts_Widget extends WP_Widget {
        public function __construct() {
            parent::__construct(
                'edd_discounts_widget',
                __( 'Easy Digital Downloads - Discounts', 'edd-discounts-widget' ),
                array(
                    'classname'   => 'edd_discounts_widget',
                    'description' => __( 'Display current discounts from any EDD powered site!', 'edd-discounts-widget' ),
                ),
                array(
                    'id_base' => 'edd_discounts_widget',
                )
            );
        }


        function widget( $args, $instance ) {
            extract( $args, EXTR_SKIP );
    
            $title          = apply_filters( 'widget_title', $instance['title'] );
            $site_url       = isset( $instance['site_url'] ) ? $instance['site_url'] : '';
            $api_key        = isset( $instance['api_key'] ) ? $instance['api_key'] : '';
            $api_token      = isset( $instance['api_token'] ) ? $instance['api_token'] : '';
            $max_discounts  = isset( $instance['max_discounts'] ) && is_numeric( $instance['max_discounts'] ) ? $instance['max_discounts'] : 5;
            $hide_exp       = isset( $instance['hide_exp'] ) ? $instance['hide_exp'] : 0;
            $count          = 0;

            echo $before_widget;

            if( $title ) echo $before_title . $title . $after_title;

            $discounts = $this->get_discounts( $site_url, $api_key, $api_token, $max_discounts );

            // A string is only returned in the event of an error.
            if (is_string($discounts)) {
                $response = __( 'Error:', 'edd-discounts-widget' ) . ' ' . $discounts;
                echo '<p>' . esc_html( $response ) . '</p>';
                echo $after_widget;
                return;
            }

            echo '<ul>';

            foreach( $discounts as $discount ) {
                if( $discount['status'] == 'active' && $count < $max_discounts ) {
                    echo '<li class="edd-discount">';
                    echo '<div class="edd-discount-title">' . $discount['name'] . '</div>';
                    echo '<div class="edd-discount-code">' . __( 'Discount Code: ', 'edd-discounts-widget' ) . $discount['code'] . '</div>';
                    echo '<div class="edd-discount-value">' . __( 'Value: ', 'edd-discounts-widget' ) . ( $discount['type'] == 'flat' ? '$' . $discount['amount'] . ' ' . __( 'off', 'edd-discounts-widget' ) : $discount['amount'] . '% ' . __( 'off', 'edd_discounts_widget' ) ) . '</div>';
                    if( isset( $discount['exp_date'] ) && !empty( $discount['exp_date'] ) && !$hide_exp ) {
                        echo '<div class="edd-discounts-exp">' . __( 'Expires: ', 'edd-discounts-widget' ) . date( get_option( 'date_format' ), strtotime( $discount['exp_date'] ) ) . '</div>';
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

            $instance['title']          = strip_tags( $new_instance['title'] );
            $instance['site_url']       = strip_tags( $new_instance['site_url'] );
            $instance['api_key']        = strip_tags( $new_instance['api_key'] );
            $instance['api_token']      = strip_tags( $new_instance['api_token'] );
            $instance['max_discounts']  = strip_tags( $new_instance['max_discounts'] );
            $instance['hide_exp']       = $new_instance['hide_exp'];

            return $instance;
        }


        function form( $instance ) {
            $defaults = array(
                'title'         => __( 'Discounts', 'edd-discounts-widget' ),
                'site_url'      => '',
                'api_key'       => '',
                'api_token'     => '',
                'max_discounts' => '5',
                'hide_exp'      => 0
            );

            $instance = wp_parse_args( (array)$instance, $defaults );

            echo '<p><label for="' . $this->get_field_id( 'title' ) . '">' . __( 'Title', 'edd-discounts-widget' ) . ':' .
                 '<input id="' . $this->get_field_id( 'title' ) . '" name="' . $this->get_field_name( 'title' ) . '" value="' . $instance['title'] . '" type="text" class="widefat" />' .
                 '</label></p>';

            echo '<p><label for="' . $this->get_field_id( 'site_url' ) . '">' . __( 'Site URL', 'edd-discounts-widget' ) . ':' .
                 '<input id="' . $this->get_field_id( 'site_url' ) . '" name="' . $this->get_field_name( 'site_url' ) . '" value="' . $instance['site_url'] . '" type="text" class="widefat" />' .
                 '</label></p>';

            echo '<p><label for="' . $this->get_field_id( 'api_key' ) . '">' . __( 'API Key', 'edd-discounts-widget' ) . ':' .
                 '<input id="' . $this->get_field_id( 'api_key' ) . '" name="' . $this->get_field_name( 'api_key' ) . '" value="' . $instance['api_key'] . '" type="text" class="widefat" />' .
                 '</label></p>';

            echo '<p><label for="' . $this->get_field_id( 'api_token' ) . '">' . __( 'API Token', 'edd-discounts-widget' ) . ':' .
                 '<input id="' . $this->get_field_id( 'api_token' ) . '" name="' . $this->get_field_name( 'api_token' ) . '" value="' . $instance['api_token'] . '" type="text" class="widefat" />' .
                 '</label></p>';

            echo '<p><label for="' . $this->get_field_id( 'max_discounts' ) . '">' . __( 'Max Discounts', 'edd-discounts-widget' ) . ':' .
                 '<input id="' . $this->get_field_id( 'max_discounts' ) . '" name="' . $this->get_field_name( 'max_discounts' ) . '" value="' . $instance['max_discounts'] . '" type="text" class="widefat" />' .
                 '</label></p>';

            echo '<p><label for="' . $this->get_field_id( 'hide_exp' ) . '">' . __( 'Hide Expiration', 'edd-discounts-widget' ) . ': ' .
                 '<input id="' . $this->get_field_id( 'hide_exp' ) . '" name="' . $this->get_field_name( 'hide_exp' ) . '" type="checkbox" value="1" ' . checked( $instance['hide_exp'], 1, false ) . ' />' .
                 '</label></p>';

        }


        function get_discounts( $site_url, $api_key, $api_token, $max_discounts ) {

            $site_url      = sanitize_text_field( $site_url );
            $api_key       = sanitize_text_field( $api_key );
            $api_token     = sanitize_text_field( $api_token );
            $max_discounts = absint( $max_discounts );

            if ( empty( $site_url ) || empty( $api_key ) || empty( $api_token ) ) {
                return __( 'Widget settings are incomplete.', 'edd-discounts-widget' );
            }

            $options = array(
                'timeout'   => 5
            );

            $temp_url = parse_url( $site_url );
            if ( isset( $temp_url['scheme'] ) ) {
                if( !$temp_url['scheme'] == 'http' && !$temp_url['scheme'] == 'https' ) {
                    $site_url = 'http://' . $site_url;
                }
            }

            $discounts = wp_remote_get( $site_url . '/edd-api/discounts?key=' . rawurlencode( $api_key ) . '&token=' . rawurlencode( $api_token ) . '&format=json', $options );

            if ( is_wp_error( $discounts ) ) {
                return esc_html( $discounts->get_error_message() );
            }

            $response_body = json_decode( wp_remote_retrieve_body( $discounts ), true );

            if ( is_wp_error( $response_body ) ) {
                return esc_html( $response_body->get_error_message() );
            }

            if ( isset( $response_body['error'] ) ) {
                return esc_html( $response_body['error'] );
            }

            if ( isset( $response_body['discounts'] ) ) {
                return (array) $response_body['discounts'];
            }

            // Fallback. Can happen if the target URL does not have EDD installed.
            return __( 'An unknown error occurred! Please check your widget settings are valid.', 'edd-discounts-widget' );
        }
    }
}


/**
 * Register Discount Widget
 *
 * @access      public
 * @since       1.0.0
 * @author      Sandhills Development, LLC
 * @return      void
 */
function edd_register_discounts_widget() {
    register_widget( 'edd_discounts_widget' );
}
add_action( 'widgets_init', 'edd_register_discounts_widget' );
