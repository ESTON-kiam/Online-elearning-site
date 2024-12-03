<?php
session_name('super_admin');
session_start();
require_once 'include/database.php';


global $database, $conn;

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin');
    exit();
}


if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $instructor_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    
    if ($instructor_id) {
        try {
           
            $stmt = $conn->prepare("SELECT profile_image FROM instructors WHERE id = ?");
            $stmt->bind_param("i", $instructor_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $instructor = $result->fetch_assoc();
            $stmt->close();
            
           
            $delete_stmt = $conn->prepare("DELETE FROM instructors WHERE id = ?");
            $delete_stmt->bind_param("i", $instructor_id);
            $delete_result = $delete_stmt->execute();
            
            if ($delete_result) {
                
                if (!empty($instructor['profile_image']) && file_exists($instructor['profile_image'])) {
                    unlink($instructor['profile_image']);
                }
                
                $_SESSION['success_message'] = "Instructor deleted successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to delete instructor.";
            }
            
            $delete_stmt->close();
            header("Location: manage_instructors.php");
            exit();
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Database error: " . $e->getMessage();
        }
    }
}


$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

$search_query = isset($_GET['search']) ? $database->sanitizeInput($_GET['search']) : '';

try {
    
    $base_query = "FROM instructors WHERE 1=1";
    $params = [];
    $param_types = "";

   
    if (!empty($search_query)) {
        $base_query .= " AND (name LIKE ? OR username LIKE ? OR email LIKE ? OR expertise LIKE ?)";
        $search_param = "%{$search_query}%";
        $params = [$search_param, $search_param, $search_param, $search_param];
        $param_types = "ssss";
    }

   
    $count_query = "SELECT COUNT(*) AS total " . $base_query;
    $count_stmt = $conn->prepare($count_query);
    
    if (!empty($params)) {
        $count_stmt->bind_param($param_types, ...$params);
    }
    
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_records = $count_result->fetch_assoc()['total'];
    $count_stmt->close();

   
    $total_pages = ceil($total_records / $records_per_page);

    
    $query = "SELECT id, name, username, email, expertise, is_active, created_at " . 
             $base_query . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    
   
    $param_types .= "ii";
    $params[] = $records_per_page;
    $params[] = $offset;

   
    $stmt = $conn->prepare($query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $instructors = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} catch (Exception $e) {
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    $instructors = [];
    $total_pages = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Instructors</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h2 class="mb-0">Manage Instructors</h2>
                        <div>
                            <a href="add_instructor.php" class="btn btn-success btn-sm">
                                <i class="bi bi-plus-circle me-1"></i>Add New Instructor
                            </a>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <form action="" method="GET" class="d-flex">
                                    <input type="search" name="search" class="form-control me-2" 
                                           placeholder="Search instructors..." 
                                           value="<?php echo htmlspecialchars($search_query); ?>">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </form>
                            </div>
                            
                            <div class="col-md-6">
                                <?php if(isset($_SESSION['success_message'])): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <?php 
                                        echo htmlspecialchars($_SESSION['success_message']); 
                                        unset($_SESSION['success_message']);
                                        ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if(isset($_SESSION['error_message'])): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <?php 
                                        echo htmlspecialchars($_SESSION['error_message']); 
                                        unset($_SESSION['error_message']);
                                        ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Expertise</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($instructors)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center">
                                                <?php echo $search_query ? 'No instructors found matching your search.' : 'No instructors have been added yet.'; ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($instructors as $instructor): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($instructor['id']); ?></td>
                                                <td><?php echo htmlspecialchars($instructor['name']); ?></td>
                                                <td><?php echo htmlspecialchars($instructor['username']); ?></td>
                                                <td><?php echo htmlspecialchars($instructor['email']); ?></td>
                                                <td><?php echo htmlspecialchars($instructor['expertise'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span class="badge <?php 
                                                        echo $instructor['is_active'] ? 'bg-success' : 'bg-danger';
                                                    ?>">
                                                        <?php echo $instructor['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d M Y', strtotime($instructor['created_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="edit_instructor.php?id=<?php echo $instructor['id']; ?>" 
                                                           class="btn btn-sm btn-primary" 
                                                           title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <a href="?action=delete&id=<?php echo $instructor['id']; ?>" 
                                                           class="btn btn-sm btn-danger delete-instructor" 
                                                           title="Delete"
                                                           onclick="return confirm('Are you sure you want to delete this instructor?');">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <nav aria-label="Instructor pagination">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; 
                                            echo $search_query ? '&search=' . urlencode($search_query) : ''; 
                                        ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php 

$database->closeConnection();
?>