<?php
require_once __DIR__ . '/../General/config/database.php';
// Fetch 3 latest published news headlines
$footer_news = [];
$stmt = $conn->prepare("SELECT id, title, created_at FROM news WHERE status = 'published' ORDER BY created_at DESC LIMIT 3");
if ($stmt && $stmt->execute()) {
    $result = $stmt->get_result();
    $footer_news = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
// Newsletter feedback
$newsletter_success = $_SESSION['newsletter_success'] ?? null;
$newsletter_error = $_SESSION['newsletter_error'] ?? null;
unset($_SESSION['newsletter_success'], $_SESSION['newsletter_error']);
?>
<!-- Professional Expanded Footer -->
<footer class="footer-section mt-5">
    <div class="footer-overlay"></div>
    <div class="container footer-content py-5">
        <div class="row g-4">
            <!-- About -->
            <div class="col-12 col-md-6 col-lg-3">
                <div class="footer-brand d-flex align-items-center mb-2">
                    <img src="../General/uploads/gallery/STVC logo.jpg" alt="STVC Logo" style="height:40px;width:auto;margin-right:10px;">
                    <span class="h5 mb-0">STVC Election System</span>
                </div>
                <p class="small mt-2">Empowering students with transparent, secure, and modern digital elections. Our mission is to foster leadership, participation, and trust in the student government process.</p>
            </div>
            <!-- Quick Links -->
            <div class="col-12 col-md-6 col-lg-3">
                <h6 class="mb-3"><i class="fas fa-link me-2"></i>Quick Links</h6>
                <ul class="footer-links list-unstyled mb-0">
                    <li><a href="../General/index.php"><i class="fas fa-home me-1"></i> Home</a></li>
                    <li><a href="../General/news.php"><i class="fas fa-newspaper me-1"></i> News</a></li>
                    <li><a href="../General/positions.php"><i class="fas fa-list me-1"></i> Positions</a></li>
                    <li><a href="../General/application.php"><i class="fas fa-user-edit me-1"></i> Apply</a></li>
                    <li><a href="../General/login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a></li>
                    <li><a href="../General/register.php"><i class="fas fa-user-plus me-1"></i> Register</a></li>
                    <li><a href="../General/Admin/admin_login.php" style="color: #e74c3c;"><i class="fas fa-user-shield me-1"></i> Admin</a></li>
                </ul>
            </div>
            <!-- Latest News -->
            <div class="col-12 col-md-6 col-lg-3">
                <h6 class="mb-3"><i class="fas fa-bullhorn me-2"></i>Latest News</h6>
                <ul class="list-unstyled mb-3">
                    <?php if (!empty($footer_news)): ?>
                        <?php foreach ($footer_news as $news): ?>
                            <li class="mb-2">
                                <a href="../General/news.php#news-<?php echo $news['id']; ?>" class="text-decoration-none footer-news-link">
                                    <i class="fas fa-angle-right me-1"></i>
                                    <?php echo htmlspecialchars($news['title']); ?>
                                    <span class="badge bg-secondary ms-2 small"><?php echo date('M d', strtotime($news['created_at'])); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="text-muted small">No recent news.</li>
                    <?php endif; ?>
                </ul>
                <a href="../General/news.php" class="btn btn-outline-light btn-sm px-3"><i class="fas fa-arrow-right me-1"></i>View All News</a>
            </div>
            <!-- Contact & Newsletter -->
            <div class="col-12 col-md-6 col-lg-3">
                <h6 class="mb-3"><i class="fas fa-envelope-open-text me-2"></i>Contact & Updates</h6>
                <div class="mb-2">
                    <strong class="d-block mb-1">Join Our Newsletter</strong>
                    <span class="small text-light">Stay updated! Get the latest news, election updates, and important announcements straight to your inbox.</span>
                </div>
                <p class="footer-contact mb-2"><i class="fas fa-envelope me-2"></i>semestvcs@gmail.com</p>
                <p class="footer-contact mb-2"><i class="fas fa-phone me-2"></i>+254 741 247 188</p>
                <p class="footer-contact mb-3"><i class="fas fa-map-marker-alt me-2"></i>STVC Campus, Kenya</p>
                <?php if ($newsletter_success): ?>
                    <div class="alert alert-success py-1 px-2 mb-2 small"><?php echo $newsletter_success; ?></div>
                <?php elseif ($newsletter_error): ?>
                    <div class="alert alert-danger py-1 px-2 mb-2 small"><?php echo $newsletter_error; ?></div>
                <?php endif; ?>
                <form id="newsletter-form" class="newsletter-form d-flex mb-2" action="/Election%20System/includes/subscribe_newsletter.php" method="POST" autocomplete="off">
                    <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                    <input type="email" name="newsletter_email" class="form-control form-control-sm me-2" placeholder="Your email for updates" required style="max-width: 160px;">
                    <button class="btn btn-primary btn-sm" type="submit"><i class="fas fa-paper-plane"></i></button>
                </form>
                <div id="newsletter-feedback"></div>
                <div class="footer-social mt-2">
                    <a href="#" class="me-2" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="me-2" title="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="me-2" title="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
        <hr class="footer-divider my-4" />
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                <span class="small">&copy; <?php echo date('Y'); ?> STVC Election System. All rights reserved.</span>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <span class="small">Useful Resources: 
                    <a href="https://www.stvc.ac.ke/" target="_blank" class="text-decoration-underline text-light ms-1">STVC Website</a> |
                    <a href="https://portal.stvc.ac.ke/" target="_blank" class="text-decoration-underline text-light ms-1">Student Portal</a> |
                    <a href="../General/news.php#faq" class="text-decoration-underline text-light ms-1">Help Center</a>
                </span>
            </div>
        </div>
    </div>
</footer>

<!-- Footer Styles (Enhanced) -->
<style>
.footer-section {
    position: relative;
    background: url('https://images.unsplash.com/photo-1465101046530-73398c7f28ca?auto=format&fit=crop&w=1500&q=80') center center/cover no-repeat;
    color: #fff;
    padding: 3.5rem 0 2rem 0;
    margin-top: 40px;
    overflow: hidden;
    font-family: 'Poppins', sans-serif;
    box-shadow: 0 -2px 16px rgba(44,62,80,0.10);
}
.footer-overlay {
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    background: linear-gradient(135deg, rgba(44, 62, 80, 0.92), rgba(52, 152, 219, 0.80));
    z-index: 1;
}
.footer-content { position: relative; z-index: 2; }
.footer-brand span { font-weight: 700; letter-spacing: 1px; color: #e0eafc; }
.footer-section h5, .footer-section h6 { color: #e0eafc; font-weight: 600; letter-spacing: 0.5px; }
.footer-section p, .footer-section a, .footer-section li, .footer-contact { color: #e0eafc; font-size: 1rem; margin-bottom: 0; text-shadow: 0 1px 4px rgba(44,62,80,0.12); }
.footer-links a { color: #e0eafc; text-decoration: none; transition: color 0.2s; font-weight: 500; padding: 0 0.3rem; }
.footer-links a:hover, .footer-news-link:hover { color: #3498db; text-decoration: underline; }
.footer-social a { color: #e0eafc; font-size: 1.3rem; margin-right: 0.5rem; transition: color 0.2s, transform 0.2s; display: inline-block; }
.footer-social a:hover { color: #3498db; transform: scale(1.15); }
.footer-divider { border: none; border-top: 1.5px solid rgba(255,255,255,0.15); margin: 1.5rem 0; }
.footer-contact i { color: #3498db; }
.newsletter-form input[type="email"] { border-radius: 20px 0 0 20px; border: none; }
.newsletter-form button { border-radius: 0 20px 20px 0; }
@media (max-width: 991px) {
    .footer-content .row > div { margin-bottom: 2rem; }
}
@media (max-width: 768px) {
    .footer-section { padding: 2rem 0 1rem 0; }
    .footer-content .row > div { margin-bottom: 1.5rem; }
    .footer-contact { display: block; margin-bottom: 0.5rem; }
    .footer-divider { margin: 1rem 0; }
}
</style>

<!-- Font Awesome CDN for icons (add in your <head> if not already present) -->
<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> --> 
<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('newsletter-form');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var feedback = document.getElementById('newsletter-feedback');
        feedback.innerHTML = '';
        var formData = new FormData(form);
        var btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                feedback.innerHTML = '<div class="alert alert-success py-1 px-2 mb-2 small">' + data.message + '</div>';
                form.reset();
            } else {
                feedback.innerHTML = '<div class="alert alert-danger py-1 px-2 mb-2 small">' + data.message + '</div>';
            }
        })
        .catch(() => {
            feedback.innerHTML = '<div class="alert alert-danger py-1 px-2 mb-2 small">An error occurred. Please try again later.</div>';
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i>';
        });
    });
});
</script> 