//--------------------------------------------------
//This Script is for insert modal
//--------------------------------------------------
document.getElementById('entryDate').valueAsDate = new Date();


// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Set today's date in the entry date field
    document.getElementById('entryDate').valueAsDate = new Date();
    
    // Get the form element
    const registrationForm = document.getElementById('registrationForm');
    
    // Add CSS for error highlights and tooltips
    const style = document.createElement('style');
    style.textContent = `
        .border-red-500 {
            border: 1px solid #f56565 !important;
        }
        
        .error-tooltip {
            position: absolute;
            background-color: #f56565;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            z-index: 50;
            white-space: nowrap;
            pointer-events: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .error-tooltip::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 10px;
            width: 0;
            height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-top: 5px solid #f56565;
        }
        
        .field-error-message {
            color: #f56565;
            font-size: 12px;
            margin-top: 4px;
            display: block;
        }
    `;
    document.head.appendChild(style);
    
    // Get the "Same as above" checkbox for emergency contact address
    const sameAsAboveCheckbox = document.getElementById('sameAsAbove');
    
    // Handle the "Same as above" checkbox for emergency contact address
    sameAsAboveCheckbox.addEventListener('change', function() {
        if (this.checked) {
            // Copy current address values to emergency contact fields
            document.getElementById('emergencyRegion').value = document.getElementById('region').value;
            document.getElementById('emergencyProvince').value = document.getElementById('province').value;
            document.getElementById('emergencyCity').value = document.getElementById('city').value;
            document.getElementById('emergencyBrgy').value = document.getElementById('barangay').value;
            document.getElementById('emergencyPurok').value = document.getElementById('purok').value;
            
            // Copy the hidden field values as well
            document.getElementById('emergency-region-name').value = document.getElementById('current-region-name').value;
            document.getElementById('emergency-province-name').value = document.getElementById('current-province-name').value;
            document.getElementById('emergency-city-name').value = document.getElementById('current-city-name').value;
            document.getElementById('emergency-barangay-name').value = document.getElementById('current-barangay-name').value;
            
            // Disable emergency address fields
            toggleEmergencyAddressFields(true);
        } else {
            // Enable emergency address fields
            toggleEmergencyAddressFields(false);
        }
    });
    
    // Function to toggle emergency address fields
    function toggleEmergencyAddressFields(disabled) {
        document.getElementById('emergencyRegion').disabled = disabled;
        document.getElementById('emergencyProvince').disabled = disabled;
        document.getElementById('emergencyCity').disabled = disabled;
        document.getElementById('emergencyBrgy').disabled = disabled;
        document.getElementById('emergencyPurok').disabled = disabled;
    }
    
    // Function to validate email format
    function validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Function to validate phone number format (11 digits for Philippines)
    function validatePhoneNumber(phone) {
        const phoneRegex = /^(09|\+639)\d{9}$/;
        return phoneRegex.test(phone);
    }
    
    // Function to check if a select element has a selected value
    function hasSelectedValue(selectElement) {
        return selectElement.selectedIndex > 0;
    }
    
    // Function to validate the entire form
    function validateForm() {
        let isValid = true;
        let firstInvalidField = null;
        let errorMessages = [];
        
        // Clear all previous error states
        clearAllFieldErrors();
        
    
    
        
        // Validate file upload if required
        const photoUpload = document.getElementById('photoUpload');
        if (photoUpload && photoUpload.required && photoUpload.files.length === 0) {
            isValid = false;
            photoUpload.classList.add('border-red-500');
            errorMessages.push("Please upload your ID photo");
            if (!firstInvalidField) firstInvalidField = photoUpload;
        } else if (photoUpload) {
            photoUpload.classList.remove('border-red-500');
        }
        
        // Validate radio button selection for referral
        const referralRadios = document.querySelectorAll('input[name="referral"]');
        let referralSelected = false;
        referralRadios.forEach(radio => {
            if (radio.checked) {
                referralSelected = true;
            }
        });
        
        if (!referralSelected) {
            isValid = false;
            errorMessages.push("Please select a referral option");
            if (!firstInvalidField) firstInvalidField = referralRadios[0];
        }
        
        // If the "Other" referral option is selected, validate the text field
        const referralOther = document.getElementById('referralOther');
        const otherReferral = document.getElementById('otherReferral');
        if (referralOther && referralOther.checked && otherReferral.value.trim() === '') {
            isValid = false;
            otherReferral.classList.add('border-red-500');
            errorMessages.push("Please specify the other referral source");
            if (!firstInvalidField) firstInvalidField = otherReferral;
        } else if (otherReferral) {
            otherReferral.classList.remove('border-red-500');
        }
        
        // Scroll to the first invalid field
        if (firstInvalidField) {
            firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstInvalidField.focus();
            
            // Add error message tooltip to the first invalid field
            addErrorTooltip(firstInvalidField, errorMessages[0]);
        }
        
        // Store error messages for display
        window.formErrorMessages = errorMessages;
        
        return isValid;
    }
    
    // Function to get the field label for error messages
    function getFieldLabel(inputElement) {
        // First try to find an associated label
        const id = inputElement.id;
        const label = document.querySelector(`label[for="${id}"]`);
        
        if (label && label.textContent) {
            // Return the trimmed text content of the label
            return label.textContent.trim().replace('*', '').replace(':', '');
        }
        
        // If no label is found, use the name attribute or id as fallback
        return inputElement.name || inputElement.id || "Field";
    }
    
    // Function to add an error tooltip to an element
    function addErrorTooltip(element, message) {
        // Remove any existing tooltips
        removeErrorTooltips();
        
        // Create tooltip element
        const tooltip = document.createElement('div');
        tooltip.className = 'error-tooltip absolute bg-red-500 text-white text-sm rounded py-1 px-2 z-10';
        tooltip.style.top = '-25px';
        tooltip.style.left = '0';
        tooltip.textContent = message;
        tooltip.id = 'error-tooltip';
        
        // Position the element's parent as relative if it's not already
        if (element.parentElement.style.position !== 'relative') {
            element.parentElement.style.position = 'relative';
        }
        
        // Add the tooltip to the DOM
        element.parentElement.appendChild(tooltip);
        
        // Remove the tooltip after 5 seconds
        setTimeout(removeErrorTooltips, 5000);
    }
    
    // Function to remove all error tooltips
    function removeErrorTooltips() {
        const tooltips = document.querySelectorAll('.error-tooltip');
        tooltips.forEach(tooltip => tooltip.remove());
    }
    
    // Function to add error message below a field
    function addFieldErrorMessage(element, message) {
        // Remove any existing error message
        const parentElement = element.parentElement;
        const existingError = parentElement.querySelector('.field-error-message');
        if (existingError) {
            existingError.remove();
        }
        
        // Create error message element
        const errorMessage = document.createElement('span');
        errorMessage.className = 'field-error-message';
        errorMessage.textContent = message;
        
        // Insert after the input element
        element.insertAdjacentElement('afterend', errorMessage);
    }
    
    // Function to clear all field error messages
    function clearAllFieldErrors() {
        const errorMessages = document.querySelectorAll('.field-error-message');
        errorMessages.forEach(message => message.remove());
        
        const errorFields = document.querySelectorAll('.border-red-500');
        errorFields.forEach(field => field.classList.remove('border-red-500'));
        
        removeErrorTooltips();
    }
    
    // Handle form submission
    registrationForm.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent the default form submission
        
        // Form validation
        if (!validateForm()) {
            // Get the error messages
            const errorMessages = window.formErrorMessages;
            
            // Format error messages for display
            const formattedErrors = errorMessages.length > 5 
                ? errorMessages.slice(0, 5).join('<br>') + '<br>...' + (errorMessages.length - 5) + ' more errors'
                : errorMessages.join('<br>');
            
            // Show error message using SweetAlert2 (assuming it's included in your page)
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    html: formattedErrors,
                    confirmButtonText: 'Fix Errors'
                });
            } else {
                alert('Please fix the following errors:\n\n' + errorMessages.join('\n'));
            }
            return;
        }
        
        // If checkbox is checked, ensure we copy the values again before submission
        if (sameAsAboveCheckbox.checked) {
            document.getElementById('emergency-region-name').value = document.getElementById('current-region-name').value;
            document.getElementById('emergency-province-name').value = document.getElementById('current-province-name').value;
            document.getElementById('emergency-city-name').value = document.getElementById('current-city-name').value;
            document.getElementById('emergency-barangay-name').value = document.getElementById('current-barangay-name').value;
        }
        
        // Show confirmation dialog using SweetAlert2
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Confirm Registration',
                text: 'Are you sure you want to submit this registration?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, submit it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Submit the form
                    submitForm(this);
                }
            });
        } else {
            // Fallback to standard confirmation if SweetAlert2 is not available
            if (confirm('Are you sure you want to submit this registration?')) {
                submitForm(this);
            }
        }
    });
    
    // Function to handle the actual form submission
