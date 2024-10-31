(function($){
	$(document).ready( function() {
		if ( $.fn.wpColorPicker ) {
			$( '.qtsndtps_color_field' ).wpColorPicker();
			$( '.qtsndtps_box_shadow_color' ).wpColorPicker();
		};

		if( $( '.qtsndtps_background_image:checked' ).val() != 'custom' ) {
			$( '#qtsndtps_custom_image' ).hide();
		}

		$( 'input[name="qtsndtps_page_load"]' ).change( function() {
			if ( '3' === $(this).val() ) {
				$( '.qtsndtps-button-text' ).show();
			} else {
				$( '.qtsndtps-button-text' ).hide();
			}
		});

		function qtsndtps_background_image() {
			if ( $( 'input[name="qtsndtps_background_image"]:checked' ).val() == 'custom' ) {
				if ($('.qtsndtps_custom_image img' ).attr('src') === '') {
					$( '#qtsndtps_custom_file, .qtsndtps_hidden' ).show();
					$( '.qtsndtps_default_image, .qtsndtps_current_image, .qtsndtps_custom_image' ).hide(); }
				else {
					$('#qtsndtps_custom_file, .qtsndtps_hidden, .qtsndtps_current_image, .qtsndtps_custom_image').show();
					$('.qtsndtps_default_image').hide();
				};
			} else if ( $( 'input[name="qtsndtps_background_image"]:checked' ).val() == 'none' ) {
				$( '#qtsndtps_custom_file, .qtsndtps_hidden, .qtsndtps_current_image, .qtsndtps_custom_image' ).hide();
			} else {				
				$( '#qtsndtps_custom_file, .qtsndtps_custom_image' ).hide();
				$( '.qtsndtps_hidden, .qtsndtps_default_image, .qtsndtps_current_image' ).show();
			}
		};
		if ( $( 'input[name="qtsndtps_background_image"]' ).length ) {
			qtsndtps_background_image();
			$( 'input[name="qtsndtps_background_image"]' ).change( function() { qtsndtps_background_image() });
		}

		if( $( '#qtsndtps_additional_options' ).is( ':checked' ) ) {
			$( '#qtsndtps-text-color-example, #qtsndtps-link-color-example' ).hide();
		}

		if( 0 < $( '#qtsndtps_slider' ).length ) {
			$( '#qtsndtps_slider' ).slider({
				range: 'min',
				min: 0.1,
				max: 1,
				step: 0.1,
				value: $( '#qtsndtps_background_opacity' ).val(),
				slide: function( event, ui ) {
					$( '#qtsndtps_background_opacity' ).val( ui.value );
				}
			});
		}
		if( 0 < $( '#qtsndtps_slider_border_radius' ).length ) {
			$( '#qtsndtps_slider_border_radius' ).slider({
				range: 'min',
				min: 0,
				max: 50,
				step: 1,
				value: $( '#qtsndtps_border_radius' ).val(),
				slide: function( event, ui ) {
					$( '#qtsndtps_border_radius' ).val( ui.value );
				}
			});
		}
		if( 0 < $( '#qtsndtps_slider_text_size' ).length ) {
			$( '#qtsndtps_slider_text_size' ).slider({
				range: 'min',
				min: 10,
				max: 30,
				step: 1,
				value: $( '#qtsndtps_text_size' ).val(),
				slide: function( event, ui ) {
					$( '#qtsndtps_text_size' ).val( ui.value );
				}
			});
		}
		if( 0 < $( '#qtsndtps_slider_title_text_size' ).length ) {
			$( '#qtsndtps_slider_title_text_size' ).slider({
				range: 'min',
				min: 15,
				max: 40,
				step: 1,
				value: $( '#qtsndtps_title_text_size' ).val(),
				slide: function( event, ui ) {
					$( '#qtsndtps_title_text_size' ).val( ui.value );
				}
			});
		}

		if ( $( '.qtsndtps_title_post:checked' ).val() == '1' ) {
			$( '.qtsndtps_title_post_fields' ).hide();
		}

		$( '.qtsndtps_title_post' ).change( function() {
			if ( $( this ).is( ':checked' ) && $( this ).val() == '1' ) {
				$( '.qtsndtps_title_post_fields' ).hide();
			} else if ( $( this ).is( ':checked' ) && $( this ).val() == '0' ) {
				$( '.qtsndtps_title_post_fields' ).show();
			}
		});

		$( '#qtsndtps-link-color-example, #qtsndtps-text-color-example' ).on( "click", function() {
			if ( typeof bws_show_settings_notice == 'function' ) {
				bws_show_settings_notice();
			}
		});

		$( '.qtsndtps_background_image_cover' ).change( function(){
			if ( $( this ).is( ':checked' ) ) {
				$( '.qtsndtps_additions_block:not(.qtsndtps_background_image_cover)' ).attr( 'disabled', 'disabled' );
				$( '.qtsndtps_background_image_position' ).attr( 'disabled', 'disabled' );
			} else {
				$( '.qtsndtps_additions_block:not(.qtsndtps_background_image_cover)' ).removeAttr( 'disabled' );
				$( '.qtsndtps_background_image_position' ).removeAttr( 'disabled' );
			}
		});
	});
})(jQuery);