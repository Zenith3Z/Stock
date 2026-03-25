<?php
require_once 'config.php';

//ตรวจสอบการล็อกอินและไม่สามารถลักไก่เพื่อเข้าหน้านี้โดยตรงได้ เช่น การพิมพ์ชื่อ url แต่ละหน้า
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

//ตรวจสอบรหัสพัสดุ
if (isset($_GET['id'])) {
    $item_id = trim($_GET['id']);
    
    try {
        //ตรวจสอบว่ามีการใช้พัสดุหรือไม่
        $check_buy = $conn->prepare("SELECT COUNT(*) FROM buy WHERE Item_ID = ?");
        $check_buy->execute([$item_id]);
        $buy_count = $check_buy->fetchColumn();
        
        $check_request = $conn->prepare("SELECT COUNT(*) FROM request WHERE Item_ID = ?");
        $check_request->execute([$item_id]);
        $request_count = $check_request->fetchColumn();
        
        if ($buy_count > 0 || $request_count > 0) {
            //มีการใช้พัสดุอยู่
            header("Location: item.php?error=" . urlencode("ไม่สามารถลบพัสดุนี้ได้ เนื่องจากมีข้อมูลที่เกี่ยวข้อง"));
            exit();
        }
        
        //ลบพัสดุ
$delete_stmt = $conn->prepare("DELETE FROM item WHERE Item_ID = ?");
if ($delete_stmt->execute([$item_id])) {
    header("Location: item.php?success=3");
    exit();
}
    } catch(PDOException $e) {
        header("Location: item.php?error=" . urlencode("เกิดข้อผิดพลาดในการลบข้อมูล"));
        exit();
    }
}

header("Location: item.php");
exit();
?>