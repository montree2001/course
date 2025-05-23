<?php
// admin/requests/pdf_position_editor.php
// หน้าสำหรับปรับตำแหน่งเอกสาร PDF

session_start();
require_once '../../config/database.php';
require_once '../../config/functions.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$pageTitle = 'ปรับตำแหน่งเอกสาร PDF';
$currentPage = 'requests';

// ตรวจสอบการบันทึกค่าตำแหน่ง
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_positions'])) {
    $positions = $_POST['positions'] ?? [];
    
    // บันทึกค่าตำแหน่งลงไฟล์ JSON
    $positions_file = '../../config/pdf_positions.json';
    file_put_contents($positions_file, json_encode($positions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    $success_message = 'บันทึกตำแหน่งเรียบร้อยแล้ว';
}

// โหลดค่าตำแหน่งที่บันทึกไว้
$positions_file = '../../config/pdf_positions.json';
$saved_positions = [];
if (file_exists($positions_file)) {
    $saved_positions = json_decode(file_get_contents($positions_file), true) ?? [];
}

// ค่าเริ่มต้นของตำแหน่ง
$default_positions = [
    'document_number' => ['x' => 40, 'y' => 45],
    'document_date_day' => ['x' => 160, 'y' => 45],
    'document_date_month' => ['x' => 185, 'y' => 45],
    'document_date_year' => ['x' => 220, 'y' => 45],
    'student_name' => ['x' => 85, 'y' => 85],
    'student_code' => ['x' => 170, 'y' => 85],
    'student_year' => ['x' => 90, 'y' => 95],
    'student_major' => ['x' => 140, 'y' => 95],
    'student_phone' => ['x' => 80, 'y' => 105],
    'courses_table_start_y' => ['x' => 0, 'y' => 130],
    'signature_student' => ['x' => 150, 'y' => 200],
    'approval_checkbox_yes' => ['x' => 40, 'y' => 250],
    'approval_checkbox_no' => ['x' => 40, 'y' => 265],
    'rejection_reason' => ['x' => 120, 'y' => 265]
];

$positions = array_merge($default_positions, $saved_positions);

// ดึงข้อมูลตัวอย่างสำหรับแสดงผล
try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // ดึงคำร้องล่าสุดสำหรับตัวอย่าง
    $stmt = $pdo->query("
        SELECT cr.*, 
               us.student_code, us.name_prefix, us.first_name, us.last_name, 
               us.education_level, us.year, us.major, us.phone_number,
               us.full_name
        FROM course_requests cr
        JOIN unified_students us ON cr.student_id = us.id
        ORDER BY cr.created_at DESC
        LIMIT 1
    ");
    $sample_request = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $sample_request = null;
}

require_once '../includes/header.php';
?>

<style>
    .pdf-preview {
        border: 2px solid #ddd;
        background: white;
        position: relative;
        width: 595px; /* A4 width in pixels */
        height: 842px; /* A4 height in pixels */
        margin: 20px auto;
        transform: scale(0.8);
        transform-origin: top center;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    .draggable-element {
        position: absolute;
        border: 2px dashed #007bff;
        background: rgba(0, 123, 255, 0.1);
        padding: 4px 8px;
        cursor: move;
        min-width: 100px;
        min-height: 20px;
        font-size: 12px;
        color: #007bff;
        font-weight: bold;
        border-radius: 4px;
    }
    
    .draggable-element:hover {
        background: rgba(0, 123, 255, 0.2);
        border-color: #0056b3;
    }
    
    .draggable-element.selected {
        border-color: #dc3545;
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }
    
    .position-controls {
        position: fixed;
        right: 20px;
        top: 100px;
        width: 300px;
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        max-height: calc(100vh - 120px);
        overflow-y: auto;
    }
    
    .position-item {
        margin-bottom: 15px;
        padding: 10px;
        border: 1px solid #eee;
        border-radius: 4px;
    }
    
    .position-item.active {
        border-color: #007bff;
        background: rgba(0, 123, 255, 0.05);
    }
    
    .position-item label {
        font-weight: bold;
        color: #333;
        margin-bottom: 5px;
        display: block;
    }
    
    .coordinate-inputs {
        display: flex;
        gap: 10px;
    }
    
    .coordinate-inputs input {
        width: 60px;
        text-align: center;
    }
    
    .preview-toolbar {
        text-align: center;
        margin-bottom: 20px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .pdf-background {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: 
            linear-gradient(to right, #f0f0f0 1px, transparent 1px),
            linear-gradient(to bottom, #f0f0f0 1px, transparent 1px);
        background-size: 20px 20px;
        opacity: 0.3;
    }
    
    .form-header {
        position: absolute;
        top: 20px;
        left: 0;
        right: 0;
        text-align: center;
        font-size: 18px;
        font-weight: bold;
        color: #333;
    }
    
    .static-content {
        position: absolute;
        color: #666;
        font-size: 11px;
        pointer-events: none;
    }
</style>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../dashboard.php">แดชบอร์ด</a></li>
        <li class="breadcrumb-item"><a href="index.php">จัดการคำร้องขอเปิดรายวิชา</a></li>
        <li class="breadcrumb-item active" aria-current="page">ปรับตำแหน่งเอกสาร PDF</li>
    </ol>
</nav>

<!-- Page Heading -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">ปรับตำแหน่งเอกสาร PDF</h1>
    <div>
        <button type="button" class="btn btn-info btn-sm" onclick="resetPositions()">
            <i class="bi bi-arrow-clockwise"></i> รีเซ็ตตำแหน่ง
        </button>
        <a href="index.php" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> กลับ
        </a>
    </div>
</div>

<!-- Alert -->
<?php if (isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Instructions -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold text-primary">คำแนะนำการใช้งาน</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>การลากและวาง:</h6>
                <ul class="mb-0">
                    <li>คลิกและลากองค์ประกอบในตัวอย่าง PDF เพื่อปรับตำแหน่ง</li>
                    <li>ใช้ช่องควบคุมด้านขวาเพื่อปรับตำแหน่งแบบละเอียด</li>
                    <li>คลิกที่องค์ประกอบเพื่อเลือกและแสดงช่องควบคุม</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6>การบันทึก:</h6>
                <ul class="mb-0">
                    <li>คลิก "บันทึกตำแหน่ง" เพื่อบันทึกการเปลี่ยนแปลง</li>
                    <li>ตำแหน่งจะถูกใช้ในการพิมพ์บันทึกราชการ</li>
                    <li>ทดสอบโดยพิมพ์เอกสารจริงหลังจากปรับตำแหน่งเสร็จ</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="preview-toolbar">
    <button type="button" class="btn btn-primary" onclick="savePositions()">
        <i class="bi bi-save"></i> บันทึกตำแหน่ง
    </button>
    <?php if ($sample_request): ?>
    <a href="print.php?id=<?php echo $sample_request['id']; ?>" class="btn btn-info" target="_blank">
        <i class="bi bi-printer"></i> ทดสอบพิมพ์
    </a>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-lg-9">
        <div class="pdf-preview" id="pdfPreview">
            <div class="pdf-background"></div>
            
            <!-- Static Content -->
            <div class="form-header">บันทึกข้อความ - คำร้องขอเปิดรายวิชาพิเศษ</div>
            
            <div class="static-content" style="top: 70px; left: 20px;">ที่: ศธ ๐๕๐๙.๓/</div>
            <div class="static-content" style="top: 70px; left: 300px;">วันที่:</div>
            <div class="static-content" style="top: 90px; left: 20px;">เรื่อง: ขออนุญาตเปิดรายวิชาพิเศษ</div>
            <div class="static-content" style="top: 110px; left: 20px;">เรียน: ผู้อำนวยการวิทยาลัยการอาชีพ</div>
            
            <div class="static-content" style="top: 140px; left: 20px;">ชื่อ-นามสกุล:</div>
            <div class="static-content" style="top: 140px; left: 300px;">รหัสนักเรียน:</div>
            <div class="static-content" style="top: 160px; left: 20px;">ชั้นปี:</div>
            <div class="static-content" style="top: 160px; left: 200px;">สาขาวิชา:</div>
            <div class="static-content" style="top: 180px; left: 20px;">เบอร์โทรศัพท์:</div>
            
            <div class="static-content" style="top: 220px; left: 20px;">รายวิชาที่ขอเปิด (ตารางจะแสดงที่นี่)</div>
            
            <div class="static-content" style="top: 400px; left: 20px;">ผลการพิจารณา:</div>
            <div class="static-content" style="top: 420px; left: 60px;">อนุมัติให้เปิดรายวิชาพิเศษตามที่ขอ</div>
            <div class="static-content" style="top: 440px; left: 60px;">ไม่อนุมัติ เนื่องจาก</div>
            
            <!-- Draggable Elements -->
            <div class="draggable-element" id="document_number" 
                 style="left: <?php echo $positions['document_number']['x']; ?>px; top: <?php echo $positions['document_number']['y']; ?>px;">
                เลขที่เอกสาร
            </div>
            
            <div class="draggable-element" id="document_date_day" 
                 style="left: <?php echo $positions['document_date_day']['x']; ?>px; top: <?php echo $positions['document_date_day']['y']; ?>px;">
                วัน
            </div>
            
            <div class="draggable-element" id="document_date_month" 
                 style="left: <?php echo $positions['document_date_month']['x']; ?>px; top: <?php echo $positions['document_date_month']['y']; ?>px;">
                เดือน
            </div>
            
            <div class="draggable-element" id="document_date_year" 
                 style="left: <?php echo $positions['document_date_year']['x']; ?>px; top: <?php echo $positions['document_date_year']['y']; ?>px;">
                ปี
            </div>
            
            <div class="draggable-element" id="student_name" 
                 style="left: <?php echo $positions['student_name']['x']; ?>px; top: <?php echo $positions['student_name']['y']; ?>px;">
                <?php echo $sample_request ? $sample_request['full_name'] : 'ชื่อ-นามสกุล'; ?>
            </div>
            
            <div class="draggable-element" id="student_code" 
                 style="left: <?php echo $positions['student_code']['x']; ?>px; top: <?php echo $positions['student_code']['y']; ?>px;">
                <?php echo $sample_request ? $sample_request['student_code'] : 'รหัสนักเรียน'; ?>
            </div>
            
            <div class="draggable-element" id="student_year" 
                 style="left: <?php echo $positions['student_year']['x']; ?>px; top: <?php echo $positions['student_year']['y']; ?>px;">
                <?php echo $sample_request ? $sample_request['year'] : 'ชั้นปี'; ?>
            </div>
            
            <div class="draggable-element" id="student_major" 
                 style="left: <?php echo $positions['student_major']['x']; ?>px; top: <?php echo $positions['student_major']['y']; ?>px;">
                <?php echo $sample_request ? $sample_request['major'] : 'สาขาวิชา'; ?>
            </div>
            
            <div class="draggable-element" id="student_phone" 
                 style="left: <?php echo $positions['student_phone']['x']; ?>px; top: <?php echo $positions['student_phone']['y']; ?>px;">
                <?php echo $sample_request ? ($sample_request['phone_number'] ?: 'เบอร์โทร') : 'เบอร์โทร'; ?>
            </div>
            
            <div class="draggable-element" id="signature_student" 
                 style="left: <?php echo $positions['signature_student']['x']; ?>px; top: <?php echo $positions['signature_student']['y']; ?>px;">
                ลายเซ็นนักเรียน
            </div>
            
            <div class="draggable-element" id="approval_checkbox_yes" 
                 style="left: <?php echo $positions['approval_checkbox_yes']['x']; ?>px; top: <?php echo $positions['approval_checkbox_yes']['y']; ?>px; width: 20px; height: 20px;">
                ✓
            </div>
            
            <div class="draggable-element" id="approval_checkbox_no" 
                 style="left: <?php echo $positions['approval_checkbox_no']['x']; ?>px; top: <?php echo $positions['approval_checkbox_no']['y']; ?>px; width: 20px; height: 20px;">
                ✗
            </div>
            
            <div class="draggable-element" id="rejection_reason" 
                 style="left: <?php echo $positions['rejection_reason']['x']; ?>px; top: <?php echo $positions['rejection_reason']['y']; ?>px;">
                เหตุผลไม่อนุมัติ
            </div>
        </div>
    </div>
    
    <div class="col-lg-3">
        <div class="position-controls">
            <h6 class="mb-3">ควบคุมตำแหน่ง</h6>
            <div id="selectedElement" class="mb-3">
                <em>คลิกที่องค์ประกอบเพื่อปรับตำแหน่ง</em>
            </div>
            
            <div id="positionInputs"></div>
        </div>
    </div>
</div>

<script>
    let selectedElement = null;
    let isDragging = false;
    let positions = <?php echo json_encode($positions); ?>;
    
    $(document).ready(function() {
        initializeDraggable();
        updatePositionInputs();
    });
    
    function initializeDraggable() {
        $('.draggable-element').each(function() {
            const element = this;
            let startX, startY, elementStartX, elementStartY;
            
            element.addEventListener('mousedown', function(e) {
                e.preventDefault();
                isDragging = true;
                
                selectElement(element);
                
                startX = e.clientX;
                startY = e.clientY;
                elementStartX = parseInt(element.style.left);
                elementStartY = parseInt(element.style.top);
                
                document.addEventListener('mousemove', dragElement);
                document.addEventListener('mouseup', stopDragging);
            });
            
            function dragElement(e) {
                if (!isDragging) return;
                
                const deltaX = e.clientX - startX;
                const deltaY = e.clientY - startY;
                
                const newX = elementStartX + deltaX;
                const newY = elementStartY + deltaY;
                
                // จำกัดไม่ให้ลากออกนอกพื้นที่
                const container = document.getElementById('pdfPreview');
                const containerRect = container.getBoundingClientRect();
                const elementWidth = element.offsetWidth;
                const elementHeight = element.offsetHeight;
                
                const clampedX = Math.max(0, Math.min(newX, 595 - elementWidth));
                const clampedY = Math.max(0, Math.min(newY, 842 - elementHeight));
                
                element.style.left = clampedX + 'px';
                element.style.top = clampedY + 'px';
                
                // อัปเดตตำแหน่งในอาเรย์
                positions[element.id] = { x: clampedX, y: clampedY };
                
                updatePositionInputs();
            }
            
            function stopDragging() {
                isDragging = false;
                document.removeEventListener('mousemove', dragElement);
                document.removeEventListener('mouseup', stopDragging);
            }
        });
    }
    
    function selectElement(element) {
        // ลบการเลือกจากองค์ประกอบอื่น
        $('.draggable-element').removeClass('selected');
        $('.position-item').removeClass('active');
        
        // เพิ่มการเลือกให้องค์ประกอบปัจจุบัน
        $(element).addClass('selected');
        selectedElement = element;
        
        updateSelectedElementInfo();
    }
    
    function updateSelectedElementInfo() {
        if (!selectedElement) return;
        
        const elementName = getElementDisplayName(selectedElement.id);
        const x = parseInt(selectedElement.style.left);
        const y = parseInt(selectedElement.style.top);
        
        $('#selectedElement').html(`
            <strong>เลือก:</strong> ${elementName}<br>
            <strong>ตำแหน่ง:</strong> X: ${x}, Y: ${y}
        `);
    }
    
    function updatePositionInputs() {
        let html = '';
        
        Object.keys(positions).forEach(key => {
            const displayName = getElementDisplayName(key);
            const pos = positions[key];
            const isSelected = selectedElement && selectedElement.id === key;
            
            html += `
                <div class="position-item ${isSelected ? 'active' : ''}" data-element="${key}">
                    <label>${displayName}</label>
                    <div class="coordinate-inputs">
                        <div>
                            <label class="small">X</label>
                            <input type="number" class="form-control form-control-sm" 
                                   value="${pos.x}" onchange="updatePosition('${key}', 'x', this.value)">
                        </div>
                        <div>
                            <label class="small">Y</label>
                            <input type="number" class="form-control form-control-sm" 
                                   value="${pos.y}" onchange="updatePosition('${key}', 'y', this.value)">
                        </div>
                    </div>
                </div>
            `;
        });
        
        $('#positionInputs').html(html);
    }
    
    function updatePosition(elementId, axis, value) {
        const element = document.getElementById(elementId);
        if (!element) return;
        
        const numValue = parseInt(value) || 0;
        positions[elementId][axis] = numValue;
        
        if (axis === 'x') {
            element.style.left = numValue + 'px';
        } else {
            element.style.top = numValue + 'px';
        }
        
        if (selectedElement && selectedElement.id === elementId) {
            updateSelectedElementInfo();
        }
    }
    
    function getElementDisplayName(id) {
        const names = {
            'document_number': 'เลขที่เอกสาร',
            'document_date_day': 'วันที่ - วัน',
            'document_date_month': 'วันที่ - เดือน',
            'document_date_year': 'วันที่ - ปี',
            'student_name': 'ชื่อ-นามสกุลนักเรียน',
            'student_code': 'รหัสนักเรียน',
            'student_year': 'ชั้นปี',
            'student_major': 'สาขาวิชา',
            'student_phone': 'เบอร์โทรศัพท์',
            'signature_student': 'ลายเซ็นนักเรียน',
            'approval_checkbox_yes': 'ช่องอนุมัติ',
            'approval_checkbox_no': 'ช่องไม่อนุมัติ',
            'rejection_reason': 'เหตุผลไม่อนุมัติ'
        };
        return names[id] || id;
    }
    
    function savePositions() {
        Swal.fire({
            title: 'ยืนยันการบันทึก?',
            text: 'ตำแหน่งใหม่จะถูกใช้ในการพิมพ์เอกสารทั้งหมด',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'บันทึก',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = $('<form>', {
                    method: 'POST',
                    action: '',
                    style: 'display: none;'
                });
                
                form.append($('<input>', {
                    type: 'hidden',
                    name: 'save_positions',
                    value: '1'
                }));
                
                Object.keys(positions).forEach(key => {
                    form.append($('<input>', {
                        type: 'hidden',
                        name: `positions[${key}][x]`,
                        value: positions[key].x
                    }));
                    form.append($('<input>', {
                        type: 'hidden',
                        name: `positions[${key}][y]`,
                        value: positions[key].y
                    }));
                });
                
                $('body').append(form);
                form.submit();
            }
        });
    }
    
    function resetPositions() {
        Swal.fire({
            title: 'รีเซ็ตตำแหน่ง?',
            text: 'ตำแหน่งทั้งหมดจะกลับเป็นค่าเริ่มต้น',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'รีเซ็ต',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                location.reload();
            }
        });
    }
    
    // คลิกที่ position item เพื่อเลือกองค์ประกอบ
    $(document).on('click', '.position-item', function() {
        const elementId = $(this).data('element');
        const element = document.getElementById(elementId);
        if (element) {
            selectElement(element);
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>