function submitForm(form) {
    // Show loading indicator
    let submitButton = form.querySelector('button[type="submit"]');
    let originalButtonText = submitButton ? submitButton.innerHTML : 'Submit';
    if(submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
    }
    
    // Enable all disabled fields before submission so their values get included
    const disabledFields = form.querySelectorAll('select:disabled, input:disabled');
    disabledFields.forEach(field => {
        field.disabled = false;
    });
    
    // DEBUG: Check what requirements are actually selected before FormData
    const requirementCheckboxes = form.querySelectorAll('input[name="requirements[]"]:checked');
    console.log('Selected requirement checkboxes:', requirementCheckboxes);
    requirementCheckboxes.forEach((checkbox, index) => {
        console.log(`Requirement ${index}:`, checkbox.value);
    });
    
    // Create FormData object for file uploads
    const formData = new FormData(form);
    
    // DEBUG: Log FormData contents (requirements specifically)
    console.log('FormData contents:');
    for (let [key, value] of formData.entries()) {
        if (key === 'requirements[]') {
            console.log(`${key}: ${value}`);
        }
    }
    
    // DEBUG: Get all requirements values from FormData
    const allRequirements = formData.getAll('requirements[]');
    console.log('All requirements from FormData:', allRequirements);
    
    // AJAX submission to the backend
    fetch('functions/registration_process.php', {
        method: 'POST',
        body: formData,
        cache: 'no-cache',
    })
    .then(response => {
        // Check if the response is valid JSON
        const contentType = response.headers.get('content-type');
        if(contentType && contentType.includes('application/json')) {
            return response.json();
        }
        throw new Error('Server returned non-JSON response');
    })
    .then(data => {
        // Re-enable the submit button
        if(submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
        }
        
        // Debug: Log the response to see what's being returned
        console.log('Server response:', data);
        console.log('Missing requirements:', data.missing_requirements);
        console.log('Submitted requirements:', data.submitted_requirements);
        
        if (data.success || data.status === 'success') {
            // Check if there are missing requirements (AND that the array has items)
            if (data.has_missing_requirements === true && 
                data.missing_requirements && 
                Array.isArray(data.missing_requirements) && 
                data.missing_requirements.length > 0) {
                
                // Show success with missing requirements option
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Registration Successful!',
                        html: `
                            <div style="text-align: left;">
                                <p><strong>Student ${data.student_name}</strong> has been registered successfully!</p>
                                <br>
                                <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 10px 0;">
                                    <p style="color: #856404; margin: 0;"><strong>⚠️ Missing Requirements:</strong></p>
                                    <ul style="color: #856404; margin: 5px 0 0 0; padding-left: 20px;">
                                        ${data.missing_requirements.map(req => `<li>${req}</li>`).join('')}
                                    </ul>
                                </div>
                                <div style="background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 10px 0;">
                                    <p style="color: #155724; margin: 0;"><strong>✅ Submitted Requirements:</strong></p>
                                    <ul style="color: #155724; margin: 5px 0 0 0; padding-left: 20px;">
                                        ${data.submitted_requirements && data.submitted_requirements.length > 0 ? 
                                            data.submitted_requirements.map(req => `<li>${req}</li>`).join('') : 
                                            '<li>None</li>'}
                                    </ul>
                                </div>
                                <p style="font-size: 14px; color: #666;">Would you like to print a requirements checklist for the student?</p>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: '🖨️ Print Requirements',
                        cancelButtonText: 'Continue',
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#6c757d',
                        allowOutsideClick: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            printMissingRequirements(data);
                        } else {
                            // Redirect after clicking Continue
                            window.location.href = data.redirect || 'student_management.php';
                        }
                    });
                } else {
                    const printRequirements = confirm(
                        `Registration Successful!\n\n` +
                        `Student: ${data.student_name}\n\n` +
                        `Missing Requirements:\n${data.missing_requirements.map(req => `• ${req}`).join('\n')}\n\n` +
                        `Would you like to print the requirements checklist?`
                    );
                    
                    if (printRequirements) {
                        printMissingRequirements(data);
                    } else {
                        window.location.href = data.redirect || 'student_management.php';
                    }
                }
            } else {
                // No missing requirements - normal success (ALL REQUIREMENTS COMPLETE)
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Registration Complete!',
                        text: data.message || 'All requirements have been submitted! Registration is now complete.',
                        allowOutsideClick: false
                    }).then(() => {
                        window.location.href = data.redirect || 'student_management.php';
                    });
                } else {
                    alert('Registration Complete: ' + (data.message || 'All requirements have been submitted!'));
                    window.location.href = data.redirect || 'student_management.php';
                }
            }
        } else {
            // Error handling
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Registration Failed',
                    html: data.message || 'There was an error submitting your application. Please try again.',
                });
            } else {
                alert('Registration Failed: ' + (data.message || 'There was an error submitting your application. Please try again.'));
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // Re-enable the submit button
        if(submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
        }
        
        // Generic error handling
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Submission Error',
                text: 'There was a problem connecting to the server. Please try again later.',
            });
        } else {
            alert('Submission Error: There was a problem connecting to the server. Please try again later.');
        }
    });
}
// SINGLE Print function for missing requirements
function printMissingRequirements(data) {
    const currentDate = new Date().toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    
    const currentTime = new Date().toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });

    const printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Missing Requirements - ${data.student_name}</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 20px;
                    line-height: 1.6;
                }
                .header {
                    text-align: center;
                    border-bottom: 2px solid #333;
                    padding-bottom: 20px;
                    margin-bottom: 30px;
                }
                .header h1 {
                    color: #333;
                    margin: 0;
                    font-size: 24px;
                }
                .header h2 {
                    color: #666;
                    margin: 5px 0 0 0;
                    font-size: 18px;
                    font-weight: normal;
                }
                .student-info {
                    background-color: #f8f9fa;
                    padding: 15px;
                    border-radius: 5px;
                    margin-bottom: 25px;
                }
                .student-info h3 {
                    margin: 0 0 10px 0;
                    color: #333;
                }
                .requirements-section {
                    margin-bottom: 25px;
                }
                .requirements-section h3 {
                    color: #d9534f;
                    border-bottom: 1px solid #d9534f;
                    padding-bottom: 5px;
                }
                .requirements-list {
                    list-style: none;
                    padding: 0;
                }
                .requirements-list li {
                    padding: 10px;
                    margin: 5px 0;
                    background-color: #fff2f2;
                    border-left: 4px solid #d9534f;
                    border-radius: 3px;
                }
                .requirements-list li:before {
                    content: "☐ ";
                    font-weight: bold;
                    color: #d9534f;
                    margin-right: 8px;
                }
                .footer {
                    text-align: center;
                    border-top: 1px solid #ddd;
                    padding-top: 20px;
                    margin-top: 30px;
                    color: #666;
                    font-size: 12px;
                }
                @media print {
                    body { margin: 0; padding: 15px; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>STUDENT REQUIREMENTS CHECKLIST</h1>
                <h2>Missing Documents Notice</h2>
            </div>
            
            <div class="student-info">
                <h3>Student Information</h3>
                <p><strong>Name:</strong> ${data.student_name}</p>
                <p><strong>Student Number:</strong> ${data.student_number}</p>
                <p><strong>Registration Date:</strong> ${currentDate}</p>
                <p><strong>Time:</strong> ${currentTime}</p>
            </div>
            
            <div class="requirements-section">
                <h3>⚠️ Missing Requirements</h3>
                <p>The following documents are still needed to complete your registration:</p>
                <ul class="requirements-list">
                    ${data.missing_requirements.map(req => `<li>${req}</li>`).join('')}
                </ul>
            </div>
            
            <div class="footer">
                <p>This document was generated on ${currentDate} at ${currentTime}</p>
                <p>Please present this checklist when submitting missing requirements</p>
            </div>
        </body>
        </html>
    `;
    
    // Create a new window for printing
    const printWindow = window.open('', '_blank', 'width=800,height=600');
    printWindow.document.write(printContent);
    printWindow.document.close();
    
    // Wait for content to load then print
    printWindow.onload = function() {
        printWindow.print();
        
        // Close print window after printing
        printWindow.onafterprint = function() {
            printWindow.close();
            // Redirect to management page
            window.location.href = 'student_management.php';
        };
    };
}
// Function to validate requirements
function validateRequirements() {
    const missingRequirements = [];
    
    // Define the required documents
    const requiredDocs = [
        { name: 'PSA Birth Certificate', checkbox: 'input[name="requirements[]"][value="PSA Birth Certificate"]' },
        { name: 'TOR, ALS Certificate or FORM 137', checkbox: 'input[name="requirements[]"][value="TOR, ALS Certificate or FORM 137"]' },
        { name: 'Passport size photos', checkbox: 'input[name="requirements[]"][value="Passport size photos"]' }
    ];
    
    // Check each required document
    requiredDocs.forEach(doc => {
        const checkbox = document.querySelector(doc.checkbox);
        if (!checkbox || !checkbox.checked) {
            missingRequirements.push(doc.name);
        }
    });
    
    return missingRequirements;
}

// Optional: Real-time validation as user checks/unchecks requirements
function setupRequirementsValidation() {
    const requirementCheckboxes = document.querySelectorAll('input[name="requirements[]"]');
    const submitButton = document.querySelector('button[type="submit"]');
    
    if (requirementCheckboxes.length > 0 && submitButton) {
        requirementCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const missingRequirements = validateRequirements();
                
                if (missingRequirements.length > 0) {
                    submitButton.style.opacity = '0.6';
                    submitButton.title = `Missing requirements: ${missingRequirements.join(', ')}`;
                } else {
                    submitButton.style.opacity = '1';
                    submitButton.title = '';
                }
            });
        });
        
        // Initial validation on page load
        const missingRequirements = validateRequirements();
        if (missingRequirements.length > 0) {
            submitButton.style.opacity = '0.6';
            submitButton.title = `Missing requirements: ${missingRequirements.join(', ')}`;
        }
    }
}

