<?php
require_once 'config.php';

//ตรวจสอบการล็อกอินและไม่สามารถลักไก่เพื่อเข้าหน้านี้โดยตรงได้ เช่น การพิมพ์ชื่อ url แต่ละหน้า
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

$error = '';
$request = null;
$items = [];

$request_id_old = '';
$item_id_old = '';
$req_total_old = '';
$selected_item_name = '';
$selected_item_stock = 0;

$departments = [
    'ฝ่าย' => [
        'แผนและควบคุมคุณภาพ',
        'สำนักงานบริการ',
        'ประชาสัมพันธ์และชุมชน',
        'พัฒนากิจการนักเรียนนักศึกษา',
        'งบประมาณและการเงิน',
        'พัฒนาวิชาการ',
        'ทรัพยากรบุคคล'
    ],
    'สาขา' => [
        'เทคโนโลยีธุรกิจดิจิทัล',
        'ดิจิทัลกราฟิก',
        'การจัดการ',
        'การบัญชี',
        'การตลาด',
        'สามัญ',
        'การท่องเที่ยว',
        'ภาษาต่างประเทศธุรกิจบริการ'
    ]
];
sort($departments['ฝ่าย']);
sort($departments['สาขา']);

try {
    $item_stmt = $conn->prepare("SELECT Item_ID, Item_Name, Item_Total FROM item ORDER BY Item_Name");
    $item_stmt->execute();
    $items = $item_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "ดึงข้อมูลพัสดุไม่สำเร็จ";
}

$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($request_id > 0) {
    try {
        $stmt = $conn->prepare("SELECT * FROM request WHERE Req_ID = ?");
        if ($stmt->execute([$request_id])) {
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($request) {
                $request_id_old = $request['Req_ID'];
                $item_id_old = $request['Item_ID'];
                $req_total_old = $request['Req_Total'];
                
                $req_datetime = explode(' ', $request['Req_Date']);
                $request['Req_Date_Only'] = $req_datetime[0];
                $request['Req_Time_Only'] = isset($req_datetime[1]) ? $req_datetime[1] : '00:00';
                
                foreach ($items as $item) {
                    if ($item['Item_ID'] == $request['Item_ID']) {
                        $selected_item_name = $item['Item_Name'];
                        $selected_item_stock = $item['Item_Total'];
                        break;
                    }
                }
            } else {
                $error = "ไม่พบรายการเบิกรหัส: " . $request_id;
            }
        }
    } catch(PDOException $e) {
        $error = "ดึงข้อมูลไม่สำเร็จ: " . $e->getMessage();
    }
} else {
    $error = "ไม่ได้ระบุรหัสรายการ";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $request_id_old = isset($_POST['request_id_old']) ? (int)$_POST['request_id_old'] : 0;
    $req_name = isset($_POST['req_name']) ? trim($_POST['req_name']) : '';
    $req_phone = isset($_POST['req_phone']) ? trim($_POST['req_phone']) : '';
    $req_date = isset($_POST['req_date']) ? trim($_POST['req_date']) : '';
    $req_time = isset($_POST['req_time']) ? trim($_POST['req_time']) : '';
    $item_id_old = isset($_POST['item_id_old']) ? trim($_POST['item_id_old']) : '';
    $item_id_new = isset($_POST['item_id']) ? trim($_POST['item_id']) : '';
    $req_total_old = isset($_POST['req_total_old']) ? (int)$_POST['req_total_old'] : 0;
    $req_total_new = isset($_POST['req_total']) ? (int)$_POST['req_total'] : 0;
    $officer = isset($_POST['officer']) ? trim($_POST['officer']) : '';
    $department = isset($_POST['department']) ? trim($_POST['department']) : '';
    
    if (!empty($req_phone) && !preg_match('/^[0-9]{10}$/', $req_phone)) {
        $error = "เบอร์โทรต้องเป็นตัวเลข 10 ตัวเท่านั้น";
    }
    
    if (!empty($req_date)) {
        $current_month = date('Y-m');
        $selected_month = date('Y-m', strtotime($req_date));
        if ($selected_month < $current_month) {
            $error = "เลือกได้แค่วันที่ในเดือน " . date('F Y') . " นี้เท่านั้น";
        }
    } else {
        $error = "กรุณาเลือกวันที่";
    }
    
    if (empty($req_name)) {
        $error = "กรุณากรอกชื่อผู้ขอเบิก";
    } elseif (empty($req_phone)) {
        $error = "กรุณากรอกเบอร์โทร";
    } elseif (empty($req_date)) {
        $error = "กรุณาเลือกวันที่";
    } elseif (empty($req_time)) {
        $error = "กรุณาเลือกเวลา";
    } elseif (empty($item_id_new)) {
        $error = "กรุณาเลือกพัสดุ";
    } elseif ($req_total_new <= 0) {
        $error = "กรุณากรอกจำนวนที่ถูกต้อง";
    } elseif (empty($officer)) {
        $error = "กรุณากรอกชื่อเจ้าหน้าที่";
    } elseif (empty($department)) {
        $error = "กรุณาเลือกฝ่าย/สาขา";
    }
    
    if (empty($error)) {
        $req_datetime = $req_date . ' ' . $req_time;
        
        try {
            $conn->beginTransaction();
            
            if ($item_id_old != $item_id_new || $req_total_old != $req_total_new) {
                if ($item_id_old != $item_id_new) {
                    if ($req_total_old > 0 && !empty($item_id_old)) {
                        $return_stmt = $conn->prepare("UPDATE item SET Item_Total = Item_Total + ? WHERE Item_ID = ?");
                        $return_stmt->execute([$req_total_old, $item_id_old]);
                    }
                    
                    $stock_stmt = $conn->prepare("SELECT Item_Total FROM item WHERE Item_ID = ?");
                    $stock_stmt->execute([$item_id_new]);
                    $stock = $stock_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($stock && $stock['Item_Total'] < $req_total_new) {
                        throw new Exception("พัสดุใหม่มีไม่พอ (เหลือ: " . $stock['Item_Total'] . ")");
                    }
                    
                    $deduct_stmt = $conn->prepare("UPDATE item SET Item_Total = Item_Total - ? WHERE Item_ID = ?");
                    $deduct_stmt->execute([$req_total_new, $item_id_new]);
                } else {
                    $adjust_stmt = $conn->prepare("UPDATE item SET Item_Total = Item_Total + ? - ? WHERE Item_ID = ?");
                    $adjust_stmt->execute([$req_total_old, $req_total_new, $item_id_new]);
                }
            }
            
            $update_stmt = $conn->prepare("UPDATE request SET Req_Name = ?, Req_Phone = ?, Req_Date = ?, Item_ID = ?, Req_Total = ?, Officer = ?, Department = ? WHERE Req_ID = ?");
            $update_stmt->execute([$req_name, $req_phone, $req_datetime, $item_id_new, $req_total_new, $officer, $department, $request_id_old]);
            
            $conn->commit();
$_SESSION['highlight_request'] = $request_id_old;
header("Location: request.php?success=2");
            exit();
        } catch(PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error = "แก้ไขข้อมูลไม่สำเร็จ: " . $e->getMessage();
        } catch(Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error = $e->getMessage();
        }
    }
}

