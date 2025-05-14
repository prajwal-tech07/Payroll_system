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

// Get total employees
$emp_query = "SELECT COUNT(*) as total_employees FROM employees";
$emp_stmt = $db->prepare($emp_query);
$emp_stmt->execute();
$total_employees = $emp_stmt->fetch(PDO::FETCH_ASSOC)['total_employees'];

// Get today's attendance
$today = date('Y-m-d');
$att_query = "SELECT 
                COUNT(*) as total_attendance,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'half-day' THEN 1 ELSE 0 END) as half_day,
                SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as on_leave
              FROM attendance 
              WHERE date = :today";
$att_stmt = $db->prepare($att_query);
$att_stmt->bindParam(':today', $today);
$att_stmt->execute();
$attendance = $att_stmt->fetch(PDO::FETCH_ASSOC);

// Get monthly payroll summary
$current_month = date('m');
$current_year = date('Y');
$payroll_query = "SELECT 
                    COUNT(*) as total_payroll,
                    SUM(gross_salary) as total_gross,
                    SUM(total_deductions) as total_deductions,
                    SUM(net_salary) as total_net,
                    SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_count
                  FROM payroll 
                  WHERE month = :month AND year = :year";
$payroll_stmt = $db->prepare($payroll_query);
$payroll_stmt->bindParam(':month', $current_month);
$payroll_stmt->bindParam(':year', $current_year);
$payroll_stmt->execute();
$payroll = $payroll_stmt->fetch(PDO::FETCH_ASSOC);

// Get recent employees
$recent_emp_query = "SELECT * FROM employees ORDER BY id DESC LIMIT 5";
$recent_emp_stmt = $db->prepare($recent_emp_query);
$recent_emp_stmt->execute();
?>

<div class="container mt-4">
    <h2 class="mb-4">Admin Dashboard</h2>
    
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Total Employees</h5>
                    <h2 class="display-4"><?php echo $total_employees; ?></h2>
                </div>
                <div class="card-footer d-flex">
                    <a href="../employees/view.php" class="text-white text-decoration-none">View Details <i class="fas fa-arrow-right ms-2"></i></a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Present Today</h5>
                    <h2 class="display-4"><?php echo $attendance['present'] ?? 0; ?></h2>
                </div>
                <div class="card-footer d-flex">
                    <a href="../attendance/view.php" class="text-white text-decoration-none">View Details <i class="fas fa-arrow-right ms-2"></i></a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <h5 class="card-title">Absent Today</h5>
                    <h2 class="display-4"><?php echo $attendance['absent'] ?? 0; ?></h2>
                </div>
                <div class="card-footer d-flex">
                    <a href="../attendance/view.php" class="text-white text-decoration-none">View Details <i class="fas fa-arrow-right ms-2"></i></a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Monthly Payroll</h5>
                    <h2 class="display-4"><?php echo $payroll['total_payroll'] ?? 0; ?></h2>
                </div>
                <div class="card-footer d-flex">
                    <a href="../payroll/view.php" class="text-white text-decoration-none">View Details <i class="fas fa-arrow-right ms-2"></i></a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Recent Employees</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Department</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_emp_stmt->rowCount() > 0): ?>
                                    <?php while ($row = $recent_emp_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['employee_id']); ?></td>
                                            <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['position']); ?></td>
                                            <td><?php echo htmlspecialchars($row['department']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No employees found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="../employees/view.php" class="btn btn-primary">View All Employees</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Monthly Payroll Summary</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6>Month: <?php echo date('F Y'); ?></h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th>Total Payroll Generated</th>
                                    <td><?php echo $payroll['total_payroll'] ?? 0; ?></td>
                                </tr>
                                <tr>
                                    <th>Total Gross Salary</th>
                                    <td>₹<?php echo number_format($payroll['total_gross'] ?? 0, 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Total Deductions</th>
                                    <td>₹<?php echo number_format($payroll['total_deductions'] ?? 0, 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Total Net Salary</th>
                                    <td>₹<?php echo number_format($payroll['total_net'] ?? 0, 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Paid Status</th>
                                    <td><?php echo $payroll['paid_count'] ?? 0; ?> / <?php echo $payroll['total_payroll'] ?? 0; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="../payroll/view.php" class="btn btn-primary">View All Payroll</a>
                    <a href="../payroll/generate.php" class="btn btn-success float-end">Generate Payroll</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="../employees/add.php" class="btn btn-primary w-100">Add New Employee</a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="../attendance/record.php" class="btn btn-success w-100">Record Attendance</a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="../payroll/generate.php" class="btn btn-warning w-100">Generate Payroll</a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="../auth/register.php" class="btn btn-info w-100">Register User</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?>
