<?php
require_once 'config.php';

//ตรวจสอบการล็อกอินและไม่สามารถลักไก่เพื่อเข้าหน้านี้โดยตรงได้ เช่น การพิมพ์ชื่อ url แต่ละหน้า
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

$error = '';
$requests = [];

$highlight_request_id = null;
$highlight_multiple_requests = [];

if (isset($_SESSION['highlight_request'])) {
    $highlight_request_id = $_SESSION['highlight_request'];
    unset($_SESSION['highlight_request']);
}

if (isset($_SESSION['highlight_multiple_requests'])) {
    $highlight_multiple_requests = $_SESSION['highlight_multiple_requests'];
    unset($_SESSION['highlight_multiple_requests']);
}

//ลบรายการเบิก
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    try {
        $conn->beginTransaction();
        
        //ดึงข้อมูลก่อนลบ
        $select_stmt = $conn->prepare("SELECT Item_ID, Req_Total FROM request WHERE Req_ID = ?");
        $select_stmt->execute([$delete_id]);
        $request_data = $select_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($request_data) {
            //คืนยอดพัสดุ พอลบรายการเบิกของที่เคยเบิกยอดที่เบิกออกมาจะกลับไปอยู่หน้าข้อมูลพัสดุ
            $update_stmt = $conn->prepare("UPDATE item SET Item_Total = Item_Total + ? WHERE Item_ID = ?");
            $update_stmt->execute([$request_data['Req_Total'], $request_data['Item_ID']]);
            
            //ลบรายการเบิก 
            $delete_stmt = $conn->prepare("DELETE FROM request WHERE Req_ID = ?");
            $delete_stmt->execute([$delete_id]);
            
            $conn->commit();
            header("Location: request.php?success=3");
            exit();
        }
    } catch(PDOException $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        $error = "เกิดข้อผิดพลาดในการลบ";
    }
}

//ดึงข้อมูลรายการเบิก
try {
    $stmt = $conn->prepare("
        SELECT r.Req_ID, r.Req_Name, r.Req_Phone, r.Req_Date, i.Item_Name, r.Req_Total, r.Officer, r.Department 
        FROM request r 
        LEFT JOIN item i ON r.Item_ID = i.Item_ID 
        ORDER BY r.Req_Date DESC, r.Req_ID DESC
    ");
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
    foreach ($requests as &$request) {
        $date_time = explode(' ', $request['Req_Date']);
        $request['Req_Date_Formatted'] = date('d/m/Y', strtotime($date_time[0]));
        $request['Req_Time_Formatted'] = isset($date_time[1]) ? $date_time[1] : '00:00';
    }
} catch(PDOException $e) {
    $error = "เกิดข้อผิดพลาดในการดึงข้อมูล";
}

require_once 'header.php';
?>

<div class="table-container">
    <h2 class="table-title">รายการเบิกพัสดุ</h2>
    
    
    <?php if (!empty($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="success-message">
            <?php 
            if ($_GET['success'] == '1') echo "เพิ่มรายการเบิกเรียบร้อย";
            elseif ($_GET['success'] == '2') echo "แก้ไขรายการเบิกเรียบร้อย";
            elseif ($_GET['success'] == '3') echo "ลบรายการเบิกเรียบร้อย";
            ?>
        </div>
    <?php endif; ?>
    
    
    <div class="button-container">
        <a href="add_request.php" class="add-form-btn">เพิ่มรายการเบิก</a>
        <a href="dashboard.php" class="back-btn">กลับเมนู</a>
    </div>
    
    
    <table class="data-table">
        <thead>
            <tr>
                <th>ลำดับ</th>
                <th>ชื่อผู้ขอเบิก</th>
                <th>เบอร์โทร</th>
                <th>วันที่เบิก</th>
                <th>เวลาเบิก</th>
                <th>พัสดุ</th>
                <th>จำนวน</th>
                <th>เจ้าหน้าที่</th>
                <th>ฝ่าย/สาขา</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($requests) > 0): ?>
                <?php foreach ($requests as $index => $req): ?>
    <?php 

    $should_highlight = false;

    if ($highlight_request_id == $req['Req_ID']) {
        $should_highlight = true;
    }
    
    if (is_array($highlight_multiple_requests) && in_array($req['Req_ID'], $highlight_multiple_requests)) {
        $should_highlight = true;
    }
    ?>
    <tr class="<?php echo $should_highlight ? 'highlight-row' : ''; ?>">
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($req['Req_Name']); ?></td>
                        <td><?php echo htmlspecialchars($req['Req_Phone'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($req['Req_Date_Formatted']); ?></td>
                        <td><?php echo htmlspecialchars($req['Req_Time_Formatted']); ?></td>
                        <td><?php echo htmlspecialchars($req['Item_Name']); ?></td>
                        <td><?php echo htmlspecialchars($req['Req_Total']); ?></td>
                        <td><?php echo htmlspecialchars($req['Officer']); ?></td>
                        <td><?php echo htmlspecialchars($req['Department']); ?></td>
                        <td class="management-buttons">
                            <a href="edit_request.php?id=<?php echo urlencode($req['Req_ID']); ?>" class="edit-btn">แก้ไข</a>
                            <a href="request.php?delete_id=<?php echo urlencode($req['Req_ID']); ?>" class="delete-btn" onclick="return confirm('ยืนยันการลบรายการนี้?')">ลบ</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10" class="no-data">ไม่มีข้อมูลรายการเบิก</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'footer.php'; ?>