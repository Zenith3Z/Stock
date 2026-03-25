<?php
require_once 'config.php';

//ตรวจสอบการล็อกอินและไม่สามารถลักไก่เพื่อเข้าหน้านี้โดยตรงได้ เช่น การพิมพ์ชื่อ url แต่ละหน้า
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

require_once 'header.php';
?>

<div class="table-container">
    <h2 class="table-title">ยืนยันการออกจากระบบ</h2>
    
    <div class="confirmation-message">
        <p>คุณต้องการออกจากระบบจริงหรือไม่?</p>
    </div>
    
    <div class="form-buttons">
        <a href="logout.php" class="delete-btn" style="text-decoration: none; display: inline-block;">ยืนยันออกจากระบบ</a>
        <a href="javascript:history.back()" class="cancel-btn">ยกเลิก</a>
    </div>
</div>

<?php require_once 'footer.php'; ?>