$officer_name = isset($_SESSION['display_name']) ? $_SESSION['display_name'] : 
                (isset($_SESSION['username']) ? $_SESSION['username'] : '');

require_once 'header.php';
?>

<div class="table-container">
    <h2 class="table-title">แก้ไขรายการเบิก</h2>
    
    <?php if (!empty($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($request): ?>
    <form method="POST" action="edit_request.php?id=<?php echo $request_id; ?>" id="editRequestForm">
        <input type="hidden" name="request_id_old" value="<?php echo htmlspecialchars($request_id_old); ?>">
        <input type="hidden" name="item_id_old" value="<?php echo htmlspecialchars($item_id_old); ?>">
        <input type="hidden" name="req_total_old" value="<?php echo htmlspecialchars($req_total_old); ?>">
        
        <div class="add-form">
            <div class="form-row">
                <div class="form-field">
                    <label for="req_name">ชื่อผู้ขอเบิก *</label>
                    <input type="text" id="req_name" name="req_name" required
                           value="<?php echo htmlspecialchars($request['Req_Name']); ?>">
                </div>
                <div class="form-field">
                    <label for="req_phone">เบอร์โทร *</label>
                    <input type="tel" id="req_phone" name="req_phone" required
                           pattern="[0-9]{10}"
                           title="กรอกเบอร์โทร 10 ตัวเลข"
                           maxlength="10"
                           oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                           value="<?php echo htmlspecialchars($request['Req_Phone'] ?? ''); ?>">
                </div>
                <div class="form-field">
                    <label for="req_date">วันที่เบิก *</label>
                    <input type="date" id="req_date" name="req_date" required
                           value="<?php echo htmlspecialchars($request['Req_Date_Only']); ?>"
                           min="<?php echo date('Y-m-01'); ?>"
                           max="<?php echo date('Y-m-t'); ?>">
                </div>
                <div class="form-field">
                    <label for="req_time">เวลาเบิก *</label>
                    <input type="time" id="req_time" name="req_time" required
                           value="<?php echo htmlspecialchars($request['Req_Time_Only']); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-field">
                    <label for="item_id">พัสดุ *</label>
                    <div class="enhanced-dropdown">
                        <div class="dropdown-header" tabindex="0">
                            <span class="selected-text"><?php echo htmlspecialchars($selected_item_name ? $selected_item_name : '-- เลือกพัสดุ --'); ?></span>
                            <span class="dropdown-arrow">▼</span>
                        </div>
                        <input type="hidden" id="item_id" name="item_id" class="item-id" value="<?php echo htmlspecialchars($request['Item_ID']); ?>">
                        <div class="dropdown-content">
                            <div class="search-box">
                                <input type="text" class="dropdown-search" placeholder="ค้นหาพัสดุ..." autocomplete="off">
                            </div>
                            <div class="options-list">
                                <?php foreach ($items as $item): ?>
                                    <div class="option-item" 
                                         data-id="<?php echo htmlspecialchars($item['Item_ID']); ?>"
                                         data-name="<?php echo htmlspecialchars($item['Item_Name']); ?>"
                                         data-stock="<?php echo htmlspecialchars($item['Item_Total']); ?>"
                                         <?php echo ($request && $request['Item_ID'] == $item['Item_ID']) ? 'class="selected"' : ''; ?>>
                                        <div class="option-name"><?php echo htmlspecialchars($item['Item_Name']); ?></div>
                                        <div class="option-details">
                                            <span class="option-id">รหัส: <?php echo htmlspecialchars($item['Item_ID']); ?></span>
                                            <span class="option-stock">คงเหลือ: <?php echo htmlspecialchars($item['Item_Total']); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (empty($items)): ?>
                                    <div class="no-results">ไม่มีข้อมูลพัสดุ</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="stock-info show">
                        <span class="stock-label">สต็อกคงเหลือ: </span>
                        <span class="stock-amount"><?php echo $selected_item_stock; ?></span>
                    </div>
                </div>
                <div class="form-field">
                    <label>จำนวน *</label>
                    <input type="number" id="req_total" name="req_total" min="1" required
                           value="<?php echo htmlspecialchars($request['Req_Total']); ?>"
                           max="<?php echo $selected_item_stock; ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-field">
                    <label for="officer">เจ้าหน้าที่ *</label>
                    <input type="text" id="officer" name="officer" required
                           value="<?php echo isset($_POST['officer']) ? htmlspecialchars($_POST['officer']) : htmlspecialchars($officer_name); ?>"
                           placeholder="ชื่อเจ้าหน้าที่" readonly
                           class="officer-input">
                </div>
                <div class="form-field">
                    <label for="department">ฝ่าย/สาขา *</label>
                    <select id="department" name="department" required class="dropdown-select">
                        <option value="" disabled>-- เลือกฝ่าย/สาขา --</option>
                        <?php foreach ($departments as $group => $dept_items): ?>
                            <optgroup label="<?php echo htmlspecialchars($group); ?>">
                                <?php foreach ($dept_items as $item): ?>
                                    <option value="<?php echo htmlspecialchars($item); ?>"
                                        <?php 
                                        $selected = false;
                                        if (isset($_POST['department'])) {
                                            $selected = ($_POST['department'] == $item);
                                        } elseif (isset($request['Department'])) {
                                            $selected = ($request['Department'] == $item);
                                        }
                                        echo $selected ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($item); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="form-buttons">
            <button type="submit" class="add-form-btn">บันทึก</button>
            <a href="request.php" class="cancel-btn">ยกเลิก</a>
        </div>
    </form>
    <?php else: ?>
        <div class="error-message">
            <p><?php echo $error ?: 'ไม่มีข้อมูลรายการเบิก'; ?></p>
            <?php if ($request_id > 0): ?>
                <p>รหัสรายการที่ค้นหา: <?php echo $request_id; ?></p>
                <p>ลองตรวจสอบในฐานข้อมูลว่ามี Req_ID นี้หรือไม่</p>
            <?php endif; ?>
            <div class="form-buttons">
                <a href="request.php" class="back-btn">กลับไปหน้าเบิกพัสดุ</a>
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
        const quantityInput = document.getElementById('req_total');
        const stockInfo = dropdown.closest('.form-field').querySelector('.stock-info');
        const stockAmount = stockInfo.querySelector('.stock-amount');
        
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
                const itemStock = parseInt(this.getAttribute('data-stock'));
                
                selectedText.textContent = itemName;
                hiddenInput.value = itemId;
                stockAmount.textContent = itemStock;
                stockInfo.classList.add('show');
                quantityInput.max = itemStock;
                
                const currentValue = parseInt(quantityInput.value);
                if (currentValue > itemStock) {
                    quantityInput.value = itemStock;
                }
                
                options.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                
                toggleDropdown();
                
                setTimeout(() => {
                    quantityInput.focus();
                    quantityInput.select();
                }, 100);
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
        
        quantityInput.addEventListener('change', function() {
            const max = parseInt(this.max);
            const value = parseInt(this.value);
            
            if (max > 0 && value > max) {
                this.value = max;
                alert(`จำนวนสูงสุดคือ ${max}`);
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

.option-details {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    font-size: 12px;
    color: #666;
    margin-top: 2px;
}

.option-id {
    color: #0066cc;
    font-weight: 500;
}

.option-stock {
    color: #28a745;
    font-weight: 500;
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