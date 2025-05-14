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

// Set default filter values
$employee_id = isset($_GET['employee_id']) ? $_GET['employee_id'] : '';
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Get all employees for filter dropdown
$employees_query = "SELECT id, employee_id, first_name, last_name FROM employees ORDER BY first_name, last_name";
$employees_stmt = $db->prepare($employees_query);
$employees_stmt->execute();

// Build query based on filters
$query = "SELECT p.*, e.employee_id as emp_id, e.first_name, e.last_name 
          FROM payroll p 
          JOIN employees e ON p.employee_id = e.id 
          WHERE 1=1";

$params = array();

if (!empty($employee_id)) {
    $query .= " AND p.employee_id = :employee_id";
    $params[':employee_id'] = $employee_id;
}

if (!empty($month)) {
    $query .= " AND p.month = :month";
    $params[':month'] = $month;
}

if (!empty($year)) {
    $query .= " AND p.year = :year";
    $params[':year'] = $year;
}

if (!empty($status)) {
    $query .= " AND p.payment_status = :status";
    $params[':status'] = $status;
}

$query .= " ORDER BY p.year DESC, p.month DESC, e.first_name, e.last_name";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();

// Handle payment status update
if (isset($_POST['update_status']) && isset($_POST['payroll_id'])) {
    $payroll_id = $_POST['payroll_id'];
    $payment_status = $_POST['payment_status'];
    
    $update_query = "UPDATE payroll SET payment_status = :status WHERE id = :id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(":status", $payment_status);
    $update_stmt->bindParam(":id", $payroll_id);
    
    if ($update_stmt->execute()) {
        $_SESSION['success'] = "Payment status updated successfully";
    } else {
        $_SESSION['error'] = "Error updating payment status";
    }
    
    // Redirect to refresh the page
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Payroll Records</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="generate.php" class="btn btn-primary">Generate Payroll</a>
        </div>
    </div>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5>Filter Payroll</h5>
        </div>
        <div class="card-body">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" class="row g-3">
                <div class="col-md-3">
                    <label for="employee_id" class="form-label">Employee</label>
                    <select class="form-select" id="employee_id" name="employee_id">
                        <option value="">All Employees</option>
                        <?php 
                        // Reset the pointer of employees_stmt
                        $employees_stmt->execute();
                        while ($employee = $employees_stmt->fetch(PDO::FETCH_ASSOC)): 
                        ?>
                            <option value="<?php echo $employee['id']; ?>" <?php echo ($employee_id == $employee['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($employee['employee_id'] . ' - ' . $employee['first_name'] . ' ' . $employee['last_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="month" class="form-label">Month</label>
                    <select class="form-select" id="month" name="month">
                        <option value="">All Months</option>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>" <?php echo ($month == str_pad($i, 2, '0', STR_PAD_LEFT)) ? 'selected' : ''; ?>>
                                <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="year" class="form-label">Year</label>
                    <select class="form-select" id="year" name="year">
                        <option value="">All Years</option>
                        <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($year == $i) ? 'selected' : ''; ?>>
                                <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Payment Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="paid" <?php echo ($status == 'paid') ? 'selected' : ''; ?>>Paid</option>
                        <option value="unpaid" <?php echo ($status == 'unpaid') ? 'selected' : ''; ?>>Unpaid</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5>Payroll List</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Month/Year</th>
                            <th>Days Worked</th>
                            <th>Gross Salary</th>
                            <th>Deductions</th>
                            <th>Net Salary</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($stmt->rowCount() > 0): ?>
                            <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                    <td><?php echo date('F Y', mktime(0, 0, 0, $row['month'], 1, $row['year'])); ?></td>
                                    <td><?php echo $row['days_worked'] . '/' . $row['working_days']; ?></td>
                                    <td>₹<?php echo number_format($row['gross_salary'], 2); ?></td>
                                    <td>₹<?php echo number_format($row['total_deductions'], 2); ?></td>
                                    <td>₹<?php echo number_format($row['net_salary'], 2); ?></td>
                                    <td>
                                        <?php if ($row['payment_status'] == 'paid'): ?>
                                            <span class="badge bg-success">Paid</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Unpaid</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="payslip.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">View</a>
                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $row['id']; ?>">
                                                Update Status
                                            </button>
                                        </div>
                                        
                                        <!-- Status Modal -->
                                        <div class="modal fade" id="statusModal<?php echo $row['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Update Payment Status</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="payroll_id" value="<?php echo $row['id']; ?>">
                                                            <div class="mb-3">
                                                                <label for="payment_status" class="form-label">Payment Status</label>
                                                                <select class="form-select" id="payment_status" name="payment_status">
                                                                    <option value="paid" <?php echo ($row['payment_status'] == 'paid') ? 'selected' : ''; ?>>Paid</option>
                                                                    <option value="unpaid" <?php echo ($row['payment_status'] == 'unpaid') ? 'selected' : ''; ?>>Unpaid</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" name="update_status" class="btn btn-primary">Update</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No payroll records found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?>
