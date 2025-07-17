<?php
require_once 'config/session_config.php';
require_once 'config/connect.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['student_db_id']);
$user_info = null;
if ($is_logged_in) {
    $student_db_id = $_SESSION['student_db_id'];
    $stmt = $conn->prepare("SELECT first_name, last_name, email, department, student_id, profile_picture FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_db_id);
    $stmt->execute();
    $user_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $profile_pic_path = (!empty($user_info['profile_picture']) && file_exists($user_info['profile_picture'])) ? $user_info['profile_picture'] : 'https://ui-avatars.com/api/?name=' . urlencode($user_info['first_name'] . ' ' . $user_info['last_name']) . '&background=3498db&color=fff&size=128';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicants - STVC Election System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        html, body { height: 100%; }
        body { display: flex; flex-direction: column; }
        main { flex: 1 0 auto; }
        .footer-section { flex-shrink: 0; }
        .carousel-item { min-height: 350px; }
        .carousel-control-prev, .carousel-control-next { filter: invert(1); }
        .btn-link { text-decoration: none; font-size: 1.2rem; }
        .btn-link:focus { outline: 2px solid #3498db; }
        .rounded-circle.shadow { border: 4px solid #3498db; }
    </style>
    <link rel="stylesheet" href="includes/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background-color: #2c3e50 !important;">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="uploads/gallery/STVC logo.jpg" alt="STVC Logo" style="height:40px;width:auto;margin-right:10px;">
                <span class="fw-bold" style="color:white;letter-spacing:1px;">STVC Election System</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i> Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="news.php"><i class="fas fa-newspaper me-1"></i> News</a></li>
                    <li class="nav-item"><a class="nav-link" href="positions.php"><i class="fas fa-list me-1"></i> Positions</a></li>
                    <?php if ($is_logged_in): ?>
                        <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle user-dropdown" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <div class="user-avatar" style="padding:0;overflow:hidden;width:36px;height:36px;">
                                    <img src="<?php echo $profile_pic_path; ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                                </div>
                                <?php echo htmlspecialchars($user_info['first_name']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="application.php"><i class="fas fa-edit me-2"></i>Apply for Position</a></li>
                                <li><a class="dropdown-item" href="positions.php"><i class="fas fa-list me-2"></i>View Positions</a></li>
                                <li><a class="dropdown-item" href="news.php"><i class="fas fa-newspaper me-2"></i>News & Q&A</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php"><i class="fas fa-user-plus me-1"></i> Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <!-- Header -->
    <section class="py-5" style="background: linear-gradient(90deg, #33506a 0%, #3498db 100%); box-shadow: 0 -2px 8px rgba(44,62,80,0.10) inset;">
        <div class="container text-center">
            <h1 class="display-5 fw-bold mb-3" style="color: #fff;"><i class="fas fa-users me-2"></i>Applicants</h1>
            <p class="lead" style="color: #e0eafc; font-size: 1.2rem;">Browse all applicants for student government positions. Like, bookmark, or share your favorite candidates!</p>
        </div>
    </section>
    <main>
        <section class="applicants-section" style="background: #f8f9fa; padding: 60px 0;">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-10">
                        <div class="registration-card" style="background: #fff; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); padding: 30px;">
                            <h2 class="text-center mb-4"><i class="fas fa-star me-2"></i>Candidate Spotlight</h2>
                            <div id="candidate-carousel" class="carousel slide" data-bs-ride="carousel">
                                <div class="carousel-inner" id="carousel-inner">
                                    <!-- Candidates will be loaded here by JS -->
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#candidate-carousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Previous</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#candidate-carousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Next</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <!-- Quick View Modal -->
    <div class="modal fade" id="quickViewModal" tabindex="-1" aria-labelledby="quickViewModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="quickViewModalLabel">Candidate Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="quickViewBody">
            <!-- Details will be loaded here -->
          </div>
        </div>
      </div>
    </div>
    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Fetch and render candidates
    function renderCarousel(candidates) {
        const carouselInner = document.getElementById('carousel-inner');
        carouselInner.innerHTML = '';
        if (candidates.length === 0) {
            carouselInner.innerHTML = '<div class="carousel-item active"><div class="text-center text-muted py-5">No spotlight candidates found.</div></div>';
            return;
        }
        candidates.forEach((c, idx) => {
            const active = idx === 0 ? 'active' : '';
            const imgSrc = c.image1 ? `uploads/applications/${c.image1}` : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(c.first_name + ' ' + c.last_name) + '&background=3498db&color=fff&size=128';
            const likeClass = c.user_liked ? 'text-success' : 'text-secondary';
            const bookmarkClass = c.user_bookmarked ? 'text-warning' : 'text-secondary';
            const card = `
            <div class="carousel-item ${active}">
                <div class="row align-items-center">
                    <div class="col-md-4 text-center">
                        <img src="${imgSrc}" alt="${c.first_name} ${c.last_name}" class="rounded-circle shadow" style="width:140px;height:140px;object-fit:cover;">
                        <div class="mt-3">
                            <button class="btn btn-link btn-like ${likeClass}" data-id="${c.id}" title="Like"><i class="fas fa-heart"></i> <span class="like-count">${c.like_count}</span></button>
                            <button class="btn btn-link btn-bookmark ${bookmarkClass}" data-id="${c.id}" title="Bookmark"><i class="fas fa-bookmark"></i> <span class="bookmark-count">${c.bookmark_count}</span></button>
                            <button class="btn btn-link btn-share text-info" data-id="${c.id}" title="Share"><i class="fas fa-share-alt"></i></button>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <h4>${c.first_name} ${c.last_name}</h4>
                        <span class="badge bg-info">${c.position_name}</span>
                        <span class="badge bg-secondary ms-1">ID: ${c.reg_student_id || c.student_id}</span>
                        <span class="badge bg-primary ms-1">Dept: ${c.department || ''}</span>
                        <div class="mt-3"><strong>Bio:</strong> ${c.biography ? c.biography.replace(/\n/g, '<br>') : 'N/A'}</div>
                        <div class="mt-2"><strong>Campaign Promises:</strong> ${c.goals ? c.goals.replace(/\n/g, '<br>') : 'N/A'}</div>
                        <div class="mt-2"><strong>Experience:</strong> ${c.experience ? c.experience.replace(/\n/g, '<br>') : 'N/A'}</div>
                        <div class="mt-2"><strong>Skills:</strong> ${c.skills ? c.skills.replace(/\n/g, '<br>') : 'N/A'}</div>
                        <button class="btn btn-outline-primary mt-3 btn-quickview" data-id="${c.id}"><i class="fas fa-eye me-1"></i>Quick View</button>
                    </div>
                </div>
            </div>`;
            carouselInner.insertAdjacentHTML('beforeend', card);
        });
    }
    function fetchCandidates() {
        fetch('get_candidates_spotlight.php')
            .then(res => res.json())
            .then(data => {
                renderCarousel(data.candidates);
            });
    }
    fetchCandidates();
    // Like, bookmark, share, quick view handlers
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-like')) {
            const btn = e.target.closest('.btn-like');
            const id = btn.getAttribute('data-id');
            fetch('like_candidate.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'candidate_id=' + encodeURIComponent(id)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    btn.querySelector('.like-count').textContent = data.like_count;
                    btn.classList.toggle('text-success', data.liked);
                    btn.classList.toggle('text-secondary', !data.liked);
                }
            });
        }
        // Bookmark button
        if (e.target.closest('.btn-bookmark')) {
            const btn = e.target.closest('.btn-bookmark');
            const id = btn.getAttribute('data-id');
            fetch('bookmark_candidate.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'candidate_id=' + encodeURIComponent(id)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    btn.querySelector('.bookmark-count').textContent = data.bookmark_count;
                    btn.classList.toggle('text-warning', data.bookmarked);
                    btn.classList.toggle('text-secondary', !data.bookmarked);
                } else if (data.message) {
                    alert(data.message);
                }
            });
        }
        // Share button
        if (e.target.closest('.btn-share')) {
            const btn = e.target.closest('.btn-share');
            const id = btn.getAttribute('data-id');
            const url = window.location.origin + '/General/application_details.php?id=' + id;
            if (navigator.share) {
                navigator.share({ title: 'Check out this candidate!', url })
            } else {
                navigator.clipboard.writeText(url);
                alert('Link copied to clipboard!');
            }
        }
        // Quick View
        if (e.target.closest('.btn-quickview')) {
            const btn = e.target.closest('.btn-quickview');
            const id = btn.getAttribute('data-id');
            fetch('application_details.php?id=' + id)
                .then(res => res.text())
                .then(html => {
                    // Extract the main details container
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const details = doc.querySelector('.details-container');
                    document.getElementById('quickViewBody').innerHTML = details ? details.outerHTML : 'Details not found.';
                    var quickViewModal = new bootstrap.Modal(document.getElementById('quickViewModal'));
                    quickViewModal.show();
                });
        }
    });
    </script>
</body>
</html> 