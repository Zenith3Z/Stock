<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//ตรวจสอบว่า navbar ขึ้นในหน้าเมนูแดชบอดมั้ย (หน้าแดชบอดจะไม่ขึ้น navbar)
$current_page = basename($_SERVER['PHP_SELF']);
$is_dashboard = ($current_page === 'dashboard.php');

//ตรวจสอบการออกจากระบบ
if (isset($_GET['logout']) && $_GET['logout'] == 'confirm') {
    $_SESSION['logout_pending'] = true;
    header("Location: logout.php");
    exit();
}

//ดึงชื่อผู้ใช้จาก session
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$display_name = isset($_SESSION['display_name']) ? $_SESSION['display_name'] : $username;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการ/เบิกพัสดุ - วิทยาลัยเทคโนโลยีหาดใหญ่อำนวยวิทย์</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:ital,opsz,wght@0,17..18,400..700;1,17..18,400..700&family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="container header-content">
            <div class="logo">
                <img src="logo.png" alt="โลโก้">
                <h1>ระบบจัดการ/เบิกพัสดุ - วิทยาลัยเทคโนโลยีหาดใหญ่อำนวยวิทย์</h1>
            </div>
            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
            <div class="login-section">
                
                <div class="user-info">
                    <span class="user-greeting">-</span>
                    <span class="user-name"><?php echo htmlspecialchars($display_name); ?></span>
                    <span class="user-greeting">-</span>
                </div>
                
                <a href="logout.php?action=confirm" class="btn-logout">ออกจากระบบ</a>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && !$is_dashboard): ?>
    <nav class="main-navbar">
        <div class="container">
            <ul class="navbar-menu">
                <li><a href="dashboard.php" class="nav-link">หน้าหลัก</a></li>
                <li><a href="item.php" class="nav-link">ข้อมูลพัสดุ</a></li>
                <li><a href="request.php" class="nav-link">รายการเบิกพัสดุ</a></li>
                <li><a href="buy.php" class="nav-link">รายการซื้อเข้า</a></li>
                <li><a href="report.php" class="nav-link">รายงาน</a></li>
            </ul>
        </div>
    </nav>
    <?php endif; ?>

    <main class="main-content">
        <div class="container">