$(document).ready(function() {
    // Function to initialize Select2 on course and teacher selects
    function initializeSelects() {
        $('.course-select').select2({
            placeholder: "เลือกรายวิชา",
            width: '100%'
        });
        
        $('.teacher-select').select2({
            placeholder: "เลือกครูประจำวิชา",
            width: '100%'
        });
    }
    
    // Initialize Select2 on page load
    initializeSelects();
    
    // Update course details when course is selected
    $(document).on('change', '.course-select', function() {
        var selectedOption = $(this).find('option:selected');
        var container = $(this).closest('.course-item');
        
        // Get course details from data attributes
        var theory = selectedOption.data('theory');
        var practice = selectedOption.data('practice');
        var credit = selectedOption.data('credit');
        var hours = selectedOption.data('hours');
        
        // Update fields
        container.find('.theory-hours').val(theory);
        container.find('.practice-hours').val(practice);
        container.find('.credits').val(credit);
        container.find('.total-hours').val(hours);
    });
    
    // Add course button click
    $('.add-course-btn').click(function() {
        // Get template
        var template = $('#courseItemTemplate').html();
        
        // Add template to container
        $('.course-items').append(template);
        
        // Initialize Select2 for new elements
        initializeSelects();
    });
    
    // Remove course button click
    $(document).on('click', '.remove-course-btn', function() {
        $(this).closest('.course-item').remove();
    });
    
    // Form submission validation
    $('#courseRequestForm').submit(function(e) {
        // Check if at least one course is selected
        if ($('.course-item').length === 0) {
            e.preventDefault();
            Swal.fire({
                title: 'ข้อผิดพลาด',
                text: 'กรุณาเพิ่มรายวิชาอย่างน้อย 1 รายวิชา',
                icon: 'error',
                confirmButtonText: 'ตกลง'
            });
            return;
        }
        
        // Check for duplicate courses
        var courses = [];
        var hasDuplicate = false;
        
        $('.course-select').each(function() {
            var courseId = $(this).val();
            if (courseId && courses.includes(courseId)) {
                hasDuplicate = true;
                return false; // Break the loop
            }
            courses.push(courseId);
        });
        
        if (hasDuplicate) {
            e.preventDefault();
            Swal.fire({
                title: 'ข้อผิดพลาด',
                text: 'มีการเลือกรายวิชาซ้ำกัน กรุณาตรวจสอบข้อมูล',
                icon: 'error',
                confirmButtonText: 'ตกลง'
            });
            return;
        }
        
        // Confirm submission
        e.preventDefault();
        Swal.fire({
            title: 'ยืนยันการส่งคำขอ',
            text: 'คุณต้องการส่งคำขอเปิดรายวิชานี้ใช่หรือไม่?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'ใช่, ส่งคำขอ',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                $(this).unbind('submit').submit();
            }
        });
    });

    // Auto-calculate total hours in course management
    $('#theory_hours, #practice_hours, #edit_theory_hours, #edit_practice_hours').on('input', function() {
        var form = $(this).closest('form');
        var theory = parseInt(form.find('[name="theory_hours"]').val()) || 0;
        var practice = parseInt(form.find('[name="practice_hours"]').val()) || 0;
        var total = theory + practice;
        form.find('[name="total_hours"]').val(total);
    });
    
    // Delete confirmations
    $('.delete-course, .delete-teacher, .delete-student, .delete-schedule').click(function() {
        var itemId = $(this).data('id');
        var formId = '#delete' + $(this).attr('class').split(' ')[0].split('-')[1].charAt(0).toUpperCase() + $(this).attr('class').split(' ')[0].split('-')[1].slice(1) + 'Form';
        var itemType = $(this).attr('class').split(' ')[0].split('-')[1];
        
        Swal.fire({
            title: 'ยืนยันการลบ',
            text: "คุณต้องการลบรายการนี้ใช่หรือไม่? การกระทำนี้ไม่สามารถเปลี่ยนแปลงได้",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ใช่, ลบเลย!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                $(formId + ' input[name="' + itemType + '_id"]').val(itemId);
                $(formId).submit();
            }
        });
    });

    // Initialize DataTables where available
    if($.fn.DataTable && $('#coursesTable').length) {
        $('#coursesTable, #teachersTable, #studentsTable, #requestsTable, #schedulesTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Thai.json"
            }
        });
    }
});