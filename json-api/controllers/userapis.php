<?php

class JSON_API_userapis_Controller {
	public function user_register() {
		global $json_api, $wpdb;
		
		$uname = urlencode($json_api->query->username);
		$email = $json_api->query->email;
		/*$fname = $json_api->query->fname;
		$lname = $json_api->query->lname;
		$gender = $json_api->query->gender;
		$dob = $json_api->query->dob;
		*/
		$usr_pwd = $json_api->query->password;
		
		$site_url = site_url();
		
		$register_api = $site_url."/api/get_nonce/?controller=user&method=register";
		
		$json_auth_cookie = file_get_contents($register_api);
		$json_data = json_decode($json_auth_cookie, true);
		$nonce_val = $json_data['nonce'];
				
		$register_api = $site_url."/api/user/register/?nonce=".$nonce_val."&username=".$uname."&email=".$email."&display_name=".$uname;
		
		$json_login_res = file_get_contents($register_api);
		$json_user_val = json_decode($json_login_res, true);
			
		$status = $json_user_val['status'];
		$user_id = $json_user_val['user_id'];

	
		//update_user_meta($user_id, 'rpr_gender', $gender);
		//update_user_meta($user_id, 'rpr_date_of_birth', $dob);
			
		if($status == 'ok'){
			if(is_null($usr_pwd)) {
				$usr_pwd = $user_id . 'user_pwd';
				wp_set_password( $usr_pwd, $user_id );
			} else {
				wp_set_password( $usr_pwd, $user_id );
			}
			$uid = $json_user_val['user']['id'];
			return array(
				"message" => "user registration successful",
				'pass' => $usr_pwd
			);
		}
		else{
			return $json_user_val;
			//echo $json_login_res;
		}
	}
	
	/*login API*/
	public function user_login() {
		global $json_api, $wpdb;
		
		$uname = $json_api->query->username;
		$usr_pwd = $json_api->query->password;
		$site_url = site_url();
		
		$auth_api = $site_url."/api/get_nonce/?controller=auth&method=generate_auth_cookie";
		
		$json_auth_cookie = file_get_contents($auth_api);
		$json_data = json_decode($json_auth_cookie, true);
		$nonce_val = $json_data['nonce'];
		
		$login_api = $site_url."/api/auth/generate_auth_cookie/?nonce=".$nonce_val."&username=".$uname."&password=".$usr_pwd;
		
		$json_login_res = file_get_contents($login_api);
		$json_user_val = json_decode($json_login_res, true);
		
		return $json_user_val;
	}
	
	/*Social login API*/
	public function social_user_login() {
		global $json_api, $wpdb;
		
		//$uname = $json_api->query->username;
		$uemail = $json_api->query->email;
		$usr_provider = $json_api->query->provider;
		$user_identifier = $json_api->query->user_identifier;
		$site_url = site_url();
		
		/*
		if (!$json_api->query->username) {
			$json_api->error("You must include 'username' var in your request. ");
		}
		else
		*/
		if (!$json_api->query->provider) {
			$json_api->error("You must include 'provider' var in your request. ");
		}
		else if (!$json_api->query->user_identifier) {
			$json_api->error("You must include 'user_identifier' var in your request. ");
		}
		
		$custom_sql =$wpdb->prepare("SELECT user_id FROM wp_wslusersprofiles WHERE identifier = %s", $user_identifier);
		$user_id = $wpdb->get_var($custom_sql);
		if(!$user_id) {
			$sql = "SELECT ID FROM wp_users WHERE user_email = '$uemail'";
			$user_id = $wpdb->get_var($sql);
			if($user_id) {
				if($provider == "google" ) {
					$profileurl = 'https://plus.google.com/' . $user_identifier;
				} else {
					$profileurl = 'https://www.facebook.com/app_scoped_user_id/' . $user_identifier;
				}
				$wpdb->insert( 
					'wp_wslusersprofiles', 
					array( 
						'user_id' => $user_id, 
						'identifier' => $user_identifier,
						'profileurl' => $profileurl
					), 
					array( 
						'%d', 
						'%s', 
						'%s' 
					) 
				);
			}
			/*
			$check_useremail_sql = $wpdb->prepare("SELECT ID FROM wp_users WHERE user_email = %s", $uemail);
			$existing_user_id2 = $wpdb->get_var($check_useremail_sql);
			//var_dump($check_useremail_sql, $existing_user_id2);exit;
			if($existing_user_id2) { 
				$json_api->error("User Email already exists");
			}
			$check_username_sql = $wpdb->prepare("SELECT ID FROM wp_users WHERE user_login = %s", $user_identifier);
			//var_dump($check_username_sql);exit;
			$existing_user_id = $wpdb->get_var($check_username_sql);
			if($existing_user_id) { 
				$json_api->error("User Name already exists");
			}
			*/
		}
		if($user_id){
			$user_info = get_userdata($user_id);
		}
		else{
			$user_data = array();
			$user_data['identifier'] = $user_identifier;
			$user_data['webSiteURL'] = '';
			if($usr_provider == 'google'){
				$profile_url = "https://plus.google.com/".$user_identifier;
			}
			else{
				$profile_url = "https://www.facebook.com/app_scoped_user_id/".$user_identifier;
			}
			$user_data['profileURL'] = $profile_url;
			$user_data['photoURL'] = '';
			$user_data['displayName'] = '';
			$user_data['webSiteURL'] = '';
			$user_data['description'] = '';
			$user_data['firstName'] = '';
			$user_data['lastName'] = '';
			$user_data['gender'] = '';
			$user_data['language'] = '';
			$user_data['age'] = '';
			$user_data['birthDay'] = '';
			$user_data['birthMonth'] = '';
			$user_data['birthYear'] = '';
			$user_data['email'] = $uemail;
			$user_data['emailVerified'] = $uemail;
			$user_data['phone'] = '';
			$user_data['address'] = '';
			$user_data['country'] = '';
			$user_data['region'] = '';
			$user_data['city'] = '';
			$user_data['zip'] = '';
			
			$userObj = (object) $user_data;
		
			$user_id = wsl_process_login_create_wp_user( $usr_provider, $userObj, $uemail, $uemail );
			
			$user_data['user_id'] = $user_id;
			$user_data['provider'] = $usr_provider;
			
			$wpdb->replace( "{$wpdb->prefix}wslusersprofiles", $user_data ); 
			
			$user_info = get_userdata($user_id);			
		}		
		
		$default_pwd = 'social_pwd'.$user_id;
		wp_set_password( $default_pwd, $user_id );
		
		$auth_api = $site_url."/api/get_nonce/?controller=auth&method=generate_auth_cookie";
		
		$json_auth_cookie = file_get_contents($auth_api);
		$json_data = json_decode($json_auth_cookie, true);
		$nonce_val = $json_data['nonce'];
		
		$login_api = $site_url."/api/auth/generate_auth_cookie/?nonce=".$nonce_val."&username=".$user_info->user_login."&password=".$default_pwd;
		
		$json_login_res = file_get_contents($login_api);
		$json_user_val = json_decode($json_login_res, true);
		
		return $json_user_val;
	}
	
	/*Listing products*/
	public function listing_products() {
		global $json_api;
		$site_url = site_url();
		$cookie = $json_api->query->cookie;
		$cat = $json_api->query->type;
		$filter_product_language = $json_api->query->language;
		$filter_product_author = $json_api->query->author;
		$filter_product_publisher = $json_api->query->publisher;
		$filter_product_max_price = $json_api->query->max_price;
		$filter_product_min_price = $json_api->query->min_price;
		$sortby = $json_api->query->sortby;
		$sortOrder = $json_api->query->order;
		$currencyCode = $json_api->query->currency_code;
		$per_page = $json_api->query->per_page;
		$paged = $json_api->query->paged;
		/*		
		$validate_cookie_api = $site_url."/api/auth/validate_auth_cookie/?cookie=".$cookie;
		
		$json_auth_cookie = file_get_contents($validate_cookie_api);
		$json_data = json_decode($json_auth_cookie, true);
		$status = $json_data['status'];
		$valid = $json_data['valid'];
		
		if($status == 'ok' && $valid){
		*/
			$products = products_list($cat, $filter_product_language, $filter_product_author, $filter_product_publisher, $filter_product_min_price, $filter_product_max_price, '', $sortby, $sortOrder, $currencyCode, $per_page, $paged);
			return array(
				"products" => $products,
			);
		/*
		}
		else{
			return "invalid user";
		}
		*/
	}
	
	/*Search products*/
	public function search_products() {
		global $json_api;
		$site_url = site_url();
		$prod_title = $json_api->query->keyword;		
		$products = products_list('', '', '', '', '', '', $prod_title, '', '', '');
		return array(
			"products" => $products,
		);
		
	}
	
