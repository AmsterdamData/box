<?php
    include(dirname(__FILE__) ."/settings.php");
    include('BoxAPI.class.php');
    
    class Box{
        var $client_id;
        var $client_secret;
        var $redirect_uri;
        var $box;

        function Box($error_msg = null){
            $this->client_id        = BOX_CLIENT_ID;
            $this->client_secret    = BOX_CLIENT_SECRET;
            $this->redirect_uri     = BOX_REDIRECT_URI;
            
            $this->box = new Box_API($this->client_id, $this->client_secret, $this->redirect_uri);
    
            if(!$this->box->load_token()){
                if(isset($_GET['code'])){
                    $token = $this->box->get_token($_GET['code'], true);
                    if($this->box->write_token($token, 'file')){
                        $this->box->load_token();
                    }
                } else {
                    $this->box->get_code($error_msg);
                }
            }

            if (isset($this->box->error)){
                echo "<h1>Error</h1>";
                echo $this->box->error . "\n";
            } else {
//                print("<h1>Logged in</h1>");
            }
        }
        
        function updateBox($folderid){
            if($folderid > 0){
                $folder = new Boxfolder($this->box, $folderid);
            } else {
                $folder = new Boxfolder($this->box, 0);
            }
            $folder->update();
        }
        
        function printBox($folderid, $fileid){
            if($folderid > 0){
                $folder = new Boxfolder($this->box, $folderid);
            } else {
                $folder = new Boxfolder($this->box, 0);
            }
            $folder->printFolder();
            
            if($fileid > 0){
                $file = new Boxfile($this->box, $fileid);
                $file->printFile();
            }
        }
    }
    
    class Boxfolder{
        var $box;
        var $folder;
        var $folderid;
        var $fileids;
        var $subfolderids;
        var $files;
        
        function Boxfolder($box, $folderid){
            $this->box = $box;
            $this->folderid = $folderid;
            $this->folder = $this->box->get_folder_details($this->folderid);
            //print("<PRE>"); print_r($this->folder); print("</PRE>");
        }
        
        function printFolder(){
            print("<H1>". $this->folder["name"] ."</H1>");
            foreach($this->folder["path_collection"]["entries"] as $entry){
                print(" &gt; <a href='index.php?folderid=". $entry["id"] ."'>". $entry["name"] ."</a>");
            }
            print("<BR><a href='update.php?folderid=". $this->folderid ."'>Update deze map</a>");
            print("<UL>");
            foreach($this->folder["item_collection"]["entries"] as $entry){
                switch($entry["type"]){
                    case "folder":
                        print("<LI><strong><a href='index.php?folderid=". $entry["id"] ."'>". $entry["name"]."</a></strong></LI>");
                        break;
                    case "file":
                    default:
                        print("<LI><a href='index.php?folderid=". $this->folderid ."&fileid=". $entry["id"] ."'>". $entry["name"]."</a></LI>");
                        break;
                }
            }
            print("</UL>");
        }
        
        function getFileids(){
            $this->fileids = Array();
            foreach($this->folder["item_collection"]["entries"] as $entry){
                if($entry["type"] == "file"){
                    $this->fileids[] = $entry["id"];
                }
            }
        }
        
        function getSubfolderids(){
            $this->subfolderids = Array();
            foreach($this->folder["item_collection"]["entries"] as $entry){
                if($entry["type"] == "folder"){
                    $this->subfolderids[] = $entry["id"];
                }
            }
        }
        
        function update(){
            $this->getFileids();
            foreach($this->fileids as $fileid){
                $file = new Boxfile($this->box, $fileid);
                $file->update();
            }
            
            $this->getSubfolderids();
            foreach($this->subfolderids as $folderid){
                $folder = new Boxfolder($this->box, $folderid);
                $folder->update();
            }
        }
    }
    
    class Boxfile{
        var $box;
        var $fileid;
        var $file;
        
        function Boxfile($box, $fileid){
            error_reporting(E_ALL);
            $this->box = $box;
            $this->fileid = $fileid;
            $this->file = $this->box->get_file_details($this->fileid, false, Array("fields" => "type,id,name,description,size,sha1,tags,created_at,modified_at,content_created_at,content_modified_at,created_by,modified_by,owned_by,shared_link,item_status"));
        }
        
        function printFile(){
            print("<h2>". $this->file["name"] ."</h2>");
            print("<PRE>"); print_r($this->file); print("</PRE>");
        }
        
        function update(){
            if(in_array("Jaarlijks", $this->file["tags"])){
                $this->checkUptodate(365);
            }
            if(in_array("Maandelijks", $this->file["tags"])){
                $this->checkUptodate(31);
            }
            if(in_array("Wekelijks", $this->file["tags"])){
                $this->checkUptodate(7);
            }
            if(in_array("Dagelijks", $this->file["tags"])){
                $this->checkUptodate(1);
            }
        }
        
        function checkUptodate($days){
            $ftime = strtotime($this->file["content_modified_at"]);
            $daysOld = floor((time() - $ftime) / (60 * 60 * 24));
            if($daysOld >= $days){
                $this->setOutdated();
            } else {
                $this->setUptodate();
            }
        }
        
        function setUptodate(){
            //Check if file is updated recently, if not ignore (because 'outdated' can be set manually)
            $this->addTag("Up to date", "Verouderd");
        }
        
        function setOutdated(){
            $this->addTag("Verouderd", "Up to date");
            //TODO: Send mail to user if e-mail is changed from Uptodate to Outdated
        }
        
        function addTag($tag_to_add, $tag_to_delete){
            if(in_array($tag_to_add, $this->file["tags"]) && !in_array($tag_to_delete, $this->file["tags"])){
                return null;
            }
            $new_tags = array_merge( array_diff($this->file["tags"], Array($tag_to_delete,$tag_to_add)), Array($tag_to_add));
            $this->box->update_file($this->fileid, Array("tags" => $new_tags));
            $this->box->upsert_metadata($this->fileid, Array("Status" => $tag_to_add));
            print("<BR>". $this->file["name"] ." => ". $tag_to_add);
        }
    }

    
    //$result = $box->update_file($id, Array("tags" => Array("Dagelijks","Up to date")));
    //print_r($result);

    
    /*
    // Get folder details
    $box->get_folder_details('FOLDER ID');

    // Get folder items list
    $box->get_folder_items('FOLDER ID');
    
    // All folders in particular folder
    $box->get_folders('FOLDER ID');
    
    // All Files in a particular folder
    $box->get_files('FOLDER ID');
    
    // All Web links in a particular folder
    $box->get_links('FOLDER ID');
    
    // Get folder collaborators list
    $box->get_folder_collaborators('FOLDER ID');
    
    // Create folder
    $box->create_folder('FOLDER NAME', 'PARENT FOLDER ID');
    
    // Update folder details
    $details['name'] = 'NEW FOLDER NAME';
    $box->update_folder('FOLDER ID', $details);
    
    // Share folder
    $params['shared_link']['access'] = 'ACCESS TYPE'; //open|company|collaborators
    print_r($box->share_folder('FOLDER ID', $params));
    
    // Delete folder
    $opts['recursive'] = 'true';
    $box->delete_folder('FOLDER ID', $opts);
    
    // Get file details
    $box->get_file_details('FILE ID');
    
    // Upload file
    $box->put_file('RELATIVE FILE URL', '0');
    
    // Update file details
    $details['name'] = 'NEW FILE NAME';
    $details['description'] = 'NEW DESCRIPTION FOR THE FILE';
    $box->update_file('FILE ID', $details);
    
    // Share file
    $params['shared_link']['access'] = 'ACCESS TYPE'; //open|company|collaborators
    print_r($box->share_file('File ID', $params));
    
    // Delete file
    $box->delete_file('FILE ID');
    */    
?>