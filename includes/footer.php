</main>
        
        <!-- Footer -->
        <footer class="bg-white text-center p-3 border-top">
            <div class="text-muted">
                &copy; <?php echo date('Y'); ?> ระบบขอเปิดรายวิชา - วิทยาลัยการอาชีพปราสาท
            </div>
        </footer>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
    
    <!-- Common Scripts -->
    <script>
        $(document).ready(function() {
            // Toggle Sidebar
            $("#sidebarToggle, #sidebarCollapseBtn").click(function() {
                $("#sidebar").toggleClass("collapsed");
                $("#contentWrapper").toggleClass("sidebar-collapsed");
                
                if ($(window).width() < 992) {
                    $("#sidebar").toggleClass("show");
                    $("#sidebarOverlay").toggleClass("show");
                }
                
                // ปรับไอคอนปุ่มจากซ้ายไปขวาหรือขวาไปซ้าย
                if ($("#sidebar").hasClass("collapsed")) {
                    $("#sidebarToggle i").removeClass("bi-chevron-left").addClass("bi-chevron-right");
                } else {
                    $("#sidebarToggle i").removeClass("bi-chevron-right").addClass("bi-chevron-left");
                }
                
                // บันทึกสถานะ sidebar ใน localStorage
                localStorage.setItem("sidebar_collapsed", $("#sidebar").hasClass("collapsed"));
            });
            
            // ปิด sidebar เมื่อคลิกนอกพื้นที่ (สำหรับมือถือ)
            $("#sidebarOverlay").click(function() {
                $("#sidebar").removeClass("show");
                $("#sidebarOverlay").removeClass("show");
            });
            
            // Dropdown Menu in Sidebar
            $(".sidebar-dropdown-toggle").click(function(e) {
                e.preventDefault();
                $(this).next(".collapse").collapse("toggle");
            });
            
            // โหลดสถานะ sidebar จาก localStorage
            if (localStorage.getItem("sidebar_collapsed") === "true") {
                $("#sidebar").addClass("collapsed");
                $("#contentWrapper").addClass("sidebar-collapsed");
                $("#sidebarToggle i").removeClass("bi-chevron-left").addClass("bi-chevron-right");
            }
            
            // Initialize Select2
            $(".select2").select2({
                theme: "bootstrap-5",
                dropdownParent: $("body")
            });
            
            // ตัวช่วยสำหรับแสดงและซ่อน Loader
            window.showLoader = function() {
                $("#loader").fadeIn(300);
            };
            
            window.hideLoader = function() {
                $("#loader").fadeOut(300);
            };
            
            // ใช้ SweetAlert2 แทน confirm ปกติ
            $(document).on("click", ".btn-delete", function(e) {
                e.preventDefault();
                
                const deleteUrl = $(this).attr("href");
                const itemName = $(this).data("item-name") || "รายการนี้";
                
                Swal.fire({
                    title: "ยืนยันการลบ?",
                    text: `คุณต้องการลบ ${itemName} ใช่หรือไม่?`,
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#dc3545",
                    cancelButtonColor: "#6c757d",
                    confirmButtonText: "ใช่, ลบเลย!",
                    cancelButtonText: "ยกเลิก"
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = deleteUrl;
                    }
                });
            });
            
            // ตั้งค่า DataTables
            $.extend(true, $.fn.dataTable.defaults, {
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/th.json"
                },
                responsive: true,
                dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                     "<'row'<'col-sm-12'tr>>" +
                     "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "ทั้งหมด"]],
                pageLength: 25
            });
            
            // เพิ่มคลาส table-striped และ table-hover ให้กับตาราง DataTable
            $(document).on("init.dt", function(e, settings) {
                var api = new $.fn.dataTable.Api(settings);
                var table = api.table().node();
                
                if ($(table).hasClass("table")) {
                    $(table).addClass("table-striped table-hover");
                }
            });
        });
        
        // ตรวจจับการกดปุ่ม Escape เพื่อปิด sidebar บนมือถือ
        $(document).keyup(function(e) {
            if (e.key === "Escape" && $(window).width() < 992) {
                $("#sidebar").removeClass("show");
                $("#sidebarOverlay").removeClass("show");
            }
        });
        
        // ปรับขนาดหน้าจอ
        $(window).resize(function() {
            if ($(window).width() >= 992) {
                $("#sidebarOverlay").removeClass("show");
                if (localStorage.getItem("sidebar_collapsed") !== "true") {
                    $("#sidebar").removeClass("show");
                }
            }
        });
    </script>
</body>
</html>