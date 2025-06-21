<?php
require_once 'config/session_config.php';
require_once 'config/connect.php';

// Get applicant ID from query
$applicant_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$applicant = null;
if ($applicant_id) {
    $sql = "SELECT a.*, p.position_name FROM applications a JOIN positions p ON a.position_id = p.id WHERE a.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $applicant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $applicant = $result->fetch_assoc();
    $stmt->close();
}
if (!$applicant) {
    echo '<div class="container mt-5"><div class="alert alert-danger">Applicant not found.</div></div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Details - STVC Election System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }
        .details-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 1000px;
            position: relative;
        }
        .details-header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .details-header h2 {
            margin: 0;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }
        .details-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        .details-body {
            padding: 2rem;
        }
        .details-label {
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }
        .details-value {
            color: #3498db;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .details-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #3498db;
            margin-bottom: 1rem;
        }
        .btn-back {
            background: linear-gradient(135deg, #3498db, #2c3e50);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            margin-top: 1.5rem;
            transition: all 0.3s;
        }
        .btn-back:hover {
            background: #217dbb;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="details-container mx-auto">
        <div class="details-header">
            <h2><i class="fas fa-user me-2"></i>Application Details</h2>
            <p>Full details for <?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?></p>
        </div>
        <div class="details-body">
            <div class="text-center">
                <img src="uploads/applications/<?php echo htmlspecialchars($applicant['image1']); ?>" class="details-img" alt="Applicant Image" style="cursor:pointer;" data-bs-toggle="modal" data-bs-target="#profileImgModal">
            </div>
            <div class="mt-3">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="details-label">First Name</div>
                        <div class="details-value"><?php echo !empty($applicant['first_name']) ? htmlspecialchars($applicant['first_name']) : 'NULL'; ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="details-label">Last Name</div>
                        <div class="details-value"><?php echo !empty($applicant['last_name']) ? htmlspecialchars($applicant['last_name']) : 'NULL'; ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="details-label">Student ID</div>
                        <div class="details-value"><?php echo !empty($applicant['student_id']) ? htmlspecialchars($applicant['student_id']) : 'NULL'; ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="details-label">Email</div>
                        <div class="details-value"><?php echo !empty($applicant['email']) ? htmlspecialchars($applicant['email']) : 'NULL'; ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="details-label">Phone</div>
                        <div class="details-value"><?php echo !empty($applicant['phone']) ? htmlspecialchars($applicant['phone']) : 'NULL'; ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="details-label">Year of Admission</div>
                        <div class="details-value"><?php echo !empty($applicant['year_of_admission']) ? htmlspecialchars($applicant['year_of_admission']) : 'NULL'; ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="details-label">Year of Graduation</div>
                        <div class="details-value"><?php echo !empty($applicant['year_of_graduation']) ? htmlspecialchars($applicant['year_of_graduation']) : 'NULL'; ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="details-label">Hometown</div>
                        <div class="details-value"><?php echo !empty($applicant['hometown']) ? htmlspecialchars($applicant['hometown']) : 'NULL'; ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="details-label">Position</div>
                        <div class="details-value"><?php echo !empty($applicant['position_name']) ? htmlspecialchars($applicant['position_name']) : 'NULL'; ?></div>
                    </div>
                </div>
                <hr class="my-4">
                <div class="details-label">Biography</div>
                <div class="details-value"><?php echo !empty($applicant['biography']) ? nl2br(htmlspecialchars($applicant['biography'])) : 'NULL'; ?></div>
                <div class="details-label">Goals</div>
                <div class="details-value"><?php echo !empty($applicant['goals']) ? nl2br(htmlspecialchars($applicant['goals'])) : 'NULL'; ?></div>
                <div class="details-label">Experience</div>
                <div class="details-value"><?php echo !empty($applicant['experience']) ? nl2br(htmlspecialchars($applicant['experience'])) : 'NULL'; ?></div>
                <div class="details-label">Skills</div>
                <div class="details-value"><?php echo !empty($applicant['skills']) ? nl2br(htmlspecialchars($applicant['skills'])) : 'NULL'; ?></div>
                <div class="text-center mt-4">
                    <div class="details-label">Status</div>
                    <div class="details-value">
                        <?php
                        $status = strtolower($applicant['status']);
                        $vetting = strtolower($applicant['vetting_status']);
                        if ($status === 'pending') {
                            echo '<span class="badge bg-secondary badge-status">Pending Approval</span>';
                        } elseif ($status === 'approved' && $vetting === 'pending') {
                            echo '<span class="badge bg-warning badge-status">Waiting Vetting Approval</span>';
                        } elseif ($status === 'approved' && $vetting === 'verified') {
                            echo '<span class="badge bg-success badge-status">Approved</span>';
                        } elseif ($status === 'rejected' || $vetting === 'rejected') {
                            echo '<span class="badge bg-danger badge-status">Rejected</span>';
                        } else {
                            echo '<span class="badge bg-secondary badge-status">' . ucfirst($status) . '</span>';
                        }
                        ?>
                    </div>
                </div>
            </div>
            <div class="text-center">
                <button onclick="goBack()" class="btn btn-back mt-4"><i class="fas fa-arrow-left me-2"></i>Go Back</button>
            </div>
        </div>
    </div>
    <!-- Profile Image Modal -->
    <div class="modal fade" id="profileImgModal" tabindex="-1" aria-labelledby="profileImgModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-body text-center p-0 position-relative">
                    <div class="position-absolute top-0 end-0 m-2" style="z-index:2;">
                        <button id="zoomInBtn" class="btn btn-sm btn-light me-1" title="Zoom In"><i class="fas fa-search-plus"></i></button>
                        <button id="zoomOutBtn" class="btn btn-sm btn-light me-1" title="Zoom Out"><i class="fas fa-search-minus"></i></button>
                        <a href="uploads/applications/<?php echo htmlspecialchars($applicant['image1']); ?>" download class="btn btn-sm btn-primary" title="Download"><i class="fas fa-download"></i></a>
                    </div>
                    <img id="modalProfileImg" src="uploads/applications/<?php echo htmlspecialchars($applicant['image1']); ?>" alt="Applicant Image Large" class="img-fluid rounded shadow" style="max-width:90vw;max-height:80vh; transition: transform 0.3s;">
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Back button functionality
    function goBack() {
        if (document.referrer) {
            window.history.back();
        } else {
            // Fallback to applicants page if no referrer
            window.location.href = 'applicants.php';
        }
    }

    let scale = 1;
    const img = document.getElementById('modalProfileImg');
    document.getElementById('zoomInBtn').onclick = function() {
        scale = Math.min(scale + 0.2, 3);
        img.style.transform = 'scale(' + scale + ')';
    };
    document.getElementById('zoomOutBtn').onclick = function() {
        scale = Math.max(scale - 0.2, 1);
        img.style.transform = 'scale(' + scale + ')';
    };
    // Reset zoom when modal closes
    document.getElementById('profileImgModal').addEventListener('hidden.bs.modal', function () {
        scale = 1;
        img.style.transform = 'scale(1)';
    });
    </script>
</body>
</html> 