<?php

add_shortcode('cc_form', 'cc_form_cb');
function cc_form_cb(){
	
	$html .= '<form id="cc_form" action="#" method="post">';
	$html .= '<p>' . __("If To and From fields contain multiple value then must be separated with comma.") .'</p>';

	$html .= '<p> <label for="cc_from">'. __("From") .'</label>' . 
			'<input type="text" id="cc_from" name="cc_from" value="BTC,ETH,XMR" placeholder="'. __('From Symbols: ex. BTC,ETH,XMR') .'" required></p>';

	$html .= '<p> <label for="cc_to">'. __('To') .'</label>'. 
			'<input type="text" id="cc_to" name="cc_to" value="USD" placeholder="'. __('To Symbols: ex. USD,GBP') .'" required></p>';

	$html .= '<p> <label for="cc_showchange">'. __('Show Change') .'</label>'.
			'<select id="cc_showchange" name="cc_showchange">'.
			'<option value="0">'. __('False') .'</option>'.
			'<option value="1">'. __('True') .'</option>'.
			'</select>'.
			'</p>';

	$html .= '<p><input type="submit" name="submit" value="'. __('Submit') .'"></p>';

	$html .= '<input type="hidden" name="cc_nonce" value="'. wp_create_nonce("cc_nonce") .'">';
	$html .= '<input type="hidden" name="action" value="cc_form_process">';

	$html .= '</form>';
	$html .= '<div class="spinner_loader"></div>';
	$html .= '<div class="remote_response"></div>';
	
	return $html;

}

add_action('wp_enqueue_scripts', 'cc_form_enqueue_scripts');
function cc_form_enqueue_scripts(){

	wp_enqueue_script( 'cc-form-js', get_template_directory_uri() . '/inc/crypto-currency-converter/cc-form.js', array('jquery'), '1.0', true );

	wp_localize_script(
		'cc-form-js',
		'ajax_obj',
		array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) )
	);

	wp_enqueue_style('cc-form-css', get_template_directory_uri() . '/inc/crypto-currency-converter/style.css', array(), '1.0');

}

add_action('wp_ajax_cc_form_process', 'cc_form_process');
add_action('wp_ajax_nopriv_cc_form_process', 'cc_form_process');
function cc_form_process(){

	if ( !wp_verify_nonce( $_REQUEST['cc_nonce'], "cc_nonce")) {
    	exit('Access Denied!');
   	} 

   	$cc_from = sanitize_symbols($_POST['cc_from']);
    $cc_to = sanitize_symbols($_POST['cc_to']);
    $cc_showchange = boolval($_POST['cc_showchange']);

    if ( empty( $cc_from ) || empty( $cc_to ) ) {
    	$response['message'] = __('From or To fields can not be blank.');
    	$response['status'] = 'error';
		wp_send_json($response);
		die();
	}

	// Build request URL
	$url = "https://min-api.cryptocompare.com/data/pricemultifull?fsyms={$cc_from}&tsyms={$cc_to}";

	// Do API request
	$wparg = array(
		'timeout' => intval( 10 ),
	);
	$remote_response = wp_remote_get( $url, $wparg );

	if ( is_wp_error( $response ) ) {
		
		$response['message'] = $remote_response->get_error_message();
    	$response['status'] = 'error';
		wp_send_json($response);
		die();
	} else {
		
		$json = wp_remote_retrieve_body( $remote_response );

		// Convert JSON to data array
		$data = json_decode( $json, true );

		// If any error ocurred, return error message
		if ( ! is_array( $data ) ) {
			$response['message'] = 'We are so sorry because data requested can not be displayed at the moment.';
	    	$response['status'] = 'error';
			wp_send_json($response);
			die();				
		}

		// Prepare empty variables used for shortcode
		$html = $icon_style = $icon_class = '';

		// Open ticker table
		$html .= '<table class="cctw"><tbody>';
		// Loop through all `from` currencies
		foreach ( $data['DISPLAY'] as $from_symbol => $to_symbols ) {
			$to_prices_html = array();
			// Loop through all `to` currencies

			foreach ( $to_symbols as $to_symbol => $to_data ) {

				// Get change dirrection
				$change_day = $data['RAW'][ $from_symbol ][ $to_symbol ]['CHANGEDAY'];
				if ( $change_day < 0 ) {
					$change_class = 'down';
				} else if ( $change_day > 0 ) {
					$change_class = 'up';
				} else {
					$change_class = 'unchanged';
				}
				// Get update timestamp from RAW
				$timestamp = $data['RAW'][ $from_symbol ][ $to_symbol ]['LASTUPDATE'];

				// Display change into?
				$change_info = '';
				if ( ! empty( $cc_showchange ) ) {
					$change_info = sprintf(
						'%1$s (%2$s%%)',
						$data['DISPLAY'][ $from_symbol ][ $to_symbol ]['CHANGEDAY'],
						$data['DISPLAY'][ $from_symbol ][ $to_symbol ]['CHANGEPCTDAY']
					);
				}

				// Compose item for amount table cell
				$to_prices_html[] = sprintf(
					'<span class="amount %7$s" title="Mkt. Cap. %5$s - Last update %4$s"><span class="price"><span class="currency">%1$s</span> %2$s</span> <span class="change">%6$s</span></span>',
					$to_data['TOSYMBOL'],                                            // 1
					str_replace( "{$to_data['TOSYMBOL']} ", '', $to_data['PRICE'] ), // 2
					$to_symbol,                                                      // 3
					date( 'r', intval( $timestamp ) ),                               // 4
					$to_data['MKTCAP'],                                              // 5
					$change_info,                                                    // 6
					$change_class                                                    // 7
				);
			}
			// Join all cell rows with linebreak separator
			$prices_html = implode( ' ', $to_prices_html );

			// Or just to print unlinked cryptocurrency?
			$from_name = sprintf(
				'<span class="%4$s" %3$s title="%1$s">%2$s</span>',
				$currency_name, // 1
				$from_symbol,   // 2
				$icon_style,    // 3
				$currency_class // 4
			);			

			// Join all details to table row
			$html .= sprintf(
				'<tr><th>%1$s</th><td>%2$s</td></tr>',
				$from_name,
				$prices_html
			);
		}

		// Close ticker table
		$html .= '</tbody>';
		
		// Close table
		$html .= '</table>';

		$response['html'] = $html;
    	$response['status'] = 'success';
    	wp_send_json($response);
		die();	
	}

   	die();
}

function sanitize_symbols( $symbols ) {
	if ( empty( $symbols ) ) {
		return false;
	}
	return preg_replace( '/[^A-Z0-9\,\*]/', '', $symbols );
}