<?php
require_once 'config.php';

//ตรวจสอบการล็อกอินและไม่สามารถลักไก่เพื่อเข้าหน้านี้โดยตรงได้ เช่น การพิมพ์ชื่อ url แต่ละหน้า
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

$error = '';
$item = null;

//dropdown หน่วยนับ
$units = [
    'อัน',
    'ชิ้น',
    'ใบ',
    'กล่อง',
    'ขวด',
    'แผ่น',
    'เล่ม',
    'คู่', 
    'ซอง',
    'กระป๋อง',
    'แท่ง',
    'ม้วน',
    'ด้าม',
    'ก้อน',
    'ตลับ',
    'รีม',
    'ลัง'
];
sort($units);

//รับค่ามาจากฐานข้อมูล item_id
if (isset($_GET['item_id'])) {
    $item_id = trim($_GET['item_id']);
} elseif (isset($_POST['item_id_old'])) {
    $item_id = trim($_POST['item_id_old']);
} else {
    $item_id = '';
}

//ดึงข้อมูลพัสดุที่ต้องการแก้ไข
if (!empty($item_id)) {
    try {
        $stmt = $conn->prepare("SELECT * FROM item WHERE Item_ID = ?");
        if ($stmt->execute([$item_id])) {
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$item) {
                $error = "ไม่พบพัสดุรหัส: " . htmlspecialchars($item_id);
            }
        }
    } catch(PDOException $e) {
        error_log("Error fetching item: " . $e->getMessage());
        $error = "เกิดข้อผิดพลาดในการดึงข้อมูลพัสดุ";
    }
} else {
    $error = "ไม่ระบุรหัสพัสดุ กรุณากลับไปเลือกพัสดุที่ต้องการแก้ไข";
}

//บันทึกการแก้ไข
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_id_old = trim($_POST['item_id_old']);
    $item_id_new = trim($_POST['item_id']);
    $item_name = trim($_POST['item_name']);
    $unit = trim($_POST['unit']);
    $item_total = (int)$_POST['item_total'];
    
    //ตรวจสอบข้อมูล
    if (empty($item_id_new) || empty($item_name) || empty($unit)) {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    } elseif (!preg_match('/^P\d{3}$/', $item_id_new)) {
        $error = "รูปแบบรหัสพัสดุไม่ถูกต้อง (เช่น P001)";
    } elseif ($item_total < 0) {
        $error = "จำนวนคงเหลือต้องไม่น้อยกว่า 0";
    } else {
        try {
            $conn->beginTransaction();
            
            //ตรวจสอบว่ามีการเปลี่ยนรหัสมั้ย
            $is_changed_id = ($item_id_old != $item_id_new);
            
            if ($is_changed_id) {
                //ตรวจสอบรหัสซ้ำ
                $check_id_stmt = $conn->prepare("SELECT COUNT(*) FROM item WHERE Item_ID = ?");
                $check_id_stmt->execute([$item_id_new]);
                if ($check_id_stmt->fetchColumn() > 0) {
                    $error = "รหัสพัสดุ '$item_id_new' มีอยู่แล้ว";
                    $conn->rollBack();
                }
            }
            
            //ตรวจสอบชื่อพัสดุซ้ำ (ครูโบว์ให้เพิ่มส่วนนี้)
            //ตรวจสอบว่ามีชื่อซ้ำกับพัสดุอื่นหรือไม่ (ไม่รวมพัสดุที่กำลังแก้ไข)
            $check_name_stmt = $conn->prepare("SELECT Item_ID FROM item WHERE Item_Name = ? AND Item_ID != ?");
            $check_name_stmt->execute([$item_name, $item_id_old]);
            if ($check_name_stmt->rowCount() > 0) {
                $existing_item = $check_name_stmt->fetch(PDO::FETCH_ASSOC);
                $error = "ชื่อพัสดุนี้มีอยู่แล้วในพัสดุรหัส: " . $existing_item['Item_ID'] . " กรุณาใช้ชื่ออื่น";
                $conn->rollBack();
            }
            
            if (empty($error)) {
                if ($is_changed_id) {
                    //อัพเดทรหัสในตารางอื่นก่อน
                    $tables = ['buy', 'request'];
                    foreach ($tables as $table) {
                        $update_stmt = $conn->prepare("UPDATE $table SET Item_ID = ? WHERE Item_ID = ?");
                        $update_stmt->execute([$item_id_new, $item_id_old]);
                    }
                }
                
                //อัพเดทข้อมูลพัสดุ
                $update_stmt = $conn->prepare("UPDATE item SET Item_ID = ?, Item_Name = ?, Unit = ?, Item_Total = ? WHERE Item_ID = ?");
                
                if ($update_stmt->execute([$item_id_new, $item_name, $unit, $item_total, $item_id_old])) {
    $rows_affected = $update_stmt->rowCount();
    
    if ($rows_affected > 0) {
        $conn->commit();
        $_SESSION['highlight_item'] = $item_id_new;
        $_SESSION['message'] = "แก้ไขพัสดุเรียบร้อยแล้ว";
        header("Location: item.php?success=2");
                        exit();
                    } else {
                        //ไม่มีข้อมูลเปลี่ยนแปลง แต่ไม่ถือเป็น error เช่น กดเข้ามาดูเฉยๆแต่ไม่ได้แก้ไขอะไร
                        $conn->commit();
                        $_SESSION['highlight_item'] = $item_id_new;
                        header("Location: item.php?success=2");
                        exit();
                    }
                } else {
                    $error = "ไม่สามารถอัพเดทข้อมูลได้";
                    $conn->rollBack();
                }
            }
        } catch(PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log("Update error: " . $e->getMessage());
            $error = "เกิดข้อผิดพลาดในการแก้ไขข้อมูล: " . $e->getMessage();
        }
    }
}

