<?php
require_once 'config.php';

//ตรวจสอบการล็อกอินและไม่สามารถลักไก่เพื่อเข้าหน้านี้โดยตรงได้ เช่น การพิมพ์ชื่อ url แต่ละหน้า
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

$error = '';
$buy = null;
$items = [];

$buy_id_old = '';
$item_id_old = '';
$buy_total_old = '';
$selected_item_name = '';

try {
    $item_stmt = $conn->prepare("SELECT Item_ID, Item_Name FROM item ORDER BY Item_Name");
    $item_stmt->execute();
    $items = $item_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "ดึงข้อมูลพัสดุไม่สำเร็จ";
}

$buy_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($buy_id > 0) {
    try {
        $stmt = $conn->prepare("SELECT * FROM buy WHERE Buy_ID = ?");
        if ($stmt->execute([$buy_id])) {
            $buy = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($buy) {
                $buy_id_old = $buy['Buy_ID'];
                $item_id_old = $buy['Item_ID'];
                $buy_total_old = $buy['Buy_Total'];
                
                $buy_datetime = explode(' ', $buy['Buy_Date']);
                $buy['Buy_Date_Only'] = $buy_datetime[0];
                $buy['Buy_Time_Only'] = isset($buy_datetime[1]) ? $buy_datetime[1] : '00:00';
                
                foreach ($items as $item) {
                    if ($item['Item_ID'] == $buy['Item_ID']) {
                        $selected_item_name = $item['Item_Name'];
                        break;
                    }
                }
            } else {
                $error = "ไม่พบรายการซื้อรหัส: " . $buy_id;
            }
        }
    } catch(PDOException $e) {
        $error = "ดึงข้อมูลไม่สำเร็จ";
    }
} else {
    $error = "ไม่ได้ระบุรหัสรายการ";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $buy_id_old = isset($_POST['buy_id_old']) ? (int)$_POST['buy_id_old'] : 0;
    $buy_date = isset($_POST['buy_date']) ? trim($_POST['buy_date']) : '';
    $buy_time = isset($_POST['buy_time']) ? trim($_POST['buy_time']) : '';
    $item_id_old = isset($_POST['item_id_old']) ? trim($_POST['item_id_old']) : '';
    $item_id_new = isset($_POST['item_id']) ? trim($_POST['item_id']) : '';
    $buy_total_old = isset($_POST['buy_total_old']) ? (int)$_POST['buy_total_old'] : 0;
    $buy_total_new = isset($_POST['buy_total']) ? (int)$_POST['buy_total'] : 0;
    $officer = isset($_POST['officer']) ? trim($_POST['officer']) : '';
    
    if (!empty($buy_date)) {
        $current_month = date('Y-m');
        $selected_month = date('Y-m', strtotime($buy_date));
        if ($selected_month < $current_month) {
            $error = "เลือกได้แค่วันที่ในเดือน " . date('F Y') . " นี้เท่านั้น";
        }
    } else {
        $error = "กรุณาเลือกวันที่";
    }
    
    if (empty($buy_date)) {
        $error = "กรุณาเลือกวันที่";
    } elseif (empty($buy_time)) {
        $error = "กรุณาเลือกเวลา";
    } elseif (empty($item_id_new)) {
        $error = "กรุณาเลือกพัสดุ";
    } elseif ($buy_total_new <= 0) {
        $error = "กรุณากรอกจำนวนที่ถูกต้อง";
    } elseif (empty($officer)) {
        $error = "กรุณากรอกชื่อเจ้าหน้าที่";
    }
    
    if (empty($error)) {
        $buy_datetime = $buy_date . ' ' . $buy_time;
        
        try {
            $conn->beginTransaction();
            
            if ($buy_total_old > 0 && !empty($item_id_old)) {
                $return_stmt = $conn->prepare("UPDATE item SET Item_Total = Item_Total - ? WHERE Item_ID = ?");
                $return_stmt->execute([$buy_total_old, $item_id_old]);
            }
            
            $add_stmt = $conn->prepare("UPDATE item SET Item_Total = Item_Total + ? WHERE Item_ID = ?");
            $add_stmt->execute([$buy_total_new, $item_id_new]);
            
            $update_stmt = $conn->prepare("UPDATE buy SET Buy_Date = ?, Item_ID = ?, Buy_Total = ?, Officer = ? WHERE Buy_ID = ?");
            $update_stmt->execute([$buy_datetime, $item_id_new, $buy_total_new, $officer, $buy_id_old]);
            
            $conn->commit();
$_SESSION['highlight_buy'] = $buy_id_old;
header("Location: buy.php?success=2");
            exit();
        } catch(PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error = "แก้ไขข้อมูลไม่สำเร็จ";
        }
    }
}