	/*Product Detail page*/
	public function getproductbyid() {
		global $json_api;
		$site_url = site_url();
		$prod_id = $json_api->query->prod_id;
		$_product = wc_get_product( $prod_id );
		
		$product_details = get_product($prod_id);
		
		$product_auth = get_the_terms( $prod_id, 'pa_product-author');
		
		$product_language = get_the_terms( $prod_id, 'pa_product-language');
		
		$prod_thumb_src = wp_get_attachment_image_src( $_product->get_image_id(), 'shop_catalog');
					
		$product_auth = get_the_terms( $prod_id, 'pa_product-author');
			
		$product_publisher = get_the_terms( $prod_id, 'pa_product-publisher');
		
		$product_narrator = get_the_terms( $prod_id, 'pa_product-narrator');
		//$prod_thumb_src = wp_get_attachment_image_src( get_post_thumbnail_id($result->product_id, array(65,65)));
		
		$product_language = get_the_terms( $prod_id, 'pa_product-language');
		
		$product_translator = get_the_terms( $prod_id, 'pa_product-translator');
		
		$prod_thumb_src = wp_get_attachment_image_src( $_product->get_image_id(), 'shop_catalog');
					
		$prod_auths = array();
		foreach($product_auth as $auth){
			$prod_auths[] =  $auth->name;
		}
		
		$prod_lang = array();
		foreach($product_language as $lang){
			$prod_lang[] =  $lang->name;
		}

		$prod_publisher = array();
		foreach($product_publisher as $publisher){
			$prod_publisher[] =  $publisher->name;
		}
		
		$prod_narrator = array();
		foreach($product_narrator as $narrator){
			$prod_narrator[] =  $narrator->name;
		}
		
		$prod_translator = array();
		foreach($product_translator as $translator){
			$prod_translator[] =  $translator->name;
		}
		
		/*
		$price = $_product->post->_sale_price;
		if($price == ''){
			$price = $_product->post->_regular_price;
		}
		*/
		$sale_price = $_product->post->_sale_price;
		$reg_price = $_product->post->_regular_price;
		
		
		$terms = get_the_terms( $prod_id, 'product_cat' );
		foreach ($terms as $term) {
			$product_cat[] = $term->name;
		}
		
		$product_format = get_post_meta( $prod_id, 'prod_select_format_type', true );
		$average = get_post_meta( $prod_id, '_wc_average_rating', true );
		$rating = (string) floatval( $average );
		
		$product = array(
				'product_id'          => $prod_id,
				'product_title'       => $_product->post->post_title,
				'product_description' => wp_strip_all_tags($_product->post->post_content),
				'product_authors' 	  => $prod_auths,						
				'product_language' 	  => $prod_lang,		
				'product_sale_price' 	  => $sale_price,
				'product_regular_price' 	  => $reg_price,
				'product_thumb_url'  => $prod_thumb_src[0],				
				'product_rating' => $product_details->get_average_rating(),
				'product_cat' => $product_cat,
				'product_publisher' => $prod_publisher,
				'product_narrator' => $prod_narrator,
				'product_translator' => $prod_translator,
				'product_book_format' => $product_format,
				'blog_post_url' => get_post_meta($prod_id, 'blog_post_url', true),
			);
		
		return $product;
		
	}
	
	/*Creating order */
	public function purchase_order() {
		global $json_api;
		$site_url = site_url();
		$cookie = $json_api->query->cookie;
		$user_id = $json_api->query->user_id;
		$productIds = $json_api->query->product_id;
		$currency = $json_api->query->currency;
		$payment_method = $json_api->query->payment_method;
		$transaction_id = $json_api->query->transaction_id;
		$amount = $json_api->query->amount;
		$coupon = $json_api->query->coupon;
		//$discount_amt = $json_api->query->discount_amt;
				
		/*
		$validate_cookie_api = $site_url."/api/auth/validate_auth_cookie/?cookie=".$cookie;
		
		$json_auth_cookie = file_get_contents($validate_cookie_api);
		$json_data = json_decode($json_auth_cookie, true);
		$status = $json_data['status'];
		$valid = $json_data['valid'];
		*/
		$error = '';
		
		$product_ids = explode(",", $productIds);
		
		if($payment_method == '' || $currency == ''){
			$error = "Payment Method & Currency not passed";
		}
		
		$coupon_data = '';
		$coupon_data = get_page_by_title($coupon, OBJECT, 'shop_coupon');
		$expires = get_post_meta($coupon_data->ID, 'expiry_date', true);
		$coupon_amount = get_post_meta($coupon_data->ID, 'coupon_amount', true);
		$coupon_type = get_post_meta( $coupon_data->ID, 'discount_type', true );
		$discount_product_ids = get_post_meta( $coupon_data->ID, 'product_ids', true );
		
		if($coupon){
			if($coupon_data == ''){
				return "invalid coupon";
			}
			else{
				if($expires){
					if (strtotime('NOW')>$expires){
						return "coupon expired";
					}
				}
				else{
					$discount_amt = 0;
					if($coupon_type == 'percent'){
						$discount_amt = $amount * $coupon_amount/100;
					}
					else{
						$discount_product_ids = explode(",", $discount_product_ids);
							
						foreach($product_ids as $product_id){
							$product = get_product( $product_id );
							$_product = wc_get_product( $product->id );
							$price = $_product->post->_sale_price;
							if($price == ''){
								$price = $_product->post->_regular_price;
							}
							if(in_array($product_id, $discount_product_ids)){
								$discount_amt += $price;
							}				
						}
						$discount_amt = $discount_amt * $coupon_amount/100;
					}
				}
			}	
		}
		
		if($error == '') {
			$order = wc_create_order();
			foreach($product_ids as $product_id){
				$product = get_product($product_id);
				$order->add_product( $product , 1 );
			}	
			
			if($coupon){
				//$discount_amt = ($coupon_amount*$amount)/100;
				$order->add_coupon( $coupon, $discount_amt ); 
				$order->set_total( $discount_amt , 'order_discount'); 
			}
			
			$order->calculate_totals();
			// assign the order to the current user
			update_post_meta($order->id, '_customer_user', $user_id );
			//update_post_meta($order->id, '_customer_user', get_current_user_id() );
			update_post_meta( $order->id, '_payment_method',  $payment_method);
			update_post_meta( $order->id, '_payment_method_title', $payment_method );
			update_post_meta($order->id, '_order_currency', $currency, true);
			update_post_meta($order->id, '_transaction_id', $transaction_id, true);
			// payment_complete
			//$order->payment_complete();
			$order->payment_complete( $transaction_id );
			update_post_meta($order->id, 'post_status', 'wc-completed', true);
			
			foreach($product_ids as $product_id){
				$product = get_product($product_id);
				if( $product->is_downloadable() ) {
					// add downloadable permission for each file
					$download_files = $product->get_files();
					foreach ( $download_files as $download_id => $file ) {
						wc_downloadable_file_permission( $download_id, $product->id, new WC_Order( $order->id ) );
					}
				}
			}
			return array(
				"order_id" => $order->id,
			);
		}
		else{
			//return "invalid user or error occured while creating order";
			$json_api->error("Payment Method & Currency not passed");
		}
	}
	
	/*Listing available coupons*/
	public function listing_coupons() {
		global $json_api, $wpdb;
		
		$site_url = site_url();		
		
		$args = array(
			'posts_per_page'   => -1,
			'orderby'          => 'title',
			'order'            => 'asc',
			'post_type'        => 'shop_coupon',
			'post_status'      => 'publish',
		);
			
		$coupons = get_posts( $args );
		
		$coupon_names = array();
		foreach ( $coupons as $coupon ) {
			// Get the name for each coupon post
			$coupon_name = $coupon->post_title;
			$coupon_id = $coupon->ID;
			$coupon_exp_date = get_post_meta( $coupon_id, 'expiry_date', true );
			$time1 = '';
			if($coupon_exp_date)
				$time1  = strtotime($coupon_exp_date);
			if(time() < $time1 || $coupon_exp_date == ''){
				$coupon_names[] =  $coupon_name;
			} 		
			/*$coupon_names[$coupon_id]['id'] =  $coupon_id;
			$coupon_names[$coupon_id]['code'] =  $coupon_name;
			$coupon_names[$coupon_id]['exp_date'] =  $coupon_exp_date;*/
		}
				
		if(!count($coupon_names)){
			return "No Coupons Available";
		}
		return $coupon_names;
		
	}
	
	/*Calculate Discount Amount based on coupon code and products list*/
	public function coupon_discount_amt_old() {
		global $json_api, $wpdb;
		$productIds = $json_api->query->product_id;
		$totalAmt = $json_api->query->amount;
		$coupon_code = $json_api->query->coupon;
		$product_ids = explode(",", $productIds);
		
		$coupon_data = get_page_by_title($coupon_code, OBJECT, 'shop_coupon');
		$expires = get_post_meta($coupon_data->ID, 'expiry_date', true);
		$coupon_amount = get_post_meta($coupon_data->ID, 'coupon_amount', true);
		$coupon_type = get_post_meta( $coupon_data->ID, 'discount_type', true );
		$discount_product_ids = get_post_meta( $coupon_data->ID, 'product_ids', true );
		
		if(!$coupon_data)
			return "Coupon Not Valid";
			
		if($coupon_code == '')
			return "Coupon Code not found";
		
		$time1 = '';
		if($expires)
			$time1  = strtotime($expires);
		if(time() > $time1 && $expires != ''){
			return "Coupon got expired";
		}	
		
		$total_amount = 0;		
		
		if($coupon_type == 'percent'){
			$total_amount = $totalAmt - ($totalAmt * $coupon_amount/100);
		}
		else{
			if($productIds == '')
				return "Product Ids not found";
			$discount_product_ids = explode(",", $discount_product_ids);
						
			foreach($product_ids as $product_id){
				$product = get_product( $product_id );
				$_product = wc_get_product( $product->id );
				$price = $_product->post->_sale_price;
				if($price == ''){
					$price = $_product->post->_regular_price;
				}
				if(in_array($product_id, $discount_product_ids)){
					$amount += $price;
				}			
			}
			$amount = $amount * $coupon_amount/100;
			$total_amount = $totalAmt - $amount;
		}
		
		return $total_amount;		
	}

