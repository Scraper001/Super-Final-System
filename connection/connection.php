<?php
session_start();
function con()
{

    $host = "localhost";
    $username = "root";
    $password = "";
    $database = "careprodb";

    $con = new mysqli($host, $username, $password, $database);
    if ($con->connect_error) {
        echo $con->connect_error;
    } else {
        return $con;
    }

}


?>