<?php
require_once 'config.php';

//ตรวจสอบความผิดพลาดการล็อกอิน ถ้าเขียนผิดจะมีข้อความขึ้นว่า "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง"
$error = '';
if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

//เป็นข้อความที่ขึ้นด้านบนช่องชื่อผู้ใช้ว่า "คุณได้ออกจากระบบเรียบร้อยแล้ว" เพื่อความสวยงามทัชใจ
$logout_message = '';
if (isset($_SESSION['logout_message'])) {
    $logout_message = $_SESSION['logout_message'];
    unset($_SESSION['logout_message']);
}

require_once 'header.php';
?>

<div class="login-container">
    <div class="login-title">
        <h2>เข้าสู่ระบบ</h2>
        <p>กรุณากรอกข้อมูลเพื่อเข้าใช้งาน</p>
    </div>

    <?php if (!empty($logout_message)): ?>
    <div class="success-message"><?php echo $logout_message; ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
    <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>

    <form action="login_process.php" method="post">
        <div class="form-group">
            <label for="username">ชื่อผู้ใช้</label>
            <input type="text" id="username" name="username" placeholder="ชื่อผู้ใช้" required autofocus>
        </div>

        <div class="form-group">
            <label for="password">รหัสผ่าน</label>
            <input type="password" id="password" name="password" placeholder="รหัสผ่าน" required>
        </div>

        <button type="submit" class="login-btn">เข้าสู่ระบบ</button>
    </form>

</div>

<?php require_once 'footer.php'; ?>