	/*Calculate Discount Amount based on coupon code and products list bw*/
	public function coupon_discount_amt() {
		global $json_api, $wpdb;
		$productIds = $json_api->query->product_id;
		$totalAmt = $json_api->query->amount;
		$coupon_code = $json_api->query->coupon;
		$product_ids = explode(",", $productIds);
		
		$coupon_data = get_page_by_title($coupon_code, OBJECT, 'shop_coupon');
		$expires = get_post_meta($coupon_data->ID, 'expiry_date', true);
		$coupon_amount = get_post_meta($coupon_data->ID, 'coupon_amount', true);
		$coupon_type = get_post_meta( $coupon_data->ID, 'discount_type', true );
		$discount_product_ids = get_post_meta( $coupon_data->ID, 'product_ids', true );
		
		if(!$coupon_data)
			return "Coupon Not Valid";
			
		if($coupon_code == '')
			return "Coupon Code not found";
		
		$time1 = '';
		if($expires)
			$time1  = strtotime($expires);
		if(time() > $time1 && $expires != ''){
			return "Coupon got expired";
		}	
		$wmcs_live_exchange_rates = get_option('wmcs_live_exchange_rates');
		//$amount += $wmcs_live_exchange_rates['rates'][$currency] * $price;
		
		$total_amount = 0;		
		$amount_INR= 0;
		$amount_EUR= 0;
		$amount_GBP= 0;
		$amount_USD= 0;
		
		// coupon is cart based
		if($coupon_type == 'percent' or $coupon_type == "fixed_cart" ){
			foreach($product_ids as $product_id) {
				$product = get_product( $product_id );
				$_product = wc_get_product( $product->id );
				$price = $_product->post->_sale_price;
				if($price == ''){
					$price = $_product->post->_regular_price;
				}
				$amount_INR += $price;
				$amount_EUR += _get_price_currency($product_id, 'EUR', $price);
				$amount_USD += _get_price_currency($product_id, 'USD', $price);
				$amount_GBP += _get_price_currency($product_id, 'GBP', $price);
			}
			if($coupon_type == 'percent') {
				$amount_INR = $amount_INR - $amount_INR * $coupon_amount/100;
				$amount_EUR = $amount_EUR - $amount_EUR * $coupon_amount/100;
				$amount_USD = $amount_USD - $amount_USD * $coupon_amount/100;
				$amount_GBP = $amount_GBP - $amount_GBP * $coupon_amount/100;
			} else {
				$amount_INR = $amount_INR - $coupon_amount;
				$amount_EUR = $amount_EUR - $coupon_amount * $wmcs_live_exchange_rates['rates']['EUR'];
				$amount_USD = $amount_USD - $coupon_amount * $wmcs_live_exchange_rates['rates']['EUR'];
				$amount_GBP = $amount_GBP - $coupon_amount * $wmcs_live_exchange_rates['rates']['EUR'];
			}
		}
		//coupon is product based
		else{
			if($productIds == '') {
				return "Product Ids not found";
			}
			$discount_product_ids = explode(",", $discount_product_ids);
			$amount = 0;		
			foreach($product_ids as $product_id){
				$product = get_product( $product_id );
				$_product = wc_get_product( $product->id );
				$price = $_product->post->_sale_price;
				if($price == ''){
					$price = $_product->post->_regular_price;
				}
				$amount_EUR_1 = _get_price_currency($product_id, 'EUR', $price);
				$amount_USD_1 = _get_price_currency($product_id, 'USD', $price);
				$amount_GBP_1 = _get_price_currency($product_id, 'GBP', $price);

				if(in_array($product_id, $discount_product_ids)){
					$amount_INR += $price - $price * $coupon_amount/100;
					$amount_EUR += $amount_EUR_1 - $amount_EUR_1 * $coupon_amount/100;
					$amount_USD += $amount_USD_1 - $amount_USD_1 * $coupon_amount/100;
					$amount_GBP += $amount_GBP_1 - $amount_GBP_1 * $coupon_amount/100;
				}	else {
					$amount_INR += $price;
					$amount_EUR += $amount_EUR_1;
					$amount_USD += $amount_USD_1;
					$amount_GBP += $amount_GBP_1;
				}
			}
		}
		$amount_INR = round($amount_INR, 2);
		$amount_EUR = round($amount_EUR, 2);
		$amount_USD = round($amount_USD, 2);
		$amount_GBP = round($amount_GBP, 2);
		$total_amount = array('INR' => $amount_INR, 'EUR' => $amount_EUR, 'USD' => $amount_USD, 'GBP' => $amount_GBP );
		return $total_amount;		
	}

	
	/*Listing categories*/
	public function listing_categories() {
		global $json_api, $wpdb;
		$site_url = site_url();
		/*
		$cookie = $json_api->query->cookie;
				
		$validate_cookie_api = $site_url."/api/auth/validate_auth_cookie/?cookie=".$cookie;
		
		$json_auth_cookie = file_get_contents($validate_cookie_api);
		$json_data = json_decode($json_auth_cookie, true);
		$status = $json_data['status'];
		$valid = $json_data['valid'];
		
		if($status == 'ok' && $valid){
		*/			
			$taxonomy     = 'product_cat';
			$orderby      = 'name';  
			$show_count   = 0;      // 1 for yes, 0 for no
			$pad_counts   = 0;      // 1 for yes, 0 for no
			$hierarchical = 1;      // 1 for yes, 0 for no  
			$title        = '';  
			$empty        = 0;

			$args = array(
				 'taxonomy'     => $taxonomy,
				 'orderby'      => $orderby,
				 'show_count'   => $show_count,
				 'pad_counts'   => $pad_counts,
				 'hierarchical' => $hierarchical,
				 'title_li'     => $title,
				 'hide_empty'   => $empty
			);
			$all_categories = get_categories( $args );
			$prod_cat = array();
			
			foreach ($all_categories as $cat) {
				if($cat->category_parent == 0) {
					$category_id = $cat->term_id;       
					//$cat_slug = get_category($cat->term_id);
					
					$prod_cat[$cat->name]['name'] = $cat->name; 
					$prod_cat[$cat->name]['key'] = $cat->slug; 
					$args2 = array(
							'taxonomy'     => $taxonomy,
							'child_of'     => 0,
							'parent'       => $category_id,
							'orderby'      => $orderby,
							'show_count'   => $show_count,
							'pad_counts'   => $pad_counts,
							'hierarchical' => $hierarchical,
							'title_li'     => $title,
							'hide_empty'   => $empty
					);
					$sub_cats = get_categories( $args2 );
					if($sub_cats) {
						$sub_cnt = 0;
						foreach($sub_cats as $sub_category) {
							//echo  $sub_category->name ;
							//$cat_slug = get_category($sub_category->term_id);
							$prod_cat[$cat->name]['sub_category'][$sub_cnt]['name'] = $sub_category->name;
							$prod_cat[$cat->name]['sub_category'][$sub_cnt]['key'] = $sub_category->slug;
							$sub_cnt++;
						}   
					}
				}       
			}
			
			return $prod_cat;
		/*}
		else{
			return "invalid user";
		}
		*/
	}
	
	/*Listing Filters*/
	public function listing_filters() {
		global $json_api, $wpdb;
		$site_url = site_url();
		$prod_cat = array();
		$filters = array('pa_product-author' => 'Author', 'pa_product-publisher' => 'Publisher');
		
		foreach($filters as $filter => $filter_name){
			$taxonomy     = $filter;
			$orderby      = 'name';  
			$show_count   = 0;      // 1 for yes, 0 for no
			$pad_counts   = 0;      // 1 for yes, 0 for no
			$hierarchical = 1;      // 1 for yes, 0 for no  
			$title        = '';  
			$empty        = 0;

			$args = array(
				 'taxonomy'     => $taxonomy,
				 'orderby'      => $orderby,
				 'show_count'   => $show_count,
				 'pad_counts'   => $pad_counts,
				 'hierarchical' => $hierarchical,
				 'title_li'     => $title,
				 'hide_empty'   => $empty
			);
			$all_categories = get_categories( $args );
			
			
			foreach ($all_categories as $cat) {
				if($cat->category_parent == 0) {
					$category_id = $cat->term_id;       
					
					$prod_cat[$filter_name][$category_id]['name'] = $cat->name; 
					$prod_cat[$filter_name][$category_id]['key'] = $cat->slug; 
					$args2 = array(
							'taxonomy'     => $taxonomy,
							'child_of'     => 0,
							'parent'       => $category_id,
							'orderby'      => $orderby,
							'show_count'   => $show_count,
							'pad_counts'   => $pad_counts,
							'hierarchical' => $hierarchical,
							'title_li'     => $title,
							'hide_empty'   => $empty
					);
					$sub_cats = get_categories( $args2 );
					if($sub_cats) {
						foreach($sub_cats as $sub_category) {
							//echo  $sub_category->name ;
							//$cat_slug = get_category($sub_category->term_id);
							$prod_cat[$filter_name][$category_id]['sub_category'][$sub_category->term_id]['name'] = $sub_category->name;
							$prod_cat[$filter_name][$category_id]['sub_category'][$sub_category->term_id]['key'] = $sub_category->slug; 
						}   
					}
				}       
			}
		
		
		}	
		
		$price_args = array( 'post_type' => 'product', 'posts_per_page' => -1, 'post_status' => 'publish');
		
		$featured_query = new WP_Query( $price_args );
		
		while ($featured_query->have_posts()){
		
			$featured_query->the_post();
			
			$product = get_product( $featured_query->post->ID );
			$_product = wc_get_product( $product->id );
			$price = $_product->post->_sale_price;
			if($price == ''){
				$price = $_product->post->_regular_price;
			}
			if($price)
				$prod_cat['Price']['prod_'.$featured_query->post->ID] = $price;
		}
		
		return $prod_cat;
	
	}
	
