/**
 * TUITION FEE MANAGEMENT SYSTEM
 * Organized JavaScript code for tuition fee management functionality
 */

// ========================
// UTILITY FUNCTIONS
// ========================

// Toast notification configuration
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2000,
    timerProgressBar: true,
});

// ========================
// DOM INITIALIZATION
// ========================

document.addEventListener('DOMContentLoaded', function() {
    // Initialize program selection auto-fill
    initProgramSelects();
    
    // Initialize fee calculation listeners
    initFeeCalculation();
    
    // Initialize modal event listeners
    initModalEvents();
    
    // Initialize search functionality
    initSearch();
});

// ========================
// INITIALIZATION FUNCTIONS
// ========================

function initProgramSelects() {
    // Auto-fill for First Form
    const programSelect = document.getElementById('programSelect');
    const packageSelect = document.getElementById('packageSelect');
    if (programSelect && packageSelect) {
        programSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const packageNumber = selectedOption.getAttribute('data-package');
            packageSelect.innerHTML = '';

            const option = document.createElement('option');
            option.value = packageNumber || '';
            option.textContent = packageNumber || 'Select Package Number';
            packageSelect.appendChild(option);
        });
    }

    // Auto-fill for Second Form
    const programSelect2 = document.getElementById('programSelect2');
    const packageSelect2 = document.getElementById('packageSelect2');
    if (programSelect2 && packageSelect2) {
        programSelect2.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const packageNumber = selectedOption.getAttribute('data-package');
            packageSelect2.innerHTML = '';

            const option = document.createElement('option');
            option.value = packageNumber || '';
            option.textContent = packageNumber || 'Select Package Number';
            packageSelect2.appendChild(option);
        });
    }
}

function initFeeCalculation() {
    // Add initial calculation for main form
    const feeInputs = document.querySelectorAll(".fee-input");
    feeInputs.forEach(input => {
        if (!input.readOnly) {
            input.addEventListener("input", calculateMainFormTotal);
        }
    });
    
    // Initial calculation on page load
    calculateMainFormTotal();
    
    // Add calculation for edit form
    const editInputs = [
        'edit_ojtmedical',
        'edit_tuitionfee',
        'edit_miscfee',
        'edit_systemfee',
        'edit_assessmentfee',
        'edit_uniform',
        'edit_id',
        'edit_books',
        'edit_kit'
    ];

    editInputs.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('input', calculateEditFormTotal);
        }
    });
}

function initModalEvents() {
    // Show modal button
    const showModalBtn = document.getElementById('showTuitionModal');
    if (showModalBtn) {
        showModalBtn.addEventListener('click', () => {
            document.getElementById('tuitionModal').classList.remove('hidden');
        });
    }
    
    // Save and close buttons for main form
    const saveTuitionBtn = document.getElementById('saveTuitionBtn');
    if (saveTuitionBtn) {
        saveTuitionBtn.addEventListener('click', submitTuitionForm);
    }
    
    const closeTuitionBtn = document.getElementById('closeTuitionBtn');
    if (closeTuitionBtn) {
        closeTuitionBtn.addEventListener('click', closeTuition);
    }
    
    // Attach event listener for edit form submission
    const editForm = document.getElementById('editTuitionForm');
    if (editForm) {
        $(editForm).on('submit', handleEditFormSubmit);
    }
    
    // Attach event listener for delete form submission
    const deleteForm = document.getElementById('deleteTuitionForm');
    if (deleteForm) {
        deleteForm.addEventListener('submit', handleDeleteFormSubmit);
    }
}

