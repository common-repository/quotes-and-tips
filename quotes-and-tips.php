<?php
/**
Plugin Name: Quotes and Tips by BestWebSoft
Plugin URI: https://bestwebsoft.com/products/wordpress/plugins/quotes-and-tips/
Description: Add customizable quotes and tips blocks to WordPress posts, pages and widgets.
Author: BestWebSoft
Text Domain: quotes-and-tips
Domain Path: /languages
Version: 1.45
Author URI: https://bestwebsoft.com/
License: GPLv2 or later
 */

/**
© Copyright 2021  BestWebSoft  ( https://support.bestwebsoft.com )

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

require_once dirname( __FILE__ ) . '/includes/deprecated.php';

if ( ! function_exists( 'add_qtsndtps_admin_menu' ) ) {
	/**
	 * Function are using to add on admin-panel WordPress page 'bws_panel' and sub-page of this plugin
	 */
	function add_qtsndtps_admin_menu() {
		global $submenu;

		if ( isset( $submenu['edit.php?post_type=quote'] ) ) {
			$settings = add_submenu_page( 'edit.php?post_type=quote', __( 'Quotes and Tips Settings', 'quotes-and-tips' ), __( 'Settings', 'quotes-and-tips' ), 'manage_options', 'quotes-and-tips.php', 'qtsndtps_settings_page' );
			add_submenu_page( 'edit.php?post_type=quote', 'BWS Panel', 'BWS Panel', 'manage_options', 'qtsndtps-bws-panel', 'bws_add_menu_render' );
		}
		if ( isset( $submenu['edit.php?post_type=tips'] ) ) {
			$settings = add_submenu_page( 'edit.php?post_type=tips', __( 'Quotes and Tips Settings', 'quotes-and-tips' ), __( 'Settings', 'quotes-and-tips' ), 'manage_options', 'quotes-and-tips.php', 'qtsndtps_settings_page' );
			add_submenu_page( 'edit.php?post_type=tips', 'BWS Panel', 'BWS Panel', 'manage_options', 'qtsndtps-bws-panel', 'bws_add_menu_render' );
		}

		if ( ! empty( $settings ) ) {
			add_action( 'load-' . $settings, 'qtsndtps_add_tabs' );
		}
		add_action( 'load-post.php', 'qtsndtps_add_tabs' );
		add_action( 'load-edit.php', 'qtsndtps_add_tabs' );
		add_action( 'load-post-new.php', 'qtsndtps_add_tabs' );
	}
}

