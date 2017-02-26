<?php
/**
* nanogp add-on for nanogallery2 to display images/albums stored in Google Photos
* http://nanogallery2.nanostudio.org
*
* PHP 5.2+
* @version    1.2.1
* @author     Christophe Brisbois - http://www.brisbois.fr/
* @copyright  Copyright 2017
* @license    GPLv3
* @link       https://github.com/nanostudio-org/nanogp
* @Support    https://github.com/nanostudio-org/nanogp/issues
*
*/

  $callback='';
  include('admin/config.php');
  include('admin/tools.php');

  set_globals();
  
  $request=$_GET;
// echo implode($request);

  $user_id=$request['nguserid'];
  unset($request['nguserid']);
  $album_id='';
  if( isset($_GET['ngalbumid']) ) {
    $album_id=$request['ngalbumid'];
    unset($request['ngalbumid']);
  }
  if( $callback != '' ) {
    unset($request['callback']);
  }
  if( isset($_GET['_']) ) {
    unset($request['_']);
  }

  $content_kind=$request['kind'];

  
  if( !function_exists('curl_version') ) {
    response_json( array('nano_status' => 'error', 'nano_message' => 'Please install/enable CURL on your web server.' ) );
    exit;
  }

  $atoken=file_get_contents('admin/users/'.$user_id.'/token_a.txt');
  if( $atoken === false || $atoken == '' ) {
    response_json( array('nano_status' => 'error', 'nano_message' => 'Missing access token. Please grant authorization.' ) );
    exit;
  }
  
  // new query parameters
  // $nq = http_build_query($request);

  
  
  // ##### retrieve the list of albums
  if( $content_kind == 'album' ) {
    // $url = 'https://picasaweb.google.com/data/feed/api/user/' . $user_id . '?access_token=' . $atoken . '&' . $nq;
    $url = 'https://picasaweb.google.com/data/feed/api/user/' . $user_id;
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
    // $url = 'https://picasaweb.google.com/data/feed/api/user/' . $user_id . '/albumid/' . $album_id . '?access_token=' . $atoken . '&' . $nq;
    $url = 'https://picasaweb.google.com/data/feed/api/user/' . $user_id . '/albumid/' . $album_id;

    if( send_gprequest( $url ) === 'token_expired') {
      // error -> get a new access token
      get_new_access_token();
      // send request again, with the new access token
      send_gprequest( $url );
    }
  }
  
  // ##### send the request to picasa/google photos
  function send_gprequest( $url ) {
    global $callback, $atoken, $request;

    $request->access_token=$atoken;
    
    $ch = curl_init();
    // curl_setopt($ch, CURLOPT_URL, $url . urlencode('&access_token=' . $atoken) );
    $url = $url . '?' . http_build_query($request);
    curl_setopt($ch, CURLOPT_URL, $url );
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("GData-Version: 3"));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_VERBOSE, true);

    $response = curl_exec($ch);
    $msg=curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    if( $response == 'Token revoked' ) {
      response_json( array('nano_status' => 'error', 'nano_message' => 'Token revoked - ' . $url ) );
      exit;
    }

    if( $response == 'No album found.' ) {
      response_json( array('nano_status' => 'error', 'nano_message' => 'No album found - ' . $url ) );
      exit;
    }
 
    if( $info['http_code'] === 403 ) {
      return 'token_expired';
    }
    
    if( $info['http_code'] === 200 ) {
      response_json( array_merge(array('nano_status' => 'ok', 'nano_message' => ''), json_decode($response, true)) );
      exit;
    }
    else {
      response_json( array('nano_status' => 'error', 'nano_message' => 'curl error' . $info['http_code'] . ' - ' . $msg . ' - ' . $url ) );
      exit;
    }
  
  }



  

?>