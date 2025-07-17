<?php
require_once '../config/session_config.php';
require_once '../config/database.php';
require_once 'admin_header.php';
// --- Access Control ---
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'super_admin'])) {
    header('Location: admin_login.php');
    exit();
}

// --- Initialization ---
$type = isset($_GET['type']) && $_GET['type'] === 'updates' ? 'updates' : 'news';
$table = $type;
$images_table = $type . '_images';
$fk = $type === 'news' ? 'news_id' : 'update_id';
$upload_dir = $type === 'news' ? '../uploads/news/' : '../uploads/updates/';

$success = '';
$error = '';

// --- Handle POST (Create/Update/Delete) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_content'])) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    $status = $_POST['status'] ?? 'published';
    $publish_date = $_POST['publish_date'] ?? date('Y-m-d');
    $is_important = ($type === 'news' && isset($_POST['is_important'])) ? 1 : 0;
    $author_id = $_SESSION['user_id'] ?? 1;
    $uploaded_images = [];
    // --- Image Upload ---
    if (isset($_FILES['images']) && count($_FILES['images']['name']) > 0) {
        $allowed = ['jpg','jpeg','png','gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
            if ($_FILES['images']['error'][$i] === 4) continue;
            $ext = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $error = 'Invalid image type. Only JPG, JPEG, PNG, GIF allowed.';
                break;
            } elseif ($_FILES['images']['size'][$i] > $maxSize) {
                $error = 'Image size must be less than 2MB.';
                break;
            } else {
                $image = $type . '_' . time() . '_' . rand(1000,9999) . "_{$i}." . $ext;
                if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $upload_dir . $image)) {
                    $uploaded_images[] = $image;
                }
            }
        }
    }
    if ($title && $content && !$error) {
        if ($item_id > 0) {
            // Update
            if ($type === 'news') {
                $stmt = $conn->prepare("UPDATE $table SET title=?, content=?, is_important=?, publish_date=?, status=? WHERE id=?");
                $stmt->bind_param('ssissi', $title, $content, $is_important, $publish_date, $status, $item_id);
            } else {
                $stmt = $conn->prepare("UPDATE $table SET title=?, content=?, status=? WHERE id=?");
                $stmt->bind_param('sssi', $title, $content, $status, $item_id);
            }
            $stmt->execute();
            $stmt->close();
            // Insert new images
            if (!empty($uploaded_images)) {
                $stmt = $conn->prepare("INSERT INTO $images_table ($fk, filename) VALUES (?, ?)");
                foreach ($uploaded_images as $img) {
                    $stmt->bind_param('is', $item_id, $img);
                    $stmt->execute();
                }
                $stmt->close();
            }
            $success = ucfirst($type) . ' updated.';
        } else {
            // Create
            if ($type === 'news') {
                $stmt = $conn->prepare("INSERT INTO $table (title, content, is_important, status, publish_date, author_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('ssissi', $title, $content, $is_important, $status, $publish_date, $author_id);
            } else {
                $stmt = $conn->prepare("INSERT INTO $table (title, content, status) VALUES (?, ?, ?)");
                $stmt->bind_param('sss', $title, $content, $status);
            }
            if ($stmt->execute()) {
                $new_item_id = $stmt->insert_id;
                $stmt->close();
                // Insert images
                if (!empty($uploaded_images)) {
                    $stmt = $conn->prepare("INSERT INTO $images_table ($fk, filename) VALUES (?, ?)");
                    foreach ($uploaded_images as $img) {
                        $stmt->bind_param('is', $new_item_id, $img);
                        $stmt->execute();
                    }
                    $stmt->close();
                }
                $success = ucfirst($type) . ' created.';
            } else {
                $error = 'Failed to create ' . $type . ': ' . $conn->error;
            }
        }
    } elseif (!$error) {
        $error = 'Title and content are required.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete']) && !empty($_POST['delete_ids'])) {
    $ids = array_map('intval', $_POST['delete_ids']);
    $in = implode(',', $ids);
    $conn->query("DELETE FROM $table WHERE id IN ($in)");
    $success = count($ids) . ' item(s) deleted.';
}
// --- AJAX: Delete image ---
if (isset($_POST['delete_image_id'])) {
    $img_id = intval($_POST['delete_image_id']);
    $stmt = $conn->prepare("SELECT filename FROM $images_table WHERE id=?");
    $stmt->bind_param('i', $img_id);
    $stmt->execute();
    $stmt->bind_result($filename);
    if ($stmt->fetch()) {
        $stmt->close();
        $conn->query("DELETE FROM $images_table WHERE id=$img_id");
        $file = $upload_dir . $filename;
        if (file_exists($file)) @unlink($file);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    }
    $stmt->close();
    header('Content-Type: application/json');
    echo json_encode(['success' => false]);
    exit();
}
// --- Delete item ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM $table WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    $success = ucfirst($type) . ' deleted.';
}
// --- Toggle importance (news only) ---
if ($type === 'news' && isset($_GET['toggle_important'])) {
    $id = intval($_GET['toggle_important']);
    $stmt = $conn->prepare("UPDATE $table SET is_important = 1 - is_important WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    $success = 'News importance toggled.';
}
// --- Send newsletter (news only) ---
if ($type === 'news' && isset($_GET['send_now'])) {
    require_once '../includes/email_helper.php';
    $id = intval($_GET['send_now']);
    $stmt = $conn->prepare("SELECT title, content FROM $table WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($title, $content);
    if ($stmt->fetch()) {
        $subject = 'Important News: ' . $title;
        $body = '<h2>' . htmlspecialchars($title) . '</h2><p>' . nl2br(htmlspecialchars($content)) . '</p>';
    }
    $stmt->close();
    if (isset($subject) && isset($body)) {
        $sent_count = sendNewsletterToAllSubscribers($conn, $subject, $body);
        $success = "Newsletter sent to $sent_count subscribers.";
    }
}
// --- Edit item ---
$edit_item = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM $table WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_item = $result->fetch_assoc();
    $stmt->close();
}
// --- Fetch items ---
$items = [];
$today = date('Y-m-d');
if ($type === 'news') {
    $result = $conn->query("SELECT * FROM $table WHERE status='published' AND publish_date <= '$today' ORDER BY publish_date DESC, created_at DESC");
} else {
    $result = $conn->query("SELECT * FROM $table WHERE status='published' ORDER BY created_at DESC");
}
if ($result) {
    $items = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Contents - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.tiny.cloud/1/d7wsni5o1ggdjwpp1oe72ygftof8a59anx10ozxzkncgafpd/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body class="bg-light">
    <div class="container py-5">
        <h2 class="mb-4"><i class="fas fa-newspaper text-success me-2"></i>Manage Contents (News & Updates)</h2>
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <div class="mb-4">
            <form method="get" class="d-inline">
                <label for="typeSelect" class="form-label me-2">Manage:</label>
                <select name="type" id="typeSelect" class="form-select d-inline w-auto" onchange="this.form.submit()">
                    <option value="news" <?php if ($type === 'news') echo 'selected'; ?>>News</option>
                    <option value="updates" <?php if ($type === 'updates') echo 'selected'; ?>>Updates</option>
                </select>
            </form>
            <?php if ($edit_item): ?>
                <a href="manage_contents.php?type=<?php echo $type; ?>" class="btn btn-outline-primary ms-2">New <?php echo ucfirst($type); ?></a>
            <?php endif; ?>
        </div>
        <!-- Content Form -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3"><?php echo $edit_item ? 'Edit ' . ucfirst($type) : 'Create ' . ucfirst($type); ?></h5>
                <form method="POST" enctype="multipart/form-data" id="contentForm" autocomplete="off">
                    <?php if ($edit_item): ?><input type="hidden" name="item_id" value="<?php echo $edit_item['id']; ?>"><?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($edit_item['title'] ?? ($success ? '' : '')); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea name="content" class="form-control wysiwyg" rows="6"><?php echo htmlspecialchars($edit_item['content'] ?? ($success ? '' : '')); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Images (JPG/PNG/GIF, max 2MB each, drag-and-drop or select multiple)</label>
                        <input type="file" name="images[]" class="form-control" accept="image/*" id="imagesInput" multiple>
                        <div id="imagesDropArea" class="border border-2 rounded p-3 mt-2 text-center bg-light" style="min-height:80px;cursor:pointer;">
                            <span class="text-muted">Drag & drop images here or click to select</span>
                            <div id="imagesPreview" class="d-flex flex-wrap mt-2"></div>
                        </div>
                    </div>
                    <?php if ($edit_item): ?>
                    <div class="mb-3">
                        <label class="form-label">Current Images</label>
                        <div class="row" id="gallery">
                            <?php
                            $stmt = $conn->prepare("SELECT * FROM $images_table WHERE $fk=?");
                            $stmt->bind_param('i', $edit_item['id']);
                            $stmt->execute();
                            $gallery = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            $stmt->close();
                            foreach ($gallery as $img): ?>
                                <div class="col-4 col-md-2 mb-2 position-relative gallery-img-wrapper">
                                    <img src="<?php echo $upload_dir . htmlspecialchars($img['filename']); ?>" class="img-thumbnail w-100" style="max-height:100px;object-fit:cover;">
                                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 delete-image" data-image-id="<?php echo $img['id']; ?>" title="Delete"><i class="fas fa-times"></i></button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Publish Date</label>
                        <input type="date" name="publish_date" class="form-control" id="publishDateInput" value="<?php echo htmlspecialchars($edit_item['publish_date'] ?? date('Y-m-d')); ?>" required>
                        <div id="futureWarning" class="text-warning small mt-1" style="display:none;">This <?php echo $type; ?> is scheduled for future publication.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="published" <?php if (($edit_item['status'] ?? 'published') === 'published') echo 'selected'; ?>>Published</option>
                            <option value="draft" <?php if (($edit_item['status'] ?? '') === 'draft') echo 'selected'; ?>>Draft</option>
                        </select>
                    </div>
                    <?php if ($type === 'news'): ?>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_important" id="is_important" value="1" <?php if (($edit_item['is_important'] ?? 0) == 1) echo 'checked'; ?>>
                        <label class="form-check-label" for="is_important">Mark as Important (send to newsletter)</label>
                    </div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-success" id="createBtn" name="submit_content"><?php echo $edit_item ? 'Update' : 'Create'; ?></button>
                    <?php if ($edit_item): ?>
                        <a href="manage_contents.php?type=<?php echo $type; ?>" class="btn btn-secondary ms-2">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <!-- List Table -->
        <form method="POST">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">All <?php echo ucfirst($type); ?></h5>
                    <div class="table-responsive">
                        <table id="contentTable" class="table table-bordered table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>#</th>
                                    <th>Title</th>
                                    <th>Created</th>
                                    <th>Publish Date</th>
                                    <th>Status</th>
                                    <th>Image</th>
                                    <?php if ($type === 'news'): ?>
                                    <th>Important</th>
                                    <?php endif; ?>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $i => $n): ?>
                                    <tr>
                                        <td><input type="checkbox" name="delete_ids[]" value="<?php echo $n['id']; ?>"></td>
                                        <td><?php echo $i+1; ?></td>
                                        <td><?php echo htmlspecialchars($n['title']); ?></td>
                                        <td><?php echo htmlspecialchars($n['created_at']); ?></td>
                                        <td><?php echo htmlspecialchars($n['publish_date'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($n['status']); ?></td>
                                        <td><?php if (!empty($n['image'])): ?><img src="<?php echo $upload_dir . htmlspecialchars($n['image']); ?>" alt="Image" style="max-width:60px;max-height:40px;"> <?php endif; ?></td>
                                        <?php if ($type === 'news'): ?>
                                        <td>
                                            <?php if (!empty($n['is_important'])): ?>
                                                <span class="badge bg-danger">Important</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Normal</span>
                                            <?php endif; ?>
                                            <a href="?type=news&toggle_important=<?php echo $n['id']; ?>" class="btn btn-sm btn-outline-warning ms-2"><?php echo !empty($n['is_important']) ? 'Unmark' : 'Mark'; ?></a>
                                        </td>
                                        <?php endif; ?>
                                        <td>
                                            <a href="?type=<?php echo $type; ?>&edit=<?php echo $n['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                            <a href="?type=<?php echo $type; ?>&delete=<?php echo $n['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this <?php echo $type; ?>?');">Delete</a>
                                            <?php if ($type === 'news' && !empty($n['is_important'])): ?>
                                                <a href="?type=news&send_now=<?php echo $n['id']; ?>" class="btn btn-sm btn-success ms-1">Send Now</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" name="bulk_delete" class="btn btn-danger mt-2" onclick="return confirm('Delete selected items?');">Bulk Delete</button>
                </div>
            </div>
        </form>
        <a href="admin_dashboard.php" class="btn btn-secondary mt-4">Back to Dashboard</a>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#contentTable').DataTable();
        $('#selectAll').on('click', function() {
            $('input[name="delete_ids[]"]').prop('checked', this.checked);
        });
        // --- Multi-image drag-and-drop and preview ---
        var dropArea = $('#imagesDropArea');
        var input = $('#imagesInput');
        dropArea.on('click', function() { input.trigger('click'); });
        dropArea.on('dragover', function(e) { e.preventDefault(); dropArea.addClass('bg-info bg-opacity-25'); });
        dropArea.on('dragleave drop', function(e) { e.preventDefault(); dropArea.removeClass('bg-info bg-opacity-25'); });
        dropArea.on('drop', function(e) {
            e.preventDefault();
            dropArea.removeClass('bg-info bg-opacity-25');
            var files = e.originalEvent.dataTransfer.files;
            input[0].files = files;
            previewImages(files);
        });
        input.on('change', function(e) { previewImages(this.files); });
        function previewImages(files) {
            var preview = $('#imagesPreview');
            preview.html('');
            if (!files) return;
            Array.from(files).forEach(function(file) {
                if (!file.type.match('image.*')) return;
                var reader = new FileReader();
                reader.onload = function(e) {
                    preview.append('<img src="'+e.target.result+'" class="img-thumbnail me-2 mb-2" style="max-width:90px;max-height:90px;object-fit:cover;">');
                };
                reader.readAsDataURL(file);
            });
        }
        // --- AJAX delete image ---
        $(document).on('click', '.delete-image', function() {
            var btn = $(this);
            var imgId = btn.data('image-id');
            if (!confirm('Delete this image?')) return;
            $.post('', {delete_image_id: imgId}, function(resp) {
                if (resp.success) {
                    btn.closest('.gallery-img-wrapper').fadeOut(300, function() { $(this).remove(); });
                } else {
                    alert('Failed to delete image.');
                }
            }, 'json');
        });
        // Future publish date warning
        $('#publishDateInput').on('change', function() {
            var val = $(this).val();
            var today = new Date().toISOString().split('T')[0];
            if (val > today) {
                $('#futureWarning').show();
            } else {
                $('#futureWarning').hide();
            }
        }).trigger('change');
        // --- Custom validation for TinyMCE content ---
        $('#contentForm').on('submit', function(e) {
            if (window.tinymce) {
                tinymce.triggerSave();
                var content = tinymce.get('content') ? tinymce.get('content').getContent({format: 'text'}).trim() : $('textarea[name="content"]').val().trim();
                if (!content) {
                    e.preventDefault();
                    alert('Content field cannot be empty.');
                    // Optionally, highlight the editor border
                    $('.tox-tinymce').css('border', '2px solid red');
                    return false;
                } else {
                    $('.tox-tinymce').css('border', '');
                }
            } else {
                var content = $('textarea[name="content"]').val().trim();
                if (!content) {
                    e.preventDefault();
                    alert('Content field cannot be empty.');
                    $('textarea[name="content"]').addClass('is-invalid');
                    return false;
                } else {
                    $('textarea[name="content"]').removeClass('is-invalid');
                }
            }
        });
        // --- Highlight invalid fields and show alert if form is blocked by validation ---
        $('#contentForm')[0].addEventListener('invalid', function(e) {
            e.preventDefault();
            $(e.target).addClass('is-invalid');
            alert('Please fill out all required fields correctly.');
        }, true);
        $('#contentForm input, #contentForm textarea, #contentForm select').on('input change', function() {
            $(this).removeClass('is-invalid');
        });
    });
    tinymce.init({ selector:'.wysiwyg', height: 300, setup: function(editor) {
        editor.on('change', function() {
            $('.tox-tinymce').css('border', '');
        });
    }});
    </script>
    <?php if ($success && !$edit_item): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('contentForm').reset();
                if (window.tinymce) tinymce.getAll().forEach(e=>e.setContent(''));
            });
        </script>
    <?php endif; ?>
</body>
</html> 