if ( ! function_exists( 'qtsndtps_plugins_loaded' ) ) {
	/**
	 * Localization
	 */
	function qtsndtps_plugins_loaded() {
		load_plugin_textdomain( 'quotes-and-tips', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
}

if ( ! function_exists( 'qtsndtps_plugin_init' ) ) {
	/**
	 * Init plugin
	 */
	function qtsndtps_plugin_init() {
		global $qtsndtps_plugin_info, $qtsndtps_options;

		require_once dirname( __FILE__ ) . '/bws_menu/bws_include.php';
		bws_include_init( plugin_basename( __FILE__ ) );

		register_qtsndtps_settings();

		if ( empty( $qtsndtps_plugin_info ) ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$qtsndtps_plugin_info = get_plugin_data( __FILE__ );
		}

		/* Function check if plugin is compatible with current WP version */
		bws_wp_min_version_check( plugin_basename( __FILE__ ), $qtsndtps_plugin_info, '4.5' );

		/* Call register settings function */
		if ( ! is_admin() || ( isset( $_GET['page'] ) && 'quotes-and-tips.php' === $_GET['page'] ) ) {
			register_qtsndtps_settings();
		}

		if ( '2' === $qtsndtps_options['page_load'] && ! wp_next_scheduled( 'qtsndtps_update_quotes_tips_daily' ) ) {
			wp_schedule_event( time(), 'daily', 'qtsndtps_update_quotes_tips_daily' );
		} elseif ( '2' !== $qtsndtps_options['page_load'] && wp_next_scheduled( 'qtsndtps_update_quotes_tips_daily' ) ) {
			wp_clear_scheduled_hook( 'qtsndtps_update_quotes_tips_daily' );
		}

		qtsndtps_register_tips_post_type();
		qtsndtps_register_quote_post_type();
		qtsndtps_csv_download();
	}
}

if ( ! function_exists( 'qtsndtps_update_quotes_tips' ) ) {
	/**
	 * Function for update post type
	 */
	function qtsndtps_update_quotes_tips() {
		global $qtsndtps_options;
		register_qtsndtps_settings();
		$quotes_args                           = array(
			'post_type'      => 'quote',
			'post_status'    => 'publish',
			'orderby'        => 'rand',
			'posts_per_page' => 1,
		);
		$quote                                 = get_posts( $quotes_args );
		$tips_args                             = array(
			'post_type'      => 'tips',
			'post_status'    => 'publish',
			'orderby'        => 'rand',
			'posts_per_page' => 1,
		);
		$tip                                   = get_posts( $tips_args );
		$qtsndtps_options['current_quotes_id'] = $quote[0]->ID;
		$qtsndtps_options['current_tips_id']   = $tip[0]->ID;
		update_option( 'qtsndtps_options', $qtsndtps_options );
	}
}

if ( ! function_exists( 'qtsndtps_plugin_admin_init' ) ) {
	/**
	 * Admin init
	 */
	function qtsndtps_plugin_admin_init() {
		global $bws_plugin_info, $qtsndtps_plugin_info, $bws_shortcode_list;

		if ( empty( $bws_plugin_info ) ) {
			$bws_plugin_info = array(
				'id'      => '82',
				'version' => $qtsndtps_plugin_info['Version'],
			);
		}

		qtsndtps_add_custom_metabox();
		/* add Quotes and Tips to global $bws_shortcode_list */
		$bws_shortcode_list['qtsndtps'] = array(
			'name'        => 'Quotes and Tips',
			'js_function' => 'qtsndtps_shortcode_init',
		);

		add_filter( 'manage_quote_posts_columns', 'qtsndtps_quote_change_columns' );
		add_action( 'manage_quote_posts_custom_column', 'qtsndtps_custom_columns', 10, 2 );
		add_filter( 'manage_tips_posts_columns', 'qtsndtps_tips_change_columns' );
		add_action( 'manage_tips_posts_custom_column', 'qtsndtps_custom_columns', 10, 2 );
	}
}

if ( ! function_exists( 'qtsndtps_plugin_activate' ) ) {
	/**
	 * Function for activation
	 */
	function qtsndtps_plugin_activate() {
		if ( is_multisite() ) {
			switch_to_blog( 1 );
			register_uninstall_hook( __FILE__, 'qtsndtps_delete_options' );
			restore_current_blog();
		} else {
			register_uninstall_hook( __FILE__, 'qtsndtps_delete_options' );
		}
	}
}

if ( ! function_exists( 'qtsndtps_get_options_default' ) ) {
	/**
	 * Default options
	 */
	function qtsndtps_get_options_default() {
		global $qtsndtps_plugin_info;

		if ( ! $qtsndtps_plugin_info ) {
			$qtsndtps_plugin_info = get_plugin_data( __FILE__ );
		}

		$qtsndtps_options_defaults = array(
			'plugin_option_version'     => $qtsndtps_plugin_info['Version'],
			'page_load'                 => '1',
			'interval_load'             => '10',
			'tip_label'                 => __( 'Tips', 'quotes-and-tips' ),
			'quote_label'               => __( 'The quotes from our clients', 'quotes-and-tips' ),
			'title_post'                => '0',
			'additional_options'        => 1,
			'background_color'          => '#2484C6',
			'background_opacity'        => 1,
			'text_color'                => '#FFFFFF',
			'text_size'                 => 14,
			'title_text_size'           => 22,
			'background_image'          => 'default', /* none | default | custom */
			'custom_background_image'   => '',
			'background_image_repeat_x' => '0',
			'background_image_repeat_y' => '0',
			'background_image_cover'    => '0',
			'background_image_position' => array( 'left', 'bottom' ),
			'button_text'               => __( 'Next', 'quotes-and-tips' ),
			'display_settings_notice'   => 1,
			'suggest_feature_banner'    => 1,
			'border_radius'             => 1,
			'author_position'           => 0,
			'box_shadow_offset_x'       => 0,
			'box_shadow_offset_y'       => 0,
			'box_shadow_blur_radius'    => 0,
			'box_shadow_color'          => '#FFFFFF',
			'block_width'               => 100,
			'block_height'              => 150,
			'remove_quatation'          => 0,
			'current_quotes_id'         => 0,
			'current_tips_id'           => 0,
		);

		return $qtsndtps_options_defaults;
	}
}

if ( ! function_exists( 'register_qtsndtps_settings' ) ) {
	/**
	 * Register settings function
	 */
	function register_qtsndtps_settings() {
		global $qtsndtps_options, $qtsndtps_plugin_info;
		$qtsndtps_options_defaults = qtsndtps_get_options_default();

		/* Install the option defaults */
		if ( ! get_option( 'qtsndtps_options' ) ) {
			add_option( 'qtsndtps_options', $qtsndtps_options_defaults );
		}
		/* Get options from the database */
		$qtsndtps_options = get_option( 'qtsndtps_options' );

		/* Array merge incase this version has added new options */
		if ( ! isset( $qtsndtps_options['plugin_option_version'] ) || $qtsndtps_options['plugin_option_version'] !== $qtsndtps_plugin_info['Version'] ) {
			/**
			 * @deprecated since 1.39
			 * @todo remove after 11.03.2021
			 */
			if (
				isset( $qtsndtps_options['plugin_option_version'] ) &&
				version_compare( $qtsndtps_options['plugin_option_version'], '1.39', '<' )
			) {
				$qtsndtps_options['background_image_position'] = array( $qtsndtps_options['background_image_gposition'], $qtsndtps_options['background_image_vposition'] );
				unset( $qtsndtps_options['background_image_vposition'], $qtsndtps_options['background_image_gposition'] );

				$qtsndtps_options['additional_options'] = ( 1 === intval( $qtsndtps_options['additional_options'] ) ) ? 0 : 1;
			}
			/* end deprecated */
			$qtsndtps_options                          = array_merge( $qtsndtps_options_defaults, $qtsndtps_options );
			$qtsndtps_options['plugin_option_version'] = $qtsndtps_plugin_info['Version'];
			update_option( 'qtsndtps_options', $qtsndtps_options );
			qtsndtps_plugin_activate();
		}
	}
}

if ( ! function_exists( 'qtsndtps_enqueue_scripts' ) ) {
	/**
	 * Add script
	 */
	function qtsndtps_enqueue_scripts() {
		wp_enqueue_script( 'qtsndtps-script-handle', plugins_url( 'js/script.js', __FILE__ ), array( 'jquery' ), '1.0', true );
	}
}

if ( ! function_exists( 'qtsndtps_register_tips_post_type' ) ) {
	/**
	 * Register post type
	 */
	function qtsndtps_register_tips_post_type() {
		register_taxonomy(
			'tips_categories',
			'tips',
			array(
				'hierarchical'      => true,
				'labels'            => array(
					'name'                  => __( 'Tips Categories', 'quotes-and-tips' ),
					'singular_name'         => __( 'Tips Category', 'quotes-and-tips' ),
					'add_new'               => __( 'Add Tips Category', 'quotes-and-tips' ),
					'add_new_item'          => __( 'Add New Tips Category', 'quotes-and-tips' ),
					'edit'                  => __( 'Edit Tips Category', 'quotes-and-tips' ),
					'edit_item'             => __( 'Edit Tips Category', 'quotes-and-tips' ),
					'new_item'              => __( 'New Tips Category', 'quotes-and-tips' ),
					'view'                  => __( 'View Tips Category', 'quotes-and-tips' ),
					'view_item'             => __( 'View Tips Category', 'quotes-and-tips' ),
					'search_items'          => __( 'Find Tips Category', 'quotes-and-tips' ),
					'not_found'             => __( 'No Tips Categories found', 'quotes-and-tips' ),
					'not_found_in_trash'    => __( 'No Tips Categories found in Trash', 'quotes-and-tips' ),
					'parent'                => __( 'Parent Tips Category', 'quotes-and-tips' ),
					'items_list_navigation' => __( 'Tips Categories list navigation', 'quotes-and-tips' ),
					'items_list'            => __( 'Tips Categories list', 'quotes-and-tips' ),
				),
				'rewrite'           => true,
				'show_ui'           => true,
				'query_var'         => true,
				'sort'              => true,
				'map_meta_cap'      => true,
				'show_admin_column' => true,
			)
		);

		$args = array(
			'label'           => __( 'Tips', 'quotes-and-tips' ),
			'singular_label'  => __( 'Tips', 'quotes-and-tips' ),
			'public'          => true,
			'show_ui'         => true,
			'capability_type' => 'post',
			'hierarchical'    => false,
			'rewrite'         => true,
			'supports'        => array( 'title', 'editor' ),
			'menu_icon'       => 'dashicons-testimonial',
			'taxonomies'      => array( 'tips_categories' ),
			'labels'          => array(
				'add_new_item'          => __( 'Add a New Tip', 'quotes-and-tips' ),
				'edit_item'             => __( 'Edit Tip', 'quotes-and-tips' ),
				'new_item'              => __( 'New Tip', 'quotes-and-tips' ),
				'view_item'             => __( 'View Tip', 'quotes-and-tips' ),
				'search_items'          => __( 'Search Tip', 'quotes-and-tips' ),
				'not_found'             => __( 'No tips found', 'quotes-and-tips' ),
				'not_found_in_trash'    => __( 'No tips found in Trash', 'quotes-and-tips' ),
				'filter_items_list'     => __( 'Filter tips list', 'quotes-and-tips' ),
				'items_list_navigation' => __( 'Tips list navigation', 'quotes-and-tips' ),
				'items_list'            => __( 'Tips list', 'quotes-and-tips' ),
			),
		);
		register_post_type( 'tips', $args );

	}
}

if ( ! function_exists( 'qtsndtps_register_quote_post_type' ) ) {
	/**
	 * Register post type
	 */
	function qtsndtps_register_quote_post_type() {
		register_taxonomy(
			'quotes_categories',
			'quote',
			array(
				'hierarchical'      => true,
				'labels'            => array(
					'name'                  => __( 'Quotes Categories', 'quotes-and-tips' ),
					'singular_name'         => __( 'Quotes Category', 'quotes-and-tips' ),
					'add_new'               => __( 'Add Quotes Category', 'quotes-and-tips' ),
					'add_new_item'          => __( 'Add New Quotes Category', 'quotes-and-tips' ),
					'edit'                  => __( 'Edit Quotes Category', 'quotes-and-tips' ),
					'edit_item'             => __( 'Edit Quotes Category', 'quotes-and-tips' ),
					'new_item'              => __( 'New Quotes Category', 'quotes-and-tips' ),
					'view'                  => __( 'View Quotes Category', 'quotes-and-tips' ),
					'view_item'             => __( 'View Quotes Category', 'quotes-and-tips' ),
					'search_items'          => __( 'Find Quotes Category', 'quotes-and-tips' ),
					'not_found'             => __( 'No Quotes Categories found', 'quotes-and-tips' ),
					'not_found_in_trash'    => __( 'No Quotes Categories found in Trash', 'quotes-and-tips' ),
					'parent'                => __( 'Parent Quotes Category', 'quotes-and-tips' ),
					'items_list_navigation' => __( 'Quotes Categories list navigation', 'quotes-and-tips' ),
					'items_list'            => __( 'Quotes Categories list', 'quotes-and-tips' ),
				),
				'rewrite'           => true,
				'show_ui'           => true,
				'query_var'         => true,
				'sort'              => true,
				'map_meta_cap'      => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
			)
		);

		$args = array(
			'label'           => __( 'Quotes', 'quotes-and-tips' ),
			'singular_label'  => __( 'Quotes', 'quotes-and-tips' ),
			'public'          => true,
			'show_ui'         => true,
			'capability_type' => 'post',
			'hierarchical'    => false,
			'rewrite'         => true,
			'supports'        => array( 'title', 'editor' ),
			'menu_icon'       => 'dashicons-format-quote',
			'taxonomies'      => array( 'quotes_categories' ),
			'labels'          => array(
				'add_new_item'          => __( 'Add a New Quote', 'quotes-and-tips' ),
				'edit_item'             => __( 'Edit Quote', 'quotes-and-tips' ),
				'new_item'              => __( 'New Quote', 'quotes-and-tips' ),
				'view_item'             => __( 'View Quote', 'quotes-and-tips' ),
				'search_items'          => __( 'Search Quote', 'quotes-and-tips' ),
				'not_found'             => __( 'No quote found', 'quotes-and-tips' ),
				'not_found_in_trash'    => __( 'No quote found in Trash', 'quotes-and-tips' ),
				'filter_items_list'     => __( 'Filter quotes list', 'quotes-and-tips' ),
				'items_list_navigation' => __( 'Quotes list navigation', 'quotes-and-tips' ),
				'items_list'            => __( 'Quotes list', 'quotes-and-tips' ),
			),
			'public'          => true,
			'supports'        => array( 'title', 'editor', 'thumbnail', 'comments' ),
			'capability_type' => 'post',
			'rewrite'         => array( 'slug' => 'quote' ),
		);
		register_post_type( 'quote', $args );
	}
}

if ( ! function_exists( 'qtsndtps_get_random_tip_quote' ) ) {
	/**
	 * Get random tip and quote
	 */
	function qtsndtps_get_random_tip_quote() {
		echo wp_kses_post( qtsndtps_create_tip_quote_block() );
	}
}

if ( ! function_exists( 'qtsndtps_create_tip_quote_block' ) ) {
	/**
	 * Create Quotes and Tips Block
	 *
	 * @param array $atts (Optional) Atts for block.
	 */
	function qtsndtps_create_tip_quote_block( $atts = '' ) {
		global $post, $qtsndtps_options;

		$atts           = shortcode_atts(
			array(
				'id'            => 0,
				'type'          => 'quotes_and_tips',
				'quotes_cat_id' => 0,
				'tips_cat_id'   => 0,
				'exclude'       => 0,
			),
			$atts
		);

		$atts['type']   = explode( '_and_', $atts['type'] );
		$display_quotes = in_array( 'quotes', $atts['type'] );
		$display_tips   = in_array( 'tips', $atts['type'] );

		$button_text = $qtsndtps_options['button_text'];

		$html = '<div class="quotes_box_and_tips">';
		if ( ! empty( $atts['id'] ) ) {
			$post_qtstps = get_post( $atts['id'] );
			if ( isset( $post_qtstps ) ) {
				if ( 'quote' === $post_qtstps->post_type ) {
					$quotes       = array( $post_qtstps );
					$display_tips = false;
				} elseif ( 'tips' === $post_qtstps->post_type ) {
					$tips           = array( $post_qtstps );
					$display_quotes = false;
				}
			} else {
				$display_tips   = false;
				$display_quotes = false;
			}
		} else {
			if ( $display_quotes ) {
				$tax_query_quotes = ( 0 !== intval( $atts['quotes_cat_id'] ) ) ? array(
					array(
						'taxonomy' => 'quotes_categories',
						'field'    => 'id',
						'terms'    => array( $atts['quotes_cat_id'] ),
					),
				) : array();
				$quotes_args      = array(
					'post_type'      => 'quote',
					'post_status'    => 'publish',
					'orderby'        => 'rand',
					'tax_query'      => $tax_query_quotes,
					'posts_per_page' => 1,
					'orderby'        => 'rand',
					'order'          => 'ASC',
				);
				if ( isset( $atts['exclude'] ) ) {
					$quotes_args['exclude'] = $atts['exclude'];
				}
				global $wpdb;
				if ( '2' === $qtsndtps_options['page_load'] && 0 !== intval( $qtsndtps_options['current_quotes_id'] ) && 0 === intval( $atts['id'] ) ) {
					$quotes = get_post( $qtsndtps_options['current_quotes_id'] );
					if ( ! empty( $quotes ) ) {
						$quotes = array( $quotes );
					}
				} else {
					$quotes = get_posts( $quotes_args );
				}
			}
			if ( $display_tips ) {
				$tax_query_tips = ( 0 !== intval( $atts['tips_cat_id'] ) ) ? array(
					array(
						'taxonomy' => 'tips_categories',
						'field'    => 'id',
						'terms'    => array( $atts['tips_cat_id'] ),
					),
				) : array();
				$tips_args      = array(
					'post_type'      => 'tips',
					'post_status'    => 'publish',
					'orderby'        => 'rand',
					'tax_query'      => $tax_query_tips,
					'posts_per_page' => 1,
					'orderby'        => 'rand',
					'order'          => 'ASC',
				);
				if ( isset( $atts['exclude'] ) ) {
					$tips_args['exclude'] = $atts['exclude'];
				}
				if ( '2' === $qtsndtps_options['page_load'] && 0 !== intval( $qtsndtps_options['current_tips_id'] ) && 0 === intval( $atts['id'] ) ) {
					$tips = get_post( $qtsndtps_options['current_tips_id'] );
					if ( ! empty( $tips ) ) {
						$tips = array( $tips );
					}
				} else {
					$tips = get_posts( $tips_args );
				}
			}
		}

		/* Display Quotes and Tips */
		if ( $display_quotes && $display_tips ) {
			$quotes_class = empty( $tips ) ? 'single_quotes_box' : 'quotes_box';
			$tips_class   = empty( $quotes ) ? 'single_tips_box' : 'tips_box';
			if ( 0 !== intval( $atts['id'] ) ) {
				$quotes_class .= ' specific_quotes_box';
				$tips_class   .= ' specific_tips_box';
			}
			$html_quotes = qtsndtps_get_quotes_html( $quotes_class, $quotes, $atts, false );
			$html_tips   = qtsndtps_get_tips_html( $tips_class, $tips, $atts, false );
			if ( ! empty( $tips ) && ! empty( $quotes ) ) {
				$background_image   = qtsndtps_get_custom_type_background( $qtsndtps_options['background_image'], $qtsndtps_options['custom_background_image'] );
				$style_parent_block = ( 'video' === $background_image ) ? 'style="position: relative;"' : '';
				$video_html         = ( 'video' === $background_image ) ? qtsndtps_get_video_background_html() : '';

				$html .= '<div ' . $style_parent_block . ' class="box_delimeter">' . $video_html . $html_quotes . $html_tips . '<div class="clear"></div>';
			} elseif ( empty( $tips ) && empty( $quotes ) ) {
				return '';
			} else {
				$html .= $html_quotes . $html_tips . '<div class="clear">';
			}
			if ( '3' === $qtsndtps_options['page_load'] ) {
				$html .= '<div class="quote_button_container"> 
					<button class="change_quote_button">' . $button_text . '</button>
				 </div>';

			}
			$html .= '</div>';
		} elseif ( $display_quotes ) {
			/* Display Quotes only */
			if ( ! empty( $quotes ) ) {
				$quotes_class = empty( $tips ) ? 'single_quotes_box' : 'quotes_box';
				if ( 0 !== intval( $atts['id'] ) ) {
					$quotes_class .= ' specific_quotes_box';
				}
				$html .= qtsndtps_get_quotes_html( $quotes_class, $quotes, $atts, true ) . '<div class="clear"></div>';
			} else {
				return '';
			}
		} elseif ( $display_tips ) {
			/* Display Tips only */
			if ( ! empty( $tips ) ) {
				$tips_class = empty( $quotes ) ? 'single_tips_box' : 'tips_box';
				if ( 0 !== intval( $atts['id'] ) ) {
					$tips_class .= ' specific_tips_box';
				}
				$html .= qtsndtps_get_tips_html( $tips_class, $tips, $atts, true ) . '<div class="clear"></div>';
			} else {
				return '';
			}
		} else {
			return '';
		}
		$html .= '</div>';
		return $html;
	}
}

if ( ! function_exists( 'qtsndtps_csv_download' ) ) {
	/**
	 * Download SCV with tips and quotes
	 */
	function qtsndtps_csv_download() {
		global $wpdb, $wp_filesystem;
		if ( isset( $_POST['qtsndtps_export_submit'] ) && check_admin_referer( 'qtsndtps_export_action', 'qtsndtps_export_field' ) ) {
			if ( ! function_exists( 'WP_Filesystem' ) ) {
				/* Include necessary file for WordPress Filesystem API */
				include_once ABSPATH . 'wp-admin/includes/file.php';
			}

			/* Initialize the WordPress filesystem. */
			WP_Filesystem();

			$quotes_query = "SELECT p.post_title, p.post_content, pm.meta_value AS 'name_field', pm1.meta_value AS 'off_cap'
							FROM {$wpdb->posts} p
							LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
							LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id
							WHERE p.post_type = 'quote' AND p.post_status = 'publish'
							AND pm.meta_key = 'name_field'
							AND pm1.meta_key = 'off_cap'";
			$tips_query = "SELECT p.post_title, p.post_content
							FROM {$wpdb->posts} p
							WHERE p.post_type = 'tips' AND p.post_status = 'publish'";

			/* Let's execute the SQL record and get the results */
			$quotes_results = $wpdb->get_results( $quotes_query, ARRAY_A );
			$tips_results   = $wpdb->get_results( $tips_query, ARRAY_A );

			/* Initialize CSV data */
			$csv_data = '"post_type";"post_title";"post_content";"name_field";"off_cap"' . PHP_EOL;

			/* Add data for quotes */
			foreach ( $quotes_results as $quote ) {
				$csv_data .= '"' . implode( '";"', array( 'quote', strip_tags( $quote['post_title'] ), strip_tags( $quote['post_content'] ), $quote['name_field'], $quote['off_cap'] ) ) . '";' . PHP_EOL;
			}

			/* Add data for tips */
			foreach ( $tips_results as $tip ) {
				$csv_data .= '"' . implode( '";"', array( 'tips', strip_tags( $tip['post_title'] ), strip_tags( $tip['post_content'] ), '', '' ) ) . '";' . PHP_EOL;
			}

			/* Use WordPress Filesystem API to create a CSV file */
			$upload_dir = wp_upload_dir();
			$file_name = $upload_dir['path'] . '/quotes_and_tips.csv';

			$result = $wp_filesystem->put_contents( $file_name, $csv_data, FS_CHMOD_FILE );
			if ( ! $result ) {
				unlink( $file_name );
				return false;
				exit;
			}

			/* We configure HTTP headers and output CSV */
			header( 'Content-Type: text/csv' );
			header( 'Content-Disposition: attachment; filename="quotes_and_tips.csv"' );
			echo $wp_filesystem->get_contents( $file_name );
			unlink( $file_name );
			exit;
		}
	}
}

if ( ! function_exists( 'qtsndtps_csv_upload' ) ) {
	/**
	 * Upload SCV with tips and quotes
	 */
	function qtsndtps_csv_upload() {
		global $wpdb;
		if ( isset( $_POST['qtsndtps_import_submit'] ) && check_admin_referer( 'qtsndtps_import_action', 'qtsndtps_import_field' ) ) {
			if ( isset( $_FILES['qtsndtps_csv_file'] ) && isset( $_FILES['qtsndtps_csv_file']['error'] ) && UPLOAD_ERR_OK === $_FILES['qtsndtps_csv_file']['error'] ) {
				$csv_file = isset( $_FILES['qtsndtps_csv_file']['tmp_name'] ) ? $_FILES['qtsndtps_csv_file']['tmp_name'] : '';

				/* Read CSV file */
				$csv_data = file_get_contents( $csv_file );

				/* Split CSV data into rows */
				$rows     = explode( PHP_EOL, $csv_data );
				foreach ( $rows as $row ) {
					$row_data = str_getcsv( $row, ';' );
					if ( empty( $row_data[0] ) || 'post_type' === $row_data[0] ) {
						continue;
					}

					$post_type    = sanitize_text_field( $row_data[0] );
					$post_title   = sanitize_text_field( $row_data[1] );
					$post_content = sanitize_text_field( $row_data[2] );

					$post_array = array(
						'post_title'   => $post_title,
						'post_content' => $post_content,
						'post_type'    => $post_type,
						'post_status'  => 'publish',
					);

					$wpdb->insert(
						$wpdb->posts,
						$post_array
					);

					$post_id = $wpdb->insert_id;

					if ( 'quote' === $row_data[0] ) {
						$name_field   = sanitize_text_field( $row_data[3] );
						$off_cap      = sanitize_text_field( $row_data[4] );
						if ( $post_id ) {
							$wpdb->insert(
								$wpdb->postmeta,
								array(
									'post_id'    => $post_id,
									'meta_key'   => 'name_field',
									'meta_value' => $name_field,
								)
							);
							$wpdb->insert(
								$wpdb->postmeta,
								array(
									'post_id'    => $post_id,
									'meta_key'   => 'off_cap',
									'meta_value' => $off_cap,
								)
							);
						}
					}
				}
				return true;
			} else {
				return false;
			}
		}
	}
}

if ( ! function_exists( 'qtsndtps_get_video_background_html' ) ) {
	/**
	 * Display Quotes Block with video background
	 */
	function qtsndtps_get_video_background_html() {
		global $qtsndtps_options;
		$fit = '';
		if ( isset( $qtsndtps_options['background_image_cover'] ) && $qtsndtps_options['background_image_cover'] ) {
			$fit = 'object-fit: cover;';
		}
		return '<video playsinline autoplay muted loop poster="cake.jpg" style="position: absolute; z-index: -1; width: 100%; height: 100%; top: 0; left: 0; object-position: ' . $qtsndtps_options['background_image_position'][0] . ' ' . $qtsndtps_options['background_image_position'][1] . '; ' . $fit . '">
			<source src="' . $qtsndtps_options['custom_background_image'] . '">
			<?php esc_html_e( \'Your browser does not support the video tag\', \'quotes-and-tips\' ); ?>.
		</video>';
	}
}

if ( ! function_exists( 'qtsndtps_get_quotes_html' ) ) {
	/**
	 * Display Quotes Block
	 *
	 * @param string $quotes_class  Class for block.
	 * @param array  $posts         Posts for display.
	 * @param array  $atts          Atts for block.
	 * @param bool   $display_video Flag for dipslay video.
	 */
	function qtsndtps_get_quotes_html( $quotes_class, $posts, $atts, $display_video ) {
		global $qtsndtps_options, $post;
		$buffer = $post;
		if ( empty( $posts ) ) {
			return '';
		}
		$background_image   = qtsndtps_get_custom_type_background( $qtsndtps_options['background_image'], $qtsndtps_options['custom_background_image'] );
		$style_parent_block = ( $display_video && 'video' === $background_image ) ? 'style="position: relative;"' : '';
		$video_html         = ( $display_video && 'video' === $background_image ) ? qtsndtps_get_video_background_html() : '';
		$html               = '';
		/* The Loop */
		$count = 0;
		foreach ( $posts as $quote ) {
			$post = $quote;
			setup_postdata( $quote );
			$name_field = get_post_meta( $post->ID, 'name_field', true );
			$off_cap    = get_post_meta( $post->ID, 'off_cap', true );
			$html .= '<div ' . $style_parent_block . ' class="' . esc_attr( $quotes_class ) .
				( 0 < $count ? ' hidden ' : ' visible ' ) .
				'" data-id="' . get_the_ID() . '">' . $video_html . '
				<div class="testimonials_box" id="testimonials_' . get_the_ID() . '">
					<h3>' .
						( ( isset( $qtsndtps_options['title_post'] ) && '1' === $qtsndtps_options['title_post'] ) ? get_the_title() : $qtsndtps_options['quote_label'] ) .
					'</h3>
					<p>
						' . ( ( $qtsndtps_options['remove_quatation'] ) ? '<i>' . get_the_content() . '</i>' : '<i>"' . get_the_content() . '"</i>' ) . '
					</p>
					<p class="signature">';
			if ( ! empty( $name_field ) ) {
				$html .= $name_field;
			}
			if ( ! empty( $off_cap ) && ! empty( $name_field ) ) {
				$html .= ' | ';
			}
			if ( ! empty( $off_cap ) ) {
				$html .= '<span>' . $off_cap . '</span>';
			}
			$html .= '</p>
				</div>
			</div>';
			$count ++;
		}
		$post = $buffer;
		wp_reset_postdata();
		/* Reset Query */
		$html = apply_filters( 'get_quotes_html', $html );
		return $html;
	}
}

if ( ! function_exists( 'qtsndtps_get_tips_html' ) ) {
	/**
	 * Display Tips Block
	 *
	 * @param string $tips_class  Class for block.
	 * @param array  $posts         Posts for display.
	 * @param array  $atts          Atts for block.
	 * @param bool   $display_video Flag for dipslay video.
	 */
	function qtsndtps_get_tips_html( $tips_class, $posts, $atts, $display_video ) {
		global $qtsndtps_options, $post;
		$buffer             = $post;
		$background_image   = qtsndtps_get_custom_type_background( $qtsndtps_options['background_image'], $qtsndtps_options['custom_background_image'] );
		$style_parent_block = ( $display_video && 'video' === $background_image ) ? 'style="position: relative;"' : '';
		$video_html         = ( $display_video && 'video' === $background_image ) ? qtsndtps_get_video_background_html() : '';
		$html               = '';
		$count              = 0;
			/* The Loop */
		foreach ( $posts as $tip ) {
			$post = $tip;
			setup_postdata( $tip );
			$html .= '<div ' . $style_parent_block . ' class="' . esc_attr( $tips_class ) .
				( 0 < $count ? ' hidden ' : ' visible ' ) .
				'" data-id="' . get_the_ID() . '">' . $video_html . '
					<h3>' .
					( ( isset( $qtsndtps_options['title_post'] ) && '1' === $qtsndtps_options['title_post'] ) ? get_the_title() : $qtsndtps_options['tip_label'] ) .
				'</h3>
					<p>' . get_the_content() . '</p>
					</div>';
			$count ++;
		}
			$post = $buffer;
			wp_reset_postdata();
		/* Reset Query */
		$html = apply_filters( 'get_tips_html', $html );
		return $html;
	}
}

if ( ! function_exists( 'qtsndtps_quote_custom_metabox' ) ) {
	/**
	 * Display Metabox with quote
	 */
	function qtsndtps_quote_custom_metabox() {
		global $post;
		$name_field = get_post_meta( $post->ID, 'name_field' );
		$off_cap    = get_post_meta( $post->ID, 'off_cap' );
		wp_nonce_field( plugin_basename( __FILE__ ), 'qtsndtps_nonce_name' ); ?>
		<p><label for="name_field"><?php esc_html_e( 'Name:', 'quotes-and-tips' ); ?><br />
				<input type="text" id="name_field" size="37" name="name_field" value="<?php echo ! empty( $name_field ) ? esc_html( $name_field[0] ) : ''; ?>" />
			</label>
		</p>
		<p><label for="off_cap"><?php esc_html_e( 'Official Position:', 'quotes-and-tips' ); ?><br />
				<input type="text" id="off_cap" size="37" name="off_cap" value="<?php echo ! empty( $off_cap ) ? esc_html( $off_cap[0] ) : ''; ?>" />
			</label>
		</p>
		<?php
	}
}

if ( ! function_exists( 'qtsndtps_add_custom_metabox' ) ) {
	/**
	 * Add Metabox with quote
	 */
	function qtsndtps_add_custom_metabox() {
		add_meta_box( 'custom-metabox', __( 'Name and Official Position', 'quotes-and-tips' ), 'qtsndtps_quote_custom_metabox', 'quote', 'normal', 'high' );
	}
}

if ( ! function_exists( 'qtsndtps_settings_page' ) ) {
	/**
	 * Settings page
	 */
	function qtsndtps_settings_page() {
		if ( ! class_exists( 'Bws_Settings_Tabs' ) ) {
			require_once dirname( __FILE__ ) . '/bws_menu/class-bws-settings.php';
		}
		require_once dirname( __FILE__ ) . '/includes/class-qtsndtps-settings.php';
		$page = new Qtsndtps_Settings_Tabs( plugin_basename( __FILE__ ) );
		if ( method_exists( $page, 'add_request_feature' ) ) {
			$page->add_request_feature();
		}
		?>
		<div class="wrap">
			<h1 class="qtsndtps-title"><?php esc_html_e( 'Quotes and Tips Settings', 'quotes-and-tips' ); ?></h1>
			<?php $page->display_content(); ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'qtsndtps_register_plugin_links' ) ) {
	/**
	 * Add Settings, FAQ and Support links
	 *
	 * @param  array  $links Links array.
	 * @param  string $file  Plugin file.
	 * @return array  $links.
	 */
	function qtsndtps_register_plugin_links( $links, $file ) {
		$base = plugin_basename( __FILE__ );
		if ( $file === $base ) {
			if ( ! is_network_admin() ) {
				$links[] = '<a href="admin.php?page=quotes-and-tips.php">' . __( 'Settings', 'quotes-and-tips' ) . '</a>';
			}
			$links[] = '<a href="https://support.bestwebsoft.com/hc/en-us/sections/200538959" target="_blank">' . __( 'FAQ', 'quotes-and-tips' ) . '</a>';
			$links[] = '<a href="https://support.bestwebsoft.com">' . __( 'Support', 'quotes-and-tips' ) . '</a>';
		}
		return $links;
	}
}

if ( ! function_exists( 'qtsndtps_plugin_action_links' ) ) {
	/**
	 * Add Settings link
	 *
	 * @param  array  $links Links array.
	 * @param  string $file  Plugin file.
	 * @return array  $links.
	 */
	function qtsndtps_plugin_action_links( $links, $file ) {
		if ( ! is_network_admin() ) {
			/* Static so we don't call plugin_basename on every plugin row. */
			static $this_plugin;
			if ( ! $this_plugin ) {
				$this_plugin = plugin_basename( __FILE__ );
			}
			if ( $file === $this_plugin ) {
				$settings_link = '<a href="admin.php?page=quotes-and-tips.php">' . __( 'Settings', 'quotes-and-tips' ) . '</a>';
				array_unshift( $links, $settings_link );
			}
		}
		return $links;
	} /* End function qtsndtps_plugin_action_links */
}

if ( ! function_exists( 'qtsndtps_get_custom_type_background' ) ) {
	/**
	 * Get background for block
	 *
	 * @param string $background_image Param for background image.
	 * @param string $custom_bg_image  Url for background image.
	 */
	function qtsndtps_get_custom_type_background( $background_image, $custom_bg_image ) {
		if ( 'custom' === $background_image ) {
			$format = substr( $custom_bg_image, strrpos( $custom_bg_image, '.' ), strlen( $custom_bg_image ) - strrpos( $custom_bg_image, '.' ) );
			switch ( $format ) {
				case '.png':
				case '.jpg':
				case '.jpeg':
				case '.gif':
							$background_image = 'image';
					break;

				case '.mp4':
				case '.m4v':
				case '.webm':
				case '.ogv':
				case '.flv':
								$background_image = 'video';
					break;
			}
		}
		return $background_image;
	}
}

if ( ! function_exists( 'qtsndtps_print_style_script' ) ) {
	/**
	 * Display styles for block
	 */
	function qtsndtps_print_style_script() {
		global $qtsndtps_options, $qtsndtps_options_defaults, $qtsndtps_plugin_info;

		if ( ! $qtsndtps_plugin_info ) {
			$qtsndtps_plugin_info = get_plugin_data( __FILE__ );
		}

		$qtsndtps_options_defaults = qtsndtps_get_options_default();

		$background_image    = $qtsndtps_options['background_image'];
		$custom_bg_image     = $qtsndtps_options['custom_background_image'];
		$background_position = $qtsndtps_options['background_image_position'];
		$background_repeat_x = $qtsndtps_options['background_image_repeat_x'];
		$background_repeat_y = $qtsndtps_options['background_image_repeat_y'];
		$interval_load       = ( '0' === $qtsndtps_options['interval_load'] ) ? '10' : $qtsndtps_options['interval_load'];
		$page_load           = $qtsndtps_options['page_load'];
		$additional_options  = $qtsndtps_options['additional_options'];
		$background_image    = qtsndtps_get_custom_type_background( $background_image, $custom_bg_image );
		$border_radius       = $qtsndtps_options['border_radius'];
		$box_shadow_x        = $qtsndtps_options['box_shadow_offset_x'];
		$box_shadow_y        = $qtsndtps_options['box_shadow_offset_y'];
		$box_shadow_blur     = $qtsndtps_options['box_shadow_blur_radius'];
		$box_shadow_color    = $qtsndtps_options['box_shadow_color'];
		$text_size           = $qtsndtps_options['text_size'];
		$title_text_size     = $qtsndtps_options['title_text_size'];
		$block_width         = $qtsndtps_options['block_width'];
		$block_height        = $qtsndtps_options['block_height'];

		if ( '0' === $additional_options ) {
			/* If additional settings is turned off */
			$background_color    = $qtsndtps_options_defaults['background_color'];
			$text_color          = $qtsndtps_options_defaults['text_color'];
			$background_image    = $qtsndtps_options['background_image'];
			$background_position = $qtsndtps_options_defaults['background_image_position'];
			$background_repeat_x = $qtsndtps_options_defaults['background_image_repeat_x'];
			$background_repeat_y = $qtsndtps_options_defaults['background_image_repeat_y'];
		} else {
			$background_color = $qtsndtps_options['background_color'];
			$text_color       = $qtsndtps_options['text_color'];
		}
		$mask = $background_repeat_x . $background_repeat_y;
		switch ( $mask ) {
			case '11':
				$repeat_rule = 'repeat';
				break;
			case '10':
				$repeat_rule = 'repeat-x';
				break;
			case '01':
				$repeat_rule = 'repeat-y';
				break;
			default:
				$repeat_rule = 'no-repeat';
				break;
		}
		?>

		<style type="text/css">
			/* Style for tips|quote block */
			<?php if ( $qtsndtps_options['background_opacity'] ) { ?>
				.quotes_box_and_tips:before {
					content: '';
					display: flex;
					position: absolute;
					top:0;
					left: 0;
					bottom:0;
					right:0;
					width: 100%;
					height: 100%;
					visibility: visible;
					color: <?php echo esc_attr( $text_color ); ?> !important;
					<?php if ( '0' !== $additional_options && 'video' === $background_image ) { ?>
						background-color: transparent !important;
					<?php } else { ?>
						background-color: <?php echo esc_attr( $background_color ); ?> !important;
					<?php } ?>
					z-index: 0;
					border-radius: <?php echo esc_attr( $border_radius . 'px' ); ?>;
				}
				.quotes_box_and_tips .signature {
					float: <?php echo ( '1' === $qtsndtps_options['author_position'] ) ? 'right' : 'left'; ?>;
				}
				.quotes_box_and_tips:after {
					content: '';
					display: flex;
					position: absolute;
					top:0;
					left: 0;
					bottom:0;
					right:0;
					width: 100%;
					height: 100%;
					visibility: visible;
					<?php if ( '0' !== $additional_options && 'image' === $background_image && ! empty( $custom_bg_image ) ) { ?>
						background-image: url( <?php echo esc_url( $custom_bg_image ); ?> );
					<?php } elseif ( '0' !== $additional_options && ( 'none' === $background_image || 'video' === $background_image ) ) { ?>
						background-image: none;
					<?php } elseif ( '0' === $additional_options || 'default' === $background_image ) { ?>
						background-image: url(<?php echo esc_url( plugins_url( '/images/quotes_box_and_tips_bg.png', __FILE__ ) ); ?>);
					<?php } ?>
					background-repeat: <?php echo esc_attr( $repeat_rule ); ?>;
					opacity: <?php echo esc_attr( $qtsndtps_options['background_opacity'] ); ?>;
					background-position: <?php echo esc_attr( $background_position[0] . ' ' . $background_position[1] ); ?>;
					z-index: 1;
					border-radius: <?php echo esc_attr( $border_radius . 'px' ); ?>;
				}
				.quotes_box_and_tips {
					position: relative;
					background-color: transparent;
					<?php if ( 'video' === $background_image ) { ?>
						padding: 0;
					<?php } ?>
					font-size: <?php echo esc_attr( $text_size . 'px' ); ?>;
					width: <?php echo esc_attr( $block_width ); ?>%;
					height: <?php echo esc_attr( $block_height ); ?>px;
					box-shadow: <?php echo esc_attr( $box_shadow_x ) . 'px ' . esc_attr( $box_shadow_y ) . 'px ' . esc_attr( $box_shadow_blur ) . 'px ' . esc_attr( $box_shadow_color ); ?>;
					border-radius: <?php echo esc_attr( $border_radius . 'px' ); ?>;
				}
				.quotes_box_and_tips > div {
					position: relative;
					z-index: 2;
					color: <?php echo esc_attr( $text_color ); ?> !important;
				}
				<?php if ( 'video' === $background_image ) { ?>
					.quotes_box_and_tips video {
						opacity: <?php echo esc_attr( $qtsndtps_options['background_opacity'] ); ?>;
					}
					.quotes_box_and_tips .box_delimeter,
					.quotes_box_and_tips .single_tips_box, 
					.quotes_box_and_tips .single_quotes_box {
						background-color: <?php echo esc_attr( $background_color ); ?> !important;
						background-image: none;
						padding: 15px 0;
					}
					.quotes_box_and_tips .box_delimeter:after {
						content: '';
						display: flex;
						position: absolute;
						top: 6%;
						left: 48%;
						bottom:0;
						right:0;
						height: 88%;
						width: 1px;
						background-color: rgba( 255,255,255,0.3 );
					}
					.quotes_box_and_tips .single_tips_box, 
					.quotes_box_and_tips .single_quotes_box {
						padding: 0;
					}
					.quotes_box_and_tips .single_quotes_box .testimonials_box, 
					.quotes_box_and_tips .single_tips_box h3,
					.quotes_box_and_tips .single_tips_box p {
						padding-left: 1.49%;
						padding-right: 1.49%;
					}
					@media (max-width: 600px) {
						.quotes_box_and_tips .box_delimeter:after {
							height: 0;
							width: 0;
						}
					}
				<?php } ?>
			<?php } else { ?>
				.quotes_box_and_tips {
					<?php if ( '0' !== $additional_options && 'video' === $background_image ) { ?>
						background-color: transparent !important;
					<?php } else { ?>
						background-color: <?php echo esc_attr( $background_color ); ?> !important;
					<?php } ?>
					background-repeat: <?php echo esc_attr( $repeat_rule ); ?>;
					color: <?php echo esc_attr( $text_color ); ?> !important;
					<?php if ( '0' !== $additional_options && 'image' === $background_image && ! empty( $custom_bg_image ) ) { ?>
						background-image: url(<?php echo esc_url( $custom_bg_image ); ?> );
					<?php } elseif ( '0' !== $additional_options && ( 'none' === $background_image || 'video' === $background_image ) ) { ?>
						background-image: none;
					<?php } elseif ( '0' === $additional_options ) { ?>
							background-image: url(<?php echo esc_url( plugins_url( '/images/quotes_box_and_tips_bg.png', __FILE__ ) ); ?>);
					<?php } ?>
					background-position: <?php echo esc_attr( $background_position[0] . ' ' . $background_position[1] ); ?>;
					border-radius: <?php echo esc_attr( $border_radius . 'px' ); ?>;
					box-shadow: <?php echo esc_attr( $box_shadow_x ) . 'px ' . esc_attr( $box_shadow_y ) . 'px ' . esc_attr( $box_shadow_blur ) . 'px ' . esc_attr( $box_shadow_color ); ?>;
				}
			<?php } ?>
			.change_quote_button {
				color: <?php echo esc_attr( $text_color ); ?>;
				background-color: <?php echo esc_attr( $background_color ); ?>;
				border: 2px inset <?php echo esc_attr( $box_shadow_color ); ?>;
				border-radius: <?php echo esc_attr( $border_radius / 2 . 'px' ); ?>;
				font-size: <?php echo esc_attr( $text_size . 'px' ); ?>;
			}
			.quotes_box_and_tips .box_delimeter {
				height: <?php echo esc_attr( $block_height - 30 ); ?>px;
			}

			.quotes_box_and_tips h3,
			.quotes_box_and_tips .signature,
			.quotes_box_and_tips .signature span,
			.quotes_box_and_tips .signature span i,
			.change_quote_button {
				color: <?php echo esc_attr( $text_color ); ?> !important;
			}
			.quotes_box_and_tips h3 {
				font-size: <?php echo esc_attr( $title_text_size . 'px' ); ?> !important;
			}
		</style>
		<?php
		if ( '0' === $page_load ) {
			$script = '( function($){
						$(document).ready( function() {
							var interval = ' . $interval_load . "
							setInterval( change_tip_quote_auto, interval * 1000 );
						});
						function change_tip_quote_auto() {
							$( 'body' ).find( '.quotes_box_and_tips' ).each( function(){
								var block = $( this );
								var tip = $( this ).find( '.tips_box' ).attr( 'data-id' );
								var quote = $( this ).find( '.quotes_box' ).attr( 'data-id' );
								$.ajax({
									type: 'POST',
									url: '" . admin_url( 'admin-ajax.php' ) . "',
									data: {
										action: 'qtsndtps_change_block',
										exclude: [ tip, quote ]
									},
									success: function(response) {
										block.replaceWith( response );										
									},
									error: function( error ) {
										console.log( error );
									}
								});
							});
						}
					})(jQuery);";
			wp_register_script( 'qtsndtps-script-update', '', array( 'jquery' ), $qtsndtps_plugin_info['Version'], true );
			wp_enqueue_script( 'qtsndtps-script-update' );
			wp_add_inline_script( 'qtsndtps-script-update', sprintf( $script ) );
		}
		if ( '3' === $page_load ) {
			$script = '( function($){
						$(document).ready( function() {
							$( "body" ).on( "click", ".change_quote_button", function() {
								var block = $( this ).parents( ".quotes_box_and_tips" );									
								var tip = block.find( ".tips_box" ).attr( "data-id" );
								var quote = block.find( ".quotes_box" ).attr( "data-id" );
								$.ajax({
									type: "POST",
									url: "' . admin_url( 'admin-ajax.php' ) . '",
									data: {
										action: "qtsndtps_change_block",
										exclude: [ tip, quote ]
									},
									success: function(response) {
										block.replaceWith( response );										
									},
									error: function( error ) {
										console.log( error );
									}
								});
							});
						});
					})(jQuery);';
			wp_register_script( 'qtsndtps-script-update', '', array( 'jquery' ), $qtsndtps_plugin_info['Version'], true );
			wp_enqueue_script( 'qtsndtps-script-update' );
			wp_add_inline_script( 'qtsndtps-script-update', sprintf( $script ) );
		}
	}
}

