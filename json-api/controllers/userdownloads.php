<?php

class JSON_API_userdownloads_Controller {

	public function downloads() {
		global $json_api;
		$uname = $json_api->query->username;
		$usr_pwd = $json_api->query->password;
		$cookie = $json_api->query->cookie;
		$site_url = site_url();
		$uid = '';

		if(isset($cookie)) { //validate cookie & get user id
			$validate_cookie_api = $site_url."/api/auth/validate_auth_cookie/?cookie=".$cookie;
			$json_auth_cookie = file_get_contents($validate_cookie_api);
			$json_data = json_decode($json_auth_cookie, true);

			$status = $json_data['status'];
			$valid = $json_data['valid'];
			if($status == 'ok' && $valid) {
				$get_currentuserinfo_api = $site_url."/api/user/get_currentuserinfo/?cookie=".$cookie;
				$json_userapi_data = file_get_contents($get_currentuserinfo_api);
				$userapi_data = json_decode($json_userapi_data, true);
				$uid = $userapi_data['user']['id'];
			} else {
				$json_api->error("Your authentication token is expired.");
			}
		} 
		else { //authenticate user by username & password
			
			$auth_api = $site_url."/api/get_nonce/?controller=auth&method=generate_auth_cookie";
			
			$json_auth_cookie = file_get_contents($auth_api);
			$json_data = json_decode($json_auth_cookie, true);
			$nonce_val = $json_data['nonce'];
			
			$login_api = $site_url."/api/auth/generate_auth_cookie/?nonce=".$nonce_val."&username=".$uname."&password=".$usr_pwd;
			
			$json_login_res = file_get_contents($login_api);
			$json_user_val = json_decode($json_login_res, true);
			
			$status = $json_user_val['status'];
			$uid = $json_user_val['user']['id'];
		}
		if($status == 'ok'){
			$downloads = customer_available_downloads($uid);
			return array(
				"downloads" => $downloads,
			);
		}
		else{
			echo $json_login_res;
		}
	}
	
	public function downloads_windows_app() {
		global $json_api;
		$uname = $json_api->query->username;
		$usr_pwd = $json_api->query->password;
		$cookie = $json_api->query->cookie;
		$site_url = site_url();
		$uid = '';
		
		if(isset($cookie)) { //validate cookie & get user id
			$validate_cookie_api = $site_url."/api/auth/validate_auth_cookie/?cookie=".$cookie;
			$json_auth_cookie = file_get_contents($validate_cookie_api);
			$json_data = json_decode($json_auth_cookie, true);

			$status = $json_data['status'];
			$valid = $json_data['valid'];
			if($status == 'ok' && $valid) {
				$get_currentuserinfo_api = $site_url."/api/user/get_currentuserinfo/?cookie=".$cookie;
				$json_userapi_data = file_get_contents($get_currentuserinfo_api);
				$userapi_data = json_decode($json_userapi_data, true);
				$uid = $userapi_data['user']['id'];
			} else {
				$json_api->error("Your authentication token is expired.");
			}
		}
		else {
			$auth_api = $site_url."/api/get_nonce/?controller=auth&method=generate_auth_cookie";
			
			$json_auth_cookie = file_get_contents($auth_api);
			$json_data = json_decode($json_auth_cookie, true);
			$nonce_val = $json_data['nonce'];
			
			$login_api = $site_url."/api/auth/generate_auth_cookie/?nonce=".$nonce_val."&username=".$uname."&password=".$usr_pwd;
			
			$json_login_res = file_get_contents($login_api);
			$json_user_val = json_decode($json_login_res, true);
			
			$status = $json_user_val['status'];
			$uid = $json_user_val['user']['id'];
		}
		if($status == 'ok'){
			$downloads = customer_available_downloads($uid, 'windows');
			return array(
				"downloads" => $downloads,
			);
		}
		else{
			echo $json_login_res;
		}
	}
	
