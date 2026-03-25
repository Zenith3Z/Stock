<?php
require_once 'config.php';

//ขึ้นยืนยันว่าต้องการออกจากระบบหรือไม่ เผื่อครูคุบไปกดปุ่มออกจากระบบแบบไม่ตั้งใจ
if (isset($_GET['action']) && $_GET['action'] == 'confirm') {
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ยืนยันการออกจากระบบ</title>
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
            </div>
        </header>
        
        <main class="main-content">
            <div class="container confirm-container">
                <div class="confirm-box">
                    <h2 class="confirm-title">ยืนยันการออกจากระบบ</h2>
                    <p class="confirm-message">คุณต้องการออกจากระบบหรือไม่?</p>
                    <div class="confirm-buttons">
                        <a href="logout.php?action=yes" class="confirm-btn confirm-yes">ยืนยัน</a>
                        <a href="javascript:history.back()" class="confirm-btn confirm-no">ยกเลิก</a>
                    </div>
                </div>
            </div>
        </main>
    </body>

    <?php require_once 'footer.php'; ?>
    
<?php
exit();
}

//ถ้ากดยืนยันจะออกจากระบบกลับไปหน้าใส่รหัสเข้าสู่ระบบอีกครั้ง
if (isset($_GET['action']) && $_GET['action'] == 'yes') {
    //ออกจากระบบ
    session_start();
    session_unset();
    session_destroy();
    
    //ตั้งค่า session ใหม่เพื่อเก็บข้อความแจ้งเตือน
    session_start();
    $_SESSION['logout_message'] = "คุณได้ออกจากระบบเรียบร้อยแล้ว";
    
    header("Location: index.php");
    exit();
}

//ถ้ากดยกเลิกว่าไม่ได้ต้องการออกจากระบบจะกลับไปหน้าเมนูแด๊สบ๊อดด
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: dashboard.php");
} else {
    header("Location: index.php");
}
exit();
?>