// Call this function when the page loads
document.addEventListener('DOMContentLoaded', function() {
    setupRequirementsValidation();
});

// Alternative function if you want to show a summary of completed vs missing requirements
function showRequirementsSummary() {
    const allRequirements = [
        'PSA Birth Certificate',
        'TOR, ALS Certificate or FORM 137', 
        'Passport size photos'
    ];
    
    const completedRequirements = [];
    const missingRequirements = [];
    
    allRequirements.forEach(req => {
        const checkbox = document.querySelector(`input[name="requirements[]"][value="${req}"]`);
        if (checkbox && checkbox.checked) {
            completedRequirements.push(req);
        } else {
            missingRequirements.push(req);
        }
    });
    
    let summaryHTML = '<div style="text-align: left;">';
    
    if (completedRequirements.length > 0) {
        summaryHTML += `<p><strong style="color: green;">✓ Completed Requirements:</strong></p>
                       <ul style="color: green; margin: 0 0 15px 0; padding-left: 20px;">
                           ${completedRequirements.map(req => `<li>${req}</li>`).join('')}
                       </ul>`;
    }
    
    if (missingRequirements.length > 0) {
        summaryHTML += `<p><strong style="color: red;">✗ Missing Requirements:</strong></p>
                       <ul style="color: red; margin: 0; padding-left: 20px;">
                           ${missingRequirements.map(req => `<li>${req}</li>`).join('')}
                       </ul>`;
    }
    
    summaryHTML += '</div>';
    
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: missingRequirements.length > 0 ? 'warning' : 'success',
            title: 'Requirements Summary',
            html: summaryHTML,
            confirmButtonText: 'OK'
        });
    }
    
    return missingRequirements;
}
    // Add input event listeners to show/hide validation styling in real-time
    const allInputs = registrationForm.querySelectorAll('input, select');
    allInputs.forEach(input => {
        input.addEventListener('input', function() {
            // Remove any existing field error message
            const parentElement = this.parentElement;
            const existingError = parentElement.querySelector('.field-error-message');
            if (existingError) {
                existingError.remove();
            }
            
            // Remove error styling when input is filled
            this.classList.remove('border-red-500');
            
            // Special validation for email fields
            if (this.type === 'email' && this.value.trim() !== '') {
                if (!validateEmail(this.value)) {
                    this.classList.add('border-red-500');
                    addFieldErrorMessage(this, 'Please enter a valid email address');
                }
            }
            
            // Special validation for phone number fields
            if ((this.name === 'user_contact' || this.name === 'emergencyContact') && this.value.trim() !== '') {
                if (!validatePhoneNumber(this.value)) {
                    this.classList.add('border-red-500');
                    addFieldErrorMessage(this, 'Please enter a valid phone number (09XXXXXXXXX or +639XXXXXXXXX)');
                }
            }
        });
    });
    
    // Special handling for "Other" referral option
    const referralOther = document.getElementById('referralOther');
    const otherReferralDiv = document.getElementById('otherReferralDiv');
    const otherReferral = document.getElementById('otherReferral');
    
    if (referralOther && otherReferralDiv && otherReferral) {
        referralOther.addEventListener('change', function() {
            otherReferralDiv.style.display = this.checked ? 'block' : 'none';
            otherReferral.required = this.checked;
        });
    }
    
    // Initialize address field dependencies
    // This would be expanded with the relevant code for populating the address dropdowns
});
//--------------------------------------------------
//End Script for insert modal
//--------------------------------------------------








