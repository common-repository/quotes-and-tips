<?php
/**
 * Displays the content on the plugin settings page
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'Qtsndtps_Settings_Tabs' ) ) {
	/**
	 * Class for display Settings
	 */
	class Qtsndtps_Settings_Tabs extends Bws_Settings_Tabs {
		/**
		 * Custom search plugin options
		 *
		 * @var array
		 */
		public $cstmsrch_options;
		/**
		 * Params for background image select
		 *
		 * @var array
		 */
		public $background_image;
		/**
		 * Params for crop select
		 *
		 * @var array
		 */
		public $crop_array;
		/**
		 * Constructor.
		 *
		 * @access public
		 *
		 * @see Bws_Settings_Tabs::__construct() for more information on default arguments.
		 *
		 * @param string $plugin_basename Plugin basename.
		 */
		public function __construct( $plugin_basename ) {
			global $qtsndtps_options, $qtsndtps_plugin_info;

			$tabs = array(
				'settings'    => array( 'label' => __( 'Settings', 'quotes-and-tips' ) ),
				'appearance'  => array( 'label' => __( 'Appearance', 'quotes-and-tips' ) ),
				'misc'        => array( 'label' => __( 'Misc', 'quotes-and-tips' ) ),
				'custom_code' => array( 'label' => __( 'Custom Code', 'quotes-and-tips' ) ),
				'import-export' => array( 'label' => __( 'Import / Export', 'quotes-and-tips' ) ),
			);

			parent::__construct(
				array(
					'plugin_basename' => $plugin_basename,
					'plugins_info'    => $qtsndtps_plugin_info,
					'prefix'          => 'qtsndtps',
					'default_options' => qtsndtps_get_options_default(),
					'options'         => $qtsndtps_options,
					'tabs'            => $tabs,
					'wp_slug'         => 'quotes-and-tips',
					'doc_link'        => 'https://bestwebsoft.com/documentation/quotes-and-tips/quotes-and-tips-user-guide/',
				)
			);

			$this->all_plugins = get_plugins();

			$this->cstmsrch_options = get_option( 'cstmsrch_options' );

			$this->background_image = array(
				'none'    => __( 'None', 'quotes-and-tips' ),
				'default' => __( 'Default', 'quotes-and-tips' ),
				'custom'  => __( 'Custom', 'quotes-and-tips' ),
			);

			$this->crop_array = array(
				array( 'left', 'top' ),
				array( 'center', 'top' ),
				array( 'right', 'top' ),
				array( 'left', 'center' ),
				array( 'center', 'center' ),
				array( 'right', 'center' ),
				array( 'left', 'bottom' ),
				array( 'center', 'bottom' ),
				array( 'right', 'bottom' ),
			);

			add_action( get_parent_class( $this ) . '_display_metabox', array( $this, 'display_metabox' ) );
			if ( ! $this->is_network_options ) {
				add_action( get_parent_class( $this ) . '_additional_import_export_options', array( $this, 'additional_import_export_options' ) );
			}
		}

		/**
		 * Save plugin options to the database
		 *
		 * @access public
		 * @return array    The action results
		 */
		public function save_options() {
			$message = '';
			$notice  = '';
			$error   = '';

			$img_formats = array( 'image/png', 'image/jpg', 'image/jpeg', 'image/gif', 'video/mp4', 'video/m4v', 'video/webm', 'video/ogv', 'video/flv' );
			$max_size    = wp_max_upload_size();

			if ( ! isset( $_POST['qtsndtps_save_field'] )
				|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qtsndtps_save_field'] ) ), 'qtsndtps_save_action' )
			) {
				 esc_html_e( 'Sorry, your nonce did not verify.', 'quotes-and-tips' );
				 exit;
			} else {
				if ( isset( $_POST['qtsndtps_import_submit'] ) ) {
					$result_upload = qtsndtps_csv_upload();
					if ( false === $result_upload ) {
						$error = __( 'Error: Failed to upload the CSV file.', 'quotes-and-tips' );
					} else {
						$message = __( 'Import completed.', 'quotes-and-tips' );
					}
				} else {
					if ( ! empty( $this->cstmsrch_options ) ) {
						if ( isset( $this->cstmsrch_options['output_order'] ) ) {
							$quotes_enabled = ( isset( $_POST['qtsndtps_add_to_search']['quote'] ) ) ? 1 : 0;
							$tips_enabled   = ( isset( $_POST['qtsndtps_add_to_search']['tips'] ) ) ? 1 : 0;
							foreach ( $this->cstmsrch_options['output_order'] as $key => $search_item ) {
								if ( isset( $search_item['name'] ) && 'quote' === $search_item['name'] ) {
									$this->cstmsrch_options['output_order'][ $key ]['enabled'] = $quotes_enabled;
									$quote_exist = true;
								} elseif ( isset( $search_item['name'] ) && 'tips' === $search_item['name'] ) {
									$this->cstmsrch_options['output_order'][ $key ]['enabled'] = $tips_enabled;
									$tips_exist = true;
								}
							}
						}
						if ( ! isset( $quote_exist ) ) {
							$this->cstmsrch_options['output_order'][] = array(
								'name'    => 'quote',
								'type'    => 'post_type',
								'enabled' => $quotes_enabled,
							);
						}
						if ( ! isset( $tips_exist ) ) {
							$this->cstmsrch_options['output_order'][] = array(
								'name'    => 'tips',
								'type'    => 'post_type',
								'enabled' => $tips_enabled,
							);
						}
						update_option( 'cstmsrch_options', $this->cstmsrch_options );
					}

					if ( isset( $_FILES['qtsndtps_custom_image'] ) && isset( $_FILES['qtsndtps_custom_image']['tmp_name'] ) && isset( $_FILES['qtsndtps_custom_image']['name'] ) && ! empty( $_FILES['qtsndtps_custom_image']['name'] ) ) {

						$allowed_mime_types = get_allowed_mime_types();

						$uploaded = $_FILES['qtsndtps_custom_image'];

						$wp_filetype     = wp_check_filetype_and_ext( $uploaded['tmp_name'], $uploaded['name'], $allowed_mime_types );
						$uploaded_ext    = empty( $wp_filetype['ext'] ) ? '' : $wp_filetype['ext'];
						$uploaded_type   = empty( $wp_filetype['type'] ) ? '' : $wp_filetype['type'];
						$proper_filename = empty( $wp_filetype['proper_filename'] ) ? '' : $wp_filetype['proper_filename'];

						// Check to see if wp_check_filetype_and_ext() determined the filename was incorrect.
						if ( $proper_filename ) {
							$file['name'] = $proper_filename;
						}

						/* Image verification before uploading */
						if ( empty( $uploaded['size'] ) ) {
							$error = __( 'There is no data about the uploaded file.', 'quotes-and-tips' );
						} elseif ( ! is_uploaded_file( $uploaded['tmp_name'] ) ) {
							$error = __( 'Image/video was not uploaded by HTTP POST. Possible file upload attack.', 'quotes-and-tips' );
						} elseif ( ! in_array( $uploaded['type'], $img_formats ) ) {
							$error = __( 'Wrong file format. The image should be png, jpg(jpeg) or gif, the video should be mp4, m4v, webm, ogv or flv', 'quotes-and-tips' );
						} elseif ( $uploaded['size'] > $max_size ) {
							$error = __( 'The file size should not exceed', 'quotes-and-tips' ) . '&nbsp;' . get_human_readable_file_size( $max_size ) . '.';
						} elseif ( ( ! $uploaded_type || ! $uploaded_ext ) && ! current_user_can( 'unfiltered_upload' ) ) {
							$error = __( 'Sorry, you are not allowed to upload this file type.', 'quotes-and-tips' ) . '.';
						} else {
							$upload_dir = wp_upload_dir();
							if ( ! empty( $upload_dir['error'] ) ) {
								$error = $upload_dir['error'];
							} else {
								$upload_dir_full = $upload_dir['basedir'] . '/quotes-and-tips-image/';
								if ( ! is_dir( $upload_dir_full ) && ! wp_mkdir_p( $upload_dir_full ) ) {
									$error = __( 'Could not create image directory.', 'quotes-and-tips' );
								} else {
									$file_name = sanitize_file_name( wp_unslash( $_FILES['qtsndtps_custom_image']['name'] ) );
									$new_file = $upload_dir_full . $file_name;
									if ( ! move_uploaded_file( $_FILES['qtsndtps_custom_image']['tmp_name'], $new_file ) ) {
										$error = sprintf( __( 'The uploaded file could not be moved to %s.', 'quotes-and-tips' ), $upload_dir_full );
									} else {
										$exist_attachment = post_exists( $file_name, '', '', 'attachment' );
										$url    = $upload_dir['baseurl'] . '/quotes-and-tips-image/' . $file_name;
										$object = array(
											'ID'             => 0 === $exist_attachment ? null : $exist_attachment,
											'post_title'     => $file_name,
											'post_content'   => $url,
											'post_mime_type' => isset( $_FILES['qtsndtps_custom_image']['type'] ) ? sanitize_text_field( wp_unslash( $_FILES['qtsndtps_custom_image']['type'] ) ) : '',
											'guid'           => $url,
											'context'        => 'qtsndtp_background_image',
										);
										/* Save the data */
										$id = wp_insert_attachment( $object, $new_file );
									}
									if ( ! $id ) {
										$error = __( 'Could not save background image/video file to WordPress media library.', 'quotes-and-tips' );
									} else {
										$images = get_posts(
											array(
												'post_type'  => 'attachment',
												'meta_key'   => '_wp_attachment_qtsndtp_background_image',
												'meta_value' => get_option( 'stylesheet' ),
												'orderby'    => 'none',
												'nopaging'   => true,
											)
										);
										if ( ! empty( $images ) && $images[0]->post_content !== $url ) {
											wp_delete_attachment( $images[0]->ID );
										}
										update_post_meta( $id, '_wp_attachment_qtsndtp_background_image', get_option( 'stylesheet' ) );
										$this->options['custom_background_image'] = $url;
									}
								}
							}
						}
					}

					$this->options['page_load']                 = isset( $_POST['qtsndtps_page_load'] ) ? sanitize_text_field( wp_unslash( $_POST['qtsndtps_page_load'] ) ) : '1';
					$this->options['interval_load']             = isset( $_POST['qtsndtps_interval_load'] ) ? absint( $_POST['qtsndtps_interval_load'] ) : 10;
					$this->options['tip_label']                 = isset( $_POST['qtsndtps_tip_label'] ) ? sanitize_text_field( wp_unslash( $_POST['qtsndtps_tip_label'] ) ) : '';
					$this->options['quote_label']               = isset( $_POST['qtsndtps_quote_label'] ) ? sanitize_text_field( wp_unslash( $_POST['qtsndtps_quote_label'] ) ) : '';
					$this->options['title_post']                = isset( $_POST['qtsndtps_title_post'] ) ? sanitize_text_field( wp_unslash( $_POST['qtsndtps_title_post'] ) ) : '';
					$this->options['additional_options']        = isset( $_POST['qtsndtps_additional_options'] ) ? 1 : 0;
					$this->options['remove_quatation']          = isset( $_POST['qtsndtps_remove_quatation'] ) ? 1 : 0;
					$this->options['background_color']          = isset( $_POST['qtsndtps_background_color'] ) ? sanitize_text_field( wp_unslash( $_POST['qtsndtps_background_color'] ) ) : $this->options['background_color'];
					$this->options['text_color']                = isset( $_POST['qtsndtps_text_color'] ) ? sanitize_text_field( wp_unslash( $_POST['qtsndtps_text_color'] ) ) : $this->options['text_color'];
					$this->options['text_size']                 = isset( $_POST['qtsndtps_text_size'] ) ? absint( $_POST['qtsndtps_text_size'] ) : 14;
					$this->options['title_text_size']           = isset( $_POST['qtsndtps_title_text_size'] ) ? absint( $_POST['qtsndtps_title_text_size'] ) : 22;
					$this->options['background_image']          = isset( $_POST['qtsndtps_background_image'] ) && array_key_exists( sanitize_text_field( wp_unslash( $_POST['qtsndtps_background_image'] ) ), $this->background_image ) ? sanitize_text_field( wp_unslash( $_POST['qtsndtps_background_image'] ) ) : $this->options['background_image'];
					$this->options['background_image_repeat_x'] = isset( $_POST['qtsndtps_background_image_repeat_x'] ) ? 1 : 0;
					$this->options['background_image_repeat_y'] = isset( $_POST['qtsndtps_background_image_repeat_y'] ) ? 1 : 0;
					$this->options['background_image_cover']    = isset( $_POST['qtsndtps_background_image_cover'] ) ? 1 : 0;
					$this->options['author_position']           = isset( $_POST['qtsndtps_author_position'] ) ? sanitize_text_field( wp_unslash( $_POST['qtsndtps_author_position'] ) ) : '';
					$this->options['background_image_position'] = isset( $_POST['qtsndtps_background_image_position'] ) ? $this->crop_array[ absint( $_POST['qtsndtps_background_image_position'] ) ] : array( 'left', 'bottom' );
					$this->options['background_opacity']        = isset( $_POST['qtsndtps_background_opacity'] ) ? floatval( $_POST['qtsndtps_background_opacity'] ) : 1;
					$this->options['border_radius']             = isset( $_POST['qtsndtps_border_radius'] ) ? absint( $_POST['qtsndtps_border_radius'] ) : 0;
					$this->options['box_shadow_offset_x']       = isset( $_POST['qtsndtps_box_shadow_offset_x'] ) ? sanitize_text_field( wp_unslash( $_POST['qtsndtps_box_shadow_offset_x'] ) ) : '';
					$this->options['box_shadow_offset_y']       = isset( $_POST['qtsndtps_box_shadow_offset_y'] ) ? sanitize_text_field( wp_unslash( $_POST['qtsndtps_box_shadow_offset_y'] ) ) : '';
					$this->options['box_shadow_blur_radius']    = isset( $_POST['qtsndtps_box_shadow_blur_radius'] ) ? sanitize_text_field( wp_unslash( $_POST['qtsndtps_box_shadow_blur_radius'] ) ) : '';
					$this->options['box_shadow_color']          = isset( $_POST['qtsndtps_box_shadow_color'] ) ? sanitize_text_field( wp_unslash( $_POST['qtsndtps_box_shadow_color'] ) ) : $this->options['box_shadow_color'];
					$this->options['block_width']               = isset( $_POST['qtsndtps_block_width'] ) ? absint( $_POST['qtsndtps_block_width'] ) : 100;
					$this->options['block_height']              = isset( $_POST['qtsndtps_block_height'] ) ? absint( $_POST['qtsndtps_block_height'] ) : 100;
					$this->options['button_text']               = isset( $_POST['qtsndtps_button_text'] ) ? sanitize_text_field( wp_unslash( $_POST['qtsndtps_button_text'] ) ) : $this->options['button_text'];

					if ( is_plugin_active( 'sender-pro/sender-pro.php' ) ) {
						$sndr_options = get_option( 'sndr_options' );
						/* mailout when publishing quote */
						if ( isset( $_POST['qtsndtps_sndr_mailout_quote'] ) ) {
							$key = array_search( 'quote', $sndr_options['automailout_new_post'] );
							if ( false !== $key ) {
								$sndr_options['automailout_new_post'][]             = 'quote';
								$sndr_options['group_for_post']['quote']            = isset( $_POST['sndr_distribution_select']['quote'] ) ? absint( $_POST['sndr_distribution_select']['quote'] ) : '';
								$sndr_options['letter_for_post']['quote']           = isset( $_POST['sndr_templates_select']['quote'] ) ? absint( $_POST['sndr_templates_select']['quote'] ) : '';
								$sndr_options['priority_for_post_letters']['quote'] = isset( $_POST['sndr_priority']['quote'] ) ? absint( $_POST['sndr_priority']['quote'] ) : '';
							}
						} else {
							$key = array_search( 'quote', $sndr_options['automailout_new_post'] );
							if ( false !== $key ) {
								unset( $sndr_options['automailout_new_post'][ $key ] );
								unset( $sndr_options['priority_for_post_letters']['quote'] );
								unset( $sndr_options['letter_for_post']['quote'] );
								unset( $sndr_options['group_for_post']['quote'] );
							}
						}
						/* mailout when publishing tips */
						if ( isset( $_POST['qtsndtps_sndr_mailout_tips'] ) ) {
							$key = array_search( 'tips', $sndr_options['automailout_new_post'] );
							if ( false !== $key ) {
								$sndr_options['automailout_new_post'][]            = 'tips';
								$sndr_options['group_for_post']['tips']            = isset( $_POST['sndr_distribution_select']['tips'] ) ? absint( $_POST['sndr_distribution_select']['tips'] ) : '';
								$sndr_options['letter_for_post']['tips']           = isset( $_POST['sndr_templates_select']['tips'] ) ? absint( $_POST['sndr_templates_select']['tips'] ) : '';
								$sndr_options['priority_for_post_letters']['tips'] = isset( $_POST['sndr_priority']['tips'] ) ? absint( $_POST['sndr_priority']['tips'] ) : '';
							}
						} else {
							$key = array_search( 'tips', $sndr_options['automailout_new_post'] );
							if ( false !== $key ) {
								unset( $sndr_options['automailout_new_post'][ $key ] );
								unset( $sndr_options['priority_for_post_letters']['tips'] );
								unset( $sndr_options['letter_for_post']['tips'] );
								unset( $sndr_options['group_for_post']['tips'] );
							}
						}
						update_option( 'sndr_options', $sndr_options );
					}

					if ( empty( $error ) ) {
						update_option( 'qtsndtps_options', $this->options );
						$message = __( 'Settings saved.', 'quotes-and-tips' );
					}
				}
			}
			return compact( 'message', 'notice', 'error' );
		}

		/**
		 * Displays 'settings' menu-tab
		 *
		 * @access public
		 */
		public function tab_settings() {
			if ( is_plugin_active( 'sender-pro/sender-pro.php' ) ) {
				$sndr_options = get_option( 'sndr_options' );
			} ?>
			<h3 class="bws_tab_label"><?php esc_html_e( 'Quotes and Tips Settings', 'quotes-and-tips' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Random Change on', 'quotes-and-tips' ); ?></th>
					<td>
						<fieldset>
							<label><input type="radio" class="bws_option_affect" data-affect-hide=".qtsndtps_change_frequency" name="qtsndtps_page_load" value="1"<?php checked( $this->options['page_load'] ); ?> /> <?php esc_html_e( 'Page reload', 'quotes-and-tips' ); ?></label><br />
							<label><input type="radio" class="bws_option_affect" data-affect-hide=".qtsndtps_change_frequency" name="qtsndtps_page_load" value="2"<?php checked( '2', $this->options['page_load'] ); ?> /> <?php esc_html_e( 'Once a day', 'quotes-and-tips' ); ?></label><br /> 
							<label><input type="radio" class="bws_option_affect" data-affect-show=".qtsndtps_change_frequency" name="qtsndtps_page_load" value="3" <?php checked( '3', $this->options['page_load'] ); ?> /> <?php esc_html_e( 'Button for changing quotes', 'quotes-and-tips' ); ?></label><br />
							<label><input type="radio" class="bws_option_affect" data-affect-show=".qtsndtps_change_frequency" name="qtsndtps_page_load" value="0"<?php checked( '0', $this->options['page_load'] ); ?> /> <?php esc_html_e( 'AJAX (no page reload)', 'quotes-and-tips' ); ?></label>
						</fieldset>
					</td>
				</tr>
				<tr valign="top" class="qtsndtps_change_frequency">
					<th scope="row"><?php esc_html_e( 'Change Frequency', 'quotes-and-tips' ); ?></th>
					<td>
						<label><input type="number" name="qtsndtps_interval_load" min="1" max="999" step="1" value="<?php echo esc_attr( $this->options['interval_load'] ); ?>" style="width:55px" /> <?php esc_html_e( 'sec', 'quotes-and-tips' ); ?></label>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Title Options', 'quotes-and-tips' ); ?></th>
					<td>
						<fieldset>
							<label><input type="radio" name="qtsndtps_title_post" value="1" class="qtsndtps_title_post"<?php checked( $this->options['title_post'] ); ?> /> <?php esc_html_e( 'Set Title From Post', 'quotes-and-tips' ); ?></label><br />
							<label><input type="radio" name="qtsndtps_title_post" value="0" class="qtsndtps_title_post"<?php checked( '0', $this->options['title_post'] ); ?> /> <?php esc_html_e( 'Set Custom Titles', 'quotes-and-tips' ); ?></label>
						</fieldset>
					</td>
				</tr>
				<tr valign="top" class="qtsndtps_title_post_fields">
					<th scope="row"><?php esc_html_e( 'Tip Title', 'quotes-and-tips' ); ?></th>
					<td>
						<input type="text" name="qtsndtps_tip_label" maxlength="250" value="<?php echo esc_html( $this->options['tip_label'] ); ?>" />
					</td>
				</tr>
				<tr valign="top" class="qtsndtps_title_post_fields">
					<th scope="row"><?php esc_html_e( 'Quote Title', 'quotes-and-tips' ); ?></th>
					<td>
						<input type="text" name="qtsndtps_quote_label" maxlength="250" value="<?php echo esc_html( $this->options['quote_label'] ); ?>" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Add Quotes and Tips to the Search', 'quotes-and-tips' ); ?></th>
					<td>
						<?php
						if ( array_key_exists( 'custom-search-plugin/custom-search-plugin.php', $this->all_plugins ) || array_key_exists( 'custom-search-pro/custom-search-pro.php', $this->all_plugins ) ) {
							if ( is_plugin_active( 'custom-search-plugin/custom-search-plugin.php' ) || is_plugin_active( 'custom-search-pro/custom-search-pro.php' ) ) {
								$custom_search_admin_url = ( is_plugin_active( 'custom-search-plugin/custom-search-plugin.php' ) ) ? 'custom_search.php' : 'custom_search_pro.php';
								?>
								<span class="bws_info">
									<?php
									printf(
										esc_html__( 'Go to %s Settings to include quotes and tips to your website search', 'quotes-and-tips' ),
										'<a href="' . esc_url( admin_url( 'admin.php?page=' . $custom_search_admin_url ) ) . '">Custom Search</a>'
									);
									?>
								</span>									
							<?php } else { ?>
									<input disabled="disabled" type="checkbox" name="qtsndtps_add_to_search[]" value="1" />
									<span class="bws_info"><?php esc_html_e( 'Enable to include quotes and tips to your website search. Custom Search plugin is required.', 'quotes-and-tips' ); ?> <a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>"><?php esc_html_e( 'Activate Now', 'quotes-and-tips' ); ?></a></span><br />
								<?php
							}
						} else {
							?>
							<input disabled="disabled" type="checkbox" name="qtsndtps_add_to_search[]" value="1" />
							<span class="bws_info"><?php esc_html_e( 'Enable to include quotes and tips to your website search. Custom Search plugin is required.', 'quotes-and-tips' ); ?> <a href="https://bestwebsoft.com/products/wordpress/plugins/custom-search/"><?php esc_html_e( 'Install Now', 'quotes-and-tips' ); ?></a></span><br />
						<?php } ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Automatic Mailout when Publishing a New:', 'quotes-and-tips' ); ?></th>
					<td>
						<?php
						if ( array_key_exists( 'sender-pro/sender-pro.php', $this->all_plugins ) ) {
							if ( is_plugin_active( 'sender-pro/sender-pro.php' ) ) {
								?>
								<span class="bws_info">
									<?php
									printf(
										esc_html__( 'Go to %s Settings to include quotes and tips to automatic mailout when publishing a new quotes and tips', 'quotes-and-tips' ),
										'<a href="' . esc_url( admin_url( 'admin.php?page=sndrpr_settings' ) ) . '">Sender Pro</a>'
									);
									?>
								</span>
							<?php } else { ?> 
								<input disabled="disabled" type="checkbox" name="qtsndtps_sndr_mailout" />&nbsp
								<span class="bws_info"><?php esc_html_e( 'Enable to automatic mailout when publishing a new quotes and tips. Sender Pro plugin is required.', 'quotes-and-tips' ); ?> <a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>"><?php esc_html_e( 'Ativate Now', 'quotes-and-tips' ); ?></a></span><br />
								<?php
							}
						} else {
							?>
							<input disabled="disabled" type="checkbox" name="qtsndtps_sndr_mailout" />&nbsp
							<span class="bws_info"><?php esc_html_e( 'Enable to automatic mailout when publishing a new quotes and tips. Sender Pro plugin is required.', 'quotes-and-tips' ); ?> <a href="https://bestwebsoft.com/products/wordpress/plugins/sender/"><?php esc_html_e( 'Install Now', 'quotes-and-tips' ); ?></a></span><br />
						<?php } ?>	
					</td>
				</tr>
					<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Remove Quatation Marks', 'quotes-and-tips' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input class="bws_option_affect" data-affect-show="[data-post-type=tips]" name='qtsndtps_remove_quatation' type='checkbox' value='1' <?php checked( $this->options['remove_quatation'] ); ?> />
								<span class="bws_info"><?php esc_html_e( 'Enable to remove quatation marks.', 'quotes-and-tips' ); ?> </span><br />
							</label><br />
						</fieldset>
					</td>
				</tr>
			</table>
			<?php
			wp_nonce_field( 'qtsndtps_save_action', 'qtsndtps_save_field' );
		}

		/**
		 * Displays 'appearance' menu-tab
		 *
		 * @access public
		 */
		public function tab_appearance() {
			$max_size    = wp_max_upload_size();
			?>
			<h3 class="bws_tab_label"><?php esc_html_e( 'Appearance Settings', 'quotes-and-tips' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Custom Styles', 'quotes-and-tips' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="qtsndtps_additional_options" id="qtsndtps_additional_options" class="bws_option_affect" data-affect-show="#qtsndtps_display_one_line" value="1"<?php checked( $this->options['additional_options'] ); ?> />
							<span class="bws_info"><?php esc_html_e( 'Enable to apply custom styles.', 'quotes-and-tips' ); ?></span>
					</label>
					</td>
				</tr>
			</table>
			<table class="form-table" id="qtsndtps_display_one_line">
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Background Color', 'quotes-and-tips' ); ?></th>
					<td>
						<input type="text" value="<?php echo esc_attr( $this->options['background_color'] ); ?>" name="qtsndtps_background_color" class="qtsndtps_color_field" data-default-color="#2484C6" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Text Color', 'quotes-and-tips' ); ?></th>
					<td>
						<input type="text" value="<?php echo esc_attr( $this->options['text_color'] ); ?>" name="qtsndtps_text_color" class="qtsndtps_color_field" data-default-color="#FFFFFF" />
					</td>
				</tr>
				<tr class="qtsndtps_hidden">
					<th><?php esc_html_e( 'Title Text Size', 'quotes-and-tips' ); ?></th>
					<td>
						<input class="small-text" name="qtsndtps_title_text_size" type="text" id="qtsndtps_title_text_size" value="<?php echo esc_attr( $this->options['title_text_size'] ); ?>" />
						<div id="qtsndtps_slider_title_text_size"></div>
					</td>
				</tr>
				<tr class="qtsndtps_hidden">
					<th><?php esc_html_e( 'Text Size', 'quotes-and-tips' ); ?></th>
					<td>
						<input class="small-text" name="qtsndtps_text_size" type="text" id="qtsndtps_text_size" value="<?php echo esc_attr( $this->options['text_size'] ); ?>" />
						<div id="qtsndtps_slider_text_size"></div>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Background Image/Video', 'quotes-and-tips' ); ?></th>
					<td>
						<fieldset>
							<?php foreach ( $this->background_image as $key => $value ) { ?>
								<label>
									<?php
									printf(
										'<input type="radio" class="qtsndtps_additions_block qtsndtps_background_image " name="qtsndtps_background_image" value="%s" %s />%s',
										esc_attr( $key ),
										checked( ( $key === $this->options['background_image'] ), true, false ),
										esc_html( $value )
									);
									?>
								</label><br>
								<?php
							}
							$opacity_background = $this->options['background_opacity'];
							?>
							<input type="file" class="qtsndtps_additions_block" name="qtsndtps_custom_image" id="qtsndtps_custom_file" /><br />
							<span class="bws_info"><?php echo esc_html__( 'The image should be png, jpg(jpeg) or gif, the video should be mp4, m4v, webm, ogv or flv. The file size should not exceed', 'quotes-and-tips' ) . '&nbsp;' . esc_attr( get_human_readable_file_size( $max_size ) ); ?></span>
							<div class="qtsndtps_current_image">
								<span><?php esc_html_e( 'Current Background', 'quotes-and-tips' ); ?></span><br /><br />
								<?php
								$format = substr( $this->options['custom_background_image'], strrpos( $this->options['custom_background_image'], '.' ), strlen( $this->options['custom_background_image'] ) - strrpos( $this->options['custom_background_image'], '.' ) );
								switch ( $format ) {
									case '.png':
									case '.jpg':
									case '.jpeg':
									case '.gif':
										?>
										<div class="qtsndtps_custom_image">
											<img src="<?php echo esc_url( $this->options['custom_background_image'] ); ?>" alt="" title="" style="max-width: 300px; height: 200px; opacity: <?php echo esc_attr( $opacity_background ); ?>" />
										</div>
										<?php
										break;

									case '.mp4':
										?>
										<div class="qtsndtps_custom_image">
											<video playsinline autoplay muted loop poster="cake.jpg">
												<source src="<?php echo esc_url( $this->options['custom_background_image'] ); ?>">
												<?php esc_html_e( 'Your browser does not support the video tag', 'quotes-and-tips' ); ?>.
											</video>
										</div>
										<?php
										break;
								}
								?>
								<div class="qtsndtps_default_image">
									<img src="<?php echo esc_url( plugins_url( '/quotes-and-tips/images/quotes_box_and_tips_bg.png' ) ); ?>" alt="" title="" style="border: 1px solid grey; max-width: 100%; height: auto; opacity: <?php echo esc_attr( $opacity_background ); ?>" />
								</div>
							</div>
						</fieldset>
					</td>
				</tr>
				<tr class="qtsndtps_hidden">
					<th><?php esc_html_e( 'Background Image Opacity', 'quotes-and-tips' ); ?></th>
					<td>
						<input class="small-text" name="qtsndtps_background_opacity" type="text" id="qtsndtps_background_opacity" value="<?php echo ! empty( $this->options['background_opacity'] ) && 0 !== floatval( $this->options['background_opacity'] ) ? esc_attr( $this->options['background_opacity'] ) : ''; ?>" />
						<div id="qtsndtps_slider"></div>
					</td>
				</tr>
				<tr class="qtsndtps_hidden">
					<th scope="row"><?php esc_html_e( 'Background Image Repeat', 'quotes-and-tips' ); ?> </th>
					<td>
						<fieldset>
							<label><input type="checkbox" class="qtsndtps_additions_block" name="qtsndtps_background_image_repeat_x" value="1" <?php checked( $this->options['background_image_repeat_x'] ); ?> <?php disabled( isset( $this->options['background_image_cover'] ) && $this->options['background_image_cover'] ); ?> /> <?php esc_html_e( 'Horizontal Repeat (x)', 'quotes-and-tips' ); ?></label><br />
							<label><input type="checkbox" class="qtsndtps_additions_block" name="qtsndtps_background_image_repeat_y" value="1" <?php checked( $this->options['background_image_repeat_y'] ); ?> <?php disabled( isset( $this->options['background_image_cover'] ) && $this->options['background_image_cover'] ); ?> /> <?php esc_html_e( 'Vertical Repeat (y)', 'quotes-and-tips' ); ?></label><br />
							<label><input type="checkbox" class="qtsndtps_additions_block qtsndtps_background_image_cover" name="qtsndtps_background_image_cover" value="1" <?php checked( isset( $this->options['background_image_cover'] ) && $this->options['background_image_cover'] ); ?> /> <?php esc_html_e( 'Cover', 'quotes-and-tips' ); ?></label>
						</fieldset>
					</td>
				</tr>
				<tr class="qtsndtps_hidden">
					<th scope="row"><?php esc_html_e( 'Background Image Alignment', 'quotes-and-tips' ); ?> </th>
					<td>
						<fieldset>
							<?php
							$i = 0;
							while ( $i < 9 ) {
								?>
								<label><input type="radio" class="qtsndtps_background_image_position" name="qtsndtps_background_image_position" value="<?php echo esc_attr( $i ); ?>" <?php checked( $this->crop_array[ $i ] === $this->options['background_image_position'] ); ?> <?php disabled( isset( $this->options['background_image_cover'] ) && $this->options['background_image_cover'] ); ?> /></label>
								<?php
								if ( 0 === ( ( $i + 1 ) % 3 ) ) {
									echo '<br />';
								}
								$i++;
							}
							?>
						</fieldset>
					</td>
				</tr>
				<tr class="qtsndtps_hidden">
					<th scope="row"><?php esc_html_e( 'Author position', 'quotes-and-tips' ); ?></th>
					<td>
						<fieldset>
							<label><input type="radio" name="qtsndtps_author_position" value="0" class="qtsndtps_author_position"<?php checked( '0', $this->options['author_position'] ); ?> /> <?php esc_html_e( 'Left', 'quotes-and-tips' ); ?></label><br />
							<label><input type="radio" name="qtsndtps_author_position" value="1" class="qtsndtps_author_position"<?php checked( $this->options['author_position'] ); ?> /> <?php esc_html_e( 'Right', 'quotes-and-tips' ); ?></label><br/>
						</fieldset>
					</td>
				</tr>
				<tr class="qtsndtps_hidden">
					<th><?php esc_html_e( 'Border Radius', 'quotes-and-tips' ); ?></th>
					<td>
						<input class="small-text" name="qtsndtps_border_radius" type="text" id="qtsndtps_border_radius" value="<?php echo esc_attr( $this->options['border_radius'] ); ?>" />
						<div id="qtsndtps_slider_border_radius"></div>
					</td>
				</tr>
				<tr class="qtsndtps_hidden">
					<th><?php esc_html_e( 'Box Shadow Size', 'quotes-and-tips' ); ?></th>
					<td>
						<fieldset>
							<label><input class="small-text" name="qtsndtps_box_shadow_offset_x" type="number" id="qtsndtps_box_shadow_offset_x" value="<?php echo esc_attr( $this->options['box_shadow_offset_x'] ); ?>" /> <?php esc_html_e( 'Offset x', 'quotes-and-tips' ); ?> (px)</label><br />
							<label><input class="small-text" name="qtsndtps_box_shadow_offset_y" type="number" id="qtsndtps_box_shadow_offset_y" value="<?php echo esc_attr( $this->options['box_shadow_offset_y'] ); ?>" /> <?php esc_html_e( 'Offset y', 'quotes-and-tips' ); ?> (px)</label><br />
							<label><input class="small-text" name="qtsndtps_box_shadow_blur_radius" type="number" id="qtsndtps_box_shadow_blur_radius" value="<?php echo esc_attr( $this->options['box_shadow_blur_radius'] ); ?>" /> <?php esc_html_e( 'Blur radius', 'quotes-and-tips' ); ?> (px)</label>
						</fieldset>
					</td>
				</tr>
				<tr class="qtsndtps_hidden">
					<th scope="row"><?php esc_html_e( 'Box Shadow Color', 'quotes-and-tips' ); ?></th>
					<td>
						<input type="text" value="<?php echo esc_attr( $this->options['box_shadow_color'] ); ?>" name="qtsndtps_box_shadow_color" class="qtsndtps_box_shadow_color" data-default-color="#FFFFFF" />
					</td>
				</tr>
				<tr class="qtsndtps_hidden">
					<th><?php esc_html_e( 'Block Size' ); ?></th>
					<td>
						<fieldset>
							<label><input class="small-text" name="qtsndtps_block_width" type="text" id="qtsndtps_block_width" value="<?php echo esc_attr( $this->options['block_width'] ); ?>" /> <?php esc_html_e( 'Width', 'quotes-and-tips' ); ?> (%)<br />
							<span class="bws_info"><?php echo esc_html__( 'Pay attention! In some themes this option may not work.', 'quotes-and-tips' ); ?></span></label><br />
							<label><input class="small-text" name="qtsndtps_block_height" type="text" id="qtsndtps_block_height" value="<?php echo esc_attr( $this->options['block_height'] ); ?>" /> <?php esc_html_e( 'Height', 'quotes-and-tips' ); ?> (px)</label><br />
						</fieldset>
					</td>
				</tr>
				<tr class="qtsndtps-button-text <?php echo 3 !== $this->options['page_load'] ? 'hidden' : ''; ?>">
					<th><?php esc_html_e( 'Button Text', 'quotes-and-tips' ); ?></th>
					<td>
						<input class="text" name="qtsndtps_button_text" type="text" id="qtsndtps_button_text" value="<?php echo esc_attr( $this->options['button_text'] ); ?>" /></br>
						<div class="bws_info"><?php esc_html_e( 'If the ability to change quotes is enabled using the button.', 'quotes-and-tips' ); ?></div>
					</td>
				</tr>
			</table>
			<?php
		}

		/**
		 * Displays 'import export' menu-tab
		 *
		 * @access public
		 */
		public function additional_import_export_options() {
			?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Export to CSV', 'quotes-and-tips' ); ?></th>
					<td>
						<input type="submit" name="qtsndtps_export_submit" class="button-secondary" value="<?php esc_html_e( 'Export Now', 'quotes-and-tips' ); ?>" />
						<?php wp_nonce_field( 'qtsndtps_export_action', 'qtsndtps_export_field' ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Import to CSV', 'quotes-and-tips' ); ?></th>
					<td>
						<input type="file" name="qtsndtps_csv_file">
						<input type="submit" name="qtsndtps_import_submit" class="button-secondary" value="<?php esc_html_e( 'Import Now', 'quotes-and-tips' ); ?>" />
						<?php wp_nonce_field( 'qtsndtps_import_action', 'qtsndtps_import_field' ); ?>
					</td>
				</tr>
			</table>
			<?php
		}

		/**
		 * Display custom metabox
		 *
		 * @access public
		 */
		public function display_metabox() {
			?>
			<div class="postbox">
				<h3 class="hndle">
					<?php esc_html_e( 'Quotes and Tips', 'quotes-and-tips' ); ?>
				</h3>
				<div class="inside">
					<?php esc_html_e( 'Add Quotes and Tips block to your page or post by using the following shortcode:', 'quotes-and-tips' ); ?>
					<?php bws_shortcode_output( '[quotes_and_tips]' ); ?>
					<p><?php esc_html_e( 'Or add the following strings into the template source code', 'quotes-and-tips' ); ?>:</p>
					<code>&#60;?php if ( function_exists( 'qtsndtps_get_random_tip_quote' ) ) qtsndtps_get_random_tip_quote(); ?>&#62;</code>
				</div>
			</div>
			<?php
		}
	}
}