	public function socialauth_downloads() {
		global $json_api, $wpdb;
		$site_url = site_url();
		$cookie = $json_api->query->cookie;
		$uid = '';
		$user_identifier = $json_api->query->user_identifier;
		if(isset($cookie)) { //validate cookie & get user id
			$validate_cookie_api = $site_url."/api/auth/validate_auth_cookie/?cookie=".$cookie;
			$json_auth_cookie = file_get_contents($validate_cookie_api);
			$json_data = json_decode($json_auth_cookie, true);

			$status = $json_data['status'];
			$valid = $json_data['valid'];
			if($status == 'ok' && $valid) {
				$get_currentuserinfo_api = $site_url."/api/user/get_currentuserinfo/?cookie=".$cookie;
				$json_userapi_data = file_get_contents($get_currentuserinfo_api);
				$userapi_data = json_decode($json_userapi_data, true);
				$uid = $userapi_data['user']['id'];
			} else {
				$json_api->error("Your authentication token is expired.");
			}
		}
		else {
			if(!isset($user_identifier)) {
				$json_api->error("Your must include user_identifier.");
			}
			$usr_id = $wpdb->get_var( "SELECT user_id FROM $wpdb->wp_wslusersprofiles " );
			$custom_sql =$wpdb->prepare("SELECT user_id FROM wp_wslusersprofiles WHERE identifier = %s", $user_identifier);
			$uid = $wpdb->get_var($custom_sql);
		}
		if ( $uid ){
			$downloads = customer_available_downloads($uid);
			return array(
				"downloads" => $downloads,
			);
		}
		else{
			return array("response" => "User not registered.", "status" => 'error');
		}
	}
	
	public function featured_products() {
		global $json_api;
		$site_url = site_url();
		$products = featured_products_list();
		return array(
			"products" => $products,
		);
	}	
}

