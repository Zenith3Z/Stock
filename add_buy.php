<?php
require_once 'config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

$error = '';
$items = [];

try {
    $stmt = $conn->prepare("SELECT Item_ID, Item_Name, Item_Total FROM item ORDER BY Item_Name");
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "ดึงข้อมูลพัสดุไม่สำเร็จ";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $buy_date = trim($_POST['buy_date']);
    $buy_time = trim($_POST['buy_time']);
    $item_id = trim($_POST['item_id']);
    $buy_total = (int)$_POST['buy_total'];
    $officer = trim($_POST['officer']);
    
    $current_month = date('Y-m');
    $selected_month = date('Y-m', strtotime($buy_date));
    if ($selected_month < $current_month) {
        $error = "เลือกได้แค่วันที่ในเดือนนี้เท่านั้น " . date('F Y');
    }
    
    $buy_datetime = $buy_date . ' ' . $buy_time;
    
    if (empty($buy_date) || empty($buy_time) || empty($item_id) || $buy_total <= 0 || empty($officer)) {
        $error = "กรอกข้อมูลให้ครบนะ";
    } elseif (!empty($error)) {
    } else {
        try {
            $conn->beginTransaction();
            
            $insert_stmt = $conn->prepare("INSERT INTO buy (Buy_Date, Item_ID, Buy_Total, Officer) VALUES (?, ?, ?, ?)");
            $insert_stmt->execute([$buy_datetime, $item_id, $buy_total, $officer]);
            $new_id = $conn->lastInsertId();
            
            $update_stmt = $conn->prepare("UPDATE item SET Item_Total = Item_Total + ? WHERE Item_ID = ?");
            $update_stmt->execute([$buy_total, $item_id]);
            
            $conn->commit();
            $_SESSION['highlight_buy'] = $new_id;
            header("Location: buy.php?success=1");
            exit();
        } catch(PDOException $e) {
            $conn->rollBack();
            $error = "บันทึกข้อมูลไม่สำเร็จ";
        }
    }
}

$officer_name = isset($_SESSION['display_name']) ? $_SESSION['display_name'] : 
                (isset($_SESSION['username']) ? $_SESSION['username'] : '');
$current_time = date('H:i');

require_once 'header.php';
?>

