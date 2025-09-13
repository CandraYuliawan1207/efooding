// Wait for the DOM to be fully loaded
document.addEventListener("DOMContentLoaded", function() {
    // Hide loader when page is loaded
    setTimeout(function() {
        document.getElementById("loader").style.opacity = "0";
        setTimeout(function() {
            document.getElementById("loader").style.display = "none";
        }, 500);
    }, 800);

    // Add smooth scrolling to all links
    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
        anchor.addEventListener("click", function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute("href"));
            if (target) {
                target.scrollIntoView({
                    behavior: "smooth",
                });
            }
        });
    });

    // Add animation to cards when they come into view
    const observerOptions = {
        threshold: 0.1,
        rootMargin: "0px 0px -50px 0px",
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add("fade-in");
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observe all cards for animation
    document.querySelectorAll(".card").forEach((card) => {
        observer.observe(card);
    });

    // Form validation and enhancement - ONLY FOR FORMS THAT NEED IT
    document.querySelectorAll("form.ajax-form").forEach((form) => {
        form.addEventListener("submit", function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML =
                    '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
            }
            // Biarkan form submit secara normal
        });
    });

    // Add tooltips
    var tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Toast notification function
function showToast(message, type = "info") {
    // Create toast container if it doesn't exist
    if (!document.getElementById("toast-container")) {
        const toastContainer = document.createElement("div");
        toastContainer.id = "toast-container";
        toastContainer.className = "toast-container position-fixed top-0 end-0 p-3";
        toastContainer.style.zIndex = "9999";
        document.body.appendChild(toastContainer);
    }

    // Create toast
    const toast = document.createElement("div");
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute("role", "alert");
    toast.setAttribute("aria-live", "assertive");
    toast.setAttribute("aria-atomic", "true");

    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas ${
                  type === "success"
                    ? "fa-check-circle"
                    : type === "error"
                    ? "fa-exclamation-circle"
                    : "fa-info-circle"
                } me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;

    document.getElementById("toast-container").appendChild(toast);

    // Show toast
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();

    // Remove toast after it's hidden
    toast.addEventListener("hidden.bs.toast", function() {
        toast.remove();
    });
}