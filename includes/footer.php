    <!-- Custom Modern Dark Footer -->
    <footer class="main-footer custom-dark-footer">
        <div class="container-fluid">
            <!-- Top Section: Links -->
            <div class="row footer-top">
                <div class="col-lg-3 col-md-4 col-sm-6 footer-col">
                    <h5 class="footer-heading text-glow">FUNFAIR ERP</h5>
                    <ul class="footer-links list-unstyled">
                        <li><a href="<?php echo BASE_URL; ?>index.php">Dashboard</a></li>
                        <li><a href="<?php echo BASE_URL; ?>modules/events/index.php">Manage Events</a></li>
                        <li><a href="<?php echo BASE_URL; ?>modules/tickets/index.php">Ticket Sales</a></li>
                        <li><a href="<?php echo BASE_URL; ?>modules/rides/booking.php">Ride Operations</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-4 col-sm-6 footer-col">
                    <h5 class="footer-heading text-glow">ADMINISTRATION</h5>
                    <ul class="footer-links list-unstyled">
                        <li><a href="<?php echo BASE_URL; ?>modules/reports/sales.php">Sales Reports</a></li>
                        <li><a href="<?php echo BASE_URL; ?>modules/reports/admissions.php">Admission Logs</a></li>
                        <?php if (function_exists('hasRole') && hasRole(['superadmin', 'admin'])): ?>
                        <li><a href="<?php echo BASE_URL; ?>modules/expenses/index.php">Expense Tracking</a></li>
                        <li><a href="<?php echo BASE_URL; ?>modules/hr/index.php">HR Management</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-4 col-sm-6 footer-col">
                    <h5 class="footer-heading text-glow">HELP & SUPPORT</h5>
                    <ul class="footer-links list-unstyled">
                        <li><a href="#">User Manual</a></li>
                        <li><a href="#">Contact Administrator</a></li>
                        <li><a href="#">System FAQs</a></li>
                        <li><a href="#">Report an Issue</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-12 footer-col footer-brand-col text-lg-right mt-4 mt-lg-0">
                    <h3 class="footer-brand-logo text-glow"><i class="fas fa-ticket-alt text-neon-primary mr-2"></i> <?php echo SITE_NAME; ?></h3>
                    <div class="footer-stats mt-4 d-flex flex-lg-column flex-row justify-content-lg-start justify-content-between">
                        <div class="stat-item mb-md-3">
                            <span class="stat-value text-glow">v2.4.0</span>
                            <span class="stat-label">System Version</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value text-neon-success">Online & Secure</span>
                            <span class="stat-label">System Status</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Section: Copyright & Socials -->
            <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 pt-4">
                <div class="footer-bottom-links mb-3 mb-md-0">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">License Info</a>
                    <a href="#">Sitemap</a>
                </div>
                <div class="footer-copyright text-center mb-3 mb-md-0">
                    Price is in PKR and includes applicable taxes.<br>
                    &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.
                </div>
                <div class="footer-socials text-md-right text-center">
                    <a href="#" class="social-twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-youtube"><i class="fab fa-youtube"></i></a>
                    <a href="#" class="social-instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-linkedin"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="social-pinterest"><i class="fab fa-pinterest-p"></i></a>
                </div>
            </div>
        </div>
    </footer>