	/*Profile api*/
	public function userProfile() {
		global $json_api, $current_user;
		$site_url = site_url();
		$cookie = $json_api->query->cookie;	
		$validate_cookie_api = $site_url."/api/auth/validate_auth_cookie/?cookie=".$cookie;
		
		$json_auth_cookie = file_get_contents($validate_cookie_api);
		$json_data = json_decode($json_auth_cookie, true);
		$status = $json_data['status'];
		$valid = $json_data['valid'];
				
		if($status == 'ok' && $valid){
			$user_profile = array();
			$user_profile['first_name'] = get_user_meta( $current_user->ID, 'first_name', true );
			$user_profile['last_name'] = get_user_meta( $current_user->ID, 'last_name', true );
			$user_profile['billing_company'] = get_user_meta( $current_user->ID, 'billing_company', true );
			$user_profile['billing_vat'] = get_user_meta( $current_user->ID, 'billing_vat', true );
			$user_profile['billing_address1'] = get_user_meta( $current_user->ID, 'billing_address_1', true ); 
			$user_profile['billing_address2'] = get_user_meta( $current_user->ID, 'billing_address_2', true );
			$user_profile['billing_city'] = get_user_meta( $current_user->ID, 'billing_city', true );
			$user_profile['billing_state'] = get_user_meta( $current_user->ID, 'billing_state', true );
			$user_profile['billing_country'] = get_user_meta( $current_user->ID, 'billing_country', true );
			$user_profile['billing_postcode'] = get_user_meta( $current_user->ID, 'billing_postcode', true );
			$user_profile['billing_email'] = get_user_meta( $current_user->ID, 'billing_email', true );
			$user_profile['billing_phone'] = get_user_meta( $current_user->ID, 'billing_phone', true );
			$user_profile['billing_gender'] = get_user_meta( $current_user->ID, 'billing_gender', true );
			$user_profile['billing_user_dob'] = get_user_meta( $current_user->ID, 'billing_user_dob', true );			
						
			return array(
				"user_profile" => $user_profile,
			);
		}
		else{
			return "invalid user";
		}
	}
	
	/*Profile api*/
	public function updateProfile() {
		global $json_api, $current_user;
		$site_url = site_url();
		$cookie = $json_api->query->cookie;	
		$first_name = $json_api->query->first_name;	
		$last_name = $json_api->query->last_name;	
		$billing_company = $json_api->query->billing_company;	
		$billing_vat = $json_api->query->billing_vat;	
		$billing_address1 = $json_api->query->billing_address1;	
		$billing_address2 = $json_api->query->billing_address2;	
		$billing_city = $json_api->query->billing_city;	
		$billing_state = $json_api->query->billing_state;	
		$billing_country = $json_api->query->billing_country;	
		$billing_postcode = $json_api->query->billing_postcode;	
		$billing_email = $json_api->query->billing_email;	
		$billing_phone = $json_api->query->billing_phone;	
		$billing_gender = $json_api->query->billing_gender;
		$billing_user_dob = $json_api->query->billing_user_dob;
		$validate_cookie_api = $site_url."/api/auth/validate_auth_cookie/?cookie=".$cookie;
		
		$json_auth_cookie = file_get_contents($validate_cookie_api);
		$json_data = json_decode($json_auth_cookie, true);
		$status = $json_data['status'];
		$valid = $json_data['valid'];
				
		if($status == 'ok' && $valid){
			$user_profile = array();
			if($first_name)
				$user_profile['first_name'] = update_user_meta( $current_user->ID, 'first_name', $first_name );
			if($last_name)
				$user_profile['last_name'] = update_user_meta( $current_user->ID, 'last_name', $last_name );
			if($billing_company)
				$user_profile['billing_company'] = update_user_meta( $current_user->ID, 'billing_company', $billing_company );
			if($billing_vat)
				$user_profile['billing_vat'] = update_user_meta( $current_user->ID, 'billing_vat', $billing_vat );
			if($billing_address1)
				$user_profile['billing_address1'] = update_user_meta( $current_user->ID, 'billing_address_1', $billing_address1 ); 
			if($billing_address2)
				$user_profile['billing_address2'] = update_user_meta( $current_user->ID, 'billing_address_2', $billing_address2 );
			if($billing_city)
				$user_profile['billing_city'] = update_user_meta( $current_user->ID, 'billing_city', $billing_city );
			if($billing_state)
				$user_profile['billing_state'] = update_user_meta( $current_user->ID, 'billing_state', $billing_state );
			if($billing_country)
				$user_profile['billing_country'] = update_user_meta( $current_user->ID, 'billing_country', $billing_country );
			if($billing_postcode)
				$user_profile['billing_postcode'] = update_user_meta( $current_user->ID, 'billing_postcode', $billing_postcode);
			if($billing_email)
				$user_profile['billing_email'] = update_user_meta( $current_user->ID, 'billing_email', $billing_email );
			if($billing_phone)
				$user_profile['billing_phone'] = update_user_meta( $current_user->ID, 'billing_phone', $billing_phone);
			if($billing_gender)
				$user_profile['billing_gender'] = update_user_meta( $current_user->ID, 'billing_gender', $billing_gender);
			if($billing_user_dob)
				$user_profile['billing_user_dob'] = update_user_meta( $current_user->ID, 'billing_user_dob', $billing_user_dob );			
						
			return array(
				"message" => 'Updated user profile successfully',
			);
		}
		else{
			return "invalid user";
		}
	}
	
	/*Listing of Publishers*/
	public function listing_publishers() {
		global $json_api, $wpdb;
		
		$publishers_list = array();
		$taxonomy = 'pa_product-publisher';
		$tax_terms = get_terms($taxonomy);
		
		foreach ($tax_terms as $tax_term) {
			$pub_arr = array();
			$pub_arr['key'] = $tax_term->slug;
			$pub_arr['name'] = $tax_term->name;
			$publishers_list[] = $pub_arr;
		}
		
		$publishers['publishers'] = $publishers_list;
		
		return $publishers;
	}
	
	/*Listing of Authors*/
	public function listing_authors() {
		global $json_api, $wpdb;
		
		$authors_list = array();
		$taxonomy = 'pa_product-author';
		$tax_terms = get_terms($taxonomy);
		
		
		foreach ($tax_terms as $tax_term) {
			$author_arr = array();
			$author_arr['key'] = $tax_term->slug;
			$author_arr['name'] = $tax_term->name;
			$authors_list[] = $author_arr;
		}
		
		$authors_arr['authors'] = $authors_list;
		
		return $authors_arr;
	}
	/* Live event page content*/
	public function live_event_content() {
		$desc = (get_option( '_vivid_event_description'));
		$video = get_option( '_vivid_video_url');
		return array("video" => $video, "content" => $desc);
	}

	/*Listing of Publishers*/
	public function listing_languages() {
		global $json_api, $wpdb;
		
		$languages_list = array();
		$taxonomy = 'pa_product-language';
		$tax_terms = get_terms($taxonomy);
		
		foreach ($tax_terms as $tax_term) {
			$lang_arr = array();
			$lang_arr['key'] = $tax_term->slug;
			$lang_arr['name'] = $tax_term->name;
			$languages_list[] = $lang_arr;
		}
		
		$langs_arr['languages'] = $languages_list;
		
		return $langs_arr;
	}
	
	/*Listing of Currencies*/
	public function listing_currencies() {
		global $json_api, $wpdb;
		
		$currencies_list = array();
		//$exchange_rates = get_option('wmcs_live_exchange_rates');
		
		$currencies_list['currencies'][] = array('code' => 'INR', 'currency' => 'INR(₹)');
		$currencies_list['currencies'][] = array('code' => 'EUR', 'currency' => 'EUR(€)');
		$currencies_list['currencies'][] = array('code' => 'GBP', 'currency' => 'GBP(£)');
		$currencies_list['currencies'][] = array('code' => 'USD', 'currency' => 'USD($)');
		//$currencies_list['currencies'][] = $exchange_rates['rates'];
		return $currencies_list;
	}
	
