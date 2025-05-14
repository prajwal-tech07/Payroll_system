<?php
require_once "../config/database.php";
include_once "../includes/header.php";

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$error = "";
$success = "";

// Check if ID parameter exists
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: view.php");
    exit;
}

$id = $_GET['id'];

// Get employee data
$query = "SELECT * FROM employees WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: view.php");
    exit;
}

$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $employee_id = trim($_POST['employee_id']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $position = trim($_POST['position']);
    $department = trim($_POST['department']);
    $hire_date = trim($_POST['hire_date']);
    $basic_salary = trim($_POST['basic_salary']);
    
    // Validate required fields
    if (empty($employee_id) || empty($first_name) || empty($last_name) || empty($email) || empty($basic_salary)) {
        $error = "Please fill in all required fields";
    } else {
        // Check if employee ID already exists (excluding current employee)
        $check_query = "SELECT id FROM employees WHERE employee_id = :employee_id AND id != :id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(":employee_id", $employee_id);
        $check_stmt->bindParam(":id", $id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $error = "Employee ID already exists";
        } else {
            // Check if email already exists (excluding current employee)
            $check_email_query = "SELECT id FROM employees WHERE email = :email AND id != :id";
            $check_email_stmt = $db->prepare($check_email_query);
            $check_email_stmt->bindParam(":email", $email);
            $check_email_stmt->bindParam(":id", $id);
            $check_email_stmt->execute();
            
            if ($check_email_stmt->rowCount() > 0) {
                $error = "Email already exists";
            } else {
                // Update employee
                $update_query = "UPDATE employees SET 
                                employee_id = :employee_id,
                                first_name = :first_name,
                                last_name = :last_name,
                                email = :email,
                                phone = :phone,
                                address = :address,
                                position = :position,
                                department = :department,
                                hire_date = :hire_date,
                                basic_salary = :basic_salary
                                WHERE id = :id";
                                
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(":employee_id", $employee_id);
                $update_stmt->bindParam(":first_name", $first_name);
                $update_stmt->bindParam(":last_name", $last_name);
                $update_stmt->bindParam(":email", $email);
                $update_stmt->bindParam(":phone", $phone);
                $update_stmt->bindParam(":address", $address);
                $update_stmt->bindParam(":position", $position);
                $update_stmt->bindParam(":department", $department);
                $update_stmt->bindParam(":hire_date", $hire_date);
                $update_stmt->bindParam(":basic_salary", $basic_salary);
                $update_stmt->bindParam(":id", $id);
                
                if ($update_stmt->execute()) {
                    // Update salary structure for the employee
                    $check_salary_query = "SELECT id FROM salary_structure WHERE employee_id = :employee_id";
                    $check_salary_stmt = $db->prepare($check_salary_query);
                    $check_salary_stmt->bindParam(":employee_id", $id);
                    $check_salary_stmt->execute();
                    
                    if ($check_salary_stmt->rowCount() > 0) {
                        $salary_query = "UPDATE salary_structure SET basic_salary = :basic_salary WHERE employee_id = :employee_id";
                    } else {
                        $salary_query = "INSERT INTO salary_structure (employee_id, basic_salary) VALUES (:employee_id, :basic_salary)";
                    }
                    
                    $salary_stmt = $db->prepare($salary_query);
                    $salary_stmt->bindParam(":employee_id", $id);
                    $salary_stmt->bindParam(":basic_salary", $basic_salary);
                    $salary_stmt->execute();
                    
                    $success = "Employee updated successfully";
                    
                    // Refresh employee data
                    $stmt->execute();
                    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error = "Something went wrong. Please try again.";
                }
            }
        }
    }
}
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Edit Employee</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="view.php" class="btn btn-secondary">Back to Employees</a>
        </div>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h5>Employee Information</h5>
        </div>
        <div class="card-body">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $id); ?>" method="post" class="needs-validation" novalidate>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="employee_id" class="form-label">Employee ID *</label>
                        <input type="text" class="form-control" id="employee_id" name="employee_id" value="<?php echo htmlspecialchars($employee['employee_id']); ?>" required>
                        <div class="invalid-feedback">Please enter an employee ID.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="hire_date" class="form-label">Hire Date</label>
                        <input type="date" class="form-control" id="hire_date" name="hire_date" value="<?php echo htmlspecialchars($employee['hire_date']); ?>">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label">First Name *</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($employee['first_name']); ?>" required>
                        <div class="invalid-feedback">Please enter the first name.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label">Last Name *</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($employee['last_name']); ?>" required>
                        <div class="invalid-feedback">Please enter the last name.</div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($employee['email']); ?>" required>
                        <div class="invalid-feedback">Please enter a valid email.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($employee['phone']); ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($employee['address']); ?></textarea>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="position" class="form-label">Position</label>
                        <input type="text" class="form-control" id="position" name="position" value="<?php echo htmlspecialchars($employee['position']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="department" class="form-label">Department</label>
                        <input type="text" class="form-control" id="department" name="department" value="<?php echo htmlspecialchars($employee['department']); ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="basic_salary" class="form-label">Basic Salary *</label>
                    <input type="number" class="form-control" id="basic_salary" name="basic_salary" step="0.01" value="<?php echo htmlspecialchars($employee['basic_salary']); ?>" required>
                    <div class="invalid-feedback">Please enter the basic salary.</div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Update Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?>
