<?php

  function write_log( $msg ) {
    if( $cfg_log === true ) {
      $time = @date('[d/M/Y:H:i:s]');
      file_put_contents( 'admin/log.txt', '[' .$time . '] ' . $msg, FILE_APPEND );
    }
  }

?>