if ( ! function_exists( 'qtsndtps_change_block' ) ) {
	/**
	 * Dinamic change block with tip and quote
	 */
	function qtsndtps_change_block() {
		$attr = '';
		if ( isset( $_POST['exclude'] ) ) {
			$attr = array( 'exclude' => $_POST['exclude'] );
		}
		echo wp_kses_post( qtsndtps_create_tip_quote_block( $attr ) );
		wp_die();
	}
}

if ( ! function_exists( 'qtsndtps_wp_head' ) ) {
	/**
	 * Added styles ans scripts for Front
	 */
	function qtsndtps_wp_head() {
		global $qtsndtps_plugin_info;

		if ( ! $qtsndtps_plugin_info ) {
			$qtsndtps_plugin_info = get_plugin_data( __FILE__ );
		}
		wp_enqueue_style( 'qtsndtps_stylesheet', plugins_url( 'css/style.css', __FILE__ ), array(), $qtsndtps_plugin_info['Version'] );

		if ( is_admin() && isset( $_GET['page'] ) && 'quotes-and-tips.php' === $_GET['page'] ) {
			bws_enqueue_settings_scripts();
			bws_plugins_include_codemirror();
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style( 'qtsndtps_admin_stylesheet', plugins_url( 'css/admin_style.css', __FILE__ ), array(), $qtsndtps_plugin_info['Version'] );
			wp_enqueue_script( 'qtsndtps_script', plugins_url( 'js/script.js', __FILE__ ), array( 'jquery', 'wp-color-picker' ), $qtsndtps_plugin_info['Version'], true );
		}
	}
}