<div class="table-container">
    <h2 class="table-title">เพิ่มรายการซื้อ</h2>
    
    <?php if (!empty($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="add_buy.php" id="addBuyForm">
    <div class="add-form">
        <div class="form-row">
            <div class="form-field">
                <label for="buy_date">วันที่ซื้อ *</label>
                <input type="date" id="buy_date" name="buy_date" required
                       value="<?php echo isset($_POST['buy_date']) ? htmlspecialchars($_POST['buy_date']) : date('Y-m-d'); ?>"
                       min="<?php echo date('Y-m-01'); ?>"
                       max="<?php echo date('Y-m-t'); ?>">
            </div>
            <div class="form-field">
                <label for="buy_time">เวลาซื้อ *</label>
                <input type="time" id="buy_time" name="buy_time" required
                       value="<?php echo isset($_POST['buy_time']) ? htmlspecialchars($_POST['buy_time']) : date('H:i'); ?>">
            </div>
            <div class="form-field">
                <label for="officer">เจ้าหน้าที่ *</label>
                <input type="text" id="officer" name="officer" required
                       value="<?php echo isset($_POST['officer']) ? htmlspecialchars($_POST['officer']) : htmlspecialchars($officer_name); ?>"
                       placeholder="ชื่อเจ้าหน้าที่" readonly
                       class="officer-input">
            </div>
        </div>
        <div class="form-row">
            <div class="form-field">
                <label>พัสดุ *</label>
                <div class="enhanced-dropdown">
    <div class="dropdown-header" tabindex="0">
        <span class="selected-text">
            <?php 
                if (isset($_POST['item_id'])) {
                    foreach ($items as $item) {
                        if ($item['Item_ID'] == $_POST['item_id']) {
                            echo htmlspecialchars($item['Item_Name']);
                            break;
                        }
                    }
                } else {
                    echo '-- เลือกพัสดุ --';
                }
            ?>
        </span>
        <span class="dropdown-arrow">▼</span>
    </div>
    <input type="hidden" id="item_id" name="item_id" class="item-id" value="<?php echo isset($_POST['item_id']) ? htmlspecialchars($_POST['item_id']) : ''; ?>">
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
                <label for="buy_total">จำนวน *</label>
                <input type="text" id="buy_total" name="buy_total" class="quantity-input" value="-" readonly style="text-align: center; background-color: #f8f9fa; color: #6c757d;">
            </div>
        </div>
    </div>
    
    <div class="form-buttons">
        <button type="submit" class="add-form-btn">บันทึก</button>
        <a href="buy.php" class="cancel-btn">ยกเลิก</a>
    </div>
</form>
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
        const stockInfo = dropdown.closest('.form-field').querySelector('.stock-info');
        const stockAmount = stockInfo.querySelector('.stock-amount');
        const quantityInput = document.getElementById('buy_total');
        
        let isOpen = false;
        
        function calculateDropdownPosition() {
            const dropdownRect = dropdown.getBoundingClientRect();
            const windowHeight = window.innerHeight;
            const dropdownHeight = 300;
            
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
                
                options.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                
                toggleDropdown();
                
                if (itemStock > 0) {
                    quantityInput.type = 'number';
                    quantityInput.min = 1;
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
    }
    
    setupEnhancedDropdown();
    
    document.getElementById('addBuyForm').addEventListener('submit', function(e) {
        const hiddenInput = document.querySelector('.item-id');
        const quantityInput = document.getElementById('buy_total');
        
        if (!hiddenInput.value) {
            e.preventDefault();
            alert('กรุณาเลือกพัสดุก่อน');
            return;
        }
        
        if (quantityInput.value === '-' || quantityInput.value === '0 (หมด)') {
            e.preventDefault();
            alert('กรุณากรอกจำนวนให้ถูกต้อง');
            return;
        }
        
        const quantityValue = parseInt(quantityInput.value);
        if (quantityValue <= 0) {
            e.preventDefault();
            alert('กรุณากรอกจำนวนให้ถูกต้อง');
            return;
        }
    });
});
</script>

<style>
.table-container {
    background-color: #FFFFFF;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.15);
    width: 100%;
    max-width: 1600px;
    margin: 30px auto;
    overflow: visible !important;
}

.add-form {
    overflow: visible !important;
    background-color: #FFFFFF;
    padding: 30px;
    border-radius: 8px;
    margin-bottom: 20px;
    border: 2px solid #DDDDDD;
    text-align: center;
    max-width: 1400px;
    margin-left: auto;
    margin-right: auto;
}

.enhanced-dropdown {
    position: relative;
    width: 100%;
    z-index: 1000;
}

.enhanced-dropdown .dropdown-header {
    width: 100%;
    padding: 15px 50px 15px 18px;
    border: 2px solid #ddd;
    border-radius: 6px;
    font-size: 16px;
    cursor: pointer;
    background-color: white;
    color: #000000;
    text-align: left;
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 50px;
    position: relative;
    transition: all 0.3s;
}

.enhanced-dropdown .dropdown-header:hover {
    border-color: #9ECAD6;
    box-shadow: 0 0 0 3px rgba(158, 202, 214, 0.3);
}

.enhanced-dropdown .dropdown-header:focus {
    border-color: #9ECAD6;
    outline: none;
    box-shadow: 0 0 0 3px rgba(158, 202, 214, 0.3);
}

.enhanced-dropdown .dropdown-header .selected-text {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    padding-right: 15px;
    font-weight: 600;
    font-size: 16px;
}

.enhanced-dropdown .dropdown-header .dropdown-arrow {
    position: absolute;
    right: 18px;
    top: 50%;
    transform: translateY(-50%);
    color: #666;
    font-size: 12px;
    pointer-events: none;
}

.enhanced-dropdown .dropdown-content {
    position: absolute;
    top: 100%;
    left: 0;
    width: 100%;
    background: white;
    border: 2px solid #9ECAD6;
    border-radius: 6px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    z-index: 2000;
    max-height: 300px;
    overflow: hidden;
    display: none;
    margin-top: 5px;
}

.enhanced-dropdown .dropdown-content.show {
    display: block;
}

.enhanced-dropdown .search-box {
    padding: 15px;
    border-bottom: 2px solid #eee;
    background-color: #f9f9f9;
    position: sticky;
    top: 0;
    z-index: 10;
}

.enhanced-dropdown .dropdown-search {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #ddd;
    border-radius: 6px;
    font-size: 16px;
    background-color: white;
    color: #000000;
}

.enhanced-dropdown .dropdown-search:focus {
    border-color: #9ECAD6;
    outline: none;
    box-shadow: 0 0 0 3px rgba(158, 202, 214, 0.3);
}

.enhanced-dropdown .options-list {
    max-height: 250px;
    overflow-y: auto;
    padding: 8px 0;
}

.enhanced-dropdown .option-item {
    padding: 8px 12px;
    border-bottom: 2px solid #eee;
    cursor: pointer;
    transition: all 0.3s;
    text-align: left;
    display: flex;
    flex-direction: column;
    gap: 5px;
    min-height: 48px;
    justify-content: center;
}

.enhanced-dropdown .option-item:hover {
    background-color: #f0f8ff;
}

.enhanced-dropdown .option-item.selected {
    background-color: #e6f3ff;
    border-left: 4px solid #9ECAD6;
}

.enhanced-dropdown .option-name {
    font-weight: 600;
    color: #333;
    font-size: 16px;
}

.enhanced-dropdown .option-details {
    font-size: 12px;
    color: #666;
    display: flex;
    justify-content: space-between;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 2px;
}

.enhanced-dropdown .option-id {
    color: #0066cc;
    font-weight: 500;
}

.enhanced-dropdown .option-stock {
    color: #28a745;
    font-weight: 500;
}

.enhanced-dropdown .no-results {
    padding: 25px;
    text-align: center;
    color: #666;
    font-style: italic;
    font-size: 16px;
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

.stock-info {
    font-size: 14px;
    color: #666;
    margin-top: 8px;
    padding: 10px 15px;
    background-color: #f8f9fa;
    border-radius: 6px;
    border-left: 4px solid #9ECAD6;
    display: none;
    font-weight: 600;
}

.stock-info.show {
    display: block;
}

.stock-info .stock-label {
    font-weight: 700;
    color: #333;
    font-size: 15px;
}

.stock-info .stock-amount {
    font-weight: 700;
    color: #28a745;
    font-size: 16px;
}

.add-form .form-row {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
}

.add-form .form-field {
    flex: 1;
    min-width: 0;
}

.add-form .form-row:first-child .form-field {
    flex: 1;
}

.add-form .form-row:last-child .form-field:nth-child(1) {
    flex: 1.5;
}

.add-form .form-row:last-child .form-field:nth-child(2) {
    flex: 0.5;
}
</style>

<?php require_once 'footer.php'; ?>