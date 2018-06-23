<?php

define('FIREBASE_API_KEY', 'AAAA7KB0DwY:APA91bG9QYrBI9OuSFyrUTBNvIq6_BhnVwYcAGM-LhWhyWyWey3UCRoJjVVs_X_qh5VwI1uVPz3LHXv2CwmqKedGWpw9KWyQBtT4PNjlyJY4h_4BGaAsYbYwUyrvKRDR-FXlcFLg5PCD');

class Firebase {
  
  // sending push message to single user by firebase reg id
  public function send($to, $message) {
    $fields = array(
      'to' => $to,
      'data' => $message
    );
    return $this->sendPushNotification($fields);
  }
  
  // Sending message to a topic by topic name
  public function sendToTopic($to, $message) {
    $fields = array(
      'to' => '/topics/' . $to,
      'data' => $message
    );
    return $this->sendPushNotification($fields);
  }
  
  // sending push message to multiple users by firebase registration ids
  public function sendMultiple($registration_ids, $message) {
    $fields = array(
      'to' => $registration_ids,
      'data' => $message
    );
    
    return $this->sendPushNotification($fields);
  }
  
  // function makes curl request to firebase servers
  private function sendPushNotification($fields) {
    
    //require_once __DIR__ . '/config.php';
    
    // Set POST variables
    $url = 'https://fcm.googleapis.com/fcm/send';
    
    $headers = array(
      'Authorization: key=' . FIREBASE_API_KEY,
      'Content-Type: application/json'
    );
    // Open connection
    $ch      = curl_init();
    
    // Set the url, number of POST vars, POST data
    curl_setopt($ch, CURLOPT_URL, $url);
    
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Disabling SSL Certificate support temporarly
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
    
    // Execute post
    $result = curl_exec($ch);
    if ($result === FALSE) {
      die('Curl failed: ' . curl_error($ch));
    }
    
    // Close connection
    curl_close($ch);
    
    return $result;
  }
}

class Push {
  
  // push message title
  private $title;
  private $message;
  private $image;
  // push message payload
  private $data;
  // flag indicating whether to show the push
  // notification or not
  // this flag will be useful when perform some opertation
  // in background when push is recevied
  private $is_background;
  
  function __construct() {
    
  }
  
  public function setTitle($title) {
    $this->title = $title;
  }
  
  public function setMessage($message) {
    $this->message = $message;
  }
  
	public function setActiontype($action_type) {
    $this->action_type = $action_type;
  }
  
  public function setImage($imageUrl) {
    $this->image = $imageUrl;
  }
  
  public function setPayload($data) {
    $this->data = $data;
  }
  
  public function setIsBackground($is_background) {
    $this->is_background = $is_background;
  }
  
  public function getPush() {
    $res                          = array();
    $res['data']['title']         = $this->title;
    $res['data']['is_background'] = $this->is_background;
    $res['data']['message']       = $this->message;
    $res['data']['image']         = $this->image;
    $res['data']['payload']       = $this->data;
    //$res['data']['icon']       = 'myicon';
    //$res['data']['sound']       = 'mySound';
    $res['data']['timestamp']     = date('Y-m-d G:i:s');
    $res['data']['action_type']     = $this->action_type;
    return $res;
  }
}

class JSON_API_notifyapis_Controller {

	public function notify_user() {
		global $json_api, $wpdb;

		$firebase = new Firebase();
		$push = new Push();
		// optional payload
		$payload = array();
		$payload['team'] = 'India';
		$payload['score'] = '5.6';

		$title = $json_api->query->title;
		$message = $json_api->query->msg;
		$regId = $json_api->query->token;
		$action_type = $json_api->query->action_type;

		$push->setTitle($title);
		$push->setMessage($message);
    $push->setImage('http://www.vividlipi.com/wp-content/uploads/2014/11/logo_new-300x69.png');
		$push->setIsBackground(FALSE);
		$push->setPayload($payload);
		$push->setActiontype($action_type);


		$json = '';
		$response = '';

		$json = $push->getPush();
		$response = $firebase->send($regId, $json);
		echo $response; exit;
  }

	public function store_device_token() {
		global $json_api, $wpdb;
		if (!$json_api->query->email) {
			$json_api->error("You must include 'user_email' var in your request. ");
		}
		if (!$json_api->query->device_token) {
			$json_api->error("You must include 'device_token' var in your request. ");
		}
		if (!$json_api->query->device_id) {
			$json_api->error("You must include 'device_id' var in your request. ");
		}
		if (!$json_api->query->device_type) {
			$json_api->error("You must include 'device_type' var in your request. ");
		}
		if (!$json_api->query->app_version) {
			$json_api->error("You must include 'app_version' var in your request. ");
		}
		$user_data = get_user_by( 'email', $json_api->query->email);
		$uid = $user_data->ID;
		if($uid) {
			$device_id = $json_api->query->device_id;
			$device_token = $json_api->query->device_token;
			$id = $wpdb->get_results("select id from wp_mobile_devices where device_token like '$device_token'  order by id desc limit 0 ,1");

			if(count($id)) {
				$a = $wpdb->update(
					'wp_mobile_devices',
					array(
						'uid' => $uid,
						'app_version' => $json_api->query->app_version,
						'device_token' => $json_api->query->device_token,
						'updated_at' => time()
					),
					array('id' => $id[0]->id),
					array('%s'), array('%s', '%s', '%s', '%s')
				);
					return array("msg" => "Token updated successfully");
			}
			else {
				$a = $wpdb->insert( 
					'wp_mobile_devices', 
					array( 
						'uid' => $uid,
						'device_type' => $json_api->query->device_type,
						'device_token' => $json_api->query->device_token,
						//'device_id' => $json_api->query->device_id,
						'app_version' => $json_api->query->app_version,
						'created_at' => time(),
						'updated_at' => time()
					), 
					array( 
						'%d', 
						'%s', 
						'%s', 
						//'%s', 
						'%s', 
						'%d', 
						'%d'
						)
				);
				return array("msg" => "Token stored successfully");
			}
		}
			$json_api->error("There is no user with this email address");
	}
	public function get_all_device_token() {
		global $json_api, $wpdb;
		$results = $wpdb->get_results("select id , device_token from wp_mobile_devices order by id desc");
		return array('res' => $results);
	}
	public function get_all_device_tokens() {
		global $json_api, $wpdb;
		$results = $wpdb->get_results("select * from wp_mobile_devices order by updated_at desc");
		return array('res' => $results);
	}
}
