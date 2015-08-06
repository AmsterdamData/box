<?php
include("FTP.php");
include(dirname(__FILE__) ."/settings.php");

  class SharedDataExchange extends FTP{
      
      function SharedDataExchange(){
          parent::FTP(FTP_DOMAIN, FTP_USERNAME, FTP_PASSWORD);
      }
      
      function setFolder($folder){
          parent::set_destination_folder($folder);
      }
  }
?>