//-----------------------------------------------------------------
//Search Students
//-----------------------------------------------------------------
const searchInput = document.getElementById('searchInput');
searchInput.addEventListener('input', function () {
        const query = this.value;

        fetch(`functions/search_students.php?search=${encodeURIComponent(query)}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('studentTableBody').innerHTML = data;
            });
});
//-----------------------------------------------------------------
//End Students Search
//-----------------------------------------------------------------





//-----------------------------------------------------------------
//Edit Script Modal
//-----------------------------------------------------------------

// Functions for the edit form modal
document.addEventListener('DOMContentLoaded', function() {
    // Bind referral radio button change events
    document.getElementById('editReferralOther').addEventListener('change', function() {
        document.getElementById('editOtherReferralDiv').style.display = this.checked ? 'block' : 'none';
    });

    document.getElementById('editReferralNone').addEventListener('change', function() {
        document.getElementById('editOtherReferralDiv').style.display = 'none';
    });

    // Bind photo upload preview
    document.getElementById('editPhotoUpload').addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('editPreviewImage').src = e.target.result;
                document.getElementById('editPhotoPreview').classList.remove('hidden');
            }
            reader.readAsDataURL(file);
        }
    });

    // Initialize edit form submit handler
    document.getElementById('editStudentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        updateStudent();
    });


    // Bind to all edit buttons
    document.querySelectorAll('.viewStudentBtn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const studentId = this.getAttribute('data-id');

            fetch(`functions/get_students.php?id=${studentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        document.getElementById('studentDetailsContent').innerHTML = `<p>${data.error}</p>`;
                        return;
                    }

                    // Render student details and buttons
                  // 1. First, update the frontend HTML with proper event handler
                    document.getElementById('studentDetailsContent').innerHTML = `
                        <div class="mb-4">
                            <img src="functions/${data.photo_path}" alt="Student Photo" class="w-22 h-22 object-cover rounded border border-gray-300" />
                        </div>
                        <p><strong>Name:</strong> ${data.last_name}, ${data.first_name} ${data.middle_name || ''}</p>
                        <p><strong>Student Number:</strong> ${data.student_number}</p>
                        <p><strong>Birthdate:</strong> ${data.birthdate}</p>
                        <p><strong>Civil Status:</strong> ${data.civil_status}</p>
                        <p><strong>Address:</strong> ${data.region}, ${data.province}, ${data.city}, ${data.brgy}</p>
                        <p><strong>Birthplace:</strong> ${data.birth_region}, ${data.birth_province}, ${data.birth_city}, ${data.birth_brgy}</p>
                         <p><strong>Contact Number:</strong>${data.user_contact}</p>
                          <p><strong>Email:</strong>${data.user_email}</p>
                        <p><strong>Emergency Contact:</strong> ${data.emergency_name} (${data.relationship}) - ${data.emergency_contact}</p>
                        <p><strong>Referal:</strong> ${data.referral} ${data.other_referral ? '(' + data.other_referral + ')' : ''}</p>
                        <div class="flex gap-2 mt-4">
                            <button id="deleteBtn" class="bg-red-500 text-white px-4 py-1 rounded">Delete</button>
                            <button id="editBtn" class="bg-yellow-500 text-white px-4 py-1 rounded">Edit</button>
                          
                        </div>
                    `;

                    // Bind edit button click
                    const editButton = document.getElementById('editBtn');
                    if (editButton) {
                        editButton.addEventListener('click', function() {
                            openEditModal(data);
                        });
                    }

                    document.getElementById('deleteBtn').addEventListener('click', function() {
                        // Get the student ID from the data object
                        const studentId = data.id; // Assuming the data object has an id property
                        
                        // Call the delete confirmation function from our mixin
                        StudentMixin.confirmDelete(studentId);
                    });
                    

                    
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        });
    });
});



