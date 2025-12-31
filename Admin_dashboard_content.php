<?php
session_start();
$hotelName = $_SESSION['hotel_name'] ?? "Demo";
$userEmail = $_SESSION['email'] ?? "admin123@gmail.com";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Content</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; padding: 20px; }
        .card { border-radius: 15px; transition: 0.3s; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
    </style>
</head>
<body>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Welcome, <?php echo htmlspecialchars($hotelName); ?>!</h2>
        <p class="text-muted mb-0">Admin: <?php echo htmlspecialchars($userEmail); ?></p>
    </div>

    <div class="row">
        <!-- Manage Food -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="fas fa-utensils fa-3x text-danger mb-3"></i>
                    <h5 class="card-title">Manage Food</h5>
                    <p class="card-text">Update and maintain food services.</p>
                    <a href="food.php" class="btn btn-danger">Go</a>
                </div>
            </div>
        </div>

        
        <!-- Manage Rooms -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="fas fa-bed fa-3x text-success mb-3"></i>
                    <h5 class="card-title">Manage Rooms</h5>
                    <p class="card-text">Add, update or remove room details.</p>
                    <a href="room.php" class="btn btn-success text-white">Go</a>
                </div>
            </div>
        </div>

        <!-- Manage Role -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="fas fa-user-shield fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">Manage Role</h5>
                    <p class="card-text">Add, update or remove staff role details.</p>
                    <a href="role.php" class="btn btn-primary text-white">Go</a>
                </div>
            </div>
        </div>

        <!-- Manage Staff (Detailed) -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="fas fa-users-cog fa-3x text-warning mb-3"></i>
                    <h5 class="card-title">Manage Staff</h5>
                    <p class="card-text">Add, update or remove staff details.</p>
                    <a href="staff.php" class="btn btn-warning text-white">Go</a>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
