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
                        +62 822-8207-6291
                    </p>
                    <p class="mb-0">
                        <i class="fas fa-envelope me-2 text-primary"></i>
                        cyuliawan275@gmail.com
                    </p>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="text-uppercase fw-bold mb-3">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="<?php echo getDashboardUrl(); ?>" class="text-white text-decoration-none">
                                <i class="fas fa-home me-2 text-primary"></i>Dashboard
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo getAboutUrl(); ?>" class="text-white text-decoration-none">
                                <i class="fas fa-info-circle me-2 text-primary"></i>Tentang Kami
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo getContactUrl(); ?>" class="text-white text-decoration-none">
                                <i class="fas fa-phone-alt me-2 text-primary"></i>Kontak
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- System Info -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6 class="text-uppercase fw-bold mb-3">System Info</h6>
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge bg-primary me-2">v1.0</span>
                        <small class="text-light">Production</small>
                    </div>
                    <div class="mb-2">
                        <small class="text-light">
                            <i class="fas fa-database me-1 text-primary"></i>
                            MySQL 8.0
                        </small>
                    </div>
                    <div class="mb-2">
                        <small class="text-light">
                            <i class="fas fa-code me-1 text-primary"></i>
                            PHP 8.1+
                        </small>
                    </div>
                    <div>
                        <small class="text-light">
                            <i class="fas fa-shield-alt me-1 text-primary"></i>
                            SSL Secured
                        </small>
                    </div>
                </div>

                <!-- Social Media & Support -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6 class="text-uppercase fw-bold mb-3">Connect With Developer</h6>
                    <div class="d-flex gap-2 mb-3">
                        <a href="https://www.facebook.com/candra.yulyawan.714" target="_blank" class="btn btn-outline-primary btn-sm rounded-circle">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://www.instagram.com/cacanmu" target="_blank" class="btn btn-outline-primary btn-sm rounded-circle">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="https://www.linkedin.com/in/candra-yuliawan-2627452b7/" target="_blank" class="btn btn-outline-primary btn-sm rounded-circle">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="https://github.com/CandraYuliawan1207" target="_blank" class="btn btn-outline-primary btn-sm rounded-circle">
                            <i class="fab fa-github"></i>
                        </a>
                    </div>

                    <h6 class="text-uppercase fw-bold mb-2 mt-4">Support</h6>
                    <p class="mb-1">
                        <small>
                            <i class="fas fa-clock me-1 text-primary"></i>
                            08:00 - 15:00 WIB
                        </small>
                    </p>
                    <p class="mb-0">
                        <small>
                            <i class="fab fas fa-whatsapp me-1 text-primary"></i>
                            0822-6948-2581
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
                        <a href="<?php echo getPrivacyUrl(); ?>" class="text-white text-decoration-none me-3">Privacy Policy</a>
                        <a href="<?php echo getTermsUrl(); ?>" class="text-white text-decoration-none me-3">Terms of Service</a>
                        <a href="<?php echo getSitemapUrl(); ?>" class="text-white text-decoration-none">Sitemap</a>
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

        });
    </script>

    <?php
    // Fungsi helper untuk mendapatkan URL berdasarkan session
    // Menggunakan fungsi yang sama seperti di header (isLoggedIn(), isAdminLoggedIn())
    
    function getDashboardUrl() {
        if (isLoggedIn()) {
            return '../user/dashboard.php';
        } elseif (isAdminLoggedIn()) {
            return '../admin/dashboard.php';
        }
        return '../index.php';
    }

    function getAboutUrl() {
        if (isLoggedIn()) {
            return '../user/about.php';
        } elseif (isAdminLoggedIn()) {
            return '../admin/about.php';
        }
        return '../about.php';
    }

    function getHelpUrl() {
        if (isLoggedIn()) {
            return '../user/help.php';
        } elseif (isAdminLoggedIn()) {
            return '../admin/help.php';
        }
        return '../help.php';
    }

    function getContactUrl() {
        if (isLoggedIn()) {
            return '../user/contact.php';
        } elseif (isAdminLoggedIn()) {
            return '../admin/contact.php';
        }
        return '../contact.php';
    }

    function getPrivacyUrl() {
        if (isLoggedIn()) {
            return '../user/privacy.php';
        } elseif (isAdminLoggedIn()) {
            return '../admin/privacy.php';
        }
        return '../privacy.php';
    }

    function getTermsUrl() {
        if (isLoggedIn()) {
            return '../user/terms.php';
        } elseif (isAdminLoggedIn()) {
            return '../admin/terms.php';
        }
        return '../terms.php';
    }

    function getSitemapUrl() {
        if (isLoggedIn()) {
            return '../user/sitemap.php';
        } elseif (isAdminLoggedIn()) {
            return '../admin/sitemap.php';
        }
        return '../sitemap.php';
    }
    ?>
</body>
</html>