const StudentMixin = {
    confirmDelete: function(studentId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // If confirmed, call the delete function
                this.deleteStudent(studentId);
            }
        });
    },
    
    deleteStudent: function(studentId) {
        // Send delete request to backend
        fetch('functions/delete_student.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                student_id: studentId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire(
                    'Deleted!',
                    'Student record has been deleted.',
                    'success'
                ).then(() => {
                    // Redirect to student list or refresh the page
                    window.location.href = 'student_management.php';
                });
            } else {
                Swal.fire(
                    'Error!',
                    data.message || 'Failed to delete student record.',
                    'error'
                );
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire(
                'Error!',
                'An unexpected error occurred.',
                'error'
            );
        });
    },
    

};
// Function to populate the edit form with student data
function openEditModal(studentData) {
    const modalOverlay = document.getElementById('editModalOverlay');
    if (!modalOverlay) {
        console.error('editModalOverlay not found in the DOM');
        return;
    }

    // Show the modal
    modalOverlay.classList.remove('hidden');

    // Set student ID for the update operation
    document.getElementById('editStudentForm').setAttribute('data-student-id', studentData.id);

    // Populate basic information
    document.getElementById('editStudentNumber').value = studentData.student_number;
    document.getElementById('editEntryDate').value = studentData.entry_date;
    document.getElementById('editFirstName').value = studentData.first_name;
    document.getElementById('editLastName').value = studentData.last_name;
    document.getElementById('editMiddleName').value = studentData.middle_name || '';
    document.getElementById('editBirthdate').value = studentData.birthdate;
    document.getElementById('editCivilStatus').value = studentData.civil_status;
    
    // Populate contact information (missing in original function)
    document.getElementById('editUserEmail').value = studentData.user_email || '';
    document.getElementById('editUserContact').value = studentData.user_contact || '';

    // Populate address - fixing reference from 'editBrgy' to 'editBarangay' as in HTML
    document.getElementById('editRegion').value = studentData.region;
    document.getElementById('editProvince').value = studentData.province;
    document.getElementById('editCity').value = studentData.city;
    document.getElementById('editBarangay').value = studentData.brgy; // Fixed selector
    document.getElementById('editPurok').value = studentData.purok || '';

    // Populate birthplace - fixing reference from 'editBirthBrgy' to match HTML
    document.getElementById('editBirthRegion').value = studentData.birth_region;
    document.getElementById('editBirthProvince').value = studentData.birth_province;
    document.getElementById('editBirthCity').value = studentData.birth_city;
    document.getElementById('editBirthBrgy').value = studentData.birth_brgy;

    // Populate educational background
    document.getElementById('editEducationalAttainment').value = studentData.educational_attainment;
    document.getElementById('editClassification').value = studentData.classification;

    // Populate emergency contact - fixing references and adding missing fields
    document.getElementById('editEmergencyName').value = studentData.emergency_name;
    document.getElementById('editRelationship').value = studentData.relationship;
    document.getElementById('editEmergencyEmail').value = studentData.emergency_email || ''; // Added missing field
    document.getElementById('editEmergencyContact').value = studentData.emergency_contact;
    document.getElementById('editEmergencyRegion').value = studentData.emergency_region;
    document.getElementById('editEmergencyProvince').value = studentData.emergency_province;
    document.getElementById('editEmergencyCity').value = studentData.emergency_city;
    document.getElementById('editEmergencyBrgy').value = studentData.emergency_brgy;
    document.getElementById('editEmergencyPurok').value = studentData.emergency_purok || '';

    // Reset "Same as above" checkbox
    document.getElementById('editSameAsAbove').checked = false;

    // Populate referral information
    if (studentData.referral === 'none') {
        document.getElementById('editReferralNone').checked = true;
        document.getElementById('editOtherReferralDiv').style.display = 'none';
    } else if (studentData.referral === 'other') {
        document.getElementById('editReferralOther').checked = true;
        document.getElementById('editOtherReferralDiv').style.display = 'block';
        document.getElementById('editOtherReferral').value = studentData.other_referral || '';
    }

    document.getElementById('editKnowledgeSource').value = studentData.knowledge_source;

    // Populate requirements checkboxes
    if (studentData.requirements) {
        // Split requirements by comma and trim each item to handle spacing
        const requirements = studentData.requirements.split(',').map(item => item.trim());
        
        // Check for PSA Birth Certificate
        document.getElementById('editReqBirthCert').checked = requirements.some(req => 
            req.includes('PSA Birth Certificate') || req.includes('PSA') || req.includes('Birth Certificate'));
        
        // Check for TOR, ALS or FORM 137
        document.getElementById('editReqTOR').checked = requirements.some(req => 
            req.includes('TOR') || req.includes('ALS') || req.includes('FORM 137'));
        
        // Check for Photos
        document.getElementById('editReqPhotos').checked = requirements.some(req => 
            req.includes('Passport size photos') || req.includes('Photos') || req.includes('Passport photos'));
    } else {
        // Ensure checkboxes are unchecked if no requirements data
        document.getElementById('editReqBirthCert').checked = false;
        document.getElementById('editReqTOR').checked = false;
        document.getElementById('editReqPhotos').checked = false;
    }

    // Display current photo - fixed reference from 'currentStudentPhoto' to 'editCurrentStudentPhoto'
    if (studentData.photo_path) {
        document.getElementById('editCurrentStudentPhoto').src = `functions/${studentData.photo_path}`;
        document.getElementById('editCurrentPhotoPreview').classList.remove('hidden');
    } else {
        document.getElementById('editCurrentPhotoPreview').classList.add('hidden');
    }

    // Reset the photo preview
    document.getElementById('editPhotoPreview').classList.add('hidden');
    document.getElementById('editPhotoUpload').value = '';
}

// Function to close the edit modal
function closeEditModal3() {
    const modal = document.getElementById('editModalOverlay');
    if (modal) {
        modal.classList.add('hidden');
    }
}

// Function to copy address from student to emergency contact
function copyEditAddress() {
    const sameAsAbove = document.getElementById('editSameAsAbove').checked;
    
    if (sameAsAbove) {
        document.getElementById('editEmergencyRegion').value = document.getElementById('editRegion').value;
        document.getElementById('editEmergencyProvince').value = document.getElementById('editProvince').value;
        document.getElementById('editEmergencyCity').value = document.getElementById('editCity').value;
        document.getElementById('editEmergencyBrgy').value = document.getElementById('editBrgy').value;
        document.getElementById('editEmergencyPurok').value = document.getElementById('editPurok').value;
    }
}
// Function to update student information
function updateStudent() {
    const form = document.getElementById('editStudentForm');
    const studentId = form.getAttribute('data-student-id');
    
    if (!studentId) {
        console.error('No student ID found');
        return;
    }
    
    // First, show confirmation dialog
    Swal.fire({
        title: 'Update Student Information',
        text: 'Are you sure you want to update this student\'s information?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, update it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // User confirmed, proceed with update
            processUpdate(form, studentId);
        }
    });
}

// Function to process the actual update
function processUpdate(form, studentId) {
    // Create FormData object to handle file uploads
    const formData = new FormData(form);
    formData.append('id', studentId);
    
    // Get referral information
    const referralType = document.querySelector('input[name="referral"]:checked')?.value || 'none';
    formData.append('referral', referralType);
    
    if (referralType === 'other') {
        formData.append('otherReferral', document.getElementById('editOtherReferral').value);
    }
    
    // Show loading state
    const updateButton = document.getElementById('updateButton');
    const originalButtonText = updateButton.textContent;
    updateButton.textContent = 'Updating...';
    updateButton.disabled = true;
    
    // Show loading indicator with SweetAlert2
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });
    
    Toast.fire({
        icon: 'info',
        title: 'Updating student information...'
    });
    
    // Send update request
    fetch('functions/update_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Show success message with SweetAlert2
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Student information updated successfully!',
                confirmButtonColor: '#3085d6'
            }).then(() => {
                closeEditModal();
                // Refresh the page to show updated data
                location.reload();
            });
        } else {
            // Show error message with SweetAlert2
            Swal.fire({
                icon: 'error',
                title: 'Update Failed',
                text: 'Error: ' + (data.message || 'Failed to update student information'),
                confirmButtonColor: '#3085d6'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // Show error message with SweetAlert2
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred while updating student information',
            confirmButtonColor: '#3085d6'
        });
    })
    .finally(() => {
        // Reset button state
        updateButton.textContent = originalButtonText;
        updateButton.disabled = false;
    });
}

   $(document).ready(function() {
    // Initialize address dropdowns for all address sections
    initializeAddressDropdowns('current', '#region', '#province', '#city', '#barangay');
    initializeAddressDropdowns('birth', '#birthRegion', '#birthProvince', '#birthCity', '#birthBrgy');
    initializeAddressDropdowns('emergency', '#emergencyRegion', '#emergencyProvince', '#emergencyCity', '#emergencyBrgy');
    
    // Handle "Same as Above" checkbox for emergency contact
    $('#sameAsAbove').on('change', function() {
        copyAddress();
    });
});


