<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "smart_meal_planner";

$conn = mysqli_connect($host, $user, $password, $database);

if(!$conn){
    die("Database Connection Failed");
}

?>