</div>
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<!-- DataTables & Plugins -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script class="core-script">
$(document).ready(function() {
    $('.datatable').each(function() {
        var table = $(this).DataTable({
            "destroy": true,
            "responsive": true,
            "lengthChange": true,
            "autoWidth": false,
            "buttons": [
                { extend: 'copy', className: 'btn btn-outline-info btn-sm' },
                { extend: 'csv', className: 'btn btn-outline-success btn-sm' },
                { extend: 'excel', className: 'btn btn-outline-success btn-sm' },
                { extend: 'pdf', className: 'btn btn-outline-danger btn-sm' },
                { extend: 'print', className: 'btn btn-outline-primary btn-sm' }
            ]
        });
        
        // Append buttons directly to the DOM wrapper generated by DataTables for this specific table
        table.buttons().container().appendTo( $(table.table().container()).find('.col-md-6:eq(0)') );
    });
    // Theme Toggler
    const toggleBtn = document.getElementById('theme-toggle');
    if (toggleBtn) {
        const body = document.body;
        const icon = toggleBtn.querySelector('i');
        
        if (localStorage.getItem('theme') === 'dark' || document.body.classList.contains('dark-mode')) {
            icon.classList.replace('fa-moon', 'fa-sun');
        }
        
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            body.classList.toggle('dark-mode');
            if (body.classList.contains('dark-mode')) {
                localStorage.setItem('theme', 'dark');
                icon.classList.replace('fa-moon', 'fa-sun');
            } else {
                localStorage.setItem('theme', 'light');
                icon.classList.replace('fa-sun', 'fa-moon');
            }
        });
    }

    // Tab/Link Click Blink Effect
    $('.nav-link').not('#theme-toggle').on('click', function() {
        $(this).addClass('click-blink');
        setTimeout(() => $(this).removeClass('click-blink'), 600);
    });

    // Dynamically set active class for sidebar links based on current URL initially
    var currentUrl = window.location.href.split('?')[0].split('#')[0];
    $('.nav-sidebar a.nav-link').each(function() {
        var linkUrl = this.href.split('?')[0].split('#')[0];
        if (currentUrl === linkUrl) {
            $(this).addClass('active');
            var $parentTreeview = $(this).closest('.nav-treeview');
            if ($parentTreeview.length > 0) {
                $parentTreeview.css('display', 'block');
                $parentTreeview.closest('.nav-item').addClass('menu-open');
                $parentTreeview.siblings('.nav-link').addClass('active');
            }
        }
    });

    // SPA (Single Page Application) AJAX Navigation logic
    $(document).off('click', '.nav-sidebar a.nav-link').on('click', '.nav-sidebar a.nav-link', function(e) {
        var url = $(this).attr('href');
        
        // Prevent AJAX routing for empty/hash links or external URLs
        if (!url || url === '#' || url.startsWith('javascript:')) {
            return;
        }

        e.preventDefault();
        
        // Instantly update active UI states
        $('.nav-sidebar a.nav-link').removeClass('active');
        $(this).addClass('active');
        var $parentTreeview = $(this).closest('.nav-treeview');
        if ($parentTreeview.length > 0) {
            $parentTreeview.siblings('.nav-link').addClass('active');
        } else {
            $('.nav-treeview').css('display', 'none');
            $('.nav-item').removeClass('menu-open');
        }

        // Fade out content slightly
        var $contentWrapper = $('.content-wrapper');
        $contentWrapper.css('opacity', '0.4');

        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                // Parse using jQuery
                var $parsed = $('<div>').html(response);
                var newContent = $parsed.find('.content-wrapper').html();
                
                if (newContent) {
                    $contentWrapper.html(newContent);
                    $contentWrapper.animate({opacity: 1}, 200);
                    
                    // Update Page Title
                    var title = $parsed.find('title').text();
                    if (title) document.title = title;

                    // Execute scripts (Charts, etc.), avoiding core-scripts to prevent duplication
                    $parsed.find('script:not(.core-script)').each(function() {
                        if (!this.src) {
                            var scriptCode = $(this).text();
                            // Rewrite DOMContentLoaded wrappers so they execute instantly
                            scriptCode = scriptCode.replace(/document\.addEventListener\(\s*['"]DOMContentLoaded['"]\s*,\s*function\s*\([^\)]*\)\s*\{/g, 'setTimeout(function() {');
                            try {
                                $.globalEval(scriptCode);
                            } catch(err) { console.warn("AJAX Script Error:", err); }
                        }
                    });

                    // Update URL gracefully
                    window.history.pushState({path: url}, '', url);

                    // Re-initialize Datatables manually
                    if ($('.datatable').length) {
                        $('.datatable').each(function() {
                            if (!$.fn.DataTable.isDataTable(this)) {
                                var table = $(this).DataTable({
                                    "destroy": true,
                                    "responsive": true, "lengthChange": true, "autoWidth": false,
                                    "buttons": [
                                        { extend: 'copy', className: 'btn btn-outline-info btn-sm' },
                                        { extend: 'csv', className: 'btn btn-outline-success btn-sm' },
                                        { extend: 'excel', className: 'btn btn-outline-success btn-sm' },
                                        { extend: 'pdf', className: 'btn btn-outline-danger btn-sm' },
                                        { extend: 'print', className: 'btn btn-outline-primary btn-sm' }
                                    ]
                                });
                                table.buttons().container().appendTo( $(table.table().container()).find('.col-md-6:eq(0)') );
                            }
                        });
                    }
                    
                    window.scrollTo(0, 0); // Scroll to top
                } else {
                    window.location.href = url; // Fallback
                }
            },
            error: function() {
                window.location.href = url; // Fallback
            }
        });
    });

    // Handle Back/Forward browser buttons
    window.addEventListener('popstate', function(e) {
        if(e.state && e.state.path) {
            window.location.reload();
        }
    });

});
</script>
</body>
</html>
