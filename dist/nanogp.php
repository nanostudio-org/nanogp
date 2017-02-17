<?php
/**
* nanogp add-on for nanogallery2 to display images/albums stored in Google Photos
* http://nanogallery2.nanostudio.org
*
* PHP 5.2+
* @version    1.0.0
* @author     Christophe Brisbois - http://www.brisbois.fr/
* @copyright  Copyright 2017
* @license    GPLv3
* @link       https://github.com/nanostudio-org/nanogp
* @Support    https://github.com/nanostudio-org/nanogp/issues
*
*/

  include('admin/config.php');
  include('admin/log.php');


  header('Content-Type: application/javascript; charset=utf-8');    // JSONP
  header("access-control-allow-origin: *");
  
  $request=$_GET;
  $user_id=$request['nguserid'];
  unset($request['nguserid']);
  $album_id='';
  if( isset($_GET['ngalbumid']) ) {
    $album_id=$request['ngalbumid'];
    unset($request['ngalbumid']);
  }
  $callback='';
  if( isset($_GET['callback']) ) {
    $callback=$request['callback'];
    unset($request['callback']);
  }
  if( isset($_GET['_']) ) {
    unset($request['_']);
  }
  $content_kind=$request['kind'];

  
  if( !function_exists('curl_version') ) {
    $output = $callback . '(' . json_encode( array('nano_status' => 'error', 'nano_message' => 'Please install/enable CURL on your web server.' . $msg ) ) .')';
    write_log( $output );
    echo $output;
    exit;
  }

  $atoken=file_get_contents('admin/users/'.$user_id.'/token_a.txt');
  if( $atoken === false || $atoken == '' ) {
    $output = $callback . '(' . json_encode( array('nano_status' => 'error', 'nano_message' => 'Missing access token. Please grant authorization.' . $msg ) ) .')';
    echo $output;
    write_log( $output );
    exit;
  }

  // new query parameters
  $nq = http_build_query($request);

  // ##### retrieve the list of albums
  if( $content_kind == 'album' ) {
    $url = 'https://picasaweb.google.com/data/feed/api/user/' . $user_id . '?' . $nq;
    // echo $url . PHP_EOL . '<br/>';    

    if( send_gprequest( $url ) === 'token_expired') {
      // error -> get a new access token
      get_new_access_token();
      // send request again, with the new access token
      send_gprequest( $url );
    }
  }
  
  // ##### retrieve the content of one album
  if( $content_kind == 'photo' ) {
    $url = 'https://picasaweb.google.com/data/feed/api/user/' . $user_id . '/albumid/' . $album_id . '?' . $nq;

    if( send_gprequest( $url ) === 'token_expired') {
      // error -> get a new access token
      get_new_access_token();
      // send request again, with the new access token
      send_gprequest( $url );
    }
  }
  
  // ##### send the request to picasa
  function send_gprequest( $url ) {
    global $callback, $atoken;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . '&access_token=' . $atoken );
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("GData-Version: 3"));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    $response = curl_exec($ch);
    $msg=curl_error($ch);
    $info = curl_getinfo($ch);
    
    curl_close($ch);
    if( $info['http_code'] === 403 ) {
      return 'token_expired';
    }
    
    if( $info['http_code'] === 200 ) {
    
      $output = $callback . '(' . json_encode( array_merge(array('nano_status' => 'ok', 'nano_message' => ''), json_decode($response, true))) . ')';
      echo $output;
      // write_log( $output );
      exit;
    }
    else {
      $output = $callback . '(' . json_encode( array('nano_status' => 'error', 'nano_message' => 'curl error' . $msg . ' - ' . $url ) ) . ')';
      echo $output;
      write_log( $output );
      exit;
    }
  
  }


  // ##### refresh the access token
  function get_new_access_token(){
    global  $user_id, $cfg_client_secret, $cfg_client_id, $rtoken, $atoken, $callback;
  
    $rtoken=file_get_contents('admin/users/' .$user_id. '/token_r.txt');
    if( $rtoken === false || $rtoken == '' ) {
      $output = $callback . '(' . json_encode( array('nano_status' => 'error', 'nano_message' => 'Missing refresh token. Please grant authorization.' . $msg ) ) . ')';
      echo $output;
      write_log( $output );
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
 		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
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
    $output = $callback . '(' . json_encode( array('nano_status' => 'error', 'nano_message' => 'Error: could not get a new access token: ' . $info['http_code']) ) .')';
    echo $output;
    write_log( $output );
    exit;
      

  }

  

?>