<?php
    error_reporting(E_ALL ^E_NOTICE);
    include('Box.php');

    $box = new Box("Refresh token expired. Use tools.amsterdamopendata.nl/box to reload token.\n");
    $box->updateBox($_REQUEST["folderid"]);
?>