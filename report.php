<?php
require_once 'config.php';

//ตรวจสอบการล็อกอินและไม่สามารถลักไก่เพื่อเข้าหน้านี้โดยตรงได้ เช่น การพิมพ์ชื่อ url แต่ละหน้า
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

$error = '';
$report_type = isset($_GET['type']) ? $_GET['type'] : 'department';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$department_type = isset($_GET['department_type']) ? $_GET['department_type'] : 'all';
$reports = [];
$department_summary = [];
$has_report_data = false;

//ข้อมูลฝ่ายและสาขา อิงตามใบรายงานจากห้องธุรการของครูโบว์
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

if (isset($_GET['generate'])) {
    $has_report_data = true;
    try {
        $where_conditions = [];
        $params = [];
        
        //วันที่ที่ต้องการออกรายงานระหว่างวันนี้จนถึงวันนั้น
        $where_conditions[] = "DATE(r.Req_Date) BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
        
        //ดอปดาวเลือก ฝ่าย/สาขา
        if ($department_type != 'all') {
            $where_conditions[] = "r.Department = ?";
            $params[] = $department_type;
        }
        
        $where_sql = implode(' AND ', $where_conditions);
        
        //บอกเป็นลิสรายการว่าสาขานี้เบิกอะไรไปบ้าง เช่น ดินสอ กระดาษ
        $stmt = $conn->prepare("
            SELECT r.Req_ID, r.Req_Name, r.Req_Date, r.Department, 
                   i.Item_Name, i.Unit, r.Req_Total, r.Officer
            FROM request r 
            LEFT JOIN item i ON r.Item_ID = i.Item_ID 
            WHERE $where_sql
            ORDER BY 
                CASE 
                    WHEN r.Department IN ('แผนและควบคุมคุณภาพ','สำนักงานบริการ','ประชาสัมพันธ์และชุมชน',
                         'พัฒนากิจการนักเรียนนักศึกษา','งบประมาณและการเงิน','พัฒนาวิชาการ','ทรัพยากรบุคคล') 
                    THEN 1 
                    ELSE 2 
                END,
                FIELD(r.Department,
                    'แผนและควบคุมคุณภาพ','สำนักงานบริการ','ประชาสัมพันธ์และชุมชน',
                    'พัฒนากิจการนักเรียนนักศึกษา','งบประมาณและการเงิน','พัฒนาวิชาการ','ทรัพยากรบุคคล',
                    'เทคโนโลยีธุรกิจดิจิทัล','ดิจิทัลกราฟิก','การจัดการ','การบัญชี',
                    'การตลาด','สามัญ','การท่องเที่ยว','ภาษาต่างประเทศธุรกิจบริการ'
                ),
                r.Req_Date DESC, 
                r.Req_ID DESC
        ");
        $stmt->execute($params);
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        //รายงานของราบการเบิกยอดรวมของ สาขา/ฝ่าย สาขานี้มียอดรวมเท่านี้ๆๆ
        $summary_stmt = $conn->prepare("
            SELECT 
                r.Department,
                CASE 
                    WHEN r.Department IN ('แผนและควบคุมคุณภาพ','สำนักงานบริการ','ประชาสัมพันธ์และชุมชน',
                         'พัฒนากิจการนักเรียนนักศึกษา','งบประมาณและการเงิน','พัฒนาวิชาการ','ทรัพยากรบุคคล') 
                    THEN 'ฝ่าย' 
                    ELSE 'สาขา' 
                END as type,
                COUNT(*) as total_requests,
                SUM(r.Req_Total) as total_items
            FROM request r 
            WHERE $where_sql
            GROUP BY r.Department
            HAVING COUNT(*) > 0
            ORDER BY 
                CASE 
                    WHEN r.Department IN ('แผนและควบคุมคุณภาพ','สำนักงานบริการ','ประชาสัมพันธ์และชุมชน',
                         'พัฒนากิจการนักเรียนนักศึกษา','งบประมาณและการเงิน','พัฒนาวิชาการ','ทรัพยากรบุคคล') 
                    THEN 1 
                    ELSE 2 
                END,
                FIELD(r.Department,
                    'แผนและควบคุมคุณภาพ','สำนักงานบริการ','ประชาสัมพันธ์และชุมชน',
                    'พัฒนากิจการนักเรียนนักศึกษา','งบประมาณและการเงิน','พัฒนาวิชาการ','ทรัพยากรบุคคล',
                    'เทคโนโลยีธุรกิจดิจิทัล','ดิจิทัลกราฟิก','การจัดการ','การบัญชี',
                    'การตลาด','สามัญ','การท่องเที่ยว','ภาษาต่างประเทศธุรกิจบริการ'
                )
        ");
        $summary_stmt->execute($params);
        $department_summary = $summary_stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        $error = "ดึงข้อมูลรายงานไม่สำเร็จ: " . $e->getMessage();
    }
}

require_once 'header.php';
?>

<div class="table-container">
    <h2 class="table-title">รายงาน</h2>
    
    <?php if (!empty($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="add-form">
        <form method="GET" action="report.php" id="reportForm">
            <input type="hidden" name="generate" value="1">
            
            <div class="form-row">
                <div class="form-field" id="specificDepartmentField">
                    <label for="department_type">เลือกฝ่าย/สาขา</label>
                    <select id="department_type" name="department_type" class="dropdown-select">
                        <option value="all" <?php echo ($department_type == 'all') ? 'selected' : ''; ?>>ทั้งหมด</option>
                        <?php if ($report_type == 'ฝ่าย'): ?>
                            <optgroup label="ฝ่าย">
                                <?php foreach ($departments['ฝ่าย'] as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo ($department_type == $dept) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php elseif ($report_type == 'สาขา'): ?>
                            <optgroup label="สาขา">
                                <?php foreach ($departments['สาขา'] as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo ($department_type == $dept) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php else: ?>
                            <optgroup label="ฝ่าย">
                                <?php foreach ($departments['ฝ่าย'] as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo ($department_type == $dept) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="สาขา">
                                <?php foreach ($departments['สาขา'] as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo ($department_type == $dept) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-field">
                    <label for="start_date">วันที่เริ่มต้น</label>
                    <input type="date" id="start_date" name="start_date" 
                           value="<?php echo htmlspecialchars($start_date); ?>" class="dropdown-select">
                </div>
                <div class="form-field">
                    <label for="end_date">วันที่สิ้นสุด</label>
                    <input type="date" id="end_date" name="end_date" 
                           value="<?php echo htmlspecialchars($end_date); ?>" class="dropdown-select">
                </div>
            </div>
        </form>
        
        <div class="form-buttons">
            <button type="submit" form="reportForm" class="add-form-btn">สร้างรายงาน</button>
            <a href="dashboard.php" class="cancel-btn">กลับเมนู</a>
        </div>
    </div>
    
    <?php if ($has_report_data): ?>
        <?php if (count($department_summary) > 0): ?>
            <div class="report-actions">
                <p class="report-period">ข้อมูลระหว่างวันที่ <?php echo htmlspecialchars($start_date); ?> ถึง <?php echo htmlspecialchars($end_date); ?></p>
                <?php if ($department_type != 'all'): ?>
                    <p class="report-department">ฝ่าย/สาขา: <?php echo htmlspecialchars($department_type); ?></p>
                <?php endif; ?>
            </div>
            
            
            <div class="report-section">
                <h3 class="report-title">สรุปผลการเบิก - จ่าย พัสดุสำนักงาน</h3>
                <?php 
                $thai_months = [
                    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
                    5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
                    9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
                ];
                $month_num = date('n', strtotime($start_date));
                $thai_month = $thai_months[$month_num];
                $thai_year = date('Y', strtotime($start_date)) + 543;
                ?>
                <p class="report-subtitle">ประจำเดือน <?php echo $thai_month . ' ' . $thai_year; ?></p>
                
                <?php 
                //แยกข้อมูลฝ่ายและสาขาจากฐานข้อมูล
                $ฝ่าย_summary_db = array_filter($department_summary, function($item) {
                    return $item['type'] == 'ฝ่าย';
                });
                $สาขา_summary_db = array_filter($department_summary, function($item) {
                    return $item['type'] == 'สาขา';
                });
                
                //เรียงฝ่ายในดอปดาว เอามาจากใบรายงานของครู
                $ฝ่าย_summary = [];
                foreach ($departments['ฝ่าย'] as $dept) {
                    foreach ($ฝ่าย_summary_db as $summary) {
                        if ($summary['Department'] == $dept) {
                            $ฝ่าย_summary[] = $summary;
                            break;
                        }
                    }
                }
                
                //เรียงสาขาในดอปดาว เอามาจากใบรายงานของครู
                $สาขา_summary = [];
                foreach ($departments['สาขา'] as $dept) {
                    foreach ($สาขา_summary_db as $summary) {
                        if ($summary['Department'] == $dept) {
                            $สาขา_summary[] = $summary;
                            break;
                        }
                    }
                }
                
                $total_requests = 0;
                $total_items = 0;
                ?>
                
                
                <?php if (count($ฝ่าย_summary) > 0): ?>
                    <h4 class="section-title">ฝ่าย</h4>
                    <table class="data-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 15%; text-align: center; background-color: #F5BABB;">ลำดับที่</th>
                                <th style="width: 45%; text-align: center; background-color: #F5BABB;">ฝ่าย</th>
                                <th style="width: 20%; text-align: center; background-color: #F5BABB;">จำนวนรายการเบิก (รายการ)</th>
                                <th style="width: 20%; text-align: center; background-color: #F5BABB;">จำนวนพัสดุที่เบิก (จำนวน)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $index = 1; ?>
                            <?php foreach ($ฝ่าย_summary as $summary): ?>
                                <?php 
                                $total_requests += $summary['total_requests'];
                                $total_items += $summary['total_items'];
                                ?>
                                <tr>
                                    <td style="text-align: center;"><?php echo $index++; ?></td>
                                    <td style="text-align: center;"><?php echo htmlspecialchars($summary['Department']); ?></td>
                                    <td style="text-align: center;"><?php echo number_format($summary['total_requests']); ?></td>
                                    <td style="text-align: center;"><?php echo number_format($summary['total_items']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                
                
                <?php if (count($สาขา_summary) > 0): ?>
                    <h4 class="section-title">สาขา</h4>
                    <table class="data-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 15%; text-align: center; background-color: #F5BABB;">ลำดับที่</th>
                                <th style="width: 45%; text-align: center; background-color: #F5BABB;">สาขา</th>
                                <th style="width: 20%; text-align: center; background-color: #F5BABB;">จำนวนรายการเบิก (รายการ)</th>
                                <th style="width: 20%; text-align: center; background-color: #F5BABB;">จำนวนพัสดุที่เบิก (จำนวน)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $index = 1; ?>
                            <?php foreach ($สาขา_summary as $summary): ?>
                                <?php 
                                $total_requests += $summary['total_requests'];
                                $total_items += $summary['total_items'];
                                ?>
                                <tr>
                                    <td style="text-align: center;"><?php echo $index++; ?></td>
                                    <td style="text-align: center;"><?php echo htmlspecialchars($summary['Department']); ?></td>
                                    <td style="text-align: center;"><?php echo number_format($summary['total_requests']); ?></td>
                                    <td style="text-align: center;"><?php echo number_format($summary['total_items']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                
                
                <?php if ($department_type == 'all' && ($total_requests > 0 || $total_items > 0)): ?>
                <div class="total-summary">
                    <table class="data-table" style="width: 100%; margin-top: 20px;">
                        <tr>
                            <td colspan="2" style="text-align: center; font-weight: bold; font-size: 1.1em;">
                                รวมพัสดุสำนักงานที่เบิกใช้ไปทั้งหมด
                            </td>
                            <td style="width: 20%; text-align: center; font-weight: bold;"><?php echo number_format($total_requests); ?></td>
                            <td style="width: 20%; text-align: center; font-weight: bold;"><?php echo number_format($total_items); ?></td>
                        </tr>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            
            
            <div class="report-section">
                <h3 class="report-title">รายละเอียดการเบิกพัสดุแยกตามฝ่าย/สาขา</h3>
                
                <?php 
                //แยกข้อมูลรายการตามฝ่าย/สาขา
                $department_items = [];
                foreach ($reports as $report) {
                    $dept = $report['Department'];
                    if (!isset($department_items[$dept])) {
                        $department_items[$dept] = [];
                    }
                    $department_items[$dept][] = $report;
                }
                
                $dept_index = 1;
                
                //แสดงชื่อฝ่ายทั้งหมด
                if ($department_type == 'all' || in_array($department_type, $departments['ฝ่าย'])): 
                    $has_ฝ่าย_data = false;
                    foreach ($departments['ฝ่าย'] as $dept):
                        if ($department_type != 'all' && $department_type != $dept) continue;
                        
                        //เช็คว่าฝ่ายนี้มีข้อมูลการเบิกมั้ย
                        $has_data_for_dept = isset($department_items[$dept]) && count($department_items[$dept]) > 0;
                        
                        if ($has_data_for_dept):
                            $has_ฝ่าย_data = true;
                            $department_total = 0;
                            foreach ($department_items[$dept] as $item) {
                                $department_total += $item['Req_Total'];
                            }
                            
                            //เรียงลำดับข้อมูลตามชื่อพัสดุ
                            usort($department_items[$dept], function($a, $b) {
                                return $a['Item_Name'] <=> $b['Item_Name'];
                            });
                ?>
                        <div class="department-details">
                            <h5 class="department-title"><?php echo $dept_index++; ?>. <?php echo htmlspecialchars($dept); ?></h5>
                            
                            <table class="data-table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th style="width: 15%; text-align: center; background-color: #F5BABB;">ลำดับที่</th>
                                        <th style="width: 45%; text-align: center; background-color: #F5BABB;">รายการเบิกพัสดุ</th>
                                        <th style="width: 20%; text-align: center; background-color: #F5BABB;">จำนวน</th>
                                        <th style="width: 20%; text-align: center; background-color: #F5BABB;">หน่วย</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $item_index = 1; ?>
                                    <?php foreach ($department_items[$dept] as $item): ?>
                                        <tr>
                                            <td style="text-align: center;"><?php echo $item_index++; ?></td>
                                            <td style="text-align: center;"><?php echo htmlspecialchars($item['Item_Name']); ?></td>
                                            <td style="text-align: center;"><?php echo number_format($item['Req_Total']); ?></td>
                                            <td style="text-align: center;"><?php echo htmlspecialchars($item['Unit']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <tr style="background-color: #e8f5e9;">
                                        <td colspan="2" style="text-align: center; font-weight: bold;">รวม</td>
                                        <td style="text-align: center; font-weight: bold;"><?php echo number_format($department_total); ?></td>
                                        <td style="text-align: center; font-weight: bold;">รายการ</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                <?php 
                        endif;
                    endforeach;
                    
                    if (!$has_ฝ่าย_data && $department_type != 'all'): ?>
                        <div class="no-data-message">
                            <p>ไม่มีข้อมูลรายการเบิกสำหรับฝ่ายนี้</p>
                        </div>
                <?php endif; ?>
                <?php endif; ?>
                
                
                <?php if ($department_type == 'all' || in_array($department_type, $departments['สาขา'])): 
                    $has_สาขา_data = false;
                    foreach ($departments['สาขา'] as $dept):
                        if ($department_type != 'all' && $department_type != $dept) continue;
                        
                        //เช็คว่าสาขานี้มีข้อมูลการเบิกมั้ย
                        $has_data_for_dept = isset($department_items[$dept]) && count($department_items[$dept]) > 0;
                        
                        if ($has_data_for_dept):
                            $has_สาขา_data = true;
                            $department_total = 0;
                            foreach ($department_items[$dept] as $item) {
                                $department_total += $item['Req_Total'];
                            }
                            
                            //เรียงลำดับข้อมูลตามชื่อพัสดุ
                            usort($department_items[$dept], function($a, $b) {
                                return $a['Item_Name'] <=> $b['Item_Name'];
                            });
                ?>
                        <div class="department-details">
                            <h5 class="department-title"><?php echo $dept_index++; ?>. <?php echo htmlspecialchars($dept); ?></h5>
                            
                            <table class="data-table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th style="width: 15%; text-align: center; background-color: #F5BABB;">ลำดับที่</th>
                                        <th style="width: 45%; text-align: center; background-color: #F5BABB;">รายการเบิกพัสดุ</th>
                                        <th style="width: 20%; text-align: center; background-color: #F5BABB;">จำนวน</th>
                                        <th style="width: 20%; text-align: center; background-color: #F5BABB;">หน่วย</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $item_index = 1; ?>
                                    <?php foreach ($department_items[$dept] as $item): ?>
                                        <tr>
                                            <td style="text-align: center;"><?php echo $item_index++; ?></td>
                                            <td style="text-align: center;"><?php echo htmlspecialchars($item['Item_Name']); ?></td>
                                            <td style="text-align: center;"><?php echo number_format($item['Req_Total']); ?></td>
                                            <td style="text-align: center;"><?php echo htmlspecialchars($item['Unit']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr style="background-color: #e8f5e9;">
                                        <td colspan="2" style="text-align: center; font-weight: bold;">รวม</td>
                                        <td style="text-align: center; font-weight: bold;"><?php echo number_format($department_total); ?></td>
                                        <td style="text-align: center; font-weight: bold;">รายการ</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                <?php 
                        endif;
                    endforeach;
                    
                    if (!$has_สาขา_data && $department_type != 'all'): ?>
                        <div class="no-data-message">
                            <p>ไม่มีข้อมูลรายการเบิกสำหรับสาขานี้</p>
                        </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            
        <?php else: ?>
            <div class="no-data-message">
                <p>ไม่มีข้อมูลรายงานสำหรับเงื่อนไขที่เลือก</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>