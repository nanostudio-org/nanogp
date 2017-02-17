<?php

  function write_log( $msg ) {
    global $cfg_log;
    
    if( $cfg_log === true ) {
      $time = @date('[d/M/Y:H:i:s]');
      file_put_contents( 'admin/log.txt', $time . ' ' . $msg . "\r\n", FILE_APPEND );
    }
  }

?>