function customer_available_downloads( $customer_id, $device = '' ) {
	global $wpdb;

	$downloads   = array();
	$_product    = null;
	$order       = null;
	$file_number = 0;

	// Get results from valid orders only
	$results = $wpdb->get_results( $wpdb->prepare( "
		SELECT permissions.*
		FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions as permissions
		LEFT JOIN {$wpdb->posts} as posts ON permissions.order_id = posts.ID
		WHERE user_id = %d
		AND permissions.order_id > 0
		AND posts.post_status IN ( '" . implode( "','", array_keys( wc_get_order_statuses() ) ) . "' )
		AND
			(
				permissions.downloads_remaining > 0
				OR
				permissions.downloads_remaining = ''
			)
		AND
			(
				permissions.access_expires IS NULL
				OR
				permissions.access_expires >= %s
			)
		GROUP BY permissions.download_id
		ORDER BY permissions.order_id, permissions.product_id, permissions.permission_id;
		", $customer_id, date( 'Y-m-d', current_time( 'timestamp' ) ) ) );

	if ( $results ) {
		foreach ( $results as $result ) {
			if ( ! $order || $order->id != $result->order_id ) {
				// new order
				$order    = wc_get_order( $result->order_id );
				$_product = null;
			}

			// Downloads permitted?
			if ( ! $order->is_download_permitted() ) {
				continue;
			}

			if ( ! $_product || $_product->id != $result->product_id ) {
				// new product
				$file_number = 0;
				$_product    = wc_get_product( $result->product_id );
			}

			// Check product exists and has the file
			if ( ! $_product || ! $_product->exists() || ! $_product->has_file( $result->download_id ) ) {
				continue;
			}

			$download_file = $_product->get_file( $result->download_id );

			// Download name will be 'Product Name' for products with a single downloadable file, and 'Product Name - File X' for products with multiple files
			$download_name = apply_filters(
				'woocommerce_downloadable_product_name',
				$_product->get_title() . ' &ndash; ' . $download_file['name'],
				$_product,
				$result->download_id,
				$file_number
			);
			
			$product_auth = get_the_terms( $result->product_id, 'pa_product-author');
			//$prod_thumb_src = wp_get_attachment_image_src( get_post_thumbnail_id($result->product_id, array(65,65)));
			
			$prod_thumb_src = wp_get_attachment_image_src( $_product->get_image_id(), 'shop_thumbnail');
			//if($device == 'windows'){
				$prod_thumb_src = wp_get_attachment_image_src( $_product->get_image_id(), 'shop_catalog');
				$prod_img_src = wp_get_attachment_image_src( $_product->get_image_id(), 'shop_single');
			//}
			
			$prod_auths = array();
			foreach($product_auth as $auth){
				$prod_auths[] =  $auth->name;
			}
			
			if($device == 'windows'){
				$downloads[] = array(
					'download_url'        => add_query_arg(
						array(
							'download_file' => $result->product_id,
							'order'         => $result->order_key,
							'email'         => $result->user_email,
							'key'           => $result->download_id
						),
						home_url( '/' )
					),
					'product_id'          => $result->product_id,
					'order_id'          => $result->order_id,
					'download_name'       => $download_name,
					'product_authors' 	  => $prod_auths,						
					'product_thumb_url'  => $prod_thumb_src[0],				
					'product_img_url'  => $prod_img_src[0],				
				);
			}
			else{	
				$downloads[] = array(
					'download_url'        => add_query_arg(
						array(
							'download_file' => $result->product_id,
							'order'         => $result->order_key,
							'email'         => $result->user_email,
							'key'           => $result->download_id
						),
						home_url( '/' )
					),
					'product_id'          => $result->product_id,
					'order_id'          => $result->order_id,
					'download_name'       => $download_name,
					'product_authors' 	  => $prod_auths,						
					'product_thumb_url '  => $prod_thumb_src[0],		
					'product_img_url'  => $prod_img_src[0],
				);
			}
			$file_number++;
		}
	}
	
	if($device != 'windows'){
		/*append list of free books to downloads list*/
		$free_books_list = get_option( 'vividlipi_free_books' );
		$free_books_list = $free_books_list['product_ids'];
		$free_books_list = explode(',', $free_books_list);		
		
		foreach($free_books_list as $book_id){
			global $json_api;
			
			$_product = null;
			$_product    = wc_get_product( $book_id );
			$downloads_files = $_product->get_files();

			foreach( $downloads_files as $key => $each_download ) {
				$product_auth = get_the_terms( $_product->id, 'pa_product-author');
				
				$prod_thumb_src = wp_get_attachment_image_src( $_product->get_image_id(), 'shop_thumbnail');
				$prod_thumb_src = wp_get_attachment_image_src( $_product->get_image_id(), 'shop_catalog');
				$prod_img_src = wp_get_attachment_image_src( $_product->get_image_id(), 'shop_single');
				
				$prod_auths = array();
				foreach($product_auth as $auth){
					$prod_auths[] =  $auth->name;
				}
				
				$download_url = $each_download["file"];
				$downloads[] = array(
						'download_url'        => $download_url,
						'product_id'          => "$_product->id",
						'order_id'          => $result->order_id,
						'download_name'       => $_product->post->post_title,
						'product_authors' 	  => $prod_auths,						
						'product_thumb_url '  => $prod_thumb_src[0],				
						'product_img_url'  => $prod_img_src[0],				
					);
			}
		}
	}
	$dwns = array();
	foreach($downloads as $key=>$download) {
		$is_rented = _check_is_rent_prod($download['product_id']);
		if($is_rented) {
			$order = new WC_Order($download['order_id']);
			$order_date = $order->order_date;
			$ordered_at = date('Y-m-d', strtotime($order_date));
			$today = date('Y-m-d');
			$downloads[$key]['order_at'] = $ordered_at ;
			$download['ordered_at'] = $ordered_at;
			$time_diff = strtotime($today) - strtotime($ordered_at); 
			//$downloads[$key]['order_at'] = date('Y-m-d', strtotime($order_date));
			if($time_diff > 86399*15) {
				continue;
				//unset($downloads[$key]);
			}
		}
		$dwns[] = $download;
	}
	$downloads = $dwns;

	$user_id = $customer_id;
	$meta_key = 'vid_subs_plan';
	$user_subscriptions = get_user_meta($user_id, $meta_key, true);
	$vposts = array();

	foreach($user_subscriptions as $user_subscription) {
		$data = explode("-", $user_subscription);

		$args = array( 'post_type' => 'product','posts_per_page' => -1,'product_cat' => $data[0], 'orderby' =>'date','order' => 'DESC' );
		
		$result = new WP_Query( $args );
		$posts = $result->posts;
		if(isset($data[1])) {
			foreach($posts as $post) {
				if(strpos($post->post_title, $data[1]) !== false) {
					//echo $post->post_title . "  " . $post->ID . "</br>";
					$vposts[] = $post; 
				}
			}
		} 
		else {
			foreach($posts as $post) {
				$catToCheck = $data[0];
				$product_cats= wp_get_post_terms( $post->ID, 'product_cat' );
				if(_vidCheckCatExists($product_cats, $catToCheck)) {
				//if(strpos($post->post_title, $data[0]) !== false) {
					//echo $post->post_title . "  " . $post->ID . "</br>";
					$vposts[] = $post; 
				}
			}
		}
	}
		//p($vposts);
		foreach($vposts as $post) {
			$_product    = wc_get_product( $post->ID );

			$downloads_files = $_product->get_files();
			if(empty($downloads_files)) {
				continue;
			}
			$product_auth = get_the_terms( $_product->id, 'pa_product-author');
			$prod_thumb_src = wp_get_attachment_image_src( $_product->get_image_id(), 'shop_thumbnail');
			$prod_thumb_src = wp_get_attachment_image_src( $_product->get_image_id(), 'shop_catalog');
			$prod_img_src = wp_get_attachment_image_src( $_product->get_image_id(), 'shop_single');
			$url = '';
			foreach($downloads_files as $downloads_file) {
				$url = $downloads_file['file'];
			}
			
			$downloads[] = array(
					'download_url'        => $url,
					'product_id'          => "$_product->id",
					'order_id'          =>  'subscription',
					'download_name'       => $_product->post->post_title,
					'product_authors' 	  => $prod_auths,						
					'product_thumb_url '  => $prod_thumb_src[0],				
					'product_img_url'  => $prod_img_src[0],				
				);
		}

	//p($customer_id, $downloads, $user_subscription);
	//p($downloads);
	/*end of append list of free books to downloads list*/
	
	return $downloads;
}

function featured_products_list() {
	global $wpdb;
	$products = array();
	
	$args = array(
		'post_type' => 'product',
		'meta_key' => '_featured',
		'meta_value' => 'yes',
		'posts_per_page' => -1
	);

	$featured_query = new WP_Query( $args );
		
	if ($featured_query->have_posts()) : 
		$i = 1;
		while ($featured_query->have_posts()) : 
		
			$featured_query->the_post();
			
			$product = get_product( $featured_query->post->ID );
			$_product    = wc_get_product( $product->id );
			
			/*Product Rating*/
			$average = $_product->get_average_rating();
			$rating_count = $_product->post->comment_count;

			$prod_rating = $average;
			
			/*get product price*/
			$prod_price = $_product->price;
			//$product->get_price_html();
			
			/*get product attributes*/
			$attributes = $_product->get_attributes();
			
			foreach ( $attributes as $attribute ) {
	
				// skip variations
				if ( $attribute['is_variation'] ) {
					continue;
				}
					
				// If on the single product, apply the visibility setting
				if ( $visibility && ! $attribute['is_visible'] ) {
					continue;
				}
				
				if ( $attribute['is_taxonomy'] ) {
					
					$terms = wp_get_post_terms( $product->id, $attribute['name'], 'all' );
					if ( ! empty( $terms ) ) {
						if ( ! is_wp_error( $terms ) ) {
		
							// get the taxonomy
							$tax = $terms[0]->taxonomy;
							 
							// get the tax object
							$tax_object = get_taxonomy($tax);
							 
							// get tax label
							if ( isset ($tax_object->labels->name) ) {
								$tax_label = $tax_object->labels->name;
							} elseif ( isset( $tax_object->label ) ) {
								$tax_label = $tax_object->label;
							}
							$attributes_customize = array();
							///Customizing the code to display for VividLipi.
							foreach ( $terms as $term ) {
								$attributes_customize[$tax_label]['class'] = esc_attr( $attribute['name'] );
								$attributes_customize[$tax_label]['terms'][] = $term->name;					  
							}
							foreach($attributes_customize as $attribute_row_label => $attribute_row_val){								
								$att_terms = implode(',', $attribute_row_val['terms']);
								if($attribute_row_label == 'Author(s)'){
									$attribute_row_label = 'Author';
								}
								$attribute_row_label = str_replace(' ', '', $attribute_row_label);
								$prod_att[$attribute_row_label] = $att_terms;
							}
						}					
					}						   
				} else {
					$prod_att[$attribute['name']] = $attribute['value'];
				}
			}
			
			/*product url*/
			$product_url = get_permalink($product->id);
			
			$product_auth = get_the_terms( $product->id, 'pa_product-author');
			//$prod_thumb_src = wp_get_attachment_image_src( get_post_thumbnail_id($result->product_id, array(65,65)));
			
			$prod_thumb_src = wp_get_attachment_image_src( $_product->get_image_id(), 'shop_catalog');
			if($device == 'windows'){
				$prod_thumb_src = wp_get_attachment_image_src( $_product->get_image_id(), 'shop_catalog');
			}
			
			$prod_auths = array();
			foreach($product_auth as $auth){
				$prod_auths[] =  $auth->name;
			}
			
			$products[] = array(
					'row_id'			=> $i,
					'product_id'          => $product->id,
					'product_title'       => get_the_title($product->id),
					'product_authors' 	  => $prod_auths,						
					'product_thumb_url'  => $prod_thumb_src[0],				
					'product_rating' 	  => $prod_rating,	
					'product_att' 	  => $prod_att,	
					'product_price' => $prod_price,
					'product_url' => $product_url,
				);
			
			// Output product information here
			$i++;
		endwhile;
		
	endif;

	wp_reset_query(); // Remember to reset	
	return $products;
}

?>