function initSearch() {
    // Initialize real-time search
    const searchTuition = document.getElementById('searchTuition');
    if (searchTuition) {
        searchTuition.addEventListener('input', function() {
            const query = this.value;
            fetch(`functions/searchTuitionFee.php?search=${encodeURIComponent(query)}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('tuitionTable').innerHTML = data;
                });
        });
    }
}

// ========================
// CALCULATION FUNCTIONS
// ========================

function calculateMainFormTotal() {
    const feeInputs = document.querySelectorAll(".fee-input");
    const totalDisplay = document.getElementById("totalFee");
    
    // Names of the fields to include in total
    const includeFields = [
        "tuition_fee",
        "misc_fee",
        "ojt_fee_and_medical",
        "system_fee",
        "assessment_fee",
        "uniform_scrub_and_polo_drifit",
        "id",
        "books",
        "kit"
    ];

    // Demo fields to populate
    const demoFields = [
        "demo1",
        "demo2",
        "demo3",
        "demo4"
    ];

    let total = 0;

    // Loop through inputs and sum only the included fields
    feeInputs.forEach(input => {
        const name = input.name;
        const value = parseFloat(input.value) || 0;

        if (includeFields.includes(name)) {
            total += value;
        }
    });

    // Display the total
    if (totalDisplay) {
        totalDisplay.textContent = total.toFixed(2);
    }

    // Calculate demo payment
    const demoValue = (total / 4).toFixed(2);
    demoFields.forEach(demo => {
        const demoInput = document.querySelector(`input[name="${demo}"]`);
        if (demoInput) {
            demoInput.value = demoValue;
        }
    });
}

function calculateEditFormTotal() {
    const editInputs = [
        'edit_ojtmedical',
        'edit_tuitionfee',
        'edit_miscfee',
        'edit_systemfee',
        'edit_assessmentfee',
        'edit_uniform',
        'edit_id',
        'edit_books',
        'edit_kit'
    ];
    
    let total = 0;

    editInputs.forEach(id => {
        const value = parseFloat(document.getElementById(id).value) || 0;
        total += value;
    });

    // Display total
    const totalDisplay = document.getElementById('editTotalFee');
    if (totalDisplay) {
        totalDisplay.innerText = total.toFixed(2);
    }
    
    // Divide total into 4 demos
    const divided = (total / 4).toFixed(2);
    document.getElementById('edit_demo1').value = divided;
    document.getElementById('edit_demo2').value = divided;
    document.getElementById('edit_demo3').value = divided;
    document.getElementById('edit_demo4').value = divided;
}

// ========================
// MODAL FUNCTIONS
// ========================

// Close add tuition modal
function closeTuition() {
    document.getElementById('tuitionModal').classList.add('hidden');
}

// Close edit tuition modal
function closeModaleditTuition() {
    document.getElementById('editTuitionModal').classList.add('hidden');
}

// Close delete tuition modal
function closeModalTuition2() {
    document.getElementById('deleteTuitionModal').classList.add('hidden');
}

// Open edit tuition modal
function openTuitionEdit(button) {
    // Set hidden field or input
    document.getElementById('id_tuition').value = button.getAttribute('data-program-id');

    // Set program name (assuming it's a <select>)
    let programSelect = document.getElementById('programSelect2');
    let programName = button.getAttribute('data-program-name');

    if (!Array.from(programSelect.options).some(option => option.value === programName)) {
        let newOption = new Option(programName, programName);
        programSelect.add(newOption);
    }
    programSelect.value = programName;

    // Set package number (assuming it's a <select>)
    let packageSelect = document.getElementById('packageSelect2');
    let packageNumber = button.getAttribute('data-package-number');

    if (!Array.from(packageSelect.options).some(option => option.value === packageNumber)) {
        let newOption = new Option(packageNumber, packageNumber);
        packageSelect.add(newOption);
    }
    packageSelect.value = packageNumber;

    // Set other fees
    document.getElementById('edit_ojtmedical').value = button.getAttribute('data-ojtmedical');
    document.getElementById('edit_tuitionfee').value = button.getAttribute('data-tuition-fee');
    document.getElementById('edit_miscfee').value = button.getAttribute('data-misc-fee');
    document.getElementById('edit_systemfee').value = button.getAttribute('data-system-fee');
    document.getElementById('edit_assessmentfee').value = button.getAttribute('data-assessment-fee');
    document.getElementById('edit_uniform').value = button.getAttribute('data-uniform');
    document.getElementById('edit_id').value = button.getAttribute('data-idfee');
    document.getElementById('edit_books').value = button.getAttribute('data-books');
    document.getElementById('edit_kit').value = button.getAttribute('data-kit');
    document.getElementById('edit_demo1').value = button.getAttribute('data-demo1');
    document.getElementById('edit_demo2').value = button.getAttribute('data-demo2');
    document.getElementById('edit_demo3').value = button.getAttribute('data-demo3');
    document.getElementById('edit_demo4').value = button.getAttribute('data-demo4');


    document.getElementById('reservationFee').value = button.getAttribute('data-reservation');    
    document.getElementById('editTotalFee').innerHTML = button.getAttribute('data-total');
    
    // Show the modal
    document.getElementById('editTuitionModal').classList.remove('hidden');
}

// Open delete tuition modal
function openDeleteTuition(button) {
    document.getElementById('deleteProgramID').value = button.getAttribute('data-program-id');
    document.getElementById('deleteProgramName').innerHTML = button.getAttribute('data-program-name');
    document.getElementById('deleteTuitionModal').classList.remove('hidden');
}

// ========================
// FORM SUBMISSION HANDLERS
// ========================

// Submit add tuition form
function submitTuitionForm() {
    console.log("Submit function called"); // Debug log
    
    const form = document.getElementById('addProgramForm');
    const formData = new FormData(form);

    // Add computed total to form data
    const total = document.getElementById('totalFee').textContent;
    formData.append('totalbasedtuition', total.replace(/,/g, ''));

    // Show loading indicator
    const submitBtn = document.getElementById('saveTuitionBtn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';
    }

    fetch('functions/register_tuition.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Reset button state
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Save Tuition Fee';
        }
        
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.text();
    })
    .then(data => {
        console.log('Server response:', data); // Log the actual response for debugging
        
        if (data.trim() === 'success') {
            Toast.fire({
                icon: 'success',
                title: 'Tuition fee saved successfully!'
            });
            closeTuition();
            form.reset();
            calculateMainFormTotal();
            setTimeout(() => location.reload(), 1500);
        } else {
            console.log('Server response:', data);
            Swal.fire('Error', 'There might be an issue with saving the data.', 'error');
        }
    })
    .catch(error => {
        // Reset button state on error too
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Save Tuition Fee';
        }
        
        console.error('Error:', error);
        Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
    });
}

// Handle edit tuition form submission
function handleEditFormSubmit(e) {
    e.preventDefault();

    Swal.fire({
        title: 'Update Tuition Fee?',
        text: "Are you sure you want to save the changes?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, update it!',
        cancelButtonText: 'No, cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = $(this).serialize(); // Serialize form data

            $.ajax({
                type: 'POST',
                url: 'functions/update_tuition.php',
                data: formData,
                success: function(response) {
                    Swal.fire({
                        title: 'Updated!',
                        text: 'The tuition fee has been updated.',
                        icon: 'success'
                    }).then(() => {
                        // Reload page to refresh data
                        location.reload();
                    });
                },
                error: function(xhr, status, error) {
                    Swal.fire(
                        'Error!',
                        'Something went wrong. Please try again.',
                        'error'
                    );
                }
            });
        }
    });
}

// Handle delete tuition form submission
function handleDeleteFormSubmit(e) {
    e.preventDefault();

    Swal.fire({
        title: 'Delete Program?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('id', document.getElementById('deleteProgramID').value);
            
            fetch('functions/delete_tuition.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Toast.fire({
                        icon: 'success',
                        title: 'Program deleted successfully!'
                    });
                    closeModalTuition2();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    Swal.fire('Error', data.message || 'Something went wrong.', 'error');
                }
            })
            .catch(() => {
                Swal.fire('Error', 'Failed to send request.', 'error');
            });
        }
    });
}