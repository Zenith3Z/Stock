<?php
require_once 'config.php';

//ตรวจสอบการล็อกอินและไม่สามารถลักไก่เพื่อเข้าหน้านี้โดยตรงได้ เช่น การพิมพ์ชื่อ url แต่ละหน้า
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

$error = '';
$items = [];

//dropdown ฝ่าย,สาขา
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

//ดึงข้อมูลพัสดุจากฐานข้อมูล
try {
    $stmt = $conn->prepare("SELECT Item_ID, Item_Name, Item_Total FROM item ORDER BY Item_Name");
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "เกิดข้อผิดพลาดในการดึงข้อมูลพัสดุ";
}

//บันทึกข้อมูล
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $req_name = trim($_POST['req_name']);
    $req_phone = trim($_POST['req_phone']);
    $req_date = trim($_POST['req_date']);
    $req_time = trim($_POST['req_time']);
    $officer = trim($_POST['officer']);
    $department = trim($_POST['department']);
    
    //ตรวจสอบเบอร์โทรใส่ได้แค่ 0 - 9 ใส่ได้แค่ 10 ตัว และต้องเป็นตัวเลขเท่านั้น
    if (!preg_match('/^[0-9]{10}$/', $req_phone)) {
        $error = "กรุณากรอกเบอร์โทรให้ถูกต้อง (ตัวเลข 10 หลัก)";
    }
    
    //ตรวจสอบวันที่ปัจจุบัน ถ้าไม่ใช่วันที่ปัจจุบันในเดือนนั้น จะขึ้นข้อความตามด้านล่าง
    $current_month = date('Y-m');
    $selected_month = date('Y-m', strtotime($req_date));
    if ($selected_month < $current_month) {
        $error = "ไม่สามารถเลือกวันที่ก่อนเดือน " . date('F Y') . " ได้";
    }
    
    //วันที่และเวลา
    $req_datetime = $req_date . ' ' . $req_time;
    
    if (empty($req_name) || empty($req_phone) || empty($req_date) || empty($req_time) || empty($officer) || empty($department)) {
        $error = "กรุณากรอกข้อมูลผู้ขอเบิกให้ครบถ้วน";
    } else {
        //ดึงข้อมูลรายการเบิกจากฟอร์ม
        $request_items = [];
        $item_count = 0;
        $validation_errors = [];
        
        //ตรวจสอบว่ามีฟิลด์ item_id ที่ส่งมามั้ย
        if (isset($_POST['item_id']) && is_array($_POST['item_id'])) {
            foreach ($_POST['item_id'] as $key => $item_id) {
                $req_total = isset($_POST['req_total'][$key]) ? (int)$_POST['req_total'][$key] : 0;
                
                if (!empty($item_id) && $req_total > 0) {
                    //หาชื่อพัสดุและจำนวนคงเหลือ
                    $item_name = '';
                    $item_stock = 0;
                    foreach ($items as $item) {
                        if ($item['Item_ID'] == $item_id) {
                            $item_name = $item['Item_Name'];
                            $item_stock = $item['Item_Total'];
                            break;
                        }
                    }
                    
                    //ตรวจสอบว่าจำนวนไม่เกินที่มีอยู่ เช่น มี 50 ใส่เกินกว่านี้ไม่ได้
                    if ($req_total > $item_stock) {
                        $validation_errors[] = "จำนวน '" . $item_name . "' เกินสต็อกที่มี (สต็อกคงเหลือ: " . $item_stock . ")";
                    }
                    
                    $request_items[] = [
                        'item_id' => $item_id,
                        'item_name' => $item_name,
                        'req_total' => $req_total,
                        'item_stock' => $item_stock
                    ];
                    $item_count++;
                }
            }
        }
        
        if ($item_count == 0) {
            $error = "กรุณาเพิ่มพัสดุที่ต้องการเบิกอย่างน้อย 1 รายการ";
        } elseif (!empty($validation_errors)) {
            $error = implode("<br>", $validation_errors);
        } elseif (!empty($error)) {
            //มี error จากตรวจสอบวันที่หรือเบอร์โทร
        } else {
            try {
                $conn->beginTransaction();
                $new_ids = [];
                
                foreach ($request_items as $request_item) {
                    //เพิ่มรายการเบิก
                    $insert_stmt = $conn->prepare("INSERT INTO request (Req_Name, Req_Phone, Req_Date, Item_ID, Req_Total, Officer, Department) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $insert_stmt->execute([$req_name, $req_phone, $req_datetime, $request_item['item_id'], $request_item['req_total'], $officer, $department]);
                    $new_id = $conn->lastInsertId();
                    $new_ids[] = $new_id;
                    
                    //ลดยอดพัสดุ
                    $update_stmt = $conn->prepare("UPDATE item SET Item_Total = Item_Total - ? WHERE Item_ID = ?");
                    $update_stmt->execute([$request_item['req_total'], $request_item['item_id']]);
                }
                
                $conn->commit();
                $_SESSION['highlight_multiple_requests'] = $new_ids;
                header("Location: request.php?success=1");
                exit();
            } catch(PDOException $e) {
                if ($conn->inTransaction()) $conn->rollBack();
                $error = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
            }
        }
    }
}

