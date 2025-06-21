<!-- Professional Footer -->
<footer class="footer-section mt-5">
    <div class="footer-overlay"></div>
    <div class="container footer-content py-4">
        <div class="row align-items-center mb-3">
            <div class="col-md-4 text-center text-md-start mb-3 mb-md-0">
                <div class="footer-brand d-flex align-items-center justify-content-center justify-content-md-start">
                    <i class="fas fa-vote-yea fa-2x me-2"></i>
                    <span class="h5 mb-0">STVC Election System</span>
                </div>
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                <ul class="footer-links list-unstyled d-flex flex-wrap justify-content-center gap-3 mb-0">
                    <li><a href="../General/index.php">Home</a></li>
                    <li><a href="../General/news.php">News</a></li>
                    <li><a href="../General/positions.php">Positions</a></li>
                    <li><a href="../General/application.php">Apply</a></li>
                    <li><a href="../General/login.php">Login</a></li>
                    <li><a href="../General/register.php">Register</a></li>
                    <li><a href="../General/Admin/admin_login.php" style="color: #e74c3c;"><i class="fas fa-user-shield me-1"></i>Admin</a></li>
                </ul>
            </div>
            <div class="col-md-4 text-center text-md-end">
                <div class="footer-social">
                    <a href="#" class="me-2" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="me-2" title="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="me-2" title="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
        <hr class="footer-divider my-3" />
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                <span class="footer-contact"><i class="fas fa-envelope me-2"></i>support@stvc.edu</span>
                <span class="footer-contact ms-3"><i class="fas fa-phone me-2"></i>+123 456 7890</span>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <span class="small">&copy; <?php echo date('Y'); ?> STVC Election System. All rights reserved.</span>
            </div>
        </div>
    </div>
</footer>

<!-- Footer Styles -->
<style>
.footer-section {
    position: relative;
    background: url('https://images.unsplash.com/photo-1465101046530-73398c7f28ca?auto=format&fit=crop&w=1500&q=80') center center/cover no-repeat;
    color: #fff;
    padding: 2.5rem 0 1.5rem 0;
    margin-top: 40px;
    overflow: hidden;
    font-family: 'Poppins', sans-serif;
}
.footer-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(44, 62, 80, 0.85);
    z-index: 1;
}
.footer-content {
    position: relative;
    z-index: 2;
}
.footer-brand i {
    color: #3498db;
    margin-right: 0.5rem;
}
.footer-brand span {
    font-weight: 700;
    letter-spacing: 1px;
    color: #e0eafc;
}
.footer-section h5, .footer-section h6 {
    color: #e0eafc;
    font-weight: 600;
}
.footer-section p, .footer-section a, .footer-section li, .footer-contact {
    color: #e0eafc;
    font-size: 1rem;
    margin-bottom: 0;
    text-shadow: 0 1px 4px rgba(44,62,80,0.12);
}
.footer-links a {
    color: #e0eafc;
    text-decoration: none;
    transition: color 0.2s;
    font-weight: 500;
    padding: 0 0.3rem;
}
.footer-links a:hover {
    color: #3498db;
    text-decoration: underline;
}
.footer-social a {
    color: #e0eafc;
    font-size: 1.3rem;
    margin-right: 0.5rem;
    transition: color 0.2s, transform 0.2s;
    display: inline-block;
}
.footer-social a:hover {
    color: #3498db;
    transform: scale(1.15);
}
.footer-divider {
    border: none;
    border-top: 1.5px solid rgba(255,255,255,0.15);
    margin: 1.5rem 0;
}
.footer-contact i {
    color: #3498db;
}
@media (max-width: 768px) {
    .footer-section {
        padding: 2rem 0 1rem 0;
    }
    .footer-content .row > div {
        margin-bottom: 1.5rem;
    }
    .footer-contact {
        display: block;
        margin-bottom: 0.5rem;
    }
    .footer-divider {
        margin: 1rem 0;
    }
}
</style>

<!-- Font Awesome CDN for icons (add in your <head> if not already present) -->
<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> --> 