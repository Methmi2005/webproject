
<?php

mysqli_report(MYSQLI_REPORT_OFF);

$host = "127.0.0.1";
$username = "root";
$password = "";
$database = "studycircle";
$port = 3306;

$conn = mysqli_connect(
    $host,
    $username,
    $password,
    $database,
    $port
);

if (!$conn) {
    die(
        "Database connection failed: " .
        mysqli_connect_error()
    );
}

mysqli_set_charset($conn, "utf8mb4");

?>
