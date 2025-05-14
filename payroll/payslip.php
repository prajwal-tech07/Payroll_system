<?php
require_once "../config/database.php";
include_once "../includes/header.php";

$database = new Database();
$db = $database->getConnection();

// Check if ID parameter exists
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];
    
    // Get payroll details
    $query = "SELECT p.*, e.employee_id as emp_id, e.first_name, e.last_name, e.position, e.department
              FROM payroll p
              JOIN employees e ON p.employee_id = e.id
              WHERE p.id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $payroll = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        // Redirect if payroll not found
        header("Location: view.php");
        exit;
    }
} elseif ($_SESSION['role'] === 'employee') {
    // If employee is viewing their own payslip
    $user_id = $_SESSION['user_id'];
    
    // Get employee ID
    $emp_query = "SELECT id FROM employees WHERE user_id = :user_id";
    $emp_stmt = $db->prepare($emp_query);
    $emp_stmt->bindParam(":user_id", $user_id);
    $emp_stmt->execute();
    
    if ($emp_stmt->rowCount() > 0) {
        $employee_id = $emp_stmt->fetch(PDO::FETCH_ASSOC)['id'];
        
        // Get latest payroll for this employee
        $month = isset($_GET['month']) ? $_GET['month'] : date('m');
        $year = isset($_GET['year']) ? $_GET['year'] : date('Y');
        
        $query = "SELECT p.*, e.employee_id as emp_id, e.first_name, e.last_name, e.position, e.department
                  FROM payroll p
                  JOIN employees e ON p.employee_id = e.id
                  WHERE p.employee_id = :employee_id AND p.month = :month AND p.year = :year";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":employee_id", $employee_id);
        $stmt->bindParam(":month", $month);
        $stmt->bindParam(":year", $year);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $payroll = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "No payslip found for the selected month.";
        }
        
        // Get all payroll months for this employee for the dropdown
        $months_query = "SELECT DISTINCT month, year FROM payroll WHERE employee_id = :employee_id ORDER BY year DESC, month DESC";
        $months_stmt = $db->prepare($months_query);
        $months_stmt->bindParam(":employee_id", $employee_id);
        $months_stmt->execute();
    } else {
        $error = "Employee record not found.";
    }
} else {
    // Redirect if no ID provided and not an employee
    header("Location: view.php");
    exit;
}
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Payslip</h2>
        </div>
        <div class="col-md-6 text-end">
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="view.php" class="btn btn-secondary">Back to Payroll</a>
            <?php else: ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" class="d-flex justify-content-end">
                    <select class="form-select me-2" name="month" style="width: auto;">
                        <?php if (isset($months_stmt) && $months_stmt->rowCount() > 0): ?>
                            <?php while ($row = $months_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?php echo $row['month']; ?>" data-year="<?php echo $row['year']; ?>" <?php echo (isset($month) && $month == $row['month'] && $year == $row['year']) ? 'selected' : ''; ?>>
                                    <?php echo date('F Y', mktime(0, 0, 0, $row['month'], 1, $row['year'])); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                    <input type="hidden" name="year" id="selected_year" value="<?php echo $year; ?>">
                    <button type="submit" class="btn btn-primary">View</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php elseif (isset($payroll)): ?>
        <div class="card">
            <div class="card-body">
                <div class="payslip">
                    <div class="payslip-header">
                        <h3>PAYSLIP</h3>
                        <h5>For the month of <?php echo date('F Y', mktime(0, 0, 0, $payroll['month'], 1, $payroll['year'])); ?></h5>
                    </div>
                    
                    <div class="payslip-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Employee Details</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <th>Employee ID:</th>
                                        <td><?php echo htmlspecialchars($payroll['emp_id']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Name:</th>
                                        <td><?php echo htmlspecialchars($payroll['first_name'] . ' ' . $payroll['last_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Position:</th>
                                        <td><?php echo htmlspecialchars($payroll['position']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Department:</th>
                                        <td><?php echo htmlspecialchars($payroll['department']); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5>Payroll Details</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <th>Payroll Period:</th>
                                        <td><?php echo date('F Y', mktime(0, 0, 0, $payroll['month'], 1, $payroll['year'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Working Days:</th>
                                        <td><?php echo $payroll['working_days']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Days Worked:</th>
                                        <td><?php echo $payroll['days_worked']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Payment Status:</th>
                                        <td>
                                            <?php if ($payroll['payment_status'] == 'paid'): ?>
                                                <span class="badge bg-success">Paid</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Unpaid</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Earnings</h5>
                                <table class="table">
                                    <tr>
                                        <th>Basic Salary</th>
                                        <td class="text-end">₹<?php echo number_format($payroll['basic_salary'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <th>HRA</th>
                                        <td class="text-end">₹<?php echo number_format($payroll['hra'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <th>DA</th>
                                        <td class="text-end">₹<?php echo number_format($payroll['da'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <th>TA</th>
                                        <td class="text-end">₹<?php echo number_format($payroll['ta'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Other Allowances</th>
                                        <td class="text-end">₹<?php echo number_format($payroll['other_allowances'], 2); ?></td>
                                    </tr>
                                    <tr class="table-primary">
                                        <th>Gross Salary</th>
                                        <td class="text-end">₹<?php echo number_format($payroll['gross_salary'], 2); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5>Deductions</h5>
                                <table class="table">
                                    <tr>
                                        <th>PF Deduction</th>
                                        <td class="text-end">₹<?php echo number_format($payroll['pf_deduction'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Tax Deduction</th>
                                        <td class="text-end">₹<?php echo number_format($payroll['tax_deduction'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Other Deductions</th>
                                        <td class="text-end">₹<?php echo number_format($payroll['other_deductions'], 2); ?></td>
                                    </tr>
                                    <tr class="table-danger">
                                        <th>Total Deductions</th>
                                        <td class="text-end">₹<?php echo number_format($payroll['total_deductions'], 2); ?></td>
                                    </tr>
                                    <tr class="table-success">
                                        <th>Net Salary</th>
                                        <td class="text-end">₹<?php echo number_format($payroll['net_salary'], 2); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="payslip-footer">
                        <p>This is a computer-generated payslip and does not require a signature.</p>
                        <button class="btn btn-primary" onclick="window.print()">Print Payslip</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    // Update year hidden field when month select changes
    document.addEventListener('DOMContentLoaded', function() {
        const monthSelect = document.querySelector('select[name="month"]');
        const yearInput = document.getElementById('selected_year');
        
        if (monthSelect) {
            monthSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const year = selectedOption.getAttribute('data-year');
                yearInput.value = year;
            });
        }
    });
</script>

<?php include_once "../includes/footer.php"; ?>