$officer_name = isset($_SESSION['display_name']) ? $_SESSION['display_name'] : 
                (isset($_SESSION['username']) ? $_SESSION['username'] : '');
$current_time = date('H:i');

require_once 'header.php';
?>

<div class="table-container">
    <h2 class="table-title">เพิ่มรายการเบิก</h2>
    
    <?php if (!empty($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="add_request.php" id="requestForm">
        <div class="add-form">
            
            <div class="form-row">
                <div class="form-field">
                    <label for="req_name">ชื่อผู้ขอเบิก *</label>
                    <input type="text" id="req_name" name="req_name" required
                           value="<?php echo isset($_POST['req_name']) ? htmlspecialchars($_POST['req_name']) : ''; ?>"
                           placeholder="กรุณากรอกชื่อผู้ขอเบิก">
                </div>
                <div class="form-field">
                    <label for="req_phone">เบอร์โทร *</label>
                    <input type="tel" id="req_phone" name="req_phone" required
                           pattern="[0-9]{10}"
                           title="กรุณากรอกเบอร์โทร 10 ตัวเลข"
                           maxlength="10"
                           oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                           value="<?php echo isset($_POST['req_phone']) ? htmlspecialchars($_POST['req_phone']) : ''; ?>"
                           placeholder="กรุณากรอกเบอร์โทร">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-field">
                    <label for="req_date">วันที่เบิก *</label>
                    <input type="date" id="req_date" name="req_date" required
                           value="<?php echo isset($_POST['req_date']) ? htmlspecialchars($_POST['req_date']) : date('Y-m-d'); ?>"
                           min="<?php echo date('Y-m-01'); ?>"
                           max="<?php echo date('Y-m-t'); ?>">
                </div>
                <div class="form-field">
                    <label for="req_time">เวลาเบิก *</label>
                    <input type="time" id="req_time" name="req_time" required
                           value="<?php echo isset($_POST['req_time']) ? htmlspecialchars($_POST['req_time']) : $current_time; ?>">
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
                        <option value="" disabled selected>-- เลือกฝ่าย/สาขา --</option>
                        <?php foreach ($departments as $group => $group_items): ?>
                            <optgroup label="<?php echo htmlspecialchars($group); ?>">
                                <?php foreach ($group_items as $item): ?>
                                    <option value="<?php echo htmlspecialchars($item); ?>"
                                        <?php echo (isset($_POST['department']) && $_POST['department'] == $item) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($item); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="margin: 20px 0;">
                <h3 style="color: #000000; border-bottom: 2px solid #F5BABB; padding-bottom: 5px;">รายการพัสดุที่ต้องการเบิก</h3>
            </div>
            
            <div id="itemsContainer" style="overflow: visible;">
                <div class="item-row" data-index="0">
                    <div class="form-row">
                        <div class="form-field">
                            <label>พัสดุ *</label>
                            <div class="enhanced-dropdown">
                                <div class="dropdown-header" tabindex="0">
                                    <span class="selected-text">-- เลือกพัสดุ --</span>
                                    <span class="dropdown-arrow">▼</span>
                                </div>
                                <input type="hidden" name="item_id[]" class="item-id">
                                <div class="dropdown-content">
                                    <div class="search-box">
                                        <input type="text" class="dropdown-search" placeholder="ค้นหาพัสดุ..." autocomplete="off">
                                    </div>
                                    <div class="options-list">
                                        <?php foreach ($items as $item): ?>
                                            <div class="option-item" 
                                                 data-id="<?php echo htmlspecialchars($item['Item_ID']); ?>"
                                                 data-name="<?php echo htmlspecialchars($item['Item_Name']); ?>"
                                                 data-stock="<?php echo htmlspecialchars($item['Item_Total']); ?>">
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
                            <div class="stock-info">
                                <span class="stock-label">สต็อกคงเหลือ: </span>
                                <span class="stock-amount">0</span>
                            </div>
                        </div>
                        <div class="form-field">
                            <label>จำนวน *</label>
                            <input type="text" name="req_total[]" class="item-quantity quantity-input" value="-" readonly style="text-align: center; background-color: #f8f9fa; color: #6c757d;">
                        </div>
                        <div class="form-field" style="display: flex; align-items: flex-end;">
                            <div style="height: 42px; width: 100px;"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            
            <div style="text-align: center; margin: 15px 0;">
                <button type="button" id="addItemBtn" class="add-form-btn" style="padding: 10px 30px;">+ เพิ่มพัสดุ</button>
            </div>
        </div>
        
        <div class="form-buttons">
            <button type="submit" class="add-form-btn">บันทึก</button>
            <a href="request.php" class="cancel-btn">ยกเลิก</a>
        </div>
    </form>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const itemsContainer = document.getElementById('itemsContainer');
    const addItemBtn = document.getElementById('addItemBtn');
    let itemIndex = 1;
    
    function createEnhancedDropdownHTML() {
        return `
            <div class="enhanced-dropdown">
                <div class="dropdown-header" tabindex="0">
                    <span class="selected-text">-- เลือกพัสดุ --</span>
                    <span class="dropdown-arrow">▼</span>
                </div>
                <input type="hidden" name="item_id[]" class="item-id">
                <div class="dropdown-content">
                    <div class="search-box">
                        <input type="text" class="dropdown-search" placeholder="ค้นหาพัสดุ..." autocomplete="off">
                    </div>
                    <div class="options-list">
                        <?php foreach ($items as $item): ?>
                            <div class="option-item" 
                                 data-id="<?php echo htmlspecialchars($item['Item_ID']); ?>"
                                 data-name="<?php echo htmlspecialchars($item['Item_Name']); ?>"
                                 data-stock="<?php echo htmlspecialchars($item['Item_Total']); ?>">
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
        `;
    }
    
    function createItemRow() {
        const newRow = document.createElement('div');
        newRow.className = 'item-row';
        newRow.dataset.index = itemIndex;
        newRow.innerHTML = `
            <div class="form-row">
                <div class="form-field">
                    <label>พัสดุ *</label>
                    ${createEnhancedDropdownHTML()}
                    <div class="stock-info">
                        <span class="stock-label">สต็อกคงเหลือ: </span>
                        <span class="stock-amount">0</span>
                    </div>
                </div>
                <div class="form-field">
                    <label>จำนวน *</label>
                    <input type="text" name="req_total[]" class="item-quantity quantity-input" value="-" readonly style="text-align: center; background-color: #f8f9fa; color: #6c757d;">
                </div>
                <div class="form-field" style="display: flex; align-items: flex-end;">
                    <button type="button" class="delete-btn remove-item" style="height: 42px; padding: 0 20px;">ลบ</button>
                </div>
            </div>
        `;
        
        itemsContainer.appendChild(newRow);
        itemIndex++;
        
        setupEnhancedDropdown(newRow);
        
        const removeBtn = newRow.querySelector('.remove-item');
        removeBtn.addEventListener('click', function() {
            removeItemRow(this);
        });
    }
    
    function setupEnhancedDropdown(row) {
        const dropdown = row.querySelector('.enhanced-dropdown');
        if (!dropdown) return;
        
        const dropdownHeader = dropdown.querySelector('.dropdown-header');
        const selectedText = dropdown.querySelector('.selected-text');
        const dropdownContent = dropdown.querySelector('.dropdown-content');
        const dropdownSearch = dropdown.querySelector('.dropdown-search');
        const options = dropdown.querySelectorAll('.option-item');
        const hiddenInput = dropdown.querySelector('.item-id');
        const quantityInput = row.querySelector('.item-quantity');
        const stockInfo = row.querySelector('.stock-info');
        const stockAmount = row.querySelector('.stock-amount');
        
        let isOpen = false;
        
        function calculateDropdownPosition() {
            const dropdownRect = dropdown.getBoundingClientRect();
            const windowHeight = window.innerHeight;
            const dropdownHeight = 400;
            
            const spaceBelow = windowHeight - dropdownRect.bottom;
            
            if (spaceBelow < dropdownHeight && dropdownRect.top > dropdownHeight) {
                dropdownContent.style.top = 'auto';
                dropdownContent.style.bottom = '100%';
                dropdownContent.style.marginTop = '0';
                dropdownContent.style.marginBottom = '5px';
            } else {
                dropdownContent.style.top = '100%';
                dropdownContent.style.bottom = 'auto';
                dropdownContent.style.marginTop = '5px';
                dropdownContent.style.marginBottom = '0';
            }
        }
        
        function toggleDropdown() {
            isOpen = !isOpen;
            if (isOpen) {
                calculateDropdownPosition();
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
                
                if (itemStock > 0) {
                    quantityInput.type = 'number';
                    quantityInput.min = 1;
                    quantityInput.max = itemStock;
                    quantityInput.value = 1;
                    quantityInput.readOnly = false;
                    quantityInput.disabled = false;
                    quantityInput.style.backgroundColor = 'white';
                    quantityInput.style.color = '#000000';
                    quantityInput.style.cursor = 'text';
                } else {
                    quantityInput.type = 'text';
                    quantityInput.value = '0 (หมด)';
                    quantityInput.readOnly = true;
                    quantityInput.disabled = true;
                    quantityInput.style.backgroundColor = '#e9ecef';
                    quantityInput.style.color = '#6c757d';
                    quantityInput.style.cursor = 'not-allowed';
                }
                
                options.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                
                toggleDropdown();
                
                setTimeout(() => {
                    if (!quantityInput.disabled) {
                        quantityInput.focus();
                        quantityInput.select();
                    }
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
            if (this.type === 'number') {
                const max = parseInt(this.max);
                const value = parseInt(this.value);
                
                if (max > 0 && value > max) {
                    this.value = max;
                    alert(`จำนวนสูงสุดคือ ${max}`);
                }
            }
        });
        
        quantityInput.addEventListener('click', function() {
            if (this.value === '-' && !hiddenInput.value) {
                alert('กรุณาเลือกพัสดุก่อน');
                dropdownHeader.focus();
            }
        });
        
        quantityInput.addEventListener('focus', function() {
            if (this.value === '-' && !hiddenInput.value) {
                alert('กรุณาเลือกพัสดุก่อน');
                dropdownHeader.focus();
            }
        });
        
        window.addEventListener('resize', function() {
            if (isOpen) {
                calculateDropdownPosition();
            }
        });
        
        window.addEventListener('scroll', function() {
            if (isOpen) {
                calculateDropdownPosition();
            }
        }, true);
    }
    
    function removeItemRow(button) {
        const row = button.closest('.item-row');
        const totalItems = document.querySelectorAll('.item-row').length;
        
        if (totalItems > 1) {
            if (confirm('ต้องการลบรายการพัสดุนี้ใช่หรือไม่?')) {
                row.remove();
            }
        } else {
            alert('ต้องมีรายการพัสดุอย่างน้อย 1 รายการ');
        }
    }
    
    setupEnhancedDropdown(document.querySelector('.item-row'));
    
    addItemBtn.addEventListener('click', createItemRow);
    
    document.getElementById('requestForm').addEventListener('submit', function(e) {
        const itemRows = document.querySelectorAll('.item-row');
        let hasValidItem = false;
        let errorMessage = '';
        
        const reqName = document.getElementById('req_name').value;
        const reqPhone = document.getElementById('req_phone').value;
        const reqDate = document.getElementById('req_date').value;
        const reqTime = document.getElementById('req_time').value;
        const officer = document.getElementById('officer').value;
        const department = document.getElementById('department').value;
        
        if (!reqName || !reqPhone || !reqDate || !reqTime || !officer || !department) {
            e.preventDefault();
            alert('กรุณากรอกข้อมูลผู้ขอเบิกให้ครบถ้วน');
            return;
        }
        
        const phonePattern = /^[0-9]{10}$/;
        if (!phonePattern.test(reqPhone)) {
            e.preventDefault();
            alert('กรุณากรอกเบอร์โทรให้ถูกต้อง (ตัวเลข 10 หลัก)');
            document.getElementById('req_phone').focus();
            return;
        }
        
        const itemIds = [];
        itemRows.forEach((row, index) => {
            const hiddenInput = row.querySelector('.item-id');
            const selectedText = row.querySelector('.selected-text');
            const quantityInput = row.querySelector('.item-quantity');
            const stockAmount = row.querySelector('.stock-amount');
            
            if (hiddenInput.value && quantityInput.value !== '-' && quantityInput.value !== '0 (หมด)') {
                const quantityValue = parseInt(quantityInput.value);
                
                if (quantityValue > 0) {
                    hasValidItem = true;
                    
                    const stockText = stockAmount.textContent;
                    const stockAmountNum = parseInt(stockText) || 0;
                    
                    if (stockText.includes('หมด') || stockAmountNum === 0) {
                        const itemName = selectedText.textContent;
                        errorMessage = `พัสดุ "${itemName}" สต็อกหมด กรุณาเลือกพัสดุอื่น`;
                        return;
                    }
                    
                    if (quantityValue > stockAmountNum) {
                        const itemName = selectedText.textContent;
                        errorMessage = `จำนวน "${itemName}" เกินสต็อกที่มี (สต็อกคงเหลือ: ${stockAmountNum})`;
                        return;
                    }
                    
                    if (itemIds.includes(hiddenInput.value)) {
                        const itemName = selectedText.textContent;
                        errorMessage = 'มีพัสดุ "' + itemName + '" ซ้ำกันในรายการ กรุณาตรวจสอบ';
                        return;
                    }
                    itemIds.push(hiddenInput.value);
                } else {
                    const itemName = selectedText.textContent;
                    errorMessage = `กรุณากรอกจำนวน "${itemName}" ให้ถูกต้อง`;
                    return;
                }
                
            } else {
                errorMessage = 'กรุณาเลือกพัสดุและระบุจำนวนให้ครบทุกรายการ';
            }
        });
        
        if (errorMessage) {
            e.preventDefault();
            alert(errorMessage);
            return;
        }
        
        if (!hasValidItem) {
            e.preventDefault();
            alert('กรุณาเพิ่มพัสดุที่ต้องการเบิกอย่างน้อย 1 รายการ');
        }
    });
});
</script>

<style>
.table-container {
    overflow: visible;
}

.add-form {
    overflow: visible;
}

#itemsContainer {
    overflow: visible;
}

.item-row {
    overflow: visible;
}

.item-row .form-row {
    display: flex;
    gap: 12px;
    margin-bottom: 0;
}

.item-row .form-field {
    flex: 1;
    min-width: 0;
}

.item-row .form-field:nth-child(1) {
    flex: 2.5;
}

.item-row .form-field:nth-child(2) {
    flex: 1;
    max-width: 150px;
}

.item-row .form-field:nth-child(3) {
    flex: 0 0 auto;
    max-width: 100px;
    min-width: 100px;
}

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

.quantity-input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    text-align: center;
    box-sizing: border-box;
}

.quantity-input[readonly] {
    background-color: #f8f9fa;
    color: #6c757d;
    cursor: not-allowed;
}

.quantity-input[type="number"] {
    background-color: white;
    color: #000000;
    cursor: text;
}

.quantity-input[value="0 (หมด)"] {
    background-color: #e9ecef;
    color: #dc3545;
    font-weight: 500;
    cursor: not-allowed;
}

.enhanced-dropdown .dropdown-content[style*="bottom: 100%"] {
    box-shadow: 0 -8px 20px rgba(0,0,0,0.2);
}

.enhanced-dropdown .dropdown-content[style*="bottom: 100%"] .search-box {
    border-bottom: none;
    border-top: 2px solid #eee;
}
</style>

<?php require_once 'footer.php'; ?>