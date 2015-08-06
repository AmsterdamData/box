<?php
    error_reporting(E_ERROR);
    include('Box.php');

    $box = new Box();
    //$box->updateBox();
    $box->printBox($_REQUEST["folderid"], $_REQUEST["fileid"]);
?>