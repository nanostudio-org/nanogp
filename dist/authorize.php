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

  if( !function_exists('curl_version') ) {
    echo 'Please install/enable CURL to execute this application.';
    exit;
  }

  $prot='http://';
  if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off'){
    $prot='https://';
  }

  if( $cfg_max_accounts == 1 ) {
    foreach( glob( 'admin/users/*', GLOB_ONLYDIR ) as $folder) 
    {	
      // echo "Filename: " . $folder . "<br />";	
      $atoken=file_get_contents( $folder . '/token_a.txt');
      $rtoken=file_get_contents( $folder . '/token_r.txt');
      // $user_id=file_get_contents('user. txt');
      $user_id = basename($folder);
      if( $atoken !== false && $atoken != '' && $rtoken !== false && $rtoken != '' && $user_id != '' ) {
        display_settings();
        exit;
      }
    }

  }
  

  if (isset($_GET['code'])) {
    // second step: get access token and refresh token
    
    $code = $_GET['code'];
    $url = 'https://www.googleapis.com/oauth2/v4/token';
    $params = array(
        "code" =>           $code,
        "client_id" =>      $cfg_client_id,
        "client_secret" =>  $cfg_client_secret,
        "redirect_uri" =>   $prot . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"],
        "grant_type" =>     "authorization_code"
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    $response = curl_exec($ch);
    $authObj = json_decode($response);
    $info = curl_getinfo($ch);
    $ce = curl_error($ch);
    curl_close($ch);
    
    if( $info['http_code'] === 200 ) {
      // ok
      
      header('Content-Type: ' . $info['content_type']);
      
      // retrieve user ID
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, 'https://picasaweb.google.com/data/feed/api/user/default?access_token=' . $authObj->access_token );
      curl_setopt($ch, CURLOPT_HEADER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array("GData-Version: 3"));
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_VERBOSE, true);
      $response = curl_exec($ch);
      curl_close($ch);
      $obj = simplexml_load_string($response);
      
      $user_id = $obj -> title;
  
      // check if retrieved user ID matches with the user ID defined in the config file
      if(  property_exists( $authObj, 'refresh_token' ) ) {
        if( !is_dir( 'admin/users/' . $user_id ) ){
          mkdir( 'admin/users/' . $user_id );
        }

        file_put_contents( 'admin/users/' . $user_id . '/token_a.txt', $authObj->access_token);
        file_put_contents( 'admin/users/' . $user_id . '/token_r.txt', $authObj->refresh_token);
        echo 'Authorisation successfully granted.' . PHP_EOL . '<br/>';
        display_settings();
      }
      else {
        echo 'Authorization already granted. To grant again, please revoke application <b>' .$cfg_application_name.'</b> permissions: https://myaccount.google.com/permissions ';
      }
    }
    else {
      echo 'curl error:' . $ce . PHP_EOL . '<br/>';
    }
    
    
  } else {
    // first step: user must grant authorization
    $url = "https://accounts.google.com/o/oauth2/auth";

    $params = array(
      "response_type" =>  "code",
      "client_id" =>      $cfg_client_id,
      "redirect_uri" =>   $prot . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"],
      "access_type" =>    "offline",
      "scope" =>          "https://picasaweb.google.com/data"
    );

    $request_to = $url . '?' . http_build_query($params);

    header("Location: " . $request_to);     // display authorization form
  }
  
  function display_settings() {
    global $user_id, $prot;
    
    echo 'Settings for nanogallery2:'. PHP_EOL . '<br/>';
    echo "  kind : 'google2'," . PHP_EOL . '<br/>';
    echo "  userID : '" . $user_id . "'," . PHP_EOL . '<br/>';
    
    
    
    $u= $prot . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"];
    $ul = explode('/', $u);
    array_pop($ul);
    // array_pop($ul);
    $u= implode('/', $ul) . '/nanogp.php';    
    echo "  google2URL : '" . $u . "'" . PHP_EOL . "<br/>";
  }

?>
