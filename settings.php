<?php 
define('MYSQL_SERVER', 'localhost');
define('MYSQL_USER', 'newss9p4_time');
define('MYSQL_PASSWORD','t&B1ett&');
define('MYSQL_DB', 'newss9p4_time');


function db_connect(){
    $connect = mysqli_connect(MYSQL_SERVER, MYSQL_USER,MYSQL_PASSWORD,MYSQL_DB
    )
     or die("Error: ".mysqli_error($connect));
    if(!mysqli_set_charset($connect,"utf8mb4")){
        print("Error: ".mysqli_error($connect));
    }
    return $connect;
}
$connect = db_connect();

?>