if ( ! function_exists( 'qtsndtps_admin_notices' ) ) {
	/**
	 * Display banner
	 */
	function qtsndtps_admin_notices() {
		global $hook_suffix, $qtsndtps_plugin_info;
		if ( 'plugins.php' === $hook_suffix && ! is_network_admin() ) {
			bws_plugin_banner_to_settings( $qtsndtps_plugin_info, 'qtsndtps_options', 'quotes-and-tips', 'admin.php?page=quotes-and-tips.php', 'post-new.php?post_type=quote' );
		}
		if ( isset( $_GET['page'] ) && 'quotes-and-tips.php' === $_GET['page'] ) {
			bws_plugin_suggest_feature_banner( $qtsndtps_plugin_info, 'qtsndtps_options', 'quotes-and-tips' );
		}
	}
}

if ( ! function_exists( 'qtsndtps_save_custom_quote' ) ) {
	/**
	 * Save post info
	 *
	 * @param int $post_id Post Id.
	 */
	function qtsndtps_save_custom_quote( $post_id ) {
		global $post;
		if ( ( ( isset( $_POST['name_field'] ) && '' !== $_POST['name_field'] ) || ( isset( $_POST['off_cap'] ) && '' !== $_POST['off_cap'] ) ) && check_admin_referer( plugin_basename( __FILE__ ), 'qtsndtps_nonce_name' ) ) {
			update_post_meta( $post->ID, 'name_field', sanitize_text_field( wp_unslash( $_POST['name_field'] ) ) );
			update_post_meta( $post->ID, 'off_cap', sanitize_text_field( wp_unslash( $_POST['off_cap'] ) ) );
		}
	}
}