// Function to copy current address to emergency contact address
function copyAddress() {
    if ($('#sameAsAbove').is(':checked')) {
        // Get values from current address
        let regionCode = $('#region').val();
        let provinceCode = $('#province').val();
        let cityCode = $('#city').val();
        let barangayText = $('#barangay option:selected').text();
        let purok = $('#purok').val();
        
        // Set values to emergency contact address fields
        $('#emergencyRegion').val(regionCode).trigger('change');
        
        // We need to wait for provinces to load before selecting province
        setTimeout(function() {
            $('#emergencyProvince').val(provinceCode).trigger('change');
            
            // Wait for cities to load before selecting city
            setTimeout(function() {
                $('#emergencyCity').val(cityCode).trigger('change');
                
                // Wait for barangays to load before selecting barangay
                setTimeout(function() {
                    // Find the barangay option with matching text
                    $('#emergencyBrgy option').each(function() {
                        if ($(this).text() === barangayText) {
                            $('#emergencyBrgy').val($(this).val()).trigger('change');
                            return false;
                        }
                    });
                    $('#emergencyPurok').val(purok);
                }, 500);
            }, 500);
        }, 500);
        
        // Disable emergency address fields
        $('#emergencyRegion, #emergencyProvince, #emergencyCity, #emergencyBrgy, #emergencyPurok').prop('disabled', true);
    } else {
        // Enable emergency address fields
        $('#emergencyRegion, #emergencyProvince, #emergencyCity, #emergencyBrgy, #emergencyPurok').prop('disabled', false);
        
        // Clear emergency address fields
        $('#emergencyRegion').val('').trigger('change');
        $('#emergencyProvince, #emergencyCity, #emergencyBrgy').empty().append('<option value="" selected disabled>Choose</option>');
        $('#emergencyPurok').val('');
    }
}

//-----------------------------------------------------------------
//End Script
//-----------------------------------------------------------------



//-----------------------------------------------------------------
//start a Address
//-----------------------------------------------------------------

// Function to initialize cascading address dropdowns
function initializeAddressDropdowns(addressType, regionSelector, provinceSelector, citySelector, barangaySelector) {
    // Load Regions
    $.getJSON('../philippine-address-selector-main/ph-json/region.json', function(data) {
        let dropdown = $(regionSelector);
        dropdown.empty().append('<option value="" selected disabled>Choose Region</option>');
        $.each(data, function(key, entry) {
            dropdown.append($('<option></option>').attr('value', entry.region_code).text(entry.region_name));
        });
    });

    // Update Region Name and Load Provinces
    $(regionSelector).on('change', function() {
        let regionCode = $(this).val();
        let regionName = $(this).find('option:selected').text();
        $(`#${addressType}-region-name`).val(regionName);  // Save name to hidden input
        
        $(provinceSelector).empty().append('<option value="" selected disabled>Choose Province</option>');
        $(citySelector).empty().append('<option value="" selected disabled>Choose City</option>');
        $(barangaySelector).empty().append('<option value="" selected disabled>Choose Barangay</option>');

        $.getJSON('../philippine-address-selector-main/ph-json/province.json', function(data) {
            let provinces = data.filter(p => p.region_code === regionCode);
            provinces.sort((a, b) => a.province_name.localeCompare(b.province_name));
            $.each(provinces, function(key, entry) {
                $(provinceSelector).append($('<option></option>').attr('value', entry.province_code).text(entry.province_name));
            });
        });
    });

    // Update Province Name and Load Cities
    $(provinceSelector).on('change', function() {
        let provinceCode = $(this).val();
        let provinceName = $(this).find('option:selected').text();
        $(`#${addressType}-province-name`).val(provinceName);  // Save name to hidden input
        
        $(citySelector).empty().append('<option value="" selected disabled>Choose City</option>');
        $(barangaySelector).empty().append('<option value="" selected disabled>Choose Barangay</option>');

        $.getJSON('../philippine-address-selector-main/ph-json/city.json', function(data) {
            let cities = data.filter(c => c.province_code === provinceCode);
            cities.sort((a, b) => a.city_name.localeCompare(b.city_name));
            $.each(cities, function(key, entry) {
                $(citySelector).append($('<option></option>').attr('value', entry.city_code).text(entry.city_name));
            });
        });
    });

    // Update City Name and Load Barangays
    $(citySelector).on('change', function() {
        let cityCode = $(this).val();
        let cityName = $(this).find('option:selected').text();
        $(`#${addressType}-city-name`).val(cityName);  // Save name to hidden input
        
        $(barangaySelector).empty().append('<option value="" selected disabled>Choose Barangay</option>');

        $.getJSON('../philippine-address-selector-main/ph-json/barangay.json', function(data) {
            let barangays = data.filter(b => b.city_code === cityCode);
            barangays.sort((a, b) => a.brgy_name.localeCompare(b.brgy_name));
            $.each(barangays, function(key, entry) {
                $(barangaySelector).append($('<option></option>').attr('value', entry.brgy_code).text(entry.brgy_name));
            });
        });
    });

    // Update Barangay Name
    $(barangaySelector).on('change', function() {
        let barangayName = $(this).find('option:selected').text();
        $(`#${addressType}-barangay-name`).val(barangayName);  // Save name to hidden input
    });
}


// Function to initialize cascading address dropdowns for the edit modal
function initializeEditAddressDropdowns(addressType, regionSelector, provinceSelector, citySelector, barangaySelector) {
    // Load Regions
    $.getJSON('../philippine-address-selector-main/ph-json/region.json', function(data) {
        let dropdown = $(regionSelector);
        dropdown.empty().append('<option value="" selected disabled>Choose Region</option>');
        $.each(data, function(key, entry) {
            dropdown.append($('<option></option>').attr('value', entry.region_code).text(entry.region_name));
        });
    });

    // Update Region Name and Load Provinces
    $(regionSelector).on('change', function() {
        let regionCode = $(this).val();
        let regionName = $(this).find('option:selected').text();
        $(`#edit${addressType}RegionName`).val(regionName);  // Save name to hidden input
        
        $(provinceSelector).empty().append('<option value="" selected disabled>Choose Province</option>');
        $(citySelector).empty().append('<option value="" selected disabled>Choose City</option>');
        $(barangaySelector).empty().append('<option value="" selected disabled>Choose Barangay</option>');

        $.getJSON('../philippine-address-selector-main/ph-json/province.json', function(data) {
            let provinces = data.filter(p => p.region_code === regionCode);
            provinces.sort((a, b) => a.province_name.localeCompare(b.province_name));
            $.each(provinces, function(key, entry) {
                $(provinceSelector).append($('<option></option>').attr('value', entry.province_code).text(entry.province_name));
            });
        });
    });

    // Update Province Name and Load Cities
    $(provinceSelector).on('change', function() {
        let provinceCode = $(this).val();
        let provinceName = $(this).find('option:selected').text();
        $(`#edit${addressType}ProvinceName`).val(provinceName);  // Save name to hidden input
        
        $(citySelector).empty().append('<option value="" selected disabled>Choose City</option>');
        $(barangaySelector).empty().append('<option value="" selected disabled>Choose Barangay</option>');

        $.getJSON('../philippine-address-selector-main/ph-json/city.json', function(data) {
            let cities = data.filter(c => c.province_code === provinceCode);
            cities.sort((a, b) => a.city_name.localeCompare(b.city_name));
            $.each(cities, function(key, entry) {
                $(citySelector).append($('<option></option>').attr('value', entry.city_code).text(entry.city_name));
            });
        });
    });

    // Update City Name and Load Barangays
    $(citySelector).on('change', function() {
        let cityCode = $(this).val();
        let cityName = $(this).find('option:selected').text();
        $(`#edit${addressType}CityName`).val(cityName);  // Save name to hidden input
        
        $(barangaySelector).empty().append('<option value="" selected disabled>Choose Barangay</option>');

        $.getJSON('../philippine-address-selector-main/ph-json/barangay.json', function(data) {
            let barangays = data.filter(b => b.city_code === cityCode);
            barangays.sort((a, b) => a.brgy_name.localeCompare(b.brgy_name));
            $.each(barangays, function(key, entry) {
                $(barangaySelector).append($('<option></option>').attr('value', entry.brgy_code).text(entry.brgy_name));
            });
        });
    });

    // Update Barangay Name
    $(barangaySelector).on('change', function() {
        let barangayName = $(this).find('option:selected').text();
        $(`#edit${addressType}BarangayName`).val(barangayName);  // Save name to hidden input
    });
}

