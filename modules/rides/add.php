<?php
require_once '../../config/config.php';
requireLogin();
requirePermission('manage_swings');

if (hasRole(['ride operator'])) {
    header("Location: index.php?error=unauthorized");
    exit;
}

$pageTitle = "Add Ride";
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $duration = $_POST['duration'];
    $capacity = $_POST['capacity'];
    $status = $_POST['status'];

    $image_name = null;
    $upload_ok = true;

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_info = pathinfo($_FILES['image']['name']);
        $file_ext = strtolower($file_info['extension'] ?? '');
        
        if (in_array($file_ext, $allowed_ext)) {
            $image_name = 'ride_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $upload_path = '../../assets/images/swings/' . $image_name;
            
            // Check if directory exists, if not, create it
            $dir = dirname($upload_path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
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
            $stmt = $pdo->prepare("INSERT INTO swings (name, price, duration, capacity, image, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $price, $duration, $capacity, $image_name, $status]);
            header("Location: index.php?success=ride_added");
            exit;
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1>Add New Swing/Ride</h1>
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
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Price per Person</label>
                            <input type="number" step="0.01" name="price" class="form-control" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Duration (Minutes)</label>
                            <input type="number" name="duration" class="form-control">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Max Capacity</label>
                            <input type="number" name="capacity" class="form-control">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="active">Active</option>
                                <option value="maintenance">Under Maintenance</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Ride Picture</label>
                            <div class="custom-file">
                                <input type="file" name="image" class="custom-file-input" id="rideImage" accept="image/*" onchange="previewImage(event)">
                                <label class="custom-file-label" for="rideImage">Choose file</label>
                            </div>
                            <small class="form-text text-muted">Select an image file (jpg, jpeg, png, gif, webp).</small>
                        </div>
                    </div>

                    <div class="form-group text-center" id="previewContainer" style="display: none;">
                        <label class="d-block">Selected Picture Preview</label>
                        <img id="imagePreview" src="#" alt="Image Preview" class="img-fluid img-thumbnail rounded shadow-sm" style="max-height: 200px; object-fit: contain;">
                    </div>

                    <button type="submit" class="btn btn-info">Save Ride</button>
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
        var container = document.getElementById('previewContainer');
        preview.src = reader.result;
        container.style.display = 'block';
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