if ( ! function_exists( 'get_human_readable_file_size' ) ) {
	/**
	 * Get file size
	 *
	 * @param int $max_size File max size.
	 */
	function get_human_readable_file_size( $max_size ) {
		if ( 104857 <= $max_size ) {
			/* if file size more then 100KB */
			return round( $max_size / 1048576, 2 ) . '&nbsp;' . __( 'MB', 'quotes-and-tips' );
		}
		if ( 1024 <= $bytes && 104857 >= $max_size ) {
			/* if file size more then 1KB but less then 100KB */
			return round( $max_size / 1024, 2 ) . '&nbsp;' . __( 'KB', 'quotes-and-tips' );
		}
		/* if file size under 1KB */
		return $max_size . '&nbsp;' . __( 'Bytes', 'quotes-and-tips' );
	}
}

if ( ! function_exists( 'qtsndtps_shortcode_button_content' ) ) {
	/**
	 * Add shortcode content
	 *
	 * @param string $content Content for shortcode.
	 */
	function qtsndtps_shortcode_button_content( $content ) {
		global $post;
		/*check which categories have posts tips and quotes*/
		$quotes_categories = get_categories(
			[
				'taxonomy'   => 'quotes_categories',
				'hide_empty' => 1,
			]
		);
		$tips_categories   = get_categories(
			[
				'taxonomy'   => 'tips_categories',
				'hide_empty' => 1,
			]
		);
		$old_post          = $post;
		?>

		<div id="qtsndtps" style="display:none;">
			<fieldset>
				<p><?php esc_html_e( 'Add Quotes and Tips block to your page or post', 'quotes-and-tips' ); ?></p>

				<label><input type="radio" name="qtsndtps_type_shortcode" value="qtsndtps_quotes_and_tips_block" checked="checked"/>
				<span class="checkbox-title">
						<?php esc_html_e( 'Quotes and Tips Block', 'quotes-and-tips' ); ?>
				</span></label><br/>

				<span class="qtsndtps_quotes_and_tips_block">
					<fieldset>
						<label class="qtsndtps_quotes">
							<input class="qtsndtps_input_quotes"type="checkbox" name="qtsndtps_select" value='[quotes_and_tips type="quotes"]'/>
							<span class="checkbox-title">
								<?php esc_html_e( 'Display quotes', 'quotes-and-tips' ); ?>
							</span>
						</label><br/>
						<select name="qtsndtps_select" class="qtsndtps_quotes_select" style="margin-top: 5px; display:none;">
							<option value='[quotes_and_tips type="quotes"]'><?php esc_html_e( 'All categories', 'quotes-and-tips' ); ?></option>
							<?php
							if ( ! empty( $quotes_categories ) ) {
								foreach ( $quotes_categories as $quotes ) {
									?>
										<option value='[quotes_and_tips type="quotes" quotes_cat_id=<?php echo esc_attr( $quotes->cat_ID ); ?>]'><?php echo esc_html( $quotes->name ); ?></option>
									<?php
								}
							}
							?>
						</select>
						<label class="qtsndtps_tips">
							<input class="qtsndtps_input_tips" type="checkbox" name="qtsndtps_select" value='[quotes_and_tips type="tips"]'/>
							<span class="checkbox-title">
								<?php esc_html_e( 'Display tips', 'quotes-and-tips' ); ?>
							</span>
						</label>
					</fieldset>
					<select name="qtsndtps_select" class="qtsndtps_tips_select" style="margin-top: 5px; display:none;">
						<option value='[quotes_and_tips type="tips"]'><?php esc_html_e( 'All categories', 'quotes-and-tips' ); ?></option>
						<?php
						if ( ! empty( $tips_categories ) ) {
							foreach ( $tips_categories as $tips ) {
								?>
									<option value='[quotes_and_tips type="tips" tips_cat_id=<?php echo esc_attr( $tips->cat_ID ); ?>]'><?php echo esc_html( $tips->name ); ?></option>
								<?php
							}
						}
						?>
					</select><br/>
				</span><hr>

				<label><input type="radio" name="qtsndtps_type_shortcode" value="qtsndtps_quote_block"/>
					<span class="checkbox-title">
						<?php esc_html_e( 'Display Quote', 'quotes-and-tips' ); ?>
				</span></label><br/>
				<span class="qtsndtps_quote_block">
					<label>
						<select name="qtsndtps_list" id="qtsndtps_quote_shortcode_list" style="max-width: 350px;">
						<?php
						$query = new WP_Query( 'post_type=quote&post_status=publish&posts_per_page=-1&order=DESC&orderby=date' );
						while ( $query->have_posts() ) {
							$query->the_post();
							if ( ! isset( $qts_first ) ) {
								$qts_first = get_the_ID();
							}
							$title = get_the_title( $post->ID );
							if ( empty( $title ) ) {
								$title = ' ( ' . __( 'no title', 'quotes-and-tips' ) . ' ) ';
							}
							?>
							<option value="<?php the_ID(); ?>"><?php echo esc_html( $title ); ?> ( <?php echo get_the_date( 'Y-m-d' ); ?> )</option>
							<?php
						}
						wp_reset_postdata();
						$post = $old_post;
						?>
						</select>
					</label><br/>
				</span><hr>

				<label><input type="radio" name="qtsndtps_type_shortcode" value="qtsndtps_tip_block"/>
				<span class="checkbox-title">
						<?php esc_html_e( 'Display Tip', 'quotes-and-tips' ); ?>
				</span></label><br/>
				<span class="qtsndtps_tip_block">
					<label>
						<select name="qtsndtps_list" id="qtsndtps_tips_shortcode_list" style="max-width: 350px;">
						<?php
						$query = new WP_Query( 'post_type=tips&post_status=publish&posts_per_page=-1&order=DESC&orderby=date' );
						while ( $query->have_posts() ) {
							$query->the_post();
							if ( ! isset( $qts_first ) ) {
								$qts_first = get_the_ID();
							}
							$title = get_the_title( $post->ID );
							if ( empty( $title ) ) {
								$title = ' ( ' . __( 'no title', 'quotes-and-tips' ) . ' ) ';
							}
							?>
							<option value="<?php the_ID(); ?>"><?php echo esc_html( $title ); ?> ( <?php echo get_the_date( 'Y-m-d' ); ?> )</option>
							<?php
						}
						wp_reset_postdata();
						$post = $old_post;
						?>
						</select>
					</label>
				</span>

				<input class="bws_default_shortcode" type="hidden" name="default" value="[quotes_and_tips]"/>
				<div class="clear"></div>
			</fieldset>
		</div>
		<script type="text/javascript">
			function qtsndtps_shortcode_init() {
				( function($) {
					let tips_cat_id   = '';
					let quotes_cat_id = '';
					$( '.qtsndtps_quote_block, .qtsndtps_tip_block' ).hide();
					$( '.mce-reset input[name = "qtsndtps_select"]' ).on( 'change', function() {
						if ( $( '.qtsndtps_quotes  input' ).is( ':checked' ) && $( '.qtsndtps_tips input' ).is( ':checked' ) ) {
								$( ' #bws_shortcode_display' ).text( '[quotes_and_tips' + quotes_cat_id + tips_cat_id + ']' );
								$( '.qtsndtps_quotes_select ' ).on( 'change', function() {
									quotes_cat_id =  ( $( this ).val().match( /quotes_cat_id=[0-9]+/ ) ) ? ' ' + $( this ).val().match( /quotes_cat_id=[0-9]+/ ) : '';
									$( ' #bws_shortcode_display' ).text( '[quotes_and_tips' + quotes_cat_id + tips_cat_id + ']' );
							} );
							$( '.qtsndtps_tips_select ' ).on( 'change', function() {
								tips_cat_id = ( $( this ).val().match( /tips_cat_id=[0-9]+/ ) ) ? ' ' + $( this ).val().match( /tips_cat_id=[0-9]+/ ) : '';
							$( ' #bws_shortcode_display' ).text( '[quotes_and_tips' + quotes_cat_id + tips_cat_id + ']' );
							} );
						} else {
							if ( $( '.qtsndtps_input_quotes' ).is( ':checked' ) ) {
								$( ' #bws_shortcode_display' ).text( '[quotes_and_tips type="quotes"' + quotes_cat_id + ']' );
								$( '.qtsndtps_quotes_select' ).on( 'change', function() {
									quotes_cat_id =  ( $( this ).val().match( /quotes_cat_id=[0-9]+/ ) ) ? ' ' + $( this ).val().match( /quotes_cat_id=[0-9]+/ ) : '';
									$( ' #bws_shortcode_display' ).text( $( this ).val() );
								} );
							} else if ( $( '.qtsndtps_input_tips' ).is( ':checked' ) ) {
								$( ' #bws_shortcode_display' ).text( '[quotes_and_tips type="tips"' + tips_cat_id + ']' );
								$( '.qtsndtps_tips_select ' ).on( 'change', function() {
									tips_cat_id = ( $( this ).val().match( /tips_cat_id=[0-9]+/ ) ) ? ' ' + $( this ).val().match( /tips_cat_id=[0-9]+/ ) : '';
									$( ' #bws_shortcode_display' ).text( $( this ).val() );
								} );
							} else $( ' #bws_shortcode_display' ).text( '[quotes_and_tips]' );
						}
					} );
					$( '.qtsndtps_input_quotes' ).click( function() {
						if ( $( '.qtsndtps_quotes_select' ).css( 'display' ) == 'none') {
							$( '.qtsndtps_quotes_select' ).css( 'display', 'block' );
						} else {
							$( '.qtsndtps_quotes_select' ).css( 'display', 'none' );
						}
					} );
					$( '.qtsndtps_input_tips' ).click( function() {
						if ( $( '.qtsndtps_tips_select' ).css( 'display' ) == 'none' ) {
							$( '.qtsndtps_tips_select' ).css( 'display', 'block' );
						} else {
							$( '.qtsndtps_tips_select' ).css( 'display', 'none' );
						}
					} );
					$( 'button[role="presentation"], button[class="mce-close"]' ).click( function () {
						$( '.qtsndtps_quotes_select' ).css( 'display', 'none' );
						$( '.qtsndtps_tips_select' ).css( 'display', 'none' );
					} );
					$( 'input[type=radio][name=qtsndtps_type_shortcode]' ).on( 'change', function() {
						$( '.qtsndtps_quotes_and_tips_block, .qtsndtps_quote_block, .qtsndtps_tip_block' ).hide();
						$( '.' + this.value ).show();
						if ( '.qtsndtps_quote_block' == this.value ) {
							$( '.mce-reset input[name = "qtsndtps_select"]' ).trigger( 'change' );
						} else if ( 'qtsndtps_quote_block' == this.value ) {
							$( ' #bws_shortcode_display' ).text( '[print_qts id="' + $( '#qtsndtps_quote_shortcode_list' ).val() + '"]' );
						} else {
							$( ' #bws_shortcode_display' ).text( '[print_tps id="' + $( '#qtsndtps_tips_shortcode_list' ).val() + '"]' );
						}
					});
					$( 'select#qtsndtps_quote_shortcode_list' ).on( 'change', function() {
						$( ' #bws_shortcode_display' ).text( '[print_qts id="' + this.value + '"]' );
					} );
					$( 'select#qtsndtps_tips_shortcode_list' ).on( 'change', function() {
						$( ' #bws_shortcode_display' ).text( '[print_tps id="' + this.value + '"]' );
					} );
				}
				)( jQuery );
			}
		</script>
		<div class="clear"></div>
		<?php
	}
}

