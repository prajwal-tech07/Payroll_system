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

// Get all employees
$employees_query = "SELECT id, employee_id, first_name, last_name FROM employees ORDER BY first_name, last_name";
$employees_stmt = $db->prepare($employees_query);
$employees_stmt->execute();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $employee_id = $_POST['employee_id'];
    $month = $_POST['month'];
    $year = $_POST['year'];
    $working_days = $_POST['working_days'];
    $days_worked = $_POST['days_worked'];
    $basic_salary = $_POST['basic_salary'];
    $hra = $_POST['hra'];
    $da = $_POST['da'];
    $ta = $_POST['ta'];
    $other_allowances = $_POST['other_allowances'];
    $gross_salary = $_POST['gross_salary'];
    $pf_deduction = $_POST['pf_deduction'];
    $tax_deduction = $_POST['tax_deduction'];
    $other_deductions = $_POST['other_deductions'];
    $total_deductions = $_POST['total_deductions'];
    $net_salary = $_POST['net_salary'];
    
    // Check if payroll already exists for this employee for the selected month and year
    $check_query = "SELECT id FROM payroll WHERE employee_id = :employee_id AND month = :month AND year = :year";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":employee_id", $employee_id);
    $check_stmt->bindParam(":month", $month);
    $check_stmt->bindParam(":year", $year);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        // Update existing payroll
        $payroll_id = $check_stmt->fetch(PDO::FETCH_ASSOC)['id'];
        
        $update_query = "UPDATE payroll SET 
                        working_days = :working_days,
                        days_worked = :days_worked,
                        basic_salary = :basic_salary,
                        hra = :hra,
                        da = :da,
                        ta = :ta,
                        other_allowances = :other_allowances,
                        gross_salary = :gross_salary,
                        pf_deduction = :pf_deduction,
                        tax_deduction = :tax_deduction,
                        other_deductions = :other_deductions,
                        total_deductions = :total_deductions,
                        net_salary = :net_salary
                        WHERE id = :id";
                        
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(":working_days", $working_days);
        $update_stmt->bindParam(":days_worked", $days_worked);
        $update_stmt->bindParam(":basic_salary", $basic_salary);
        $update_stmt->bindParam(":hra", $hra);
        $update_stmt->bindParam(":da", $da);
        $update_stmt->bindParam(":ta", $ta);
        $update_stmt->bindParam(":other_allowances", $other_allowances);
        $update_stmt->bindParam(":gross_salary", $gross_salary);
        $update_stmt->bindParam(":pf_deduction", $pf_deduction);
        $update_stmt->bindParam(":tax_deduction", $tax_deduction);
        $update_stmt->bindParam(":other_deductions", $other_deductions);
        $update_stmt->bindParam(":total_deductions", $total_deductions);
        $update_stmt->bindParam(":net_salary", $net_salary);
        $update_stmt->bindParam(":id", $payroll_id);
        
        if ($update_stmt->execute()) {
            $success = "Payroll updated successfully";
        } else {
            $error = "Error updating payroll";
        }
    } else {
        // Insert new payroll
        $insert_query = "INSERT INTO payroll (
                        employee_id, month, year, working_days, days_worked, 
                        basic_salary, hra, da, ta, other_allowances, 
                        gross_salary, pf_deduction, tax_deduction, other_deductions, 
                        total_deductions, net_salary
                        ) VALUES (
                        :employee_id, :month, :year, :working_days, :days_worked,
                        :basic_salary, :hra, :da, :ta, :other_allowances,
                        :gross_salary, :pf_deduction, :tax_deduction, :other_deductions,
                        :total_deductions, :net_salary
                        )";
                        
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindParam(":employee_id", $employee_id);
        $insert_stmt->bindParam(":month", $month);
        $insert_stmt->bindParam(":year", $year);
        $insert_stmt->bindParam(":working_days", $working_days);
        $insert_stmt->bindParam(":days_worked", $days_worked);
        $insert_stmt->bindParam(":basic_salary", $basic_salary);
        $insert_stmt->bindParam(":hra", $hra);
        $insert_stmt->bindParam(":da", $da);
        $insert_stmt->bindParam(":ta", $ta);
        $insert_stmt->bindParam(":other_allowances", $other_allowances);
        $insert_stmt->bindParam(":gross_salary", $gross_salary);
        $insert_stmt->bindParam(":pf_deduction", $pf_deduction);
        $insert_stmt->bindParam(":tax_deduction", $tax_deduction);
        $insert_stmt->bindParam(":other_deductions", $other_deductions);
        $insert_stmt->bindParam(":total_deductions", $total_deductions);
        $insert_stmt->bindParam(":net_salary", $net_salary);
        
        if ($insert_stmt->execute()) {
            $success = "Payroll generated successfully";
        } else {
            $error = "Error generating payroll";
        }
    }
}

