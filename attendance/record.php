<?php
require_once "../config/database.php";
include_once "../includes/header.php";

$database = new Database();
$db = $database->getConnection();

$error = "";
$success = "";

// Get current date
$current_date = date('Y-m-d');

// If user is an employee, get their employee record
if ($_SESSION['role'] === 'employee') {
    $user_id = $_SESSION['user_id'];
    
    $query = "SELECT id, first_name, last_name FROM employees WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        $employee_id = $employee['id'];
        
        // Check if attendance already recorded for today
        $check_query = "SELECT * FROM attendance WHERE employee_id = :employee_id AND date = :date";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(":employee_id", $employee_id);
        $check_stmt->bindParam(":date", $current_date);
        $check_stmt->execute();
        
        $attendance_recorded = ($check_stmt->rowCount() > 0);
        if ($attendance_recorded) {
            $attendance = $check_stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Handle attendance submission
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $status = $_POST['status'];
            $time_in = !empty($_POST['time_in']) ? $_POST['time_in'] : null;
            $time_out = !empty($_POST['time_out']) ? $_POST['time_out'] : null;
            
            if ($attendance_recorded) {
                // Update existing attendance
                $update_query = "UPDATE attendance SET status = :status, time_in = :time_in, time_out = :time_out WHERE id = :id";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(":status", $status);
                $update_stmt->bindParam(":time_in", $time_in);
                $update_stmt->bindParam(":time_out", $time_out);
                $update_stmt->bindParam(":id", $attendance['id']);
                
                if ($update_stmt->execute()) {
                    $success = "Attendance updated successfully";
                    // Refresh attendance data
                    $check_stmt->execute();
                    $attendance = $check_stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error = "Error updating attendance";
                }
            } else {
                // Insert new attendance
                $insert_query = "INSERT INTO attendance (employee_id, date, time_in, time_out, status) 
                                VALUES (:employee_id, :date, :time_in, :time_out, :status)";
                $insert_stmt = $db->prepare($insert_query);
                $insert_stmt->bindParam(":employee_id", $employee_id);
                $insert_stmt->bindParam(":date", $current_date);
                $insert_stmt->bindParam(":time_in", $time_in);
                $insert_stmt->bindParam(":time_out", $time_out);
                $insert_stmt->bindParam(":status", $status);
                
                if ($insert_stmt->execute()) {
                    $success = "Attendance recorded successfully";
                    $attendance_recorded = true;
                    // Get the new attendance record
                    $check_stmt->execute();
                    $attendance = $check_stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error = "Error recording attendance";
                }
            }
        }
    } else {
        $error = "Employee record not found";
    }
}

// If user is admin, show form to record attendance for any employee
if ($_SESSION['role'] === 'admin') {
    // Get all employees
    $employees_query = "SELECT id, employee_id, first_name, last_name FROM employees ORDER BY first_name, last_name";
    $employees_stmt = $db->prepare($employees_query);
    $employees_stmt->execute();
    
    // Handle attendance submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $employee_id = $_POST['employee_id'];
        $date = $_POST['date'];
        $status = $_POST['status'];
        $time_in = !empty($_POST['time_in']) ? $_POST['time_in'] : null;
        $time_out = !empty($_POST['time_out']) ? $_POST['time_out'] : null;
        
        // Check if attendance already recorded for this date
        $check_query = "SELECT id FROM attendance WHERE employee_id = :employee_id AND date = :date";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(":employee_id", $employee_id);
        $check_stmt->bindParam(":date", $date);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $attendance_id = $check_stmt->fetch(PDO::FETCH_ASSOC)['id'];
            
            // Update existing attendance
            $update_query = "UPDATE attendance SET status = :status, time_in = :time_in, time_out = :time_out WHERE id = :id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(":status", $status);
            $update_stmt->bindParam(":time_in", $time_in);
            $update_stmt->bindParam(":time_out", $time_out);
            $update_stmt->bindParam(":id", $attendance_id);
            
            if ($update_stmt->execute()) {
                $success = "Attendance updated successfully";
            } else {
                $error = "Error updating attendance";
            }
        } else {
            // Insert new attendance
            $insert_query = "INSERT INTO attendance (employee_id, date, time_in, time_out, status) 
                            VALUES (:employee_id, :date, :time_in, :time_out, :status)";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(":employee_id", $employee_id);
            $insert_stmt->bindParam(":date", $date);
            $insert_stmt->bindParam(":time_in", $time_in);
            $insert_stmt->bindParam(":time_out", $time_out);
            $insert_stmt->bindParam(":status", $status);
            
            if ($insert_stmt->execute()) {
                $success = "Attendance recorded successfully";
            } else {
                $error = "Error recording attendance";
            }
        }
    }
}
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Attendance Management</h2>
        </div>
        <div class="col-md-6 text-end">
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="view.php" class="btn btn-secondary">View All Attendance</a>
            <?php endif; ?>
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
            <h5>Record Attendance</h5>
        </div>
        <div class="card-body">
            <?php if ($_SESSION['role'] === 'employee'): ?>
                <?php if (isset($employee)): ?>
                    <div class="mb-4">
                        <h6>Employee: <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h6>
                        <h6>Date: <?php echo date('d F Y', strtotime($current_date)); ?></h6>
                    </div>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="needs-validation" novalidate>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="present" <?php echo (isset($attendance) && $attendance['status'] === 'present') ? 'selected' : ''; ?>>Present</option>
                                    <option value="absent" <?php echo (isset($attendance) && $attendance['status'] === 'absent') ? 'selected' : ''; ?>>Absent</option>
                                    <option value="half-day" <?php echo (isset($attendance) && $attendance['status'] === 'half-day') ? 'selected' : ''; ?>>Half Day</option>
                                    <option value="leave" <?php echo (isset($attendance) && $attendance['status'] === 'leave') ? 'selected' : ''; ?>>Leave</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="time_in" class="form-label">Time In</label>
                                <input type="time" class="form-control" id="time_in" name="time_in" value="<?php echo isset($attendance) ? $attendance['time_in'] : ''; ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="time_out" class="form-label">Time Out</label>
                                <input type="time" class="form-control" id="time_out" name="time_out" value="<?php echo isset($attendance) ? $attendance['time_out'] : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary"><?php echo $attendance_recorded ? 'Update Attendance' : 'Record Attendance'; ?></button>
                        </div>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="needs-validation" novalidate>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="employee_id" class="form-label">Employee *</label>
                            <select class="form-select" id="employee_id" name="employee_id" required>
                                <option value="">Select Employee</option>
                                <?php while ($employee = $employees_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                    <option value="<?php echo $employee['id']; ?>">
                                        <?php echo htmlspecialchars($employee['employee_id'] . ' - ' . $employee['first_name'] . ' ' . $employee['last_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <div class="invalid-feedback">Please select an employee.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="date" class="form-label">Date *</label>
                            <input type="date" class="form-control" id="date" name="date" value="<?php echo $current_date; ?>" required>
                            <div class="invalid-feedback">Please select a date.</div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status *</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="present">Present</option>
                                <option value="absent">Absent</option>
                                <option value="half-day">Half Day</option>
                                <option value="leave">Leave</option>
                            </select>
                            <div class="invalid-feedback">Please select a status.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="time_in" class="form-label">Time In</label>
                            <input type="time" class="form-control" id="time_in" name="time_in">
                        </div>
                        <div class="col-md-4">
                            <label for="time_out" class="form-label">Time Out</label>
                            <input type="time" class="form-control" id="time_out" name="time_out">
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Record Attendance</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?>