if ( ! function_exists( 'qtsndtps_add_tabs' ) ) {
	/**
	 * Add help tab on settings page
	 */
	function qtsndtps_add_tabs() {
		$screen = get_current_screen();
		if ( ( ! empty( $screen->post_type ) && 'quote' === $screen->post_type ) ||
			( ! empty( $screen->post_type ) && 'tips' === $screen->post_type ) ||
			( isset( $_GET['page'] ) && 'quotes-and-tips.php' === $_GET['page'] ) ) {
			$args = array(
				'id'      => 'qtsndtps',
				'section' => '200538959',
			);
			bws_help_tab( $screen, $args );
		}
	}
}

if ( ! function_exists( 'qtsndtps_get_data' ) ) {
	/**
	 * Get post info by id
	 *
	 * @param mixed $qtsndtps_id Array with ids or 'all' param.
	 */
	function qtsndtps_get_data( $qtsndtps_id ) {

		$post_type = array( 'quote', 'tips' );

		$qtsndtps_posts = array();

		if ( 'all' === $qtsndtps_id || is_array( $qtsndtps_id ) ) {

			$qtsndtps_id_list = ( is_array( $qtsndtps_id ) && ! empty( $qtsndtps_id ) ) ? $qtsndtps_id : array();
			$args             = ( is_array( $qtsndtps_id ) ) ? array(
				'post_type' => $post_type,
				'include'   => $qtsndtps_id_list,
			) : array( 'post_type' => $post_type );
			$qtsndtps_posts   = get_posts( $args );

		} elseif ( is_int( $qtsndtps_id ) || is_string( $qtsndtps_id ) ) {

			$qtsndtps_int_id = is_int( $qtsndtps_id ) ? $qtsndtps_id : intval( $qtsndtps_id );
			$qtsndtps_posts  = get_post( $qtsndtps_int_id );

		}
		$qtsndtps_posts_end = array();
		foreach ( (array) $qtsndtps_posts as $key => $qtsndtps_post ) {

			$qtsndtps_meta = get_post_meta( $qtsndtps_post->ID, '' );
			unset( $qtsndtps_meta['_edit_lock'] );
			unset( $qtsndtps_meta['_edit_last'] );
			$qtsndtps_posts[ $key ]->qtsndtps_post_meta = $qtsndtps_meta;
		}

		return $qtsndtps_posts;
	}
}