// Function to populate dropdown based on saved value
function selectDropdownOption(selector, value, textProp, valueProp, jsonUrl, filterProp, filterValue) {
    return new Promise((resolve) => {
        if (!value) {
            resolve();
            return;
        }

        $.getJSON(jsonUrl, function(data) {
            let items = data;
            
            // Apply filter if provided
            if (filterProp && filterValue) {
                items = data.filter(item => item[filterProp] === filterValue);
            }
            
            // Try to find by exact value match first
            let option = items.find(item => item[valueProp] === value);
            
            // If not found, try by name match
            if (!option) {
                option = items.find(item => item[textProp].toLowerCase() === value.toLowerCase());
            }
            
            if (option) {
                // Ensure the option exists in dropdown
                if ($(selector + ' option[value="' + option[valueProp] + '"]').length === 0) {
                    $(selector).append($('<option></option>').attr('value', option[valueProp]).text(option[textProp]));
                }
                
                $(selector).val(option[valueProp]).trigger('change');
                resolve(option[valueProp]);
            } else {
                resolve();
            }
        });
    });
}

// Enhanced function to open edit modal with address dropdowns
function openEditModal(studentData) {
    const modalOverlay = document.getElementById('editModalOverlay');
    if (!modalOverlay) {
        console.error('editModalOverlay not found in the DOM');
        return;
    }

    // Show the modal
    modalOverlay.classList.remove('hidden');

    // Set student ID for the update operation
    document.getElementById('editStudentForm').setAttribute('data-student-id', studentData.id);

    // Populate basic information
    document.getElementById('editStudentNumber').value = studentData.student_number;
    document.getElementById('editEntryDate').value = studentData.entry_date;
    document.getElementById('editFirstName').value = studentData.first_name;
    document.getElementById('editLastName').value = studentData.last_name;
    document.getElementById('editMiddleName').value = studentData.middle_name || '';
    document.getElementById('editBirthdate').value = studentData.birthdate;
    document.getElementById('editCivilStatus').value = studentData.civil_status;
    
    // Populate contact information
    document.getElementById('editUserEmail').value = studentData.user_email || '';
    document.getElementById('editUserContact').value = studentData.user_contact || '';

    // Initialize address dropdowns
    initializeEditAddressDropdowns('', '#editRegion', '#editProvince', '#editCity', '#editBarangay');
    initializeEditAddressDropdowns('Birth', '#editBirthRegion', '#editBirthProvince', '#editBirthCity', '#editBirthBrgy');
    initializeEditAddressDropdowns('Emergency', '#editEmergencyRegion', '#editEmergencyProvince', '#editEmergencyCity', '#editEmergencyBrgy');

    // Add hidden inputs for storing names if they don't exist
    if (!$('#editRegionName').length) {
        $('#editStudentForm').append('<input type="hidden" id="editRegionName" name="region_name">');
        $('#editStudentForm').append('<input type="hidden" id="editProvinceName" name="province_name">');
        $('#editStudentForm').append('<input type="hidden" id="editCityName" name="city_name">');
        $('#editStudentForm').append('<input type="hidden" id="editBarangayName" name="barangay_name">');
        
        $('#editStudentForm').append('<input type="hidden" id="editBirthRegionName" name="birth_region_name">');
        $('#editStudentForm').append('<input type="hidden" id="editBirthProvinceName" name="birth_province_name">');
        $('#editStudentForm').append('<input type="hidden" id="editBirthCityName" name="birth_city_name">');
        $('#editStudentForm').append('<input type="hidden" id="editBirthBarangayName" name="birth_barangay_name">');
        
        $('#editStudentForm').append('<input type="hidden" id="editEmergencyRegionName" name="emergency_region_name">');
        $('#editStudentForm').append('<input type="hidden" id="editEmergencyProvinceName" name="emergency_province_name">');
        $('#editStudentForm').append('<input type="hidden" id="editEmergencyCityName" name="emergency_city_name">');
        $('#editStudentForm').append('<input type="hidden" id="editEmergencyBarangayName" name="emergency_barangay_name">');
    }

    // Set values for address dropdowns (after a short delay to ensure dropdowns are loaded)
    setTimeout(() => {
        // Current address
        selectDropdownOption('#editRegion', studentData.region, 'region_name', 'region_code', 
            '../philippine-address-selector-main/ph-json/region.json')
        .then(regionCode => {
            if (regionCode) {
                return selectDropdownOption('#editProvince', studentData.province, 'province_name', 'province_code',
                    '../philippine-address-selector-main/ph-json/province.json', 'region_code', regionCode);
            }
        })
        .then(provinceCode => {
            if (provinceCode) {
                return selectDropdownOption('#editCity', studentData.city, 'city_name', 'city_code',
                    '../philippine-address-selector-main/ph-json/city.json', 'province_code', provinceCode);
            }
        })
        .then(cityCode => {
            if (cityCode) {
                selectDropdownOption('#editBarangay', studentData.brgy, 'brgy_name', 'brgy_code',
                    '../philippine-address-selector-main/ph-json/barangay.json', 'city_code', cityCode);
            }
        });

        // Birth place
        selectDropdownOption('#editBirthRegion', studentData.birth_region, 'region_name', 'region_code',
            '../philippine-address-selector-main/ph-json/region.json')
        .then(regionCode => {
            if (regionCode) {
                return selectDropdownOption('#editBirthProvince', studentData.birth_province, 'province_name', 'province_code',
                    '../philippine-address-selector-main/ph-json/province.json', 'region_code', regionCode);
            }
        })
        .then(provinceCode => {
            if (provinceCode) {
                return selectDropdownOption('#editBirthCity', studentData.birth_city, 'city_name', 'city_code',
                    '../philippine-address-selector-main/ph-json/city.json', 'province_code', provinceCode);
            }
        })
        .then(cityCode => {
            if (cityCode) {
                selectDropdownOption('#editBirthBrgy', studentData.birth_brgy, 'brgy_name', 'brgy_code',
                    '../philippine-address-selector-main/ph-json/barangay.json', 'city_code', cityCode);
            }
        });

        // Emergency address
        selectDropdownOption('#editEmergencyRegion', studentData.emergency_region, 'region_name', 'region_code',
            '../philippine-address-selector-main/ph-json/region.json')
        .then(regionCode => {
            if (regionCode) {
                return selectDropdownOption('#editEmergencyProvince', studentData.emergency_province, 'province_name', 'province_code',
                    '../philippine-address-selector-main/ph-json/province.json', 'region_code', regionCode);
            }
        })
        .then(provinceCode => {
            if (provinceCode) {
                return selectDropdownOption('#editEmergencyCity', studentData.emergency_city, 'city_name', 'city_code',
                    '../philippine-address-selector-main/ph-json/city.json', 'province_code', provinceCode);
            }
        })
        .then(cityCode => {
            if (cityCode) {
                selectDropdownOption('#editEmergencyBrgy', studentData.emergency_brgy, 'brgy_name', 'brgy_code',
                    '../philippine-address-selector-main/ph-json/barangay.json', 'city_code', cityCode);
            }
        });
    }, 500);

    // Populate purok if available
    document.getElementById('editPurok').value = studentData.purok || '';
    document.getElementById('editEmergencyPurok').value = studentData.emergency_purok || '';

    // Populate educational background
    document.getElementById('editEducationalAttainment').value = studentData.educational_attainment;
    document.getElementById('editClassification').value = studentData.classification;

    // Populate emergency contact
    document.getElementById('editEmergencyName').value = studentData.emergency_name;
    document.getElementById('editRelationship').value = studentData.relationship;
    document.getElementById('editEmergencyEmail').value = studentData.emergency_email || '';
    document.getElementById('editEmergencyContact').value = studentData.emergency_contact;

    // Reset "Same as above" checkbox
    document.getElementById('editSameAsAbove').checked = false;

    // Populate referral information
    if (studentData.referral === 'none') {
        document.getElementById('editReferralNone').checked = true;
        document.getElementById('editOtherReferralDiv').style.display = 'none';
    } else if (studentData.referral === 'other') {
        document.getElementById('editReferralOther').checked = true;
        document.getElementById('editOtherReferralDiv').style.display = 'block';
        document.getElementById('editOtherReferral').value = studentData.other_referral || '';
    }

    document.getElementById('editKnowledgeSource').value = studentData.knowledge_source;

    // Populate requirements checkboxes
    if (studentData.requirements) {
        // Split requirements by comma and trim each item to handle spacing
        const requirements = studentData.requirements.split(',').map(item => item.trim());
        
        // Check for PSA Birth Certificate
        document.getElementById('editReqBirthCert').checked = requirements.some(req => 
            req.includes('PSA Birth Certificate') || req.includes('PSA') || req.includes('Birth Certificate'));
        
        // Check for TOR, ALS or FORM 137
        document.getElementById('editReqTOR').checked = requirements.some(req => 
            req.includes('TOR') || req.includes('ALS') || req.includes('FORM 137'));
        
        // Check for Photos
        document.getElementById('editReqPhotos').checked = requirements.some(req => 
            req.includes('Passport size photos') || req.includes('Photos') || req.includes('Passport photos'));
    } else {
        // Ensure checkboxes are unchecked if no requirements data
        document.getElementById('editReqBirthCert').checked = false;
        document.getElementById('editReqTOR').checked = false;
        document.getElementById('editReqPhotos').checked = false;
    }

    // Display current photo
    if (studentData.photo_path) {
        document.getElementById('editCurrentStudentPhoto').src = `functions/${studentData.photo_path}`;
        document.getElementById('editCurrentPhotoPreview').classList.remove('hidden');
    } else {
        document.getElementById('editCurrentPhotoPreview').classList.add('hidden');
    }

    // Reset the photo preview
    document.getElementById('editPhotoPreview').classList.add('hidden');
    document.getElementById('editPhotoUpload').value = '';
}

