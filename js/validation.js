document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const forms = document.querySelectorAll('form.validate-form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let valid = true;
            const errorMessages = [];
            
            // Reset previous error messages
            const existingErrors = form.querySelectorAll('.field-error');
            existingErrors.forEach(error => error.remove());
            
            // Validate required fields
            const requiredFields = form.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    showFieldError(field, 'This field is required');
                    errorMessages.push(`${field.name || 'Field'} is required`);
                }
            });
            
            // Validate email fields
            const emailFields = form.querySelectorAll('input[type="email"]');
            
            emailFields.forEach(field => {
                if (field.value.trim() && !isValidEmail(field.value)) {
                    valid = false;
                    showFieldError(field, 'Please enter a valid email address');
                    errorMessages.push('Invalid email address');
                }
            });
            
            // Validate password fields
            const passwordFields = form.querySelectorAll('input[type="password"][data-min-length]');
            
            passwordFields.forEach(field => {
                const minLength = parseInt(field.getAttribute('data-min-length'), 10) || 6;
                
                if (field.value && field.value.length < minLength) {
                    valid = false;
                    showFieldError(field, `Password must be at least ${minLength} characters`);
                    errorMessages.push(`Password too short (minimum ${minLength} characters)`);
                }
            });
            
            // Validate password confirmation
            const passwordField = form.querySelector('input[name="password"]');
            const confirmField = form.querySelector('input[name="confirm_password"]');
            
            if (passwordField && confirmField && passwordField.value !== confirmField.value) {
                valid = false;
                showFieldError(confirmField, 'Passwords do not match');
                errorMessages.push('Passwords do not match');
            }
            
            // Show form-level error if needed
            if (!valid) {
                e.preventDefault();
                
                const formError = document.createElement('div');
                formError.className = 'error-message';
                formError.innerHTML = '<strong>Please correct the following errors:</strong><ul>' + 
                                     errorMessages.map(msg => `<li>${msg}</li>`).join('') + 
                                     '</ul>';
                
                // Insert at the top of the form
                form.insertBefore(formError, form.firstChild);
                
                // Scroll to the first error
                const firstError = form.querySelector('.field-error, .error-message');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    });
    
    // Real-time validation for inputs
    const validatedInputs = document.querySelectorAll('.validate-input');
    
    validatedInputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        // Clear error on input
        input.addEventListener('input', function() {
            const errorSpan = this.nextElementSibling;
            if (errorSpan && errorSpan.className === 'field-error') {
                errorSpan.remove();
                this.classList.remove('invalid-input');
            }
        });
    });
    
    // Helper functions
    function showFieldError(field, message) {
        // Remove any existing error for this field
        const existingError = field.nextElementSibling;
        if (existingError && existingError.className === 'field-error') {
            existingError.remove();
        }
        
        // Add error class to the input
        field.classList.add('invalid-input');
        
        // Create and insert error message
        const errorSpan = document.createElement('span');
        errorSpan.className = 'field-error';
        errorSpan.textContent = message;
        
        field.parentNode.insertBefore(errorSpan, field.nextSibling);
    }
    
    function validateField(field) {
        // Remove any existing error
        const existingError = field.nextElementSibling;
        if (existingError && existingError.className === 'field-error') {
            existingError.remove();
        }
        
        field.classList.remove('invalid-input');
        
        // Check required
        if (field.hasAttribute('required') && !field.value.trim()) {
            showFieldError(field, 'This field is required');
            return false;
        }
        
        // Check email
        if (field.type === 'email' && field.value.trim() && !isValidEmail(field.value)) {
            showFieldError(field, 'Please enter a valid email address');
            return false;
        }
        
        // Check password length
        if (field.type === 'password' && field.hasAttribute('data-min-length')) {
            const minLength = parseInt(field.getAttribute('data-min-length'), 10) || 6;
            
            if (field.value && field.value.length < minLength) {
                showFieldError(field, `Password must be at least ${minLength} characters`);
                return false;
            }
        }
        
        // Check password confirmation
        if (field.name === 'confirm_password') {
            const passwordField = field.form.querySelector('input[name="password"]');
            
            if (passwordField && field.value !== passwordField.value) {
                showFieldError(field, 'Passwords do not match');
                return false;
            }
        }
        
        return true;
    }
    
    function isValidEmail(email) {
        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }
});