if ( ! function_exists( 'qtsndtps_delete_options' ) ) {
	/**
	 * Function for delete options
	 */
	function qtsndtps_delete_options() {
		global $wpdb;
		/* Delete options */
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			$old_blog = $wpdb->blogid;
			/* Get all blog ids */
			$blogids = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );
			foreach ( $blogids as $blog_id ) {
				switch_to_blog( $blog_id );
				delete_option( 'qtsndtps_options' );
			}
			switch_to_blog( $old_blog );
		} else {
			delete_option( 'qtsndtps_options' );
		}

		require_once dirname( __FILE__ ) . '/bws_menu/bws_include.php';
		bws_include_init( plugin_basename( __FILE__ ) );
		bws_delete_plugin( plugin_basename( __FILE__ ) );
		wp_clear_scheduled_hook( 'qtsndtps_update_quotes_tips_daily' );
	}
}

if ( ! function_exists( 'qtsndtps_show_font_selector' ) ) {
	/**
	 * Function for add button select font in Classic Editor
	 *
	 * @param array $buttons All buttons array.
	 * @retrun array $buttons
	 */
	function qtsndtps_show_font_selector( $buttons ) {
		$buttons[] = 'fontselect';
		return $buttons;
	}
}

if ( ! function_exists( 'qtsndtps_quote_change_columns' ) ) {
	/**
	 * Function for change column in post type table
	 *
	 * @param array $cols All columns for post type.
	 * @retrun array $cols
	 */
	function qtsndtps_quote_change_columns( $cols ) {
		$cols = array(
			'cb'                => '<input type="checkbox" />',
			'title'             => __( 'Title', 'quotes-and-tips' ),
			'shortcode_quote'   => __( 'Shortcode', 'quotes-and-tips' ),
			'quotes_categories' => __( 'Quotes Categories', 'quotes-and-tips' ),
			'author'            => __( 'Author', 'quotes-and-tips' ),
			'date'              => __( 'Date', 'quotes-and-tips' ),
		);
		return $cols;
	}
}

