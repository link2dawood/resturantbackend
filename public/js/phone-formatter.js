/**
 * US Phone Number Formatter
 * Formats phone numbers in real-time to (XXX) XXX-XXXX format
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Function to format phone number
    function formatPhoneNumber(value) {
        // Remove all non-digit characters
        const phoneNumber = value.replace(/\D/g, '');
        const phoneNumberLength = phoneNumber.length;
        
        if (phoneNumberLength < 4) return phoneNumber;
        if (phoneNumberLength < 7) {
            return `(${phoneNumber.slice(0, 3)}) ${phoneNumber.slice(3)}`;
        }
        return `(${phoneNumber.slice(0, 3)}) ${phoneNumber.slice(3, 6)}-${phoneNumber.slice(6, 10)}`;
    }
    
    // Function to get clean phone number (digits only)
    function getCleanPhoneNumber(value) {
        return value.replace(/\D/g, '');
    }
    
    // Function to validate US phone number
    function isValidUSPhone(value) {
        const cleanPhone = getCleanPhoneNumber(value);
        return cleanPhone.length === 10 && /^[2-9]\d{2}[2-9]\d{6}$/.test(cleanPhone);
    }
    
    // Apply formatting to all phone input fields
    function initPhoneFormatting() {
        // Find all phone inputs by various selectors
        const phoneSelectors = [
            'input[name*="phone"]',
            'input[id*="phone"]',
            'input[type="tel"]',
            'input.phone',
            'input.phone-input'
        ];
        
        const phoneInputs = document.querySelectorAll(phoneSelectors.join(', '));
        
        phoneInputs.forEach(function(input) {
            // Set input type and attributes
            input.setAttribute('type', 'tel');
            input.setAttribute('maxlength', '14');
            input.setAttribute('placeholder', '(555) 123-4567');
            input.setAttribute('autocomplete', 'tel');
            
            // Format existing value on page load
            if (input.value) {
                input.value = formatPhoneNumber(input.value);
            }
            
            // Add input event listener for real-time formatting
            input.addEventListener('input', function(e) {
                const cursorPosition = e.target.selectionStart;
                const previousValue = e.target.value;
                const formattedValue = formatPhoneNumber(e.target.value);
                
                e.target.value = formattedValue;
                
                // Maintain cursor position
                let newCursorPosition = cursorPosition;
                if (formattedValue.length > previousValue.length) {
                    newCursorPosition = cursorPosition + (formattedValue.length - previousValue.length);
                }
                
                // Set cursor position after formatting
                setTimeout(() => {
                    e.target.setSelectionRange(newCursorPosition, newCursorPosition);
                }, 0);
            });
            
            // Add paste event listener
            input.addEventListener('paste', function(e) {
                setTimeout(() => {
                    e.target.value = formatPhoneNumber(e.target.value);
                }, 0);
            });
            
            // Add validation on blur
            input.addEventListener('blur', function(e) {
                const cleanPhone = getCleanPhoneNumber(e.target.value);
                
                // Remove existing validation classes
                e.target.classList.remove('phone-invalid', 'phone-valid');
                
                if (e.target.value && cleanPhone.length > 0) {
                    if (isValidUSPhone(e.target.value)) {
                        e.target.classList.add('phone-valid');
                        // Remove any existing error messages
                        const existingError = e.target.parentNode.querySelector('.phone-error');
                        if (existingError) {
                            existingError.remove();
                        }
                    } else if (cleanPhone.length === 10) {
                        // 10 digits but invalid format
                        e.target.classList.add('phone-invalid');
                        showPhoneError(e.target, 'Please enter a valid US phone number');
                    } else if (cleanPhone.length > 0 && cleanPhone.length !== 10) {
                        // Wrong number of digits
                        e.target.classList.add('phone-invalid');
                        showPhoneError(e.target, 'Phone number must be 10 digits');
                    }
                }
            });
            
            // Add focus event to select all on focus (optional UX improvement)
            input.addEventListener('focus', function(e) {
                // Remove validation classes on focus
                e.target.classList.remove('phone-invalid', 'phone-valid');
                const existingError = e.target.parentNode.querySelector('.phone-error');
                if (existingError) {
                    existingError.remove();
                }
            });
        });
    }
    
    // Function to show phone validation error
    function showPhoneError(input, message) {
        // Remove existing error message
        const existingError = input.parentNode.querySelector('.phone-error');
        if (existingError) {
            existingError.remove();
        }
        
        // Create new error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'phone-error text-danger small mt-1';
        errorDiv.textContent = message;
        
        // Insert after the input
        input.parentNode.insertBefore(errorDiv, input.nextSibling);
    }
    
    // Initialize phone formatting
    initPhoneFormatting();
    
    // Re-initialize when new content is dynamically loaded
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length > 0) {
                initPhoneFormatting();
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Export functions for global use if needed
    window.phoneFormatter = {
        format: formatPhoneNumber,
        clean: getCleanPhoneNumber,
        validate: isValidUSPhone,
        init: initPhoneFormatting
    };
});