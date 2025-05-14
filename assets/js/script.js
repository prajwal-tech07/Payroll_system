document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Confirm delete
    const deleteButtons = document.querySelectorAll('.delete-btn');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });
    
    // Calculate salary in payroll generation
    const basicSalaryInput = document.getElementById('basic_salary');
    const hraInput = document.getElementById('hra');
    const daInput = document.getElementById('da');
    const taInput = document.getElementById('ta');
    const otherAllowancesInput = document.getElementById('other_allowances');
    const pfDeductionInput = document.getElementById('pf_deduction');
    const taxDeductionInput = document.getElementById('tax_deduction');
    const otherDeductionsInput = document.getElementById('other_deductions');
    const grossSalaryInput = document.getElementById('gross_salary');
    const totalDeductionsInput = document.getElementById('total_deductions');
    const netSalaryInput = document.getElementById('net_salary');
    
    const calculateSalary = function() {
        if (!basicSalaryInput) return;
        
        const basicSalary = parseFloat(basicSalaryInput.value) || 0;
        const hra = parseFloat(hraInput.value) || 0;
        const da = parseFloat(daInput.value) || 0;
        const ta = parseFloat(taInput.value) || 0;
        const otherAllowances = parseFloat(otherAllowancesInput.value) || 0;
        const pfDeduction = parseFloat(pfDeductionInput.value) || 0;
        const taxDeduction = parseFloat(taxDeductionInput.value) || 0;
        const otherDeductions = parseFloat(otherDeductionsInput.value) || 0;
        
        const grossSalary = basicSalary + hra + da + ta + otherAllowances;
        const totalDeductions = pfDeduction + taxDeduction + otherDeductions;
        const netSalary = grossSalary - totalDeductions;
        
        grossSalaryInput.value = grossSalary.toFixed(2);
        totalDeductionsInput.value = totalDeductions.toFixed(2);
        netSalaryInput.value = netSalary.toFixed(2);
    };
    
    if (basicSalaryInput) {
        const salaryInputs = [
            basicSalaryInput, hraInput, daInput, taInput, otherAllowancesInput,
            pfDeductionInput, taxDeductionInput, otherDeductionsInput
        ];
        
        salaryInputs.forEach(input => {
            input.addEventListener('input', calculateSalary);
        });
    }
});
