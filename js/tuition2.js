    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true,
    });

    function closeModaleditTuition() {
        document.getElementById('editTuitionModal').classList.add('hidden');
    }

    function openTuitionEdit(button) {
        // Set hidden field or input
        document.getElementById('id_tuition').value = button.getAttribute('data-program-id'); // Set correct row id
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
        document.getElementById('editTotalFee').innerHTML = button.getAttribute('data-total');
        document.getElementById('editTuitionModal').classList.remove('hidden');

    }

    const inputs = [
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

    inputs.forEach(id => {
        document.getElementById(id).addEventListener('input', calculateTotal);
    });

    function calculateTotal() {
        let total = 0;

        inputs.forEach(id => {
            const value = parseFloat(document.getElementById(id).value) || 0;
            total += value;
        });

        // Display total
        document.getElementById('editTotalFee').innerText = total.toFixed(2);
        
        
        // Divide total into 4 demos
        const divided = (total / 4).toFixed(2);
        document.getElementById('edit_demo1').value = divided;
        document.getElementById('edit_demo2').value = divided;
        document.getElementById('edit_demo3').value = divided;
        document.getElementById('edit_demo4').value = divided;
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Save & Close buttons
        document.getElementById('saveTuitionBtn').addEventListener('click', submitTuitionForm);
        document.getElementById('closeTuitionBtn').addEventListener('click', closeTuition);

        // Auto-fill for First Form
        const programSelect = document.getElementById('programSelect');
        const packageSelect = document.getElementById('packageSelect');
        if (programSelect && packageSelect) {
            programSelect.addEventListener('change', function () {
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
            programSelect2.addEventListener('change', function () {
                const selectedOption = this.options[this.selectedIndex];
                const packageNumber = selectedOption.getAttribute('data-package');
                packageSelect2.innerHTML = '';

                const option = document.createElement('option');
                option.value = packageNumber || '';
                option.textContent = packageNumber || 'Select Package Number';
                packageSelect2.appendChild(option);
            });
        }

        // Auto-calculate Total
        document.querySelectorAll('.fee-input').forEach(input => {
            input.addEventListener('input', calculateTotal);
        });
    });

  function closeModalTuition2() {
        document.getElementById('deleteTuitionModal').classList.add('hidden');
    }

    function openDeleteTuition(button) {
        document.getElementById('deleteProgramID').value = button.getAttribute('data-program-id');
        document.getElementById('deleteProgramName').innerHTML = button.getAttribute('data-program-name');
        document.getElementById('deleteTuitionModal').classList.remove('hidden');
    }

    document.getElementById('deleteTuitionForm').addEventListener('submit', function (e) {
        e.preventDefault();

        Swal.fire({
            title: 'Save changes?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, save it!',
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
                            closeModal();
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
    });

    $('#editTuitionForm').on('submit', function (e) {
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
                    url: 'functions/update_tuition.php', // Change this to your actual PHP handler
                    data: formData,
                    success: function (response) {
                        Swal.fire({
                            title: 'Updated!',
                            text: 'The tuition fee has been updated.',
                            icon: 'success'
                        }).then(() => {
                            // Optional: reload page or refresh table
                            location.reload();
                        });
                    },
                    error: function (xhr, status, error) {
                        Swal.fire(
                            'Error!',
                            'Something went wrong. Please try again.',
                            'error'
                        );
                    }
                });
            }
        });
    });

    // for realtime search
   const searchTuition = document.getElementById('searchTuition');
    searchTuition.addEventListener('input', function () {
        const query = this.value;

        fetch(`functions/searchTuitionFee.php?search=${encodeURIComponent(query)}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('tuitionTable').innerHTML = data;
            });
   });