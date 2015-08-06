<?php
    class Ftp{
      var $ftp_server, $ftp_user_name, $ftp_user_pass;
      var $conn_id;
      var $destination_folder;
      
      function Ftp($ftp_server, $ftp_user_name, $ftp_user_pass){
          $this->ftp_server = $ftp_server;
          $this->ftp_user_name = $ftp_user_name;
          $this->ftp_user_pass = $ftp_user_pass;
          $this->destination_folder = "";
          
          $this->conn_id = ftp_connect($this->ftp_server); 
          $login_result = ftp_login($this->conn_id, $this->ftp_user_name, $this->ftp_user_pass); 

          // check connection
          if ((!$this->conn_id) || (!$login_result)) { 
            echo "FTP connection has failed!";
            echo "Attempted to connect to $ftp_server for user $ftp_user_name"; 
            exit; 
          }
      }
      
      function set_destination_folder($folder){
          $this->destination_folder = $folder;
          ftp_chdir($this->conn_id, $folder);
     }
      
      function upload($source_file, $type = FTP_BINARY){
        $fname = substr($source_file,strrpos($source_file,"/")+1);
        $destination_file = $fname;
        
        //echo "<BR>Upload ". $source_file ." to ". ftp_pwd($this->conn_id) ."/". $destination_file;
        // upload the file
        $upload = ftp_put($this->conn_id, $destination_file, $source_file, $type); 

        // check upload status
        if (!$upload) { 
            echo "FTP upload has failed!";
        } else {
        }
      }
      
      function close(){
        // close the FTP stream 
        ftp_close($this->conn_id); 
      }
  }
?>