	/*API for currency conversion*/
	public function currencyConverter() {
		global $json_api, $wpdb;
		
		$conversion_amt = '';
		$currencyCode = $json_api->query->currency_code;	
		$amt = $json_api->query->amt;	
		$exchange_rates = get_option( 'wmcs_live_exchange_rates' );
		if($currencyCode != 'INR'){
			$ex_rate = $exchange_rates['rates'][$currencyCode];
			$conversion_amt = $amt*$ex_rate;
			$conversion_amt = round($conversion_amt, 2);
		}
		else{
			$conversion_amt = $amt;
		}
		return $conversion_amt;
	}
	public function listing_parent_categories() {
		global $json_api, $wpdb;
		$site_url = site_url();
		
		$taxonomy     = 'product_cat';
		$orderby      = 'name';  
		$show_count   = 0;      // 1 for yes, 0 for no
		$pad_counts   = 0;      // 1 for yes, 0 for no
		$hierarchical = 1;      // 1 for yes, 0 for no  
		$title        = '';  
		$empty        = 0;

		$args = array(
			 'taxonomy'     => $taxonomy,
			 'orderby'      => $orderby,
			 'show_count'   => $show_count,
			 'pad_counts'   => $pad_counts,
			 'hierarchical' => $hierarchical,
			 'title_li'     => $title,
			 'hide_empty'   => $empty,
			 'parent'				=> 0,
		);

		$paged = $json_api->query->paged;
		$per_page = $json_api->query->per_page;
		if($per_page != 'all') {
			$paged = $paged ? $paged : 1;
			$cat_per_page = $per_page ? $per_page : 10;
			$offset = ($cat_per_page * $paged) - $cat_per_page ;
			$args['number'] = $cat_per_page; 
			$args['offset'] = $offset; 
		}

		$pareant_categories_data = get_categories( $args );
		$pareant_categories = array();

		foreach($pareant_categories_data as $category_data) {
			$pareant_categories[] = array('term_id' => $category_data->term_id, 'name' => $category_data->name, 'slug' => $category_data->slug);			
		}
		return array(
				"categories" => $pareant_categories,
		);
	}
	public function listing_children_categories() {
		global $json_api;
		$parent_id = $json_api->query->pt_id;

		if (!$json_api->query->pt_id) {
			$json_api->error("You must include 'pt_id' var in your request. ");
		}

		$args = array(
				'taxonomy'     => 'product_cat',
				'child_of'     => 0,
				'parent'       => $parent_id,
				'orderby'      => 'name',
				'show_count'   => 0,
				'pad_counts'   => 0,
				'hierarchical' => 1,
				'title_li'     => '',
				'hide_empty'   => 0
		);
		$paged = $json_api->query->paged;
		$per_page = $json_api->query->per_page;
		$cat_per_page = $per_page ? $per_page : 10;
		if($per_page != 'all') {
			$paged = $paged ? $paged : 1;
			$cat_per_page = $cat_per_page;
			$offset = ($cat_per_page * $paged) - $cat_per_page ;
			$args['number'] = $cat_per_page; 
			$args['offset'] = $offset; 
		}

		$children_categories_data = get_categories( $args );
		$children_categories = array();

		foreach($children_categories_data as $category_data) {
			$children_categories[] = array('term_id' => $category_data->term_id, 'name' => $category_data->name, 'slug' => $category_data->slug);			
		}
		return array(
				"childCategories" => $children_categories,
		);
	}
	public function check_apk_ver() {
		global $json_api, $current_user;
		$site_url = site_url();
		$app_version = $json_api->query->app_version;
		if(empty($app_version)) {
			$json_api->error("Please send app version.");
		} else {
			$ver = get_option( '_vivid_app_version');
			if($ver === $app_version) {
				return array("success" => "true");
			} else {
				$json_api->error("App version is outdated.");
			}
		}
	}
	public function check_android_app_ver() {
		global $json_api, $current_user;
		$site_url = site_url();
		$app_version = $json_api->query->app_version;
		if(empty($app_version)) {
			$json_api->error("Please send app version.");
		} else {
			$ver = get_option( '_vivid_android_app_version');
			//p($ver, $app_version);
			if($ver === $app_version) {
				return array("success" => "true");
			} else {
				$json_api->error("App version is outdated.");
			}
		}
	}

	public function check_ios_app_ver() {
		global $json_api, $current_user;
		$site_url = site_url();
		$app_version = $json_api->query->app_version;
		if(empty($app_version)) {
			$json_api->error("Please send app version.");
		} else {
			$ver = get_option( '_vivid_ios_app_version');
			if($ver === $app_version) {
				return array("success" => "true");
			} else {
				$json_api->error("App version is outdated.");
			}
		}
	}

	public function vid_generate_hash() {
		global $json_api, $wpdb;
		
		$payu_key = $json_api->query->payu_key;
		$payu_salt = $json_api->query->payu_salt;
		$txnid = $json_api->query->txnid;
		$amount = $json_api->query->amount;
		$product_info = $json_api->query->product_info;
		$c_name = $json_api->query->c_name;
		$c_email = $json_api->query->c_email;
		
		$payu_key = "EOcrTa";
		$payu_salt = "4WnWF3vr";
		/*
		if (!$json_api->query->payu_key) {
			$json_api->error("You must include 'payu_key' var in your request. ");
		}
		if (!$json_api->query->payu_salt) {
			$json_api->error("You must include 'salt' var in your request. ");
		}
		*/
		/*
		if (!$json_api->query->txnid) {
			$json_api->error("You must include 'txnid' var in your request. ");
		}
		*/
		if (!$json_api->query->amount) {
			$json_api->error("You must include 'amount' var in your request. ");
		}
		if (!$json_api->query->product_info) {
			$json_api->error("You must include 'product_info' var in your request. ");
		}
		if (!$json_api->query->c_name) {
			$json_api->error("You must include 'customer name' var in your request. ");
		}
		if (!$json_api->query->c_email) {
			$json_api->error("You must include 'customer email' var in your request. ");
		}
		
		$txnid=time().rand(1000,99999);
		
		$string = ($payu_key|$txnid|$amount|$product_info|$c_name|$c_email|''|''|''|''|''|''|''|''|''|''|$payu_salt);
		return array( 
			"hash" => strtolower(hash("sha512",$string)),
			'txnid' => $txnid
			);
	}
	public function vid_generate_revhash() {
		global $json_api, $wpdb;
		
		/*
		$payu_key = $json_api->query->payu_key;
		$payu_salt = $json_api->query->payu_salt;
		*/
		$txnid = $json_api->query->txnid;
		$amount = $json_api->query->amount;
		$product_info = $json_api->query->product_info;
		$c_name = $json_api->query->c_name;
		$c_email = $json_api->query->c_email;
		$status = $json_api->query->status;
		
		$payu_key = "EOcrTa";
		$payu_salt = "4WnWF3vr";

		/*
		if (!$json_api->query->payu_key) {
			$json_api->error("You must include 'payu_key' var in your request. ");
		}
		if (!$json_api->query->payu_salt) {
			$json_api->error("You must include 'salt' var in your request. ");
		}
		*/
		if (!$json_api->query->txnid) {
			$json_api->error("You must include 'txnid' var in your request. ");
		}
		if (!$json_api->query->amount) {
			$json_api->error("You must include 'amount' var in your request. ");
		}
		if (!$json_api->query->product_info) {
			$json_api->error("You must include 'product_info' var in your request. ");
		}
		if (!$json_api->query->c_name) {
			$json_api->error("You must include 'customer name' var in your request. ");
		}
		if (!$json_api->query->c_email) {
			$json_api->error("You must include 'customer email' var in your request. ");
		}
		if (!$json_api->query->status) {
			$json_api->error("You must include 'status' var in your request. ");
		}
		
		$string2 = ($payu_salt|$status|''|''|''|''|''|''|''|''|''|''|$c_email|$c_name|$product_info|$amount|$txnid|$payu_key);

		//sha512(SALT|status||||||udf5|udf4|udf3|udf2|udf1|email|firstname|productinfo|amount|txnid|key)
		return array( 
			"rev_hash" => hash("sha512",$string2),
			'c_email' => $c_email
			);
	}

	public function vid_push_notification() {
		global $json_api, $wpdb;
		
		$uid = $json_api->query->uid;

		if (!$json_api->query->uid) {
			$json_api->error("You must include 'uid' var in your request. ");
		}
		
		$notifications =  $wpdb->get_results("select * from wp_mobile_notifications where uid = $uid");
		
		$data = array();
		$m = array();
		if($notifications) {
			foreach($notifications as $notification) {
				$n = unserialize($notification->notification);
				if(in_array($n,  $data)) {
					continue;
				} else {
					$data[] = $n;
					$m[]=  (array)$notification;
				}
			}
		}
		$n= array();
		foreach($m as $mn) {
			$n[] = unserialize($mn['notification']);
		}

		return array('notifications' => $n);
	}
}

