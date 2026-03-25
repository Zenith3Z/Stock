<?php
require_once 'config.php';

//ตรวจสอบการล็อกอินและไม่สามารถลักไก่เพื่อเข้าหน้านี้โดยตรงได้ เช่น การพิมพ์ชื่อ url แต่ละหน้า
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

$error = '';

//สร้างรหัสพัสดุใหม่ตัวอักษร P + กับตัวเลขตรงนี้จะรันอัตโนมัติ (แบบที่ครูโบว์บอก)
$next_item_id = 'P001';
try {
    $stmt = $conn->prepare("SELECT Item_ID FROM item ORDER BY Item_ID DESC LIMIT 1");
    if ($stmt->execute()) {
        $last_item = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($last_item) {
            $last_id = $last_item['Item_ID'];
            $number = intval(substr($last_id, 1)) + 1;
            $next_item_id = 'P' . str_pad($number, 3, '0', STR_PAD_LEFT);
        }
    }
} catch(PDOException $e) {
    error_log("Item ID error: " . $e->getMessage());
}

//dropdown หน่วยนับต่างๆ
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

//บันทึกข้อมูล
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_id = trim($_POST['item_id']);
    $item_name = trim($_POST['item_name']);
    $unit = trim($_POST['unit']);
    $item_total = (int)$_POST['item_total'];
    
    //ตรวจสอบข้อมูล
    if (empty($item_id) || empty($item_name) || empty($unit)) {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    } elseif (!preg_match('/^P\d{3}$/', $item_id)) {
        $error = "รูปแบบรหัสพัสดุไม่ถูกต้อง (เช่น P001)";
    } else {
        try {
            //ตรวจสอบรหัสพัสดุซ้ำ เช่น มีรหัสพัสดุนี้อยู่แล้วจะซ้ำอันเดิมไม่ด้ายยย
            $check_id_stmt = $conn->prepare("SELECT * FROM item WHERE Item_ID = ?");
            if ($check_id_stmt->execute([$item_id]) && $check_id_stmt->rowCount() > 0) {
                $error = "รหัสพัสดุนี้มีอยู่แล้ว";
            } else {
                //ตรวจสอบชื่อพัสดุซ้ำ ขึ้นเตือนว่าให้ใช้ชื่ออื่นนะจ้ะอิอิ
                $check_name_stmt = $conn->prepare("SELECT * FROM item WHERE Item_Name = ?");
                if ($check_name_stmt->execute([$item_name]) && $check_name_stmt->rowCount() > 0) {
                    $error = "ชื่อพัสดุนี้มีอยู่แล้ว กรุณาใช้ชื่ออื่น";
                } else {
                    //บันทึกข้อมูล
                    $insert_stmt = $conn->prepare("INSERT INTO item (Item_ID, Item_Name, Unit, Item_Total) VALUES (?, ?, ?, ?)");
                    if ($insert_stmt->execute([$item_id, $item_name, $unit, $item_total])) {
    $_SESSION['highlight_item'] = $item_id;
    header("Location: item.php?success=1");
                        exit();
                    }
                }
            }
        } catch(PDOException $e) {
            $error = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
        }
    }
}

require_once 'header.php';
?>

<div class="table-container">
    <h2 class="table-title">เพิ่มพัสดุใหม่</h2>
    
    <?php if (!empty($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="add_item.php">
        <div class="add-form">
            <div class="form-row">
                <div class="form-field">
                    <label for="item_id">รหัสพัสดุ *</label>
                    <input type="text" id="item_id" name="item_id" required pattern="P\d{3}"
                        value="<?php echo isset($_POST['item_id']) ? htmlspecialchars($_POST['item_id']) : htmlspecialchars($next_item_id); ?>"
                        readonly class="item-id-input">
                </div>
                <div class="form-field">
                    <label for="item_name">ชื่อพัสดุ *</label>
                    <input type="text" id="item_name" name="item_name" required
                        value="<?php echo isset($_POST['item_name']) ? htmlspecialchars($_POST['item_name']) : ''; ?>"
                        placeholder="กรุณากรอกชื่อพัสดุ">
                </div>
            </div>
            <div class="form-row">
                <div class="form-field">
                    <label for="unit">หน่วยนับ *</label>
                    <select id="unit" name="unit" required class="dropdown-select">
                        <option value="" disabled <?php echo !isset($_POST['unit']) ? 'selected' : ''; ?>>-- เลือกหน่วยนับ --</option>
                        <?php foreach ($units as $unit_item): ?>
                            <option value="<?php echo htmlspecialchars($unit_item); ?>"
                                <?php echo (isset($_POST['unit']) && $_POST['unit'] == $unit_item) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($unit_item); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                </div>
                <div class="form-field">
                    <label for="item_total">จำนวนเริ่มต้น</label>
                    <input type="number" id="item_total" name="item_total" min="0" 
                           value="<?php echo isset($_POST['item_total']) ? htmlspecialchars($_POST['item_total']) : '0'; ?>"
                           placeholder="0">
                </div>
            </div>
        </div>
        
        <div class="form-buttons">
            <button type="submit" class="add-form-btn">บันทึก</button>
            <a href="item.php" class="cancel-btn">ยกเลิก</a>
        </div>
    </form>
</div>

<?php require_once 'footer.php'; ?>