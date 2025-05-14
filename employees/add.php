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
        // Check if employee ID already exists
        $check_query = "SELECT id FROM employees WHERE employee_id = :employee_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(":employee_id", $employee_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $error = "Employee ID already exists";
        } else {
            // Check if email already exists
            $check_email_query = "SELECT id FROM employees WHERE email = :email";
            $check_email_stmt = $db->prepare($check_email_query);
            $check_email_stmt->bindParam(":email", $email);
            $check_email_stmt->execute();
            
            if ($check_email_stmt->rowCount() > 0) {
                $error = "Email already exists";
            } else {
                // Insert employee
                $insert_query = "INSERT INTO employees (employee_id, first_name, last_name, email, phone, address, position, department, hire_date, basic_salary) 
                                VALUES (:employee_id, :first_name, :last_name, :email, :phone, :address, :position, :department, :hire_date, :basic_salary)";
                $insert_stmt = $db->prepare($insert_query);
                $insert_stmt->bindParam(":employee_id", $employee_id);
                $insert_stmt->bindParam(":first_name", $first_name);
                $insert_stmt->bindParam(":last_name", $last_name);
                $insert_stmt->bindParam(":email", $email);
                $insert_stmt->bindParam(":phone", $phone);
                $insert_stmt->bindParam(":address", $address);
                $insert_stmt->bindParam(":position", $position);
                $insert_stmt->bindParam(":department", $department);
                $insert_stmt->bindParam(":hire_date", $hire_date);
                $insert_stmt->bindParam(":basic_salary", $basic_salary);
                
                if ($insert_stmt->execute()) {
                    $employee_id_db = $db->lastInsertId();
                    
                    // Create salary structure for the employee
                    $salary_query = "INSERT INTO salary_structure (employee_id, basic_salary) VALUES (:employee_id, :basic_salary)";
                    $salary_stmt = $db->prepare($salary_query);
                    $salary_stmt->bindParam(":employee_id", $employee_id_db);
                    $salary_stmt->bindParam(":basic_salary", $basic_salary);
                    $salary_stmt->execute();
                    
                    $success = "Employee added successfully";
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
            <h2>Add New Employee</h2>
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
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="needs-validation" novalidate>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="employee_id" class="form-label">Employee ID *</label>
                        <input type="text" class="form-control" id="employee_id" name="employee_id" required>
                        <div class="invalid-feedback">Please enter an employee ID.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="hire_date" class="form-label">Hire Date</label>
                        <input type="date" class="form-control" id="hire_date" name="hire_date">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label">First Name *</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                        <div class="invalid-feedback">Please enter the first name.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label">Last Name *</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                        <div class="invalid-feedback">Please enter the last name.</div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <div class="invalid-feedback">Please enter a valid email.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="position" class="form-label">Position</label>
                        <input type="text" class="form-control" id="position" name="position">
                    </div>
                    <div class="col-md-6">
                        <label for="department" class="form-label">Department</label>
                        <input type="text" class="form-control" id="department" name="department">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="basic_salary" class="form-label">Basic Salary *</label>
                    <input type="number" class="form-control" id="basic_salary" name="basic_salary" step="0.01" required>
                    <div class="invalid-feedback">Please enter the basic salary.</div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Add Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?>
