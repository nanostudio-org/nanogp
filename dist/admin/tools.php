<?php

  // set value global variables
  function set_globals () {
    global $callback;

    if( isset($_GET['callback']) ) {
      $callback=$_GET['callback'];
    }
  }

  // ##### send JSON response
  function response_json ( $msg_array ) {
    global $callback;
 
 
 
    if( $callback != '' ) {
      // JSONP
      header('Content-Type: application/javascript; charset=utf-8');
      header("access-control-allow-origin: *");
      $output = $callback . '(' . json_encode( $msg_array ) . ')';
    }
    else {
      // JSON
      header('Content-type: application/json; charset=utf-8');
      $output = json_encode( $msg_array );
    }
    echo $output;
    write_log( $output );

  }

  
  
  
  // ##### refresh the access token
  function get_new_access_token(){
    global  $user_id, $cfg_client_secret, $cfg_client_id, $rtoken, $atoken;
  
    $rtoken=file_get_contents('admin/users/' .$user_id. '/token_r.txt');
    if( $rtoken === false || $rtoken == '' ) {
      response_json( array('nano_status' => 'error', 'nano_message' => 'Missing refresh token. Please grant authorization.' ) );
      exit;
    }
    $params = array(
      "client_id" =>      $cfg_client_id,
      "client_secret" =>  $cfg_client_secret,
      "grant_type" =>     "refresh_token",
      'refresh_token' =>  $rtoken
    );

 		$ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.googleapis.com/oauth2/v4/token");
 		curl_setopt($ch, CURLOPT_POST, true);
 		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
 		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
 		curl_setopt($ch, CURLOPT_VERBOSE, true);
 		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params, '', '&'));
 		$response = curl_exec ($ch);
 		$authObj=json_decode($response);
    $msg=curl_error($ch);
    $info = curl_getinfo($ch);
    // print_r($response);
 		
 		curl_close($ch);

    if( $info['http_code'] === 200 ) {
      if( property_exists( $authObj, 'access_token' ) ) {
        // ok, we have a new access token -> save it for later use
        $atoken=$authObj->access_token;
        file_put_contents('admin/users/' .$user_id. '/token_a.txt', $atoken);
        write_log( 'new access token obtained - ' . $user_id );
        return true;
      }
    }
    
    // error
    response_json( array('nano_status' => 'error', 'nano_message' => 'Error: could not get a new access token: ' . $info['http_code'] ) );
    exit;
      

  }

  // ##### write to the log file
  function write_log( $msg ) {
    global $cfg_log;
    
    if( $cfg_log === true ) {
      $time = @date('[d/M/Y:H:i:s]');
      file_put_contents( 'admin/log.txt', $time . ' ' . $msg . "\r\n", FILE_APPEND );
    }
  }
  

?>
