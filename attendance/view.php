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

// Get all employees for filter dropdown
$employees_query = "SELECT id, employee_id, first_name, last_name FROM employees ORDER BY first_name, last_name";
$employees_stmt = $db->prepare($employees_query);
$employees_stmt->execute();

// Build query based on filters
$query = "SELECT a.*, e.employee_id as emp_id, e.first_name, e.last_name 
          FROM attendance a 
          JOIN employees e ON a.employee_id = e.id 
          WHERE 1=1";

$params = array();

if (!empty($employee_id)) {
    $query .= " AND a.employee_id = :employee_id";
    $params[':employee_id'] = $employee_id;
}

if (!empty($month) && !empty($year)) {
    $query .= " AND MONTH(a.date) = :month AND YEAR(a.date) = :year";
    $params[':month'] = $month;
    $params[':year'] = $year;
}

$query .= " ORDER BY a.date DESC, e.first_name, e.last_name";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Attendance Records</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="record.php" class="btn btn-primary">Record Attendance</a>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5>Filter Attendance</h5>
        </div>
        <div class="card-body">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" class="row g-3">
                <div class="col-md-4">
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
                <div class="col-md-3">
                    <label for="month" class="form-label">Month</label>
                    <select class="form-select" id="month" name="month">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>" <?php echo ($month == str_pad($i, 2, '0', STR_PAD_LEFT)) ? 'selected' : ''; ?>>
                                <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="year" class="form-label">Year</label>
                    <select class="form-select" id="year" name="year">
                        <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($year == $i) ? 'selected' : ''; ?>>
                                <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
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
            <h5>Attendance List</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Employee ID</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($stmt->rowCount() > 0): ?>
                            <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($row['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['emp_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                    <td>
                                        <?php 
                                        switch($row['status']) {
                                            case 'present':
                                                echo '<span class="badge bg-success">Present</span>';
                                                break;
                                            case 'absent':
                                                echo '<span class="badge bg-danger">Absent</span>';
                                                break;
                                            case 'half-day':
                                                echo '<span class="badge bg-warning">Half Day</span>';
                                                break;
                                            case 'leave':
                                                echo '<span class="badge bg-info">Leave</span>';
                                                break;
                                            default:
                                                echo '<span class="badge bg-secondary">Unknown</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo $row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : '-'; ?></td>
                                    <td><?php echo $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : '-'; ?></td>
                                    <td>
                                        <a href="record.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No attendance records found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?>
