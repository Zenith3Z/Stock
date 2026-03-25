<?php
require_once 'config.php';

//ตรวจสอบการส่งฟอร์มเมื่อกรอกเสร็จจจ
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    //ตรวจสอบข้อมูลว่างต้องกรอกเท่านั้นถ้าไม่กรอกจะไม่ไปไหนย้ำอยู่กับที่นะจ้ะ
    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = "กรุณากรอกชื่อผู้ใช้และรหัสผ่าน";
        header("Location: index.php");
        exit();
    }
    
    try {
        //ตรวจสอบผู้ใช้ในฐานข้อมูล
        $stmt = $conn->prepare("SELECT * FROM admin WHERE Username = :username AND Password = :password");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password);
        
        if ($stmt->execute() && $stmt->rowCount() > 0) {
            //ล็อกอินสำเร็จว่ะฮ่าๆๆ
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            
            if (isset($user_data['Note'])) {
                $_SESSION['display_name'] = $user_data['Note'];
            } elseif (isset($user_data['Username'])) {
                $_SESSION['display_name'] = $user_data['Username'];
            } else {
                $_SESSION['display_name'] = $username;
            }
            
            header("Location: dashboard.php");
            exit();
        } else {
            //ล็อกอินไม่สำเร็จแป้วๆๆๆ
            $_SESSION['login_error'] = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
            header("Location: index.php");
            exit();
        }
    } catch(PDOException $e) {
        $_SESSION['login_error'] = "เกิดข้อผิดพลาดในการเข้าสู่ระบบ";
        header("Location: index.php");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>