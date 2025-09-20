    </div>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5 mt-5">
        <div class="container">
            <div class="row">
                <!-- Company Info -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="footer-brand mb-3">
                        <h4 class="text-primary">
                            <i class="fas fa-utensils me-2"></i>E-Fooding System
                        </h4>
                    </div>
                    <p class="mb-2">
                        <i class="fas fa-building me-2 text-primary"></i>
                        PT. Selatan Agro Makmur Lestari
                    </p>
                    <p class="mb-2">
                        <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                        Sungai Raden, Rengas Abang, Air Sugihan<br>
                        Ogan Komering Ilir, Sumatera Selatan
                    </p>
                    <p class="mb-2">
                        <i class="fas fa-phone me-2 text-primary"></i>
                        +62 812-3456-7890
                    </p>
                    <p class="mb-0">
                        <i class="fas fa-envelope me-2 text-primary"></i>
                        info@selatanagro.com
                    </p>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="text-uppercase fw-bold mb-3">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="../dashboard.php" class="text-white text-decoration-none">
                                <i class="fas fa-home me-2 text-primary"></i>Dashboard
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="../about.php" class="text-white text-decoration-none">
                                <i class="fas fa-info-circle me-2 text-primary"></i>Tentang Kami
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="../help.php" class="text-white text-decoration-none">
                                <i class="fas fa-question-circle me-2 text-primary"></i>Bantuan
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="../contact.php" class="text-white text-decoration-none">
                                <i class="fas fa-phone-alt me-2 text-primary"></i>Kontak
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- System Info -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6 class="text-uppercase fw-bold mb-3">System Info</h6>
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge bg-primary me-2">v1.0.0</span>
                        <small class="text-muted">Production</small>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">
                            <i class="fas fa-database me-1"></i>
                            MySQL 8.0
                        </small>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">
                            <i class="fas fa-code me-1"></i>
                            PHP 8.1+
                        </small>
                    </div>
                    <div>
                        <small class="text-muted">
                            <i class="fas fa-shield-alt me-1"></i>
                            SSL Secured
                        </small>
                    </div>
                </div>

                <!-- Social Media & Support -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6 class="text-uppercase fw-bold mb-3">Connect With Us</h6>
                    <div class="d-flex gap-2 mb-3">
                        <a href="#" class="btn btn-outline-primary btn-sm rounded-circle">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="btn btn-outline-primary btn-sm rounded-circle">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="btn btn-outline-primary btn-sm rounded-circle">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" class="btn btn-outline-primary btn-sm rounded-circle">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>

                    <h6 class="text-uppercase fw-bold mb-2 mt-4">Support</h6>
                    <p class="mb-1">
                        <small>
                            <i class="fas fa-clock me-1 text-primary"></i>
                            Support: 08:00 - 17:00 WIB
                        </small>
                    </p>
                    <p class="mb-0">
                        <small>
                            <i class="fas fa-envelope me-1 text-primary"></i>
                            support@selatanagro.com
                        </small>
                    </p>
                </div>
            </div>

            <hr class="my-4 bg-secondary">

            <!-- Copyright & Legal -->
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">
                        &copy; <?php echo date('Y'); ?> PT. Selatan Agro Makmur Lestari. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted">
                        <a href="../privacy.php" class="text-white text-decoration-none me-3">Privacy Policy</a>
                        <a href="../terms.php" class="text-white text-decoration-none me-3">Terms of Service</a>
                        <a href="../sitemap.php" class="text-white text-decoration-none">Sitemap</a>
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap & jQuery JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script src="../assets/js/script.js?v=<?php echo time(); ?>"></script>

    <!-- Footer Scripts -->
    <script>
        $(document).ready(function() {
            // Smooth scroll untuk anchor links
            $('a[href^="#"]').on('click', function(e) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: $($(this).attr('href')).offset().top
                }, 500);
            });

            // Tooltip initialization
            $('[data-bs-toggle="tooltip"]').tooltip();

            // Back to top button
            $(window).scroll(function() {
                if ($(this).scrollTop() > 300) {
                    $('#backToTop').fadeIn();
                } else {
                    $('#backToTop').fadeOut();
                }
            });

            $('#backToTop').click(function() {
                $('html, body').animate({
                    scrollTop: 0
                }, 500);
                return false;
            });
        });
    </script>

    <!-- Back to Top Button -->
    <button id="backToTop" class="btn btn-primary rounded-circle shadow"
        style="position: fixed; bottom: 20px; right: 20px; display: none; z-index: 1000;"
        data-bs-toggle="tooltip" data-bs-placement="left" title="Kembali ke atas">
        <i class="fas fa-arrow-up"></i>
    </button>
    </body>

    </html>