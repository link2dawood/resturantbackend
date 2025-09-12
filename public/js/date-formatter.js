/**
 * US Date Formatter (MM-DD-YYYY)
 * Formats dates in US format consistently across the application
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Main date formatting functions
    window.dateFormatter = {
        
        /**
         * Format date to MM-DD-YYYY
         */
        format: function(dateValue, separator = '-') {
            if (!dateValue) return '';
            
            let date;
            
            // Handle different input types
            if (typeof dateValue === 'string') {
                // Handle various input formats
                if (dateValue.match(/^\d{4}-\d{2}-\d{2}$/)) {
                    // YYYY-MM-DD format
                    date = new Date(dateValue + 'T00:00:00');
                } else if (dateValue.match(/^\d{2}[-\/]\d{2}[-\/]\d{4}$/)) {
                    // MM-DD-YYYY or MM/DD/YYYY format
                    const parts = dateValue.split(/[-\/]/);
                    date = new Date(parts[2], parts[0] - 1, parts[1]);
                } else {
                    date = new Date(dateValue);
                }
            } else if (dateValue instanceof Date) {
                date = dateValue;
            } else {
                date = new Date(dateValue);
            }
            
            // Check if date is valid
            if (isNaN(date.getTime())) {
                return '';
            }
            
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const year = date.getFullYear();
            
            return `${month}${separator}${day}${separator}${year}`;
        },
        
        /**
         * Convert MM-DD-YYYY to YYYY-MM-DD for backend
         */
        toISO: function(dateValue) {
            if (!dateValue) return '';
            
            // If already in ISO format, return as is
            if (dateValue.match(/^\d{4}-\d{2}-\d{2}$/)) {
                return dateValue;
            }
            
            // Handle MM-DD-YYYY or MM/DD/YYYY
            if (dateValue.match(/^\d{2}[-\/]\d{2}[-\/]\d{4}$/)) {
                const parts = dateValue.split(/[-\/]/);
                const month = parts[0].padStart(2, '0');
                const day = parts[1].padStart(2, '0');
                const year = parts[2];
                return `${year}-${month}-${day}`;
            }
            
            // Try to parse and convert
            const date = new Date(dateValue);
            if (isNaN(date.getTime())) {
                return '';
            }
            
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const year = date.getFullYear();
            
            return `${year}-${month}-${day}`;
        },
        
        /**
         * Get today's date in MM-DD-YYYY format
         */
        today: function() {
            return this.format(new Date());
        },
        
        /**
         * Validate US date format
         */
        validate: function(dateValue) {
            if (!dateValue) return false;
            
            const date = new Date(this.toISO(dateValue));
            return !isNaN(date.getTime());
        },
        
        /**
         * Format for display (with slashes)
         */
        displayFormat: function(dateValue) {
            return this.format(dateValue, '/');
        }
    };
    
    /**
     * Initialize date formatting for all date inputs
     */
    function initDateFormatting() {
        // Find all date inputs
        const dateSelectors = [
            'input[type="date"]',
            'input[name*="date"]',
            'input[id*="date"]',
            'input.date',
            'input.date-input'
        ];
        
        const dateInputs = document.querySelectorAll(dateSelectors.join(', '));
        
        dateInputs.forEach(function(input) {
            // Skip if already processed
            if (input.hasAttribute('data-date-formatted')) {
                return;
            }
            
            input.setAttribute('data-date-formatted', 'true');
            
            // Set input attributes for better UX
            if (input.type !== 'date') {
                input.setAttribute('type', 'text');
                input.setAttribute('placeholder', 'MM-DD-YYYY');
                input.setAttribute('maxlength', '10');
                input.setAttribute('pattern', '\\d{2}-\\d{2}-\\d{4}');
            }
            
            // Format existing value if present
            if (input.value && input.type !== 'date') {
                const formattedValue = window.dateFormatter.format(input.value);
                if (formattedValue) {
                    input.value = formattedValue;
                }
            }
            
            // For HTML5 date inputs, convert display but keep ISO for backend
            if (input.type === 'date') {
                input.addEventListener('change', function(e) {
                    // Add hidden input with MM-DD-YYYY format for display purposes
                    let displayInput = document.getElementById(input.id + '_display');
                    if (!displayInput) {
                        displayInput = document.createElement('input');
                        displayInput.type = 'hidden';
                        displayInput.id = input.id + '_display';
                        displayInput.name = input.name + '_display';
                        input.parentNode.insertBefore(displayInput, input.nextSibling);
                    }
                    displayInput.value = window.dateFormatter.format(e.target.value);
                });
                return; // Skip text input formatting for HTML5 date inputs
            }
            
            // Add input event listener for real-time formatting
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/[^\d]/g, ''); // Remove non-digits
                
                if (value.length >= 2) {
                    value = value.substring(0, 2) + '-' + value.substring(2);
                }
                if (value.length >= 5) {
                    value = value.substring(0, 5) + '-' + value.substring(5, 9);
                }
                
                // Limit to MM-DD-YYYY format
                if (value.length > 10) {
                    value = value.substring(0, 10);
                }
                
                e.target.value = value;
            });
            
            // Add blur event for validation
            input.addEventListener('blur', function(e) {
                if (e.target.value && !window.dateFormatter.validate(e.target.value)) {
                    e.target.classList.add('date-invalid');
                    showDateError(e.target, 'Please enter a valid date in MM-DD-YYYY format');
                } else {
                    e.target.classList.remove('date-invalid');
                    const existingError = e.target.parentNode.querySelector('.date-error');
                    if (existingError) {
                        existingError.remove();
                    }
                }
            });
            
            // Add focus event to clear validation
            input.addEventListener('focus', function(e) {
                e.target.classList.remove('date-invalid');
                const existingError = e.target.parentNode.querySelector('.date-error');
                if (existingError) {
                    existingError.remove();
                }
            });
        });
    }
    
    /**
     * Show date validation error
     */
    function showDateError(input, message) {
        // Remove existing error message
        const existingError = input.parentNode.querySelector('.date-error');
        if (existingError) {
            existingError.remove();
        }
        
        // Create new error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'date-error text-danger small mt-1';
        errorDiv.textContent = message;
        
        // Insert after the input
        input.parentNode.insertBefore(errorDiv, input.nextSibling);
    }
    
    /**
     * Format all date displays on the page
     */
    function formatDateDisplays() {
        // Find elements that display dates (common patterns)
        const dateDisplaySelectors = [
            '.date-display',
            '[data-date]',
            '.created-at',
            '.updated-at',
            '.report-date'
        ];
        
        dateDisplaySelectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(element => {
                const dateValue = element.textContent || element.getAttribute('data-date');
                if (dateValue) {
                    const formatted = window.dateFormatter.displayFormat(dateValue);
                    if (formatted) {
                        element.textContent = formatted;
                    }
                }
            });
        });
    }
    
    /**
     * Convert form data before submission
     */
    function handleFormSubmission() {
        document.addEventListener('submit', function(e) {
            const form = e.target;
            
            // Find all date inputs in the form
            const dateInputs = form.querySelectorAll('input[data-date-formatted="true"]');
            
            dateInputs.forEach(input => {
                if (input.type !== 'date' && input.value) {
                    // Convert MM-DD-YYYY to YYYY-MM-DD for backend
                    const isoDate = window.dateFormatter.toISO(input.value);
                    if (isoDate) {
                        // Create hidden input with ISO date
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = input.name + '_iso';
                        hiddenInput.value = isoDate;
                        form.appendChild(hiddenInput);
                    }
                }
            });
        });
    }
    
    // Initialize everything
    initDateFormatting();
    formatDateDisplays();
    handleFormSubmission();
    
    // Re-initialize when new content is dynamically loaded
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length > 0) {
                initDateFormatting();
                formatDateDisplays();
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Expose formatAllDates function for manual triggering
    window.formatAllDates = function() {
        initDateFormatting();
        formatDateDisplays();
    };
});