function products_list($category, $filter_product_language, $filter_product_author, $filter_product_publisher, $filter_product_min_price, $filter_product_max_price, $prod_title, $sortby, $sortOrder, $currencyCode, $per_page = null, $paged = null ) {
	global $wpdb;
	$products = array();
	
	$exchange_rates = get_option( 'wmcs_live_exchange_rates' );
	
	$sortArr = array('price' => '_price', 'date' => 'date');
	
	if(!$sortOrder){
		$sortOrder = 'ASC';
	}
	
	if($filter_product_language != '' || $filter_product_author !='' || $filter_product_publisher != ''){
		if($filter_product_language != ''){
			if($filter_product_author !='' && $filter_product_publisher != ''){
				if($sortby){
					$args = array( 
					'post_type'				=> 'product',
					'post_status' 			=> 'publish',
					'order' 				=> $sortOrder,
					'orderby' 				=> 'meta_value_num',
					'meta_key' 				=> $sortArr[$sortby],	
					'posts_per_page' 		=> -1,
					'meta_query' 			=> array(
						array(
							'key' 			=> '_visibility',
							'value' 		=> array('catalog', 'visible'),
							'compare' 		=> 'IN'
						)
					),
					'tax_query' 			=> array(
						'relation' => 'AND',
							array(
								'taxonomy' 		=> 'pa_product-language',
								'terms' 		=> explode(",", $filter_product_language),
								'field' 		=> 'slug',
								'operator' 		=> 'IN'
							),
							array(
								'taxonomy' 		=> 'pa_product-author',
								'terms' 		=> explode(",", $filter_product_author),
								'field' 		=> 'slug',
								'operator' 		=> 'IN'
							),
							array(
								'taxonomy' 		=> 'pa_product-publisher',
								'terms' 		=> explode(",", $filter_product_publisher),
								'field' 		=> 'slug',
								'operator' 		=> 'IN'
							)
						)
					);
				}
				else{
					$args = array( 
					'post_type'				=> 'product',
					'post_status' 			=> 'publish',
					'posts_per_page' 		=> -1,
					'meta_query' 			=> array(
							array(
								'key' 			=> '_visibility',
								'value' 		=> array('catalog', 'visible'),
								'compare' 		=> 'IN'
							)
						),
						'tax_query' 			=> array(
							'relation' => 'AND',
							array(
								'taxonomy' 		=> 'pa_product-language',
								'terms' 		=> explode(",", $filter_product_language),
								'field' 		=> 'slug',
								'operator' 		=> 'IN'
							),
							array(
								'taxonomy' 		=> 'pa_product-author',
								'terms' 		=> explode(",", $filter_product_author),
								'field' 		=> 'slug',
								'operator' 		=> 'IN'
							),
							array(
								'taxonomy' 		=> 'pa_product-publisher',
								'terms' 		=> explode(",", $filter_product_publisher),
								'field' 		=> 'slug',
								'operator' 		=> 'IN'
							)
						)
					);
				}				
			}
			else if($filter_product_author !='' && $filter_product_publisher == ''){
				
				if($sortby){
					$args = array( 
						'post_type'				=> 'product',
						'post_status' 			=> 'publish',
						'order' 				=> $sortOrder,
						'orderby' 				=> 'meta_value_num',
						'meta_key' 				=> $sortArr[$sortby],	
						'posts_per_page' 		=> -1,
						'meta_query' 			=> array(
							array(
								'key' 			=> '_visibility',
								'value' 		=> array('catalog', 'visible'),
								'compare' 		=> 'IN'
							)
						),
						'tax_query' 			=> array(
							'relation' => 'AND',
							array(
								'taxonomy' 		=> 'pa_product-language',
								'terms' 		=> explode(",", $filter_product_language),
								'field' 		=> 'slug',
								'operator' 		=> 'IN'
							),
							array(
								'taxonomy' 		=> 'pa_product-author',
								'terms' 		=> explode(",", $filter_product_author),
								'field' 		=> 'slug',
								'operator' 		=> 'IN'
							)
						)
					);
				}
				else{
					$args = array( 
						'post_type'				=> 'product',
						'post_status' 			=> 'publish',
						'posts_per_page' 		=> -1,
						'meta_query' 			=> array(
							array(
								'key' 			=> '_visibility',
								'value' 		=> array('catalog', 'visible'),
								'compare' 		=> 'IN'
							)
						),
						'tax_query' 			=> array(
							'relation' => 'AND',
							array(
								'taxonomy' 		=> 'pa_product-language',
								'terms' 		=> explode(",", $filter_product_language),
								'field' 		=> 'slug',
								'operator' 		=> 'IN'
							),
							array(
								'taxonomy' 		=> 'pa_product-author',
								'terms' 		=> explode(",", $filter_product_author),
								'field' 		=> 'slug',
								'operator' 		=> 'IN'
							)
						)
					);
				}				
			}
			else if($filter_product_author =='' && $filter_product_publisher != ''){
				
				if($sortby){
					$args = array( 
						'post_type'				=> 'product',
						'post_status' 			=> 'publish',
						'order' 				=> $sortOrder,
						'orderby' 				=> 'meta_value_num',
						'meta_key' 				=> $sortArr[$sortby],	
						'posts_per_page' 		=> -1,
						'meta_query' 			=> array(
							array(
								'key' 			=> '_visibility',
								'value' 		=> array('catalog', 'visible'),
								'compare' 		=> 'IN'
							)
						),
						'tax_query' 			=> array(
							'relation' => 'AND',
							array(
								'taxonomy' 		=> 'pa_product-language',
								'terms' 		=> explode(",", $filter_product_language),
								'field' 		=> 'slug',
								'operator' 		=> 'IN'
							),
							array(
								'taxonomy' 		=> 'pa_product-publisher',
								'terms' 		=> explode(",", $filter_product_publisher),
								'field' 		=> 'slug',
								'operator' 		=> 'IN'
							)
						)
					);
				}
				else{
					$args = array( 
						'post_type'				=> 'product',
						'post_status' 			=> 'publish',	
						'posts_per_page' 		=> -1,
						'meta_query' 			=> array(
							array(
								'key' 			=> '_visibility',
								'value' 		=> array('catalog', 'visible'),
								'compare' 		=> 'IN'
							)
						),
						'tax_query' 			=> array(
							'relation' => 'AND',
							array(
								'taxonomy' 		=> 'pa_product-language',
								'terms' 		=> explode(",", $filter_product_language),
								'field' 		=> 'slug',
								'operator' 		=> 'IN'
							),
							array(
								'taxonomy' 		=> 'pa_product-publisher',
								'terms' 		=> explode(",", $filter_product_publisher),
								'field' 		=> 'slug',
								'operator' 		=> 'IN'
							)
						)
					);
				}				
			}
			else{				
				if($sortby){
					$args = array( 
						'post_type'				=> 'product',
						'post_status' 			=> 'publish',
						'order' 				=> $sortOrder,
						'orderby' 				=> 'meta_value_num',
						'meta_key' 				=> $sortArr[$sortby],	
						'posts_per_page' 		=> -1,
						'meta_query' 			=> array(
							array(
								'key' 			=> '_visibility',
								'value' 		=> array('catalog', 'visible'),
								'compare' 		=> 'IN'
							)
						),
						'tax_query' 			=> array(
							array(
								'taxonomy' 		=> 'pa_product-language',
								'terms' 		=> explode(",", $filter_product_language),
								'field' 		=> 'slug',
								'operator' 		=> 'IN'
							)
						)
					);
				}
				else{
					$args = array( 
						'post_type'				=> 'product',
						'post_status' 			=> 'publish',						
						'posts_per_page' 		=> -1,
						'meta_query' 			=> array(
							array(
								'key' 			=> '_visibility',
								'value' 		=> array('catalog', 'visible'),
								'compare' 		=> 'IN'
							)
						),
						'tax_query' 			=> array(
							array(
								'taxonomy' 		=> 'pa_product-language',
								'terms' 		=> explode(",", $filter_product_language),
								'field' 		=> 'slug',
								'operator' 		=> 'IN'
							)
						)
					);
				}				
			}			
		}
		else if($filter_product_author !=''){
			if($filter_product_author !='' && $filter_product_publisher != ''){
				if($sortby){
					$args = array( 
						'post_type'				=> 'product',
						'post_status' 			=> 'publish',
						'order' 				=> $sortOrder,
						'orderby' 				=> 'meta_value_num',
						'meta_key' 				=> $sortArr[$sortby],	
						'posts_per_page' 		=> -1,
						'meta_query' 			=> array(
							array(
								'key' 			=> '_visibility',
								'value' 		=> array('catalog', 'visible'),
								'compare' 		=> 'IN'
							)
						),
						'tax_query' 			=> array(
							'relation' => 'AND',
							array(
								'taxonomy' 		=> 'pa_product-author',
								'terms' 		=> explode(",", $filter_product_author),
								'field' 		=> 'slug',
								'operator' 		=> 'IN'
							),
							array(
								'taxonomy' 		=> 'pa_product-publisher',
								'terms' 		=> explode(",", $filter_product_publisher),
								'field' 		=> 'slug',
								'operator' 		=> 'IN'
							)
						)
					);
				}
				else{
					$args = array( 
						'post_type'				=> 'product',
						'post_status' 			=> 'publish',	
						'posts_per_page' 		=> -1,
						'meta_query' 			=> array(
							array(
								'key' 			=> '_visibility',
								'value' 		=> array('catalog', 'visible'),
								'compare' 		=> 'IN'
							)
						),
						'tax_query' 			=> array(
							'relation' => 'AND',
							array(
								'taxonomy' 		=> 'pa_product-author',
								'terms' 		=> explode(",", $filter_product_author),
								'field' 		=> 'slug',
								'operator' 		=> 'IN'
							),
							array(
								'taxonomy' 		=> 'pa_product-publisher',
								'terms' 		=> explode(",", $filter_product_publisher),
								'field' 		=> 'slug',
								'operator' 		=> 'IN'
							)
						)
					);
				}				
			}
			else{
				
				if($sortby){
					$args = array( 
						'post_type'				=> 'product',
						'post_status' 			=> 'publish',
						'order' 				=> $sortOrder,
						'orderby' 				=> 'meta_value_num',
						'meta_key' 				=> $sortArr[$sortby],	
						'posts_per_page' 		=> -1,
						'meta_query' 			=> array(
							array(
								'key' 			=> '_visibility',
								'value' 		=> array('catalog', 'visible'),
								'compare' 		=> 'IN'
							)
						),
						'tax_query' 			=> array(
							array(
								'taxonomy' 		=> 'pa_product-author',
								'terms' 		=> explode(",", $filter_product_author),
								'field' 		=> 'slug',
								'operator' 		=> 'IN'
							)
						)
					);
				}
				else{
					$args = array( 
						'post_type'				=> 'product',
						'post_status' 			=> 'publish',
						'posts_per_page' 		=> -1,
						'meta_query' 			=> array(
							array(
								'key' 			=> '_visibility',
								'value' 		=> array('catalog', 'visible'),
								'compare' 		=> 'IN'
							)
						),
						'tax_query' 			=> array(
							array(
								'taxonomy' 		=> 'pa_product-author',
								'terms' 		=> explode(",", $filter_product_author),
								'field' 		=> 'slug',
								'operator' 		=> 'IN'
							)
						)
					);
				}				
			}
		}
		else{
			
			if($sortby){
				$args = array( 
					'post_type'				=> 'product',
					'post_status' 			=> 'publish',
					'order' 				=> $sortOrder,
					'orderby' 				=> 'meta_value_num',
					'meta_key' 				=> $sortArr[$sortby],	
					'posts_per_page' 		=> -1,
					'meta_query' 			=> array(
						array(
							'key' 			=> '_visibility',
							'value' 		=> array('catalog', 'visible'),
							'compare' 		=> 'IN'
						)
					),
					'tax_query' 			=> array(
						array(
							'taxonomy' 		=> 'pa_product-publisher',
							'terms' 		=> explode(",", $filter_product_publisher),
							'field' 		=> 'slug',
							'operator' 		=> 'IN'
						)
					)
				);
			}
			else{
				$args = array( 
					'post_type'				=> 'product',
					'post_status' 			=> 'publish',
					'posts_per_page' 		=> -1,
					'meta_query' 			=> array(
						array(
							'key' 			=> '_visibility',
							'value' 		=> array('catalog', 'visible'),
							'compare' 		=> 'IN'
						)
					),
					'tax_query' 			=> array(
						array(
							'taxonomy' 		=> 'pa_product-publisher',
							'terms' 		=> explode(",", $filter_product_publisher),
							'field' 		=> 'slug',
							'operator' 		=> 'IN'
						)
					)
				);
			}			
		}
		if($category != '' && $category != 'all'){
			$args['product_cat'] = $category;
			//return $args;
		}
	}
	else if($category == 'featured'){
	
		if($sortby){
			$args = array(
				'post_type' => 'product',
				'posts_per_page' => -1,
				'order' => $sortOrder,
				'orderby' => 'meta_value_num',
				'meta_key' => $sortArr[$sortby],
				'meta_query' => array(
									array(
									'key' => '_featured',
									'value' => 'yes',
									)
								),
			);
		}
		else{
			$args = array(
				'post_type' => 'product',
				'posts_per_page' => -1,
				'meta_query' => array(
									array(
									'key' => '_featured',
									'value' => 'yes',
									)
								),
			);
		}
	}
	else if($prod_title != ''){	
		if($sortby){
			$args = array(
				'post_type' => 'product',
				'posts_per_page' => -1,
				'order' => $sortOrder,
				'orderby' => 'meta_value_num',
				'meta_key' => $sortArr[$sortby],
				's' => "$prod_title"
			);
		}
		else{
			$args = array(
				'post_type' => 'product',
				'posts_per_page' => -1,
				's' => "$prod_title"
			);
		}
	}	
	/*else if($prod_title != ''){	
		$args = array( 'post_type' => 'product', 'posts_per_page' => -1, 's' => "$prod_title");
	}*/
	else if($filter_product_min_price != '' && $filter_product_max_price != ''){	
		if($sortby){
			$args = array(
					'post_status' => 'publish',
					'post_type' => 'product',
					'posts_per_page' => -1,
					'order' => $sortOrder,
					'orderby' => 'meta_value_num',
					'meta_key' => $sortArr[$sortby],
					'meta_query' => array(
						array(
							'key' => '_price',
							'value' => array($filter_product_min_price, $filter_product_max_price),
							'compare' => 'BETWEEN',
							'type' => 'NUMERIC'
						)
					)
				);
		}
		else{
			$args = array(
					'post_status' => 'publish',
					'post_type' => 'product',
					'posts_per_page' => -1,
					'meta_query' => array(
						array(
							'key' => '_price',
							'value' => array($filter_product_min_price, $filter_product_max_price),
							'compare' => 'BETWEEN',
							'type' => 'NUMERIC'
						)
					)
				);
		}
		if($category != '' && $category != 'all'){
			$args['product_cat'] = $category;
			//return $args;
		}
	}
	else if($category == 'all'){
		
		$featured_prods_arr = array();
		
		$featured_prods_arr['key'] = 'featured';
		$featured_prods_arr['value'] = 'Featured Products';
		$featured_prods_arr['products'] = products_list('featured', '', '', '', '', '', '');
		$featuredProducts['featured'][] = $featured_prods_arr;
		
		$taxonomy     = 'product_cat';
		$orderby      = 'name';  
		$show_count   = 0;      // 1 for yes, 0 for no
		$pad_counts   = 0;      // 1 for yes, 0 for no
		$hierarchical = 1;      // 1 for yes, 0 for no  
		$title        = '';  
		$empty        = 0;

		$args = array(
			 'taxonomy'     => $taxonomy,
			 'orderby'      => $orderby,
			 'show_count'   => $show_count,
			 'pad_counts'   => $pad_counts,
			 'hierarchical' => $hierarchical,
			 'title_li'     => $title,
			 'hide_empty'   => $empty
		);
		$all_categories = get_categories( $args );
		$list_categories = array();
		$subCatArr = array();
		foreach ($all_categories as $cat) {
			if($cat->category_parent == 0) {
				$new_arr = array();
				$category_id = $cat->term_id;       
				 
				$new_arr['key'] = $cat->slug; 
				$args2 = array(
						'taxonomy'     => $taxonomy,
						'child_of'     => 0,
						'parent'       => $category_id,
						'orderby'      => $orderby,
						'show_count'   => $show_count,
						'pad_counts'   => $pad_counts,
						'hierarchical' => $hierarchical,
						'title_li'     => $title,
						'hide_empty'   => $empty
				);
				$sub_cats = get_categories( $args2 );
				if($sub_cats) {
					
					foreach($sub_cats as $sub_category) {
						$subCatArr[$sub_category->slug] = $sub_category->name;
						$new_arr['sub_category'][] = $sub_category->slug;
						
					}   
				}
				$list_categories[] = $new_arr;
			}
		}
		
		$cat_prod_list = array();
		foreach($list_categories as $pcat){
			$cat_slug = $pcat['key'];
			$sub_cat_arr = $pcat['sub_category'];
			
			foreach($sub_cat_arr as $sub_cat){
				$cat_name = $sub_cat;
				if($cat_name != ''){
					
					if($sortby){
						$args = array(
							'post_type' => 'product',
							'posts_per_page' => -1,
							'order' => $sortOrder,
							'orderby' => 'meta_value_num',
							'meta_key' => $sortArr[$sortby],
							'product_cat' => "$cat_name"
						);
					}
					else{
						$args = array(
							'post_type' => 'product',
							'posts_per_page' => -1,
							'product_cat' => "$cat_name"
						);
					}

					$featured_query = new WP_Query( $args );
			
					if ($featured_query->have_posts()) : 
						$Prodarr = array();
						while ($featured_query->have_posts()) : 
						
							$featured_query->the_post();
							
							$product = get_product( $featured_query->post->ID );
							$_product    = wc_get_product( $product->id );
							
							$product_auth = get_the_terms( $product->id, 'pa_product-author');
							
							$product_publisher = get_the_terms( $product->id, 'pa_product-publisher');
							
							$product_narrator = get_the_terms( $product->id, 'pa_product-narrator');
							//$prod_thumb_src = wp_get_attachment_image_src( get_post_thumbnail_id($result->product_id, array(65,65)));
							
							$product_language = get_the_terms( $product->id, 'pa_product-language');
							
							$prod_thumb_src = wp_get_attachment_image_src( $_product->get_image_id(), 'shop_catalog');
										
							$prod_auths = array();
							foreach($product_auth as $auth){
								$prod_auths[] =  $auth->name;
							}
							
							$prod_lang = array();
							foreach($product_language as $lang){
								$prod_lang[] =  $lang->name;
							}

							$prod_publisher = array();
							foreach($product_publisher as $publisher){
								$prod_publisher[] =  $publisher->name;
							}
							
							$prod_narrator = array();
							foreach($product_narrator as $narrator){
								$prod_narrator[] =  $narrator->name;
							}
							
							$sale_price = $_product->post->_sale_price;
							$reg_price = $_product->post->_regular_price;
							
							if($currencyCode && $currencyCode != 'INR'){
								$ex_rate = $exchange_rates['rates'][$currencyCode];
								if($sale_price)
									$sale_price = $sale_price*$ex_rate;
									$sale_price = round($sale_price, 2);
								
								if($reg_price)
									$reg_price = $reg_price*$ex_rate;
									$reg_price = round($reg_price, 2);
							}
							
							$product_cat = array();
							$terms = get_the_terms( $product->ID, 'product_cat' );
							foreach ($terms as $term) {
								$product_cat[] = $term->name;
							}
							
							$average = get_post_meta( $product->ID, '_wc_average_rating', true );
							$rating = (string) floatval( $average );
							
							$Prodarr[] = array(
									'product_id'          => $product->id,
									'product_title'       => $_product->post->post_title,
									'product_authors' 	  => $prod_auths,						
									'product_language' 	  => $prod_lang,		
									'product_sale_price' 	  => $sale_price,
									'product_regular_price' 	  => $reg_price,
									'product_thumb_url'  => $prod_thumb_src[0],				
									'product_rating' => $product->get_average_rating(),
									'product_cat' => $product_cat,
									'product_publisher' => $prod_publisher,
									'prod_narrator' => $prod_narrator,
									'blog_post_url' => get_post_meta($product->id, 'blog_post_url', true),
								);
							
							// Output product information here
							
						endwhile;
						//[$cat_slug][$cat_name]['products'] = $Prodarr;
						
						$cat_prod_list1['key'] = $cat_name;
						$cat_prod_list1['value'] = $subCatArr[$cat_name];
						$cat_prod_list1['products'] = $Prodarr;
						$cat_prod_list[$cat_slug][] = $cat_prod_list1;
					else:
						$Prodarr1 = array();
						//$cat_prod_list[$cat_slug][$cat_name]['products'] = $Prodarr1;
						
						$cat_prod_list1['key'] = $cat_name;
						$cat_prod_list1['value'] = $subCatArr[$cat_name];
						$cat_prod_list1['products'] = $Prodarr1;
						$cat_prod_list[$cat_slug][] = $cat_prod_list1;
					endif;

					wp_reset_query(); // Remember to reset						
				}
			}
			/*fetch featured products of the given category*/
			//$args = array( 'post_type' => 'product', 'posts_per_page' => -1, 'product_cat' => "$cat_name");
			
			if($sortby){
				$args = array(
						'post_type' => 'product',
						'posts_per_page' => -1,
						'product_cat' => $cat_slug,
						'order' => $sortOrder,
						'orderby' => 'meta_value_num',
						'meta_key' => $sortArr[$sortby],
						'meta_query' => array(
											array(
											'key' => '_featured',
											'value' => 'yes',
											)
										),
					);
			}
			else{
				$args = array(
						'post_type' => 'product',
						'posts_per_page' => -1,
						'product_cat' => $cat_slug,
						'meta_query' => array(
											array(
											'key' => '_featured',
											'value' => 'yes',
											)
										),
					);
			}
			

			$featured_query = new WP_Query( $args );
	
			if ($featured_query->have_posts()) : 
				$Prodarr = array();
				while ($featured_query->have_posts()) : 
				
					$featured_query->the_post();
					
					$product = get_product( $featured_query->post->ID );
					$_product    = wc_get_product( $product->id );
					
					$product_auth = get_the_terms( $product->id, 'pa_product-author');
					
					$product_publisher = get_the_terms( $product->id, 'pa_product-publisher');
					
					$product_narrator = get_the_terms( $product->id, 'pa_product-narrator');
					//$prod_thumb_src = wp_get_attachment_image_src( get_post_thumbnail_id($result->product_id, array(65,65)));
					
					$product_language = get_the_terms( $product->id, 'pa_product-language');
					
					$prod_thumb_src = wp_get_attachment_image_src( $_product->get_image_id(), 'shop_catalog');
								
					$prod_auths = array();
					foreach($product_auth as $auth){
						$prod_auths[] =  $auth->name;
					}
					
					$prod_lang = array();
					foreach($product_language as $lang){
						$prod_lang[] =  $lang->name;
					}

					$prod_publisher = array();
					foreach($product_publisher as $publisher){
						$prod_publisher[] =  $publisher->name;
					}
					
					$prod_narrator = array();
					foreach($product_narrator as $narrator){
						$prod_narrator[] =  $narrator->name;
					}
					
					$sale_price = $_product->post->_sale_price;
					$reg_price = $_product->post->_regular_price;
					
					if($currencyCode && $currencyCode != 'INR'){
						$ex_rate = $exchange_rates['rates'][$currencyCode];
						if($sale_price)
							$sale_price = $sale_price*$ex_rate;
							$sale_price = round($sale_price, 2);
						
						if($reg_price)
							$reg_price = $reg_price*$ex_rate;
							$reg_price = round($reg_price, 2);
					}
					
					$product_cat = array();
					$terms = get_the_terms( $product->ID, 'product_cat' );
					foreach ($terms as $term) {
						$product_cat[] = $term->name;
					}
					
					$average = get_post_meta( $product->ID, '_wc_average_rating', true );
					$rating = (string) floatval( $average );
					
					$Prodarr[] = array(
							'product_id'          => $product->id,
							'product_title'       => $_product->post->post_title,
							'product_authors' 	  => $prod_auths,						
							'product_language' 	  => $prod_lang,		
							'product_sale_price' 	  => $sale_price,
							'product_regular_price' 	  => $reg_price,
							'product_thumb_url'  => $prod_thumb_src[0],				
							'product_rating' => $product->get_average_rating(),
							'product_cat' => $product_cat,
							'product_publisher' => $prod_publisher,
							'prod_narrator' => $prod_narrator,
							'blog_post_url' => get_post_meta($product->id, 'blog_post_url', true),
						);
					
					// Output product information here
					
				endwhile;
				//$cat_prod_list[$cat_slug]['featured']['products'] = $Prodarr;
				$cat_prod_list1['key'] = 'featured';
				$cat_prod_list1['value'] = 'Featured Products';
				$cat_prod_list1['products'] = $Prodarr;
				if($cat_prod_list[$cat_slug]) {
					array_unshift($cat_prod_list[$cat_slug], $cat_prod_list1); // to show featured product at first position
				} else {
					$cat_prod_list[$cat_slug][] = $cat_prod_list1;
				}
			else:
				$Prodarr1 = array();
				//$cat_prod_list[$cat_slug]['featured']['products'] = $Prodarr1;
				$cat_prod_list1['key'] = 'featured';
				$cat_prod_list1['value'] = 'Featured Products';
				$cat_prod_list1['products'] = $Prodarr1;
				if($cat_prod_list[$cat_slug]) {
					array_unshift($cat_prod_list[$cat_slug], $cat_prod_list1); // to show featured product at first position
				} else {
					$cat_prod_list[$cat_slug][] = $cat_prod_list1;
				}
			endif;

			wp_reset_query(); // Remember to reset						
			
			
		}
		
		$fullProdList = array();
		$fullProdList = array_merge($featuredProducts, $cat_prod_list);
				
		//return $cat_prod_list;
		return $fullProdList;
	}
	else{	
	
		if($sortby){
			$args = array( 
					'post_type' => 'product', 
					'posts_per_page' => -1, 
					'product_cat' => "$category",
					'order' => $sortOrder,
					'orderby' => 'meta_value_num',
					'meta_key' => $sortArr[$sortby],	
				);
		}
		else{
			$args = array( 
					'post_type' => 'product', 
					'posts_per_page' => -1, 
					'product_cat' => "$category",
				);
		}
	}
	// for pagination
	if(isset($per_page)) {
		$args['posts_per_page'] = $per_page;
		$args['paged'] = isset($paged) ? $paged : 1;
	}
	$featured_query = new WP_Query( $args );
		
	if ($featured_query->have_posts()) : 

		while ($featured_query->have_posts()) : 
		
			$featured_query->the_post();
			
			$product = get_product( $featured_query->post->ID );
			$_product    = wc_get_product( $product->id );
			
			$product_auth = get_the_terms( $product->id, 'pa_product-author');
			
			$product_publisher = get_the_terms( $product->id, 'pa_product-publisher');
			
			$product_narrator = get_the_terms( $product->id, 'pa_product-narrator');
			//$prod_thumb_src = wp_get_attachment_image_src( get_post_thumbnail_id($result->product_id, array(65,65)));
			
			$product_language = get_the_terms( $product->id, 'pa_product-language');
			
			$prod_thumb_src = wp_get_attachment_image_src( $_product->get_image_id(), 'shop_catalog');
						
			$prod_auths = array();
			foreach($product_auth as $auth){
				$prod_auths[] =  $auth->name;
			}
			
			$prod_lang = array();
			foreach($product_language as $lang){
				$prod_lang[] =  $lang->name;
			}

			$prod_publisher = array();
			foreach($product_publisher as $publisher){
				$prod_publisher[] =  $publisher->name;
			}
			
			$prod_narrator = array();
			foreach($product_narrator as $narrator){
				$prod_narrator[] =  $narrator->name;
			}
			
			/*
			$price = $_product->post->_sale_price;
			if($price == ''){
				$price = $_product->post->_regular_price;
			}
			*/
			$sale_price = $_product->post->_sale_price;
			$reg_price = $_product->post->_regular_price;
			
			if($currencyCode && $currencyCode != 'INR'){
				$ex_rate = $exchange_rates['rates'][$currencyCode];
				if($sale_price)
					$sale_price = $sale_price*$ex_rate;
					$sale_price = round($sale_price, 2);
				
				if($reg_price)
					$reg_price = $reg_price*$ex_rate;
					$reg_price = round($reg_price, 2);
			}
			
			$product_cat = array();
			$terms = get_the_terms( $product->ID, 'product_cat' );
			foreach ($terms as $term) {
				if($term->parent == 0) {
					array_unshift($product_cat, $term->name);
				} else {
					$product_cat[] = $term->name;
				}
			}
			
			$average = get_post_meta( $product->ID, '_wc_average_rating', true );
			$rating = (string) floatval( $average );
			
			$products[] = array(
					'product_id'          => $product->id,
					'product_title'       => $_product->post->post_title,
					'product_authors' 	  => $prod_auths,						
					'product_language' 	  => $prod_lang,		
					'product_sale_price' 	  => $sale_price,
					'product_regular_price' 	  => $reg_price,
					'product_thumb_url'  => $prod_thumb_src[0],				
					'product_rating' => $product->get_average_rating(),
					'product_cat' => $product_cat,
					'product_publisher' => $prod_publisher,
					'prod_narrator' => $prod_narrator,
					'blog_post_url' => get_post_meta($product->id, 'blog_post_url', true),
				);
			
			// Output product information here
			
		endwhile;
		
	endif;

	wp_reset_query(); // Remember to reset	
	return $products;
}

function _get_price_currency($product_id, $currency, $price=null) {
	$amount = 0;
	$wmcs_live_exchange_rates = get_option('wmcs_live_exchange_rates');
	$currency_prices = get_post_meta( $product_id, 'wmcs_currency_prices', true );
	if($currency_prices[$currency]) {
		if($currency_prices[$currency]['sale'] == '' && $currency_prices[$currency]['regular'] == '') {
			$amount += $wmcs_live_exchange_rates['rates'][$currency] * $price;
		} 
		else {
			if(!empty($currency_prices[$currency]['sale'])) {
				$amount += $currency_prices[$currency]['sale'];
			}
			else {
				$amount += $currency_prices[$currency]['regular'];
			}
		}
	}
	return round($amount,2);
}
?>