<?php
/**
* nanogp add-on for nanogallery2 to display Google Photos images/albums
* http://nanogallery2.nanostudio.org
*
* PHP 5.2+
* @version    1.3.0
* @author     Christophe Brisbois - http://www.brisbois.fr/
* @copyright  Copyright 2017
* @license    GPLv3
* @link       https://github.com/nanostudio-org/nanogp
* @Support    https://github.com/nanostudio-org/nanogp/issues
*
*/



  const API_BASE_PATH =       'https://www.googleapis.com';
  const OAUTH2_TOKEN_URI =    'https://www.googleapis.com/oauth2/v4/token';
  const OAUTH2_AUTH_URL =     'https://accounts.google.com/o/oauth2/auth';
  const OAUTH2_REVOKE_URI =   'https://accounts.google.com/o/oauth2/revoke';
  // Google OAUTH 2.0 API: https://developers.google.com/identity/protocols/OpenIDConnect
  $user_id = '';
  $atoken = '';
  $rtoken = '';
  $callback = '';
  
  include('admin/config.php');
  include('admin/tools.php');

  // check CURL installation
  if( !function_exists('curl_version') ) {
    response_json( array('nano_status' => 'error', 'nano_message' => 'Please install/enable CURL to execute this application.' ) );
    exit;
  }
  
  // check write permissions
  if( !is_writable('admin/users') ) {
    response_json( array('nano_status' => 'error', 'nano_message' => 'Error: no write permissions to folder admin/users.' ) );
    exit;
  }
  
  $prot='http://';
  if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off'){
    $prot='https://';
  }

  set_globals();

  // if( count($_GET) == 0 && $cfg_max_accounts == 1  ) {
  if( !isset($_GET['code']) && !isset($_GET['revoke']) && !isset($_GET['user_info']) && $cfg_max_accounts == 1  ) {
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
  

  // ##########
  // STEP 1: user must grant authorization
  if( !isset($_GET['code']) && !isset($_GET['revoke']) && !isset($_GET['user_info']) ) {

    $params = array(
      "response_type" =>  "code",
      "client_id" =>      $cfg_client_id,
      "redirect_uri" =>   $prot . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"],
      "access_type" =>    "offline",
      // "scope" =>          "https://picasaweb.google.com/data profile"
      "scope" =>          "https://picasaweb.google.com/data profile email"
    );

    $request_to = OAUTH2_AUTH_URL . '?' . http_build_query($params, '', '&');

    header("Location: " . $request_to);     // display authorization form
  }

  
  // ##########
  // STEP 2: get access token and refresh token
  if( isset($_GET['code']) ) {
    $code = $_GET['code'];
    $params = array(
        "code" =>           $code,
        "client_id" =>      $cfg_client_id,
        "client_secret" =>  $cfg_client_secret,
        "redirect_uri" =>   $prot . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"],
        "grant_type" =>     "authorization_code"
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, OAUTH2_TOKEN_URI );
    curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params, '', '&'));
    $response = curl_exec($ch);
    $authObj = json_decode($response);
    $info = curl_getinfo($ch);
    $ce = curl_error($ch);
    curl_close($ch);
    
    if( $info['http_code'] === 200 ) {
      // ok
      
      // retrieve user ID
      $ch = curl_init();
      // curl_setopt($ch, CURLOPT_URL, 'https://picasaweb.google.com/data/feed/api/user/default?access_token=' . $authObj->access_token );
      curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $authObj->access_token );
      curl_setopt($ch, CURLOPT_HEADER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      // curl_setopt($ch, CURLOPT_HTTPHEADER, array("GData-Version: 3"));
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_VERBOSE, true);
      $response = curl_exec($ch);
      //var_dump($response);
      $info = curl_getinfo($ch);
      $ce = curl_error($ch);
      curl_close($ch);
      
      if( $info['http_code'] === 200 ) {
        $objProfile = json_decode($response);
        if(  property_exists( $objProfile, 'id' ) ) {
          // we got the user ID
          $user_id = $objProfile -> id;
          
          if( $user_id == '' ) {
            response_json( array('nano_status' => 'error', 'nano_message' => 'Retrieved user ID is empty.' ) );
            exit;
          }
          
          if(  property_exists( $authObj, 'refresh_token' ) ) {
            // refresh token present -> ok, it's the first authorization grant
            // store tokens
            if( !is_dir( 'admin/users/' . $user_id ) ){
              if( @mkdir( 'admin/users/' . $user_id ) === false ) {
                $error = error_get_last();
                response_json( array('nano_status' => 'error', 'nano_message' => 'error on mkdir(admin/users/' . $user_id .'):' . $error['message'] ) );
                exit;
              }
            }

            file_put_contents( 'admin/users/' . $user_id . '/token_a.txt', $authObj->access_token);
            file_put_contents( 'admin/users/' . $user_id . '/token_r.txt', $authObj->refresh_token);
            if(  property_exists( $objProfile, 'email' ) ) {
              file_put_contents( 'admin/users/' . $user_id . '/profile.txt', $objProfile->email);
            }
            
            // echo 'Authorisation successfully granted.' . PHP_EOL . '<br/>';
            response_json( array('nano_status' => 'ok', 'nano_message' => 'Authorisation successfully granted (userID='.$user_id.').' ) );
          }
          else {
            // no refresh token -> authorization has already been granted -> revoke to get a new refresh token
            response_json( array('nano_status' => 'warning', 'nano_message' => 'Authorization already granted. Please revoke authorization first: https://myaccount.google.com/permissions' ) );
          }
        }
        else {
          response_json( array('nano_status' => 'error', 'nano_message' => 'Could not retrieve the user profile. Curl error:' . $ce ) );
          exit;
        }
      }
      else {
        response_json( array('nano_status' => 'error', 'nano_message' => 'Could not retrieve the user ID.' ) );
        exit;
      }
  
    }
    else {
      response_json( array('nano_status' => 'error', 'nano_message' => 'Could not grant authorization. Curl error:' . $ce ) );
    }
  } 


  
  
  // ##########
  // REVOKE USER AUTHORIZATION
  if( isset($_GET['revoke']) ) {
    // can be done manually by the user: https://security.google.com/settings/security/permissions (in this case the user folder remains and must be deleted manually in the admin/users folder)
    // but here we can clear also the users data
    $user_id = $_GET['revoke'];
    
    if( $user_id == '' ) {
      response_json( array('nano_status' => 'error', 'nano_message' => 'missing user ID') );
      exit;
    }
    
    if( !is_dir( 'admin/users/' . $user_id ) ) {
      response_json( array('nano_status' => 'error', 'nano_message' => 'user ID does not exist (userID='.$user_id.')') );
      exit;
    }

    $atoken=file_get_contents( 'admin/users/' . $user_id . '/token_a.txt');
    if( $atoken === false || $atoken == '' ) {
      response_json( array('nano_status' => 'error', 'nano_message' => 'could not find any access token (userID='.$user_id.')') );
      exit;
    }
    
    if( revoke( $user_id ) === 'token_expired') {
      // error -> get a new access token
      get_new_access_token();
      // send request again, with the new access token
      revoke( $user_id );
    }
    

  }
  
  function revoke( $user_id ) {
    global $atoken;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, OAUTH2_REVOKE_URI . '?token=' .$atoken );
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    $ce = curl_error($ch);
    curl_close($ch);

    if( $info['http_code'] === 403 ) {
      // token expired?
      return 'token_expired';
    }
    
    if( $info['http_code'] === 200) {
      array_map('unlink', glob('admin/users/' . $user_id . "/*.*"));
      rmdir('admin/users/' . $user_id);
      response_json( array('nano_status' => 'ok', 'nano_message' => 'authorisation revoked successfully (userID='.$user_id.').') );
      exit;
    } 
    
    response_json( array('nano_status' => 'error', 'nano_message' => 'Error (userID='.$user_id.'): '. $info['http_code'] . '-' . $ce ) );
    exit;
  }
  

  // ##########
  // CHECK IF ACCESS ALREADY GRANTED
  if( isset($_GET['user_info']) ) {
    $user_id = $_GET['user_info'];

    if( $user_id == '' ) {
      response_json( array('nano_status' => 'error', 'nano_message' => 'missing user ID') );
      exit;
    }

    if( !is_dir( 'admin/users/' . $user_id ) ) {
      response_json( array('nano_status' => 'error', 'nano_message' => 'user '. $user_id .' does not exist') );
      exit;
    }

    $atoken=file_get_contents( 'admin/users/' . $user_id . '/token_a.txt');
    if( $atoken === false || $atoken == '' ) {
      response_json( array('nano_status' => 'error', 'nano_message' => 'could not find access token (userID='.$user_id.')') );
      exit;
    }    
    
    $rtoken=file_get_contents( 'admin/users/' . $user_id . '/token_r.txt');
    if( $rtoken === false || $rtoken == '' ) {
      response_json( array('nano_status' => 'error', 'nano_message' => 'could not find refresh token (userID='.$user_id.')') );
      exit;
    }    
    
    response_json( array('nano_status' => 'ok', 'nano_message' => 'authorization already granted (userID='.$user_id.')') );
  }

  
  
  // ##########
  // Display connection info for nanogallery2 
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
