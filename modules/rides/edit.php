<?php
require_once '../../config/config.php';
requireLogin();
requirePermission('manage_swings');

$isOperator = hasRole(['ride operator']);

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM swings WHERE id = ?");
$stmt->execute([$id]);
$swing = $stmt->fetch();

if (!$swing) {
    header("Location: index.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];

    if ($isOperator) {
        try {
            $stmt = $pdo->prepare("UPDATE swings SET status=? WHERE id=?");
            $stmt->execute([$status, $id]);
            header("Location: index.php?success=ride_updated");
            exit;
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    } else {
        $name = $_POST['name'];
        $price = $_POST['price'];
        $duration = $_POST['duration'];
        $capacity = $_POST['capacity'];

        $image_name = $swing['image'];
        $upload_ok = true;

        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $file_info = pathinfo($_FILES['image']['name']);
            $file_ext = strtolower($file_info['extension'] ?? '');
            
            if (in_array($file_ext, $allowed_ext)) {
                $new_image = 'ride_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
                $upload_path = '../../assets/images/swings/' . $new_image;
                
                $dir = dirname($upload_path);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    // Delete old image if it exists and is a file
                    if (!empty($swing['image']) && file_exists('../../assets/images/swings/' . $swing['image'])) {
                        @unlink('../../assets/images/swings/' . $swing['image']);
                    }
                    $image_name = $new_image;
                } else {
                    $message = "Failed to upload image.";
                    $upload_ok = false;
                }
            } else {
                $message = "Invalid file type. Only JPG, JPEG, PNG, GIF, and WEBP are allowed.";
                $upload_ok = false;
            }
        }

        if ($upload_ok) {
            try {
                $stmt = $pdo->prepare("UPDATE swings SET name=?, price=?, duration=?, capacity=?, image=?, status=? WHERE id=?");
                $stmt->execute([$name, $price, $duration, $capacity, $image_name, $status, $id]);
                header("Location: index.php?success=ride_updated");
                exit;
            } catch (PDOException $e) {
                $message = "Error: " . $e->getMessage();
            }
        }
    }
}

$pageTitle = $isOperator ? "Update Ride Status" : "Edit Ride";
include '../../includes/header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1><?php echo $isOperator ? 'Update Ride Status' : 'Edit Ride: ' . htmlspecialchars($swing['name']); ?></h1>
        </div>
    </section>

    <section class="content">
        <div class="card card-dark card-info">
            <div class="card-body">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo $message; ?>
                        <button type="button" class="close text-white" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <form action="" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Ride Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($swing['name']); ?>" class="form-control" required <?php echo $isOperator ? 'readonly disabled' : ''; ?>>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Price per Person</label>
                            <input type="number" step="0.01" name="price" value="<?php echo $swing['price']; ?>" class="form-control" required <?php echo $isOperator ? 'readonly disabled' : ''; ?>>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Duration (Minutes)</label>
                            <input type="number" name="duration" value="<?php echo $swing['duration']; ?>" class="form-control" <?php echo $isOperator ? 'readonly disabled' : ''; ?>>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Max Capacity</label>
                            <input type="number" name="capacity" value="<?php echo $swing['capacity']; ?>" class="form-control" <?php echo $isOperator ? 'readonly disabled' : ''; ?>>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="<?php echo $isOperator ? 'col-md-12' : 'col-md-6'; ?> form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="active" <?php echo $swing['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="maintenance" <?php echo $swing['status'] == 'maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                                <option value="inactive" <?php echo $swing['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group" <?php echo $isOperator ? 'style="display: none;"' : ''; ?>>
                            <label>Ride Picture</label>
                            <div class="custom-file">
                                <input type="file" name="image" class="custom-file-input" id="rideImage" accept="image/*" onchange="previewImage(event)">
                                <label class="custom-file-label" for="rideImage">Choose file</label>
                            </div>
                            <small class="form-text text-muted">Select a new image file (jpg, jpeg, png, gif, webp) to replace the current one.</small>
                        </div>
                    </div>

                    <div class="form-group text-center" id="previewContainer">
                        <label class="d-block">Ride Picture Preview</label>
                        <?php if ($swing['image']): ?>
                            <img id="imagePreview" src="<?php echo BASE_URL; ?>assets/images/swings/<?php echo $swing['image']; ?>" alt="Image Preview" class="img-fluid img-thumbnail rounded shadow-sm" style="max-height: 200px; object-fit: contain;">
                        <?php else: ?>
                            <img id="imagePreview" src="#" alt="Image Preview" class="img-fluid img-thumbnail rounded shadow-sm" style="max-height: 200px; object-fit: contain; display: none;">
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-info"><?php echo $isOperator ? 'Update Status' : 'Update Ride'; ?></button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </section>
</div>

<script>
function previewImage(event) {
    var input = event.target;
    var reader = new FileReader();
    reader.onload = function(){
        var preview = document.getElementById('imagePreview');
        preview.src = reader.result;
        preview.style.display = 'inline-block';
    };
    if (input.files && input.files[0]) {
        reader.readAsDataURL(input.files[0]);
        // Update label text
        var label = input.nextElementSibling;
        if (label) {
            label.textContent = input.files[0].name;
        }
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