$officer_name = isset($_SESSION['display_name']) ? $_SESSION['display_name'] : 
                (isset($_SESSION['username']) ? $_SESSION['username'] : '');

require_once 'header.php';
?>

<div class="table-container">
    <h2 class="table-title">แก้ไขรายการซื้อ</h2>
    
    <?php if (!empty($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($buy): ?>
    <form method="POST" action="edit_buy.php?id=<?php echo $buy_id; ?>" id="editBuyForm">
        <input type="hidden" name="buy_id_old" value="<?php echo htmlspecialchars($buy_id_old); ?>">
        <input type="hidden" name="item_id_old" value="<?php echo htmlspecialchars($item_id_old); ?>">
        <input type="hidden" name="buy_total_old" value="<?php echo htmlspecialchars($buy_total_old); ?>">
        
        <div class="add-form">
            <div class="form-row">
                <div class="form-field">
                    <label for="buy_date">วันที่ซื้อ *</label>
                    <input type="date" id="buy_date" name="buy_date" required
                           value="<?php echo htmlspecialchars($buy['Buy_Date_Only']); ?>"
                           min="<?php echo date('Y-m-01'); ?>"
                           max="<?php echo date('Y-m-t'); ?>">
                </div>
                <div class="form-field">
                    <label for="buy_time">เวลาซื้อ *</label>
                    <input type="time" id="buy_time" name="buy_time" required
                           value="<?php echo htmlspecialchars($buy['Buy_Time_Only']); ?>">
                </div>
                <div class="form-field">
                    <label for="item_id">พัสดุ *</label>
                    <div class="enhanced-dropdown">
                        <div class="dropdown-header" tabindex="0">
                            <span class="selected-text"><?php echo htmlspecialchars($selected_item_name ? $selected_item_name : '-- เลือกพัสดุ --'); ?></span>
                            <span class="dropdown-arrow">▼</span>
                        </div>
                        <input type="hidden" id="item_id" name="item_id" class="item-id" value="<?php echo htmlspecialchars($buy['Item_ID']); ?>">
                        <div class="dropdown-content">
                            <div class="search-box">
                                <input type="text" class="dropdown-search" placeholder="ค้นหาพัสดุ..." autocomplete="off">
                            </div>
                            <div class="options-list">
                                <?php foreach ($items as $item): ?>
                                    <div class="option-item" 
                                         data-id="<?php echo htmlspecialchars($item['Item_ID']); ?>"
                                         data-name="<?php echo htmlspecialchars($item['Item_Name']); ?>"
                                         <?php echo ($buy && $buy['Item_ID'] == $item['Item_ID']) ? 'class="selected"' : ''; ?>>
                                        <div class="option-name"><?php echo htmlspecialchars($item['Item_Name']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (empty($items)): ?>
                                    <div class="no-results">ไม่มีข้อมูลพัสดุ</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-field">
                    <label for="buy_total">จำนวน *</label>
                    <input type="number" id="buy_total" name="buy_total" min="1" required
                           value="<?php echo htmlspecialchars($buy['Buy_Total']); ?>">
                </div>
                <div class="form-field">
                    <label for="officer">เจ้าหน้าที่ *</label>
                    <input type="text" id="officer" name="officer" required
                           value="<?php echo isset($_POST['officer']) ? htmlspecialchars($_POST['officer']) : htmlspecialchars($officer_name); ?>"
                           placeholder="ชื่อเจ้าหน้าที่" readonly
                           class="officer-input">
                </div>
            </div>
        </div>
        
        <div class="form-buttons">
            <button type="submit" class="add-form-btn">บันทึก</button>
            <a href="buy.php" class="cancel-btn">ยกเลิก</a>
        </div>
    </form>
    <?php else: ?>
        <div class="error-message">
            <p><?php echo $error ?: 'ไม่มีข้อมูลรายการซื้อ'; ?></p>
            <?php if ($buy_id > 0): ?>
                <p>รหัสรายการที่ค้นหา: <?php echo $buy_id; ?></p>
            <?php endif; ?>
            <div class="form-buttons">
                <a href="buy.php" class="back-btn">กลับไปหน้าซื้อเข้า</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function setupEnhancedDropdown() {
        const dropdown = document.querySelector('.enhanced-dropdown');
        if (!dropdown) return;
        
        const dropdownHeader = dropdown.querySelector('.dropdown-header');
        const selectedText = dropdown.querySelector('.selected-text');
        const dropdownContent = dropdown.querySelector('.dropdown-content');
        const dropdownSearch = dropdown.querySelector('.dropdown-search');
        const options = dropdown.querySelectorAll('.option-item');
        const hiddenInput = dropdown.querySelector('.item-id');
        
        let isOpen = false;
        
        function toggleDropdown() {
            isOpen = !isOpen;
            if (isOpen) {
                dropdownContent.classList.add('show');
                dropdownSearch.focus();
                filterOptions('');
                dropdownContent.style.maxHeight = '400px';
                const optionsList = dropdown.querySelector('.options-list');
                optionsList.style.maxHeight = '320px';
            } else {
                dropdownContent.classList.remove('show');
            }
        }
        
        dropdownHeader.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleDropdown();
        });
        
        dropdownHeader.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleDropdown();
            }
            
            if (e.key === 'Escape' && isOpen) {
                toggleDropdown();
            }
        });
        
        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target) && isOpen) {
                toggleDropdown();
            }
        });
        
        dropdownSearch.addEventListener('input', function() {
            filterOptions(this.value);
        });
        
        function filterOptions(searchTerm) {
            const searchLower = searchTerm.toLowerCase();
            let hasVisibleOptions = false;
            let visibleCount = 0;
            
            options.forEach(option => {
                const name = option.getAttribute('data-name').toLowerCase();
                const id = option.getAttribute('data-id').toLowerCase();
                
                if (searchTerm === '' || name.includes(searchLower) || id.includes(searchLower)) {
                    option.style.display = 'flex';
                    option.style.flexDirection = 'column';
                    hasVisibleOptions = true;
                    visibleCount++;
                } else {
                    option.style.display = 'none';
                }
            });
            
            const noResults = dropdown.querySelector('.no-results');
            if (noResults) {
                noResults.style.display = hasVisibleOptions ? 'none' : 'block';
            }
            
            const optionsList = dropdown.querySelector('.options-list');
            if (visibleCount > 0) {
                const itemHeight = 50;
                const minVisible = 5;
                const maxHeight = Math.min(visibleCount, minVisible) * itemHeight;
                optionsList.style.maxHeight = maxHeight + 'px';
            }
        }
        
        options.forEach(option => {
            option.addEventListener('click', function() {
                const itemId = this.getAttribute('data-id');
                const itemName = this.getAttribute('data-name');
                
                selectedText.textContent = itemName;
                hiddenInput.value = itemId;
                
                options.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                
                toggleDropdown();
            });
        });
        
        dropdownSearch.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const visibleOptions = Array.from(options).filter(opt => opt.style.display !== 'none');
                if (visibleOptions.length > 0) {
                    visibleOptions[0].click();
                }
            }
            
            if (e.key === 'Escape') {
                toggleDropdown();
                dropdownHeader.focus();
            }
            
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                const visibleOptions = Array.from(options).filter(opt => opt.style.display !== 'none');
                if (visibleOptions.length === 0) return;
                
                let currentIndex = -1;
                visibleOptions.forEach((option, index) => {
                    if (option.classList.contains('selected')) {
                        currentIndex = index;
                    }
                });
                
                if (e.key === 'ArrowDown') {
                    currentIndex = (currentIndex + 1) % visibleOptions.length;
                } else if (e.key === 'ArrowUp') {
                    currentIndex = currentIndex <= 0 ? visibleOptions.length - 1 : currentIndex - 1;
                }
                
                options.forEach(opt => opt.classList.remove('selected'));
                visibleOptions[currentIndex].classList.add('selected');
                
                visibleOptions[currentIndex].scrollIntoView({
                    block: 'nearest',
                    behavior: 'smooth'
                });
            }
        });
    }
    
    setupEnhancedDropdown();
});
</script>

<style>
.enhanced-dropdown .dropdown-content {
    max-height: 400px !important;
}

.enhanced-dropdown .options-list {
    max-height: 320px !important;
    overflow-y: auto;
}

.enhanced-dropdown .option-item {
    min-height: 48px;
    padding: 8px 12px;
}

.enhanced-dropdown .options-list::-webkit-scrollbar {
    width: 8px;
}

.enhanced-dropdown .options-list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.enhanced-dropdown .options-list::-webkit-scrollbar-thumb {
    background: #9ECAD6;
    border-radius: 4px;
}

.enhanced-dropdown .options-list::-webkit-scrollbar-thumb:hover {
    background: #8DB9C6;
}
</style>

<?php require_once 'footer.php'; ?>