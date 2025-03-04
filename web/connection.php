<?php

$dbhost = "localhost";
$dbuser = "golf"; // เปลี่ยนเป็น root
$dbpass = "1234567890"; // ค่าเริ่มต้นของ XAMPP ไม่มีรหัสผ่าน
$dbname = "budget_buddy";

if(!$con = mysqli_connect($dbhost,$dbuser,$dbpass,$dbname))
{

	die("failed to connect!");
}
