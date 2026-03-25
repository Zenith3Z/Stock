<?php
require_once 'config.php';

//ตรวจสอบการล็อกอินและไม่สามารถลักไก่เพื่อเข้าหน้านี้โดยตรงได้ เช่น การพิมพ์ชื่อ url แต่ละหน้า
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

$error = '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$items = [];

$highlight_item_id = null;
if (isset($_SESSION['highlight_item'])) {
    $highlight_item_id = $_SESSION['highlight_item'];
    unset($_SESSION['highlight_item']);
}

//ดึงข้อมูลพัสดุจากฐานข้อมูล
try {
    if (!empty($search)) {
        //การค้นหาพัสดุไท่ต้องไปนั่งเลื่อนหาให้เสียเวลา
        $stmt = $conn->prepare("SELECT * FROM item WHERE Item_Name LIKE :search ORDER BY Item_ID");
        $stmt->execute([':search' => '%' . $search . '%']);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        //แสดงสมาชิกรายชื่อพัสดุทั้งหมด
        $stmt = $conn->prepare("SELECT * FROM item ORDER BY Item_ID");
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch(PDOException $e) {
    $error = "เกิดข้อผิดพลาดในการดึงข้อมูล";
}

require_once 'header.php';
?>

<div class="table-container">
    <h2 class="table-title">ข้อมูลพัสดุ</h2>
    
    
    <?php if (!empty($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="success-message">
            <?php 
            if ($_GET['success'] == '1') echo "เพิ่มพัสดุเรียบร้อย";
            elseif ($_GET['success'] == '2') echo "แก้ไขพัสดุเรียบร้อย";
            elseif ($_GET['success'] == '3') echo "ลบพัสดุเรียบร้อย";
            ?>
        </div>
    <?php endif; ?>
    
    
    <div class="button-container">
        <a href="add_item.php" class="add-form-btn">เพิ่มพัสดุใหม่</a>
        <a href="dashboard.php" class="back-btn">กลับเมนู</a>
    </div>
    
    
    <div class="search-container">
        <form method="GET" action="item.php" class="search-form">
            <input type="text" name="search" placeholder="ค้นหาพัสดุ..." value="<?php echo htmlspecialchars($search); ?>" class="search-input">
            <button type="submit" class="search-btn">ค้นหา</button>
            <?php if (!empty($search)): ?>
                <a href="item.php" class="clear-search-btn">แสดงทั้งหมด</a>
            <?php endif; ?>
        </form>
    </div>
    
    
    <?php if (count($items) > 0): ?>
        <div class="search-results-info">
            <?php echo !empty($search) ? "พบ " . count($items) . " รายการ" : "แสดงพัสดุทั้งหมด " . count($items) . " รายการ"; ?>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>ลำดับ</th>
                    <th>รหัสพัสดุ</th>
                    <th>ชื่อพัสดุ</th>
                    <th>จำนวนคงเหลือ</th>
                    <th>หน่วย</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $index => $item): ?>
    <tr class="<?php echo ($highlight_item_id == $item['Item_ID']) ? 'highlight-row' : ''; ?>">
        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($item['Item_ID']); ?></td>
                        <td><?php echo htmlspecialchars($item['Item_Name']); ?></td>
                        <td><?php echo htmlspecialchars($item['Item_Total']); ?></td>
                        <td><?php echo htmlspecialchars($item['Unit']); ?></td>
                        <td class="management-buttons">
                            <a href="edit_item.php?item_id=<?php echo htmlspecialchars($item['Item_ID']); ?>" class="edit-btn">แก้ไข</a>
                            <a href="item_delete.php?id=<?php echo urlencode($item['Item_ID']); ?>" class="delete-btn" onclick="return confirm('ยืนยันการลบพัสดุนี้?')">ลบ</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-data-message">
            <p><?php echo !empty($search) ? "ไม่พบข้อมูลที่ค้นหา" : "ยังไม่มีข้อมูลพัสดุ"; ?></p>
            <?php if (!empty($search)): ?>
                <a href="item.php" class="back-btn">แสดงทั้งหมด</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>