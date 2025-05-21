/**
 * main.js - ไฟล์ JavaScript หลักสำหรับฟอร์มขอเปิดรายวิชา
 * แก้ไขปัญหาข้อมูลไม่แสดงเมื่อเลือกรายวิชา
 */
$(document).ready(function() {
    // Function to initialize Select2 on dropdowns
    function initializeSelects() {
        $('.course-select, .teacher-select, .teacher-select-custom').select2({
            placeholder: "เลือกรายการ",
            width: '100%'
        });
    }
    
    // Initialize Select2 dropdowns on page load
    initializeSelects();
    
    // Update course details when course is selected
    $(document).on('change', '.course-select', function() {
        console.log("Course selected");
        var selectedOption = $(this).find('option:selected');
        var container = $(this).closest('.course-item');
        
        // Get course details from data attributes
        var theory = selectedOption.data('theory') || '0';
        var practice = selectedOption.data('practice') || '0';
        var credit = selectedOption.data('credit') || '0';
        var hours = selectedOption.data('hours') || '0';
        
        console.log("Data from option:", theory, practice, credit, hours);
        
        // Update fields
        container.find('.theory-hours').val(theory);
        container.find('.practice-hours').val(practice);
        container.find('.credits').val(credit);
        container.find('.total-hours').val(hours);
    });
    
    // คำนวณชั่วโมงรวมจากทฤษฎีและปฏิบัติสำหรับรายวิชาที่กรอกเอง
    $(document).on('input', '.custom-theory-hours, .custom-practice-hours', function() {
        var container = $(this).closest('.custom-course-container');
        var theory = parseInt(container.find('.custom-theory-hours').val()) || 0;
        var practice = parseInt(container.find('.custom-practice-hours').val()) || 0;
        var total = theory + practice;
        
        container.find('.custom-total-hours').val(total);
        
        // คำนวณหน่วยกิตอัตโนมัติ (ตามเกณฑ์ ศธ. ทฤษฎี 1 ชม. = 1 หน่วยกิต, ปฏิบัติ 2-3 ชม. = 1 หน่วยกิต)
        var credits = theory;
        if (practice > 0) {
            credits += Math.ceil(practice / 3);
        }
        container.find('.custom-credits').val(credits);
    });
    
    // Toggle between select course and custom course
    $(document).on('change', '.course-type-radio', function() {
        var container = $(this).closest('.course-item');
        var value = $(this).val();
        
        if (value === 'select') {
            container.find('.select-course-container').show();
            container.find('.custom-course-container').hide();
            
            // Enable required on select fields
            container.find('.course-select').prop('required', true);
            container.find('.teacher-select').prop('required', true);
            
            // Disable required on custom fields
            container.find('.custom-course-code').prop('required', false);
            container.find('.custom-course-name').prop('required', false);
            container.find('.teacher-select-custom').prop('required', false);
        } else {
            container.find('.select-course-container').hide();
            container.find('.custom-course-container').show();
            
            // Disable required on select fields
            container.find('.course-select').prop('required', false);
            container.find('.teacher-select').prop('required', false);
            
            // Enable required on custom fields
            container.find('.custom-course-code').prop('required', true);
            container.find('.custom-course-name').prop('required', true);
            container.find('.teacher-select-custom').prop('required', true);
        }
    });
    
    // Add course button click - สร้างรายวิชาใหม่
    $('.add-course-btn').click(function() {
        // Get current index - count existing items to ensure unique index
        var currentIndex = $('.course-item').length;
        
        // Clone template HTML
        var template = $('#courseItemTemplate').html();
        
        // Replace all INDEX placeholders with the current index
        template = template.replace(/\[INDEX\]/g, '[' + currentIndex + ']');
        template = template.replace(/_INDEX/g, '_' + currentIndex);
        
        // Append to container
        $('.course-items').append(template);
        
        // Initialize newly added elements
        var newItem = $('.course-items .course-item:last');
        
        // Destroy and re-initialize Select2 for the new elements
        newItem.find('.course-select, .teacher-select, .teacher-select-custom').select2({
            placeholder: "เลือกรายการ",
            width: '100%'
        });
        
        // Set up course type toggles for the new item
        newItem.find('.course-type-radio').first().prop('checked', true);
        newItem.find('.select-course-container').show();
        newItem.find('.custom-course-container').hide();
    });
    
    // Remove course button click (uses event delegation for dynamic elements)
    $(document).on('click', '.remove-course-btn', function() {
        // Only allow removal if there's more than one course item
        if ($('.course-item').length > 1) {
            $(this).closest('.course-item').remove();
            
            // Re-index all course items
            $('.course-item').each(function(index) {
                var courseItem = $(this);
                
                // Update name attributes for all form elements
                courseItem.find('[name^="course_type["], [name^="course_id["], [name^="teacher_id["], [name^="theory_hours["], [name^="practice_hours["], [name^="credits["], [name^="total_hours["], [name^="custom_course_code["], [name^="custom_course_name["], [name^="custom_theory_hours["], [name^="custom_practice_hours["], [name^="custom_credits["], [name^="custom_total_hours["]').each(function() {
                    var name = $(this).attr('name').replace(/\[\d+\]/, '[' + index + ']');
                    $(this).attr('name', name);
                });
                
                // Update ID attributes for radio buttons
                courseItem.find('[id^="course_type_select_"], [id^="course_type_custom_"]').each(function() {
                    var id = $(this).attr('id').replace(/\d+$/, index);
                    $(this).attr('id', id);
                });
                
                // Update for attributes for labels
                courseItem.find('label[for^="course_type_select_"], label[for^="course_type_custom_"]').each(function() {
                    var forAttr = $(this).attr('for').replace(/\d+$/, index);
                    $(this).attr('for', forAttr);
                });
            });
        } else {
            Swal.fire({
                title: 'ไม่สามารถลบได้',
                text: 'ต้องมีรายวิชาอย่างน้อย 1 รายวิชา',
                icon: 'warning',
                confirmButtonText: 'ตกลง'
            });
        }
    });

    // Form validation before submission
    $('#courseRequestForm').submit(function(e) {
        var isValid = true;
        var errorMessages = [];
        
        // ตรวจสอบแต่ละรายวิชา
        $('.course-item').each(function(index) {
            var courseItem = $(this);
            var courseType = courseItem.find('input[name^="course_type"]:checked').val();
            
            if (courseType === 'select') {
                // ตรวจสอบรายวิชาที่เลือกจากระบบ
                if (!courseItem.find('.course-select').val()) {
                    isValid = false;
                    errorMessages.push('กรุณาเลือกรายวิชาที่ ' + (index + 1));
                }
                
                if (!courseItem.find('.teacher-select').val()) {
                    isValid = false;
                    errorMessages.push('กรุณาเลือกครูประจำวิชาที่ ' + (index + 1));
                }
            } else {
                // ตรวจสอบรายวิชาที่กรอกเอง
                if (!courseItem.find('.custom-course-code').val()) {
                    isValid = false;
                    errorMessages.push('กรุณากรอกรหัสวิชาที่ ' + (index + 1));
                }
                
                if (!courseItem.find('.custom-course-name').val()) {
                    isValid = false;
                    errorMessages.push('กรุณากรอกชื่อรายวิชาที่ ' + (index + 1));
                }
                
                if (!courseItem.find('.teacher-select-custom').val()) {
                    isValid = false;
                    errorMessages.push('กรุณาเลือกครูประจำวิชาที่ ' + (index + 1));
                }
            }
        });
        
        // แสดงข้อความแจ้งเตือนถ้ามีข้อผิดพลาด
        if (!isValid) {
            e.preventDefault();
            Swal.fire({
                title: 'ข้อมูลไม่ครบถ้วน',
                html: errorMessages.join('<br>'),
                icon: 'error',
                confirmButtonText: 'ตกลง'
            });
            return;
        }
        
        // Check for duplicate courses
        var courseCodes = [];
        var hasDuplicate = false;
        
        $('.course-item').each(function() {
            var courseItem = $(this);
            var courseType = courseItem.find('input[name^="course_type"]:checked').val();
            var courseCode = '';
            
            if (courseType === 'select') {
                var courseId = courseItem.find('.course-select').val();
                if (courseId) {
                    var courseText = courseItem.find('.course-select option:selected').text();
                    courseCode = courseText.split(' - ')[0];
                }
            } else {
                courseCode = courseItem.find('.custom-course-code').val();
            }
            
            if (courseCode && courseCodes.includes(courseCode)) {
                hasDuplicate = true;
                return false; // break the loop
            }
            
            if (courseCode) {
                courseCodes.push(courseCode);
            }
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
                // ส่งฟอร์ม
                $(this).unbind('submit').submit();
            }
        });
    });

    // แก้ปัญหาเรื่องข้อมูลไม่แสดงหน่วยกิตเมื่อเลือกรายวิชา
    // ตรวจสอบหลังจากโหลดเสร็จว่ามีการเลือกรายวิชาไว้หรือไม่
    setTimeout(function() {
        $('.course-select').each(function() {
            if ($(this).val()) {
                $(this).trigger('change');
            }
        });
    }, 500);
});