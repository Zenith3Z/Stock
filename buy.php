<?php
require_once 'config.php';

//ตรวจสอบการล็อกอินและไม่สามารถลักไก่เพื่อเข้าหน้านี้โดยตรงได้ เช่น การพิมพ์ชื่อ url แต่ละหน้า
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

$error = '';
$buys = [];

$highlight_buy_id = null;
if (isset($_SESSION['highlight_buy'])) {
    $highlight_buy_id = $_SESSION['highlight_buy'];
    unset($_SESSION['highlight_buy']);
}

//ลบรายการซื้อ
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    try {
        $conn->beginTransaction();
        
        //ดึงข้อมูลก่อนลบ
        $select_stmt = $conn->prepare("SELECT Item_ID, Buy_Total FROM buy WHERE Buy_ID = ?");
        $select_stmt->execute([$delete_id]);
        $buy_data = $select_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($buy_data) {
            //ลดยอดพัสดุ
            $update_stmt = $conn->prepare("UPDATE item SET Item_Total = Item_Total - ? WHERE Item_ID = ?");
            $update_stmt->execute([$buy_data['Buy_Total'], $buy_data['Item_ID']]);
            
            //ลบรายการซื้อ
            $delete_stmt = $conn->prepare("DELETE FROM buy WHERE Buy_ID = ?");
            $delete_stmt->execute([$delete_id]);
            
            $conn->commit();
            header("Location: buy.php?success=3");
            exit();
        }
    } catch(PDOException $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        $error = "เกิดข้อผิดพลาดในการลบ";
    }
}

//ดึงข้อมูลรายการซื้อ
try {
    $stmt = $conn->prepare("
        SELECT b.Buy_ID, b.Buy_Date, i.Item_Name, b.Buy_Total, b.Officer 
        FROM buy b 
        LEFT JOIN item i ON b.Item_ID = i.Item_ID 
        ORDER BY b.Buy_Date DESC, b.Buy_ID DESC
    ");
    $stmt->execute();
    $buys = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    //แปลงวันที่สำหรับแสดงผล
    foreach ($buys as &$buy) {
        $date_time = explode(' ', $buy['Buy_Date']);
        $buy['Buy_Date_Formatted'] = date('d/m/Y', strtotime($date_time[0]));
        $buy['Buy_Time_Formatted'] = isset($date_time[1]) ? $date_time[1] : '00:00';
    }
} catch(PDOException $e) {
    $error = "เกิดข้อผิดพลาดในการดึงข้อมูล";
}

require_once 'header.php';
?>

<div class="table-container">
    <h2 class="table-title">รายการซื้อพัสดุ</h2>
    
    
    <?php if (!empty($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="success-message">
            <?php 
            if ($_GET['success'] == '1') echo "เพิ่มรายการซื้อเรียบร้อย";
            elseif ($_GET['success'] == '2') echo "แก้ไขรายการซื้อเรียบร้อย";
            elseif ($_GET['success'] == '3') echo "ลบรายการซื้อเรียบร้อย";
            ?>
        </div>
    <?php endif; ?>
    
    
    <div class="button-container">
        <a href="add_buy.php" class="add-form-btn">เพิ่มรายการซื้อ</a>
        <a href="add_item.php" class="add-form-btn">เพิ่มพัสดุใหม่</a>
        <a href="dashboard.php" class="back-btn">กลับเมนู</a>
    </div>
    
    
    <table class="data-table">
        <thead>
            <tr>
                <th>ลำดับ</th>
                <th>วันที่ซื้อ</th>
                <th>เวลาซื้อ</th>
                <th>พัสดุ</th>
                <th>จำนวน</th>
                <th>เจ้าหน้าที่</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($buys) > 0): ?>
                <?php foreach ($buys as $index => $buy): ?>
    <tr class="<?php echo ($highlight_buy_id == $buy['Buy_ID']) ? 'highlight-row' : ''; ?>">
        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($buy['Buy_Date_Formatted']); ?></td>
                        <td><?php echo htmlspecialchars($buy['Buy_Time_Formatted']); ?></td>
                        <td><?php echo htmlspecialchars($buy['Item_Name']); ?></td>
                        <td><?php echo htmlspecialchars($buy['Buy_Total']); ?></td>
                        <td><?php echo htmlspecialchars($buy['Officer']); ?></td>
                        <td class="management-buttons">
                            <a href="edit_buy.php?id=<?php echo urlencode($buy['Buy_ID']); ?>" class="edit-btn">แก้ไข</a>
                            <a href="buy.php?delete_id=<?php echo urlencode($buy['Buy_ID']); ?>" class="delete-btn" onclick="return confirm('ยืนยันการลบรายการนี้?')">ลบ</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="no-data">ไม่มีข้อมูลรายการซื้อ</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'footer.php'; ?>