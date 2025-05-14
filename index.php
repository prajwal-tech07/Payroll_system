<?php
include_once "includes/header.php";
?>

<div class="jumbotron mt-4">
    <h1 class="display-4">Welcome to Payroll Management System</h1>
    <p class="lead">A simple and efficient way to manage employee payroll.</p>
    <hr class="my-4">
    
    <?php if (isset($_SESSION['user_id'])): ?>
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <p>As an administrator, you can manage employees, track attendance, and generate payroll.</p>
            <a class="btn btn-primary btn-lg" href="admin/dashboard.php" role="button">Go to Dashboard</a>
        <?php else: ?>
            <p>As an employee, you can view your attendance and payslips.</p>
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">My Attendance</h5>
                            <p class="card-text">View and record your daily attendance.</p>
                            <a href="attendance/record.php" class="btn btn-primary">Go to Attendance</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">My Payslips</h5>
                            <p class="card-text">View and download your payslips.</p>
                            <a href="payroll/payslip.php" class="btn btn-primary">View Payslips</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <p>Please login to access the system.</p>
        <a class="btn btn-primary btn-lg" href="auth/login.php" role="button">Login</a>
    <?php endif; ?>
</div>

<?php
include_once "includes/footer.php";
?>
