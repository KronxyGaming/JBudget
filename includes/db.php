<?php
    $serverName = "localhost";
    $userName = "root";
    $password = "";
    $dbName = "jbudget";

    //create connection
    $con = mysqli_connect($serverName, $userName, $password, $dbName);

    //Checking connection
    //if (mysqli_connect_errno()) {
        //die("Connection failed: " . mysqli_connect_error());
    //}

    //echo "Connected successfully";
?>