// Get employee details when selected
if (isset($_GET['employee_id']) && !empty($_GET['employee_id'])) {
    $emp_id = $_GET['employee_id'];
    $month = isset($_GET['month']) ? $_GET['month'] : date('m');
    $year = isset($_GET['year']) ? $_GET['year'] : date('Y');
    
    // Get employee details
    $emp_query = "SELECT e.*, ss.hra_percentage, ss.da_percentage, ss.ta_amount, ss.other_allowances, 
                 ss.pf_percentage, ss.tax_percentage, ss.other_deductions
                 FROM employees e
                 LEFT JOIN salary_structure ss ON e.id = ss.employee_id
                 WHERE e.id = :id";
    $emp_stmt = $db->prepare($emp_query);
    $emp_stmt->bindParam(":id", $emp_id);
    $emp_stmt->execute();
    
    if ($emp_stmt->rowCount() > 0) {
        $employee = $emp_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate working days in the month
        $working_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        
        // Get attendance for the month
        $att_query = "SELECT COUNT(*) as days_worked FROM attendance 
                     WHERE employee_id = :employee_id 
                     AND MONTH(date) = :month 
                     AND YEAR(date) = :year 
                     AND status IN ('present', 'half-day')";
        $att_stmt = $db->prepare($att_query);
        $att_stmt->bindParam(":employee_id", $emp_id);
        $att_stmt->bindParam(":month", $month);
        $att_stmt->bindParam(":year", $year);
        $att_stmt->execute();
        
        $days_worked = $att_stmt->fetch(PDO::FETCH_ASSOC)['days_worked'];
        
        // Check if payroll already exists
        $check_query = "SELECT * FROM payroll 
                       WHERE employee_id = :employee_id 
                       AND month = :month 
                       AND year = :year";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(":employee_id", $emp_id);
        $check_stmt->bindParam(":month", $month);
        $check_stmt->bindParam(":year", $year);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            // Use existing payroll data
            $payroll = $check_stmt->fetch(PDO::FETCH_ASSOC);
            $working_days = $payroll['working_days'];
            $days_worked = $payroll['days_worked'];
            $basic_salary = $payroll['basic_salary'];
            $hra = $payroll['hra'];
            $da = $payroll['da'];
            $ta = $payroll['ta'];
            $other_allowances = $payroll['other_allowances'];
            $gross_salary = $payroll['gross_salary'];
            $pf_deduction = $payroll['pf_deduction'];
            $tax_deduction = $payroll['tax_deduction'];
            $other_deductions = $payroll['other_deductions'];
            $total_deductions = $payroll['total_deductions'];
            $net_salary = $payroll['net_salary'];
        } else {
            // Calculate salary components
            $basic_salary = $employee['basic_salary'];
            $hra = ($employee['hra_percentage'] / 100) * $basic_salary;
            $da = ($employee['da_percentage'] / 100) * $basic_salary;
            $ta = $employee['ta_amount'];
            $other_allowances = $employee['other_allowances'];
            
            $gross_salary = $basic_salary + $hra + $da + $ta + $other_allowances;
            
            $pf_deduction = ($employee['pf_percentage'] / 100) * $basic_salary;
            $tax_deduction = ($employee['tax_percentage'] / 100) * $gross_salary;
            $other_deductions = $employee['other_deductions'];
            
            $total_deductions = $pf_deduction + $tax_deduction + $other_deductions;
            $net_salary = $gross_salary - $total_deductions;
        }
    }
}
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Generate Payroll</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="view.php" class="btn btn-secondary">View All Payroll</a>
        </div>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5>Select Employee and Month</h5>
        </div>
        <div class="card-body">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" class="row g-3">
                <div class="col-md-4">
                    <label for="employee_id" class="form-label">Employee</label>
                    <select class="form-select" id="employee_id" name="employee_id" required>
                        <option value="">Select Employee</option>
                        <?php 
                        // Reset the pointer of employees_stmt
                        $employees_stmt->execute();
                        while ($employee = $employees_stmt->fetch(PDO::FETCH_ASSOC)): 
                        ?>
                            <option value="<?php echo $employee['id']; ?>" <?php echo (isset($emp_id) && $emp_id == $employee['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($employee['employee_id'] . ' - ' . $employee['first_name'] . ' ' . $employee['last_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="month" class="form-label">Month</label>
                    <select class="form-select" id="month" name="month">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>" <?php echo (isset($month) && $month == str_pad($i, 2, '0', STR_PAD_LEFT)) ? 'selected' : ''; ?>>
                                <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="year" class="form-label">Year</label>
                    <select class="form-select" id="year" name="year">
                        <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                            <option value="<?php echo $i; ?>" <?php echo (isset($year) && $year == $i) ? 'selected' : ''; ?>>
                                <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Get Details</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php if (isset($employee)): ?>
    <div class="card">
        <div class="card-header">
            <h5>Generate Payroll for <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h5>
            <h6 class="text-muted">Month: <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></h6>
        </div>
        <div class="card-body">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="needs-validation" novalidate>
                <input type="hidden" name="employee_id" value="<?php echo $emp_id; ?>">
                <input type="hidden" name="month" value="<?php echo $month; ?>">
                <input type="hidden" name="year" value="<?php echo $year; ?>">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="working_days" class="form-label">Working Days</label>
                        <input type="number" class="form-control" id="working_days" name="working_days" value="<?php echo $working_days; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="days_worked" class="form-label">Days Worked</label>
                        <input type="number" class="form-control" id="days_worked" name="days_worked" value="<?php echo $days_worked; ?>" required>
                    </div>
                </div>
                
                <h5 class="mt-4 mb-3">Earnings</h5>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="basic_salary" class="form-label">Basic Salary</label>
                        <input type="number" step="0.01" class="form-control" id="basic_salary" name="basic_salary" value="<?php echo $basic_salary; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="hra" class="form-label">HRA</label>
                        <input type="number" step="0.01" class="form-control" id="hra" name="hra" value="<?php echo $hra; ?>">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="da" class="form-label">DA</label>
                        <input type="number" step="0.01" class="form-control" id="da" name="da" value="<?php echo $da; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="ta" class="form-label">TA</label>
                        <input type="number" step="0.01" class="form-control" id="ta" name="ta" value="<?php echo $ta; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="other_allowances" class="form-label">Other Allowances</label>
                        <input type="number" step="0.01" class="form-control" id="other_allowances" name="other_allowances" value="<?php echo $other_allowances; ?>">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="gross_salary" class="form-label">Gross Salary</label>
                        <input type="number" step="0.01" class="form-control" id="gross_salary" name="gross_salary" value="<?php echo $gross_salary; ?>" readonly>
                    </div>
                </div>
                
                <h5 class="mt-4 mb-3">Deductions</h5>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="pf_deduction" class="form-label">PF Deduction</label>
                        <input type="number" step="0.01" class="form-control" id="pf_deduction" name="pf_deduction" value="<?php echo $pf_deduction; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="tax_deduction" class="form-label">Tax Deduction</label>
                        <input type="number" step="0.01" class="form-control" id="tax_deduction" name="tax_deduction" value="<?php echo $tax_deduction; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="other_deductions" class="form-label">Other Deductions</label>
                        <input type="number" step="0.01" class="form-control" id="other_deductions" name="other_deductions" value="<?php echo $other_deductions; ?>">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="total_deductions" class="form-label">Total Deductions</label>
                        <input type="number" step="0.01" class="form-control" id="total_deductions" name="total_deductions" value="<?php echo $total_deductions; ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="net_salary" class="form-label">Net Salary</label>
                        <input type="number" step="0.01" class="form-control" id="net_salary" name="net_salary" value="<?php echo $net_salary; ?>" readonly>
                    </div>
                </div>
                
                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">Generate Payroll</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include_once "../includes/footer.php"; ?>