if ( ! function_exists( 'qtsndtps_tips_change_columns' ) ) {
	/**
	 * Function for change column in post type table
	 *
	 * @param array $cols All columns for post type.
	 * @retrun array $cols
	 */
	function qtsndtps_tips_change_columns( $cols ) {
		$cols = array(
			'cb'              => '<input type="checkbox" />',
			'title'           => __( 'Title', 'quotes-and-tips' ),
			'shortcode_tips'  => __( 'Shortcode', 'quotes-and-tips' ),
			'tips_categories' => __( 'Tips Categories', 'quotes-and-tips' ),
			'author'          => __( 'Author', 'quotes-and-tips' ),
			'date'            => __( 'Date', 'quotes-and-tips' ),
		);
		return $cols;
	}
}

if ( ! function_exists( 'qtsndtps_custom_columns' ) ) {
	/**
	 * Function for change custom columns in post type table
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	function qtsndtps_custom_columns( $column, $post_id ) {
		$post = get_post( $post_id );
		switch ( $column ) {
			case 'shortcode_quote':
				bws_shortcode_output( '[print_qts id=' . $post->ID . ']' );
				echo '<br/>';
				break;
			case 'shortcode_tips':
				bws_shortcode_output( '[print_tps id=' . $post->ID . ']' );
				echo '<br/>';
				break;
			case 'quotes_categories':
				$terms = get_the_terms( $post->ID, 'quotes_categories' );
				$out   = '';
				if ( $terms ) {
					foreach ( $terms as $term ) {
						$out .= '' . $term->name . '</a><br />';
					}
					echo wp_kses_post( trim( $out ) );
				}
				break;
			case 'tips_categories':
				$terms = get_the_terms( $post->ID, 'tips_categories' );
				$out   = '';
				if ( $terms ) {
					foreach ( $terms as $term ) {
						$out .= '' . $term->name . '</a><br />';
					}
					echo wp_kses_post( trim( $out ) );
				}
				break;
		}
	}
}

if ( ! function_exists( 'qtsndtps_add_column' ) ) {
	/**
	 * Function for adding column in taxonomy
	 *
	 * @param array $columns All columns for taxonomy.
	 * @return array $columns
	 */
	function qtsndtps_add_column( $columns ) {
		$columns['shortcode'] = __( 'Shortcode', 'quotes-and-tips' );
		return $columns;
	}
}

if ( ! function_exists( 'qtsndtps_quotes_fill_column' ) ) {
	/**
	 * Function for filling column in taxonomy
	 *
	 * @param string $out         Custom column output.
	 * @param string $column_name Name of the column.
	 * @param int    $id          Term ID.
	 * @return string Html for column
	 */
	function qtsndtps_quotes_fill_column( $out, $column_name, $id ) {
		if ( 'shortcode' === $column_name ) {
			return bws_shortcode_output( "[quotes_and_tips type='quotes' quotes_cat_id=" . $id . ']' );
		}
	}
}

if ( ! function_exists( 'qtsndtps_tips_fill_column' ) ) {
	/**
	 * Function for filling column in taxonomy
	 *
	 * @param string $out         Custom column output.
	 * @param string $column_name Name of the column.
	 * @param int    $id          Term ID.
	 * @return array $columns
	 */
	function qtsndtps_tips_fill_column( $out, $column_name, $id ) {
		if ( 'shortcode' === $column_name ) {
			return bws_shortcode_output( "[quotes_and_tips type='tips' tips_cat_id=" . $id . ']' );
		}
	}
}

register_activation_hook( __FILE__, 'qtsndtps_plugin_activate' );

add_action( 'admin_menu', 'add_qtsndtps_admin_menu' );

add_action( 'init', 'qtsndtps_plugin_init' );
add_action( 'admin_init', 'qtsndtps_plugin_admin_init' );
add_action( 'plugins_loaded', 'qtsndtps_plugins_loaded' );

add_action( 'wp_head', 'qtsndtps_print_style_script' );
add_action( 'admin_enqueue_scripts', 'qtsndtps_wp_head' );
add_action( 'wp_enqueue_scripts', 'qtsndtps_wp_head' );
/*AJAX and connection scripts */
add_action( 'wp_ajax_qtsndtps_change_block', 'qtsndtps_change_block' );
add_action( 'wp_enqueue_scripts', 'qtsndtps_enqueue_scripts' );

add_action( 'save_post', 'qtsndtps_save_custom_quote' );

add_shortcode( 'quotes_and_tips', 'qtsndtps_create_tip_quote_block' );
add_shortcode( 'print_qts', 'qtsndtps_create_tip_quote_block' );
add_shortcode( 'print_tps', 'qtsndtps_create_tip_quote_block' );

add_action( 'qtsndtps_update_quotes_tips_daily', 'qtsndtps_update_quotes_tips' );

/* custom filter for bws button in tinyMCE */
add_filter( 'bws_shortcode_button_content', 'qtsndtps_shortcode_button_content' );
/* Additional links on the plugin page */
add_filter( 'plugin_row_meta', 'qtsndtps_register_plugin_links', 10, 2 );
/* Adds "Settings" link to the plugin action page */
add_filter( 'plugin_action_links', 'qtsndtps_plugin_action_links', 10, 2 );
/* add admin notices */
add_action( 'admin_notices', 'qtsndtps_admin_notices' );
/*add button select font in Classic Editor*/
add_filter( 'mce_buttons_2', 'qtsndtps_show_font_selector' );
/*add column of quotes categories*/
add_filter( 'manage_edit-quotes_categories_columns', 'qtsndtps_add_column' );
/*filling column in quotes category*/
add_filter( 'manage_quotes_categories_custom_column', 'qtsndtps_quotes_fill_column', 10, 3 );
/*add column of tips categories*/
add_filter( 'manage_edit-tips_categories_columns', 'qtsndtps_add_column' );
/*filling column in tips category*/
add_filter( 'manage_tips_categories_custom_column', 'qtsndtps_tips_fill_column', 10, 3 );
