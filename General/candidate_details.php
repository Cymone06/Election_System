<?php
require_once 'config/session_config.php';
require_once 'config/connect.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$candidate = null;
if ($id > 0) {
    $stmt = $conn->prepare("SELECT a.*, c.status as candidate_status, c.vetting_status, p.position_name FROM candidates c JOIN applications a ON c.application_id = a.id JOIN positions p ON c.position_id = p.id WHERE c.id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $candidate = $result->fetch_assoc();
    $stmt->close();
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <a href="view_candidates.php" class="btn btn-outline-primary mb-4"><i class="fas fa-arrow-left me-2"></i>Back to Applicants & Candidates</a>
        <?php if ($candidate): ?>
            <div class="card mx-auto shadow" style="max-width:600px;">
                <div class="card-body text-center">
                    <img src="uploads/applications/<?php echo htmlspecialchars($candidate['image1']); ?>" class="rounded-circle mb-3" style="width:120px;height:120px;object-fit:cover;">
                    <h3 class="mb-1"><?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></h3>
                    <p class="mb-1"><strong>Position:</strong> <?php echo htmlspecialchars($candidate['position_name']); ?></p>
                    <p class="mb-1"><strong>Status:</strong> <span class="badge bg-success"><?php echo ucfirst($candidate['candidate_status']); ?></span></p>
                    <p class="mb-1"><strong>Vetting Status:</strong> <span class="badge bg-info text-dark"><?php echo ucfirst($candidate['vetting_status']); ?></span></p>
                    <hr>
                    <p><strong>Admission Number:</strong> <?php echo htmlspecialchars($candidate['admission_number']); ?></p>
                    <p><strong>Year of Admission:</strong> <?php echo htmlspecialchars($candidate['year_of_admission']); ?></p>
                    <p><strong>Year of Graduation:</strong> <?php echo htmlspecialchars($candidate['year_of_graduation']); ?></p>
                    <p><strong>Hometown:</strong> <?php echo htmlspecialchars($candidate['hometown']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($candidate['email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($candidate['phone']); ?></p>
                    <p><strong>Biography:</strong> <?php echo nl2br(htmlspecialchars($candidate['biography'])); ?></p>
                    <p><strong>Goals:</strong> <?php echo nl2br(htmlspecialchars($candidate['goals'])); ?></p>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger text-center">Candidate not found.</div>
        <?php endif; ?>
    </div>
</body>
</html> 