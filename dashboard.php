<?php
require_once 'config.php';

//ตรวจสอบการล็อกอินและไม่สามารถลักไก่เพื่อเข้าหน้านี้โดยตรงได้ เช่น การพิมพ์ชื่อ url แต่ละหน้า
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

require_once 'header.php';
?>

<div class="dashboard">
    <div class="welcome-message">
        <h2>ระบบจัดการพัสดุ</h2>
        <p>เลือกเมนูที่ต้องการใช้งาน</p>
    </div>

    <div class="menu-grid">
        <a href="item.php" class="menu-item-link">
            <div class="menu-item">
                <div class="menu-icon"><img src="menu1.png" alt="ข้อมูลพัสดุ"></div>
                <h3>ข้อมูลพัสดุ</h3>
            </div>
        </a>

        <a href="request.php" class="menu-item-link">
            <div class="menu-item">
                <div class="menu-icon"><img src="menu2.png" alt="รายการเบิกพัสดุ"></div>
                <h3>รายการเบิกพัสดุ</h3>
            </div>
        </a>

        <a href="buy.php" class="menu-item-link">
            <div class="menu-item">
                <div class="menu-icon"><img src="menu3.png" alt="รายการซื้อเข้า"></div>
                <h3>รายการซื้อเข้า</h3>
            </div>
        </a>

        <a href="report.php" class="menu-item-link">
            <div class="menu-item">
                <div class="menu-icon"><img src="menu4.png" alt="รายงาน"></div>
                <h3>รายงาน</h3>
            </div>
        </a>
    </div>
</div>

<?php require_once 'footer.php'; ?>