require_once 'header.php';
?>

<div class="table-container">
    <h2 class="table-title">แก้ไขพัสดุ</h2>
    
    <?php if (!empty($error)): ?>
        <div class="error-message">
            <?php echo $error; ?>
            <?php if (strpos($error, 'ไม่ระบุรหัสพัสดุ') !== false): ?>
                <p style="margin-top: 10px;">URL ที่ควรจะเป็น: edit_item.php?item_id=P001</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($item): ?>
    <form method="POST" action="edit_item.php">
        <input type="hidden" name="item_id_old" value="<?php echo htmlspecialchars($item['Item_ID']); ?>">
        
        <div class="add-form">
            <div class="form-row">
                <div class="form-field">
                    <label for="item_id">รหัสพัสดุ *</label>
                    <input type="text" id="item_id" name="item_id" required 
                           pattern="P\d{3}" title="รูปแบบ: P001, P002, ..."
                           value="<?php echo isset($_POST['item_id']) ? htmlspecialchars($_POST['item_id']) : htmlspecialchars($item['Item_ID']); ?>">
                </div>
                <div class="form-field">
                    <label for="item_name">ชื่อพัสดุ *</label>
                    <input type="text" id="item_name" name="item_name" required
                           value="<?php echo isset($_POST['item_name']) ? htmlspecialchars($_POST['item_name']) : htmlspecialchars($item['Item_Name']); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-field">
                    <label for="unit">หน่วยนับ *</label>
                    <select id="unit" name="unit" required class="dropdown-select">
                        <option value="" disabled <?php echo !isset($_POST['unit']) && empty($item['Unit']) ? 'selected' : ''; ?>>-- เลือกหน่วยนับ --</option>
                        <?php foreach ($units as $unit_item): ?>
                            <option value="<?php echo htmlspecialchars($unit_item); ?>"
                                <?php echo ((isset($_POST['unit']) && $_POST['unit'] == $unit_item) || (!isset($_POST['unit']) && $item['Unit'] == $unit_item)) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($unit_item); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="item_total">จำนวนคงเหลือ *</label>
                    <input type="number" id="item_total" name="item_total" min="0" required
                           value="<?php echo isset($_POST['item_total']) ? htmlspecialchars($_POST['item_total']) : htmlspecialchars($item['Item_Total']); ?>">
                </div>
            </div>
        </div>
        
        <div class="form-buttons">
            <button type="submit" class="add-form-btn">บันทึก</button>
            <a href="item.php" class="cancel-btn">ยกเลิก</a>
        </div>
    </form>
    <?php elseif (empty($error)): ?>
        <div class="error-message">
            <p>ไม่พบข้อมูลพัสดุ</p>
            <p>item_id ที่ได้รับ: <?php echo htmlspecialchars($item_id); ?></p>
            <div class="form-buttons">
                <a href="item.php" class="back-btn">กลับไปหน้าหลัก</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>