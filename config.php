<?php
//เริ่มการทำงาน
session_start();

//ตั้งค่าเวลาประเทศไทยนี้รักสงบ
date_default_timezone_set('Asia/Bangkok');

//การตั้งค่าฐานข้อมูล
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "stock";

//เชื่อมฐานข้อมูล
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES utf8");
} catch(PDOException $e) {
    die("เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล");
}
?>