// Function to copy current address to emergency address in the edit modal
function copyEditAddress() {
    const sameAsAbove = document.getElementById('editSameAsAbove').checked;
    
    if (sameAsAbove) {
        // Get current region and trigger change to load provinces
        const regionVal = $('#editRegion').val();
        const regionText = $('#editRegion option:selected').text();
        $('#editEmergencyRegion').val(regionVal).trigger('change');
        
        // We need to wait for provinces to load before selecting province
        setTimeout(function() {
            const provinceVal = $('#editProvince').val();
            const provinceText = $('#editProvince option:selected').text();
            $('#editEmergencyProvince').val(provinceVal).trigger('change');
            
            // Wait for cities to load before selecting city
            setTimeout(function() {
                const cityVal = $('#editCity').val();
                const cityText = $('#editCity option:selected').text();
                $('#editEmergencyCity').val(cityVal).trigger('change');
                
                // Wait for barangays to load before selecting barangay
                setTimeout(function() {
                    const brgyVal = $('#editBarangay').val();
                    const brgyText = $('#editBarangay option:selected').text();
                    $('#editEmergencyBrgy').val(brgyVal);
                    
                    $('#editEmergencyPurok').val($('#editPurok').val());
                    
                    // Copy hidden field values
                    $('#editEmergencyRegionName').val(regionText);
                    $('#editEmergencyProvinceName').val(provinceText);
                    $('#editEmergencyCityName').val(cityText);
                    $('#editEmergencyBarangayName').val(brgyText);
                }, 500);
            }, 500);
        }, 500);
        
        // Disable emergency address fields
        $('#editEmergencyRegion, #editEmergencyProvince, #editEmergencyCity, #editEmergencyBrgy, #editEmergencyPurok').prop('disabled', true);
    } else {
        // Enable emergency address fields
        $('#editEmergencyRegion, #editEmergencyProvince, #editEmergencyCity, #editEmergencyBrgy, #editEmergencyPurok').prop('disabled', false);
        
        // Clear emergency address fields
        $('#editEmergencyRegion').val('').trigger('change');
        $('#editEmergencyProvince, #editEmergencyCity, #editEmergencyBrgy').empty().append('<option value="" selected disabled>Choose</option>');
        $('#editEmergencyPurok').val('');
    }
}

// Add event listeners when document is ready
$(document).ready(function() {
    // Bind "Same as Above" checkbox in edit modal
    $('#editSameAsAbove').on('change', function() {
        copyEditAddress();
    });
    
    // Bind close button for edit modal
    $('#closeEditModal').on('click', function() {
        $('#editModalOverlay').addClass('hidden');
    });
});

//-----------------------------------------------------------------
//End a Address
//-----------------------------------------------------------------


// other buttons

// Function to close the edit modal
function CloseButton() {
    const modal = document.getElementById('editModalOverlay');
    if (modal) {
        modal.classList.add('hidden');
    }
}