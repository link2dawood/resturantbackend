/**
 * Responsive JavaScript Enhancements for Restaurant Backend
 * Provides mobile-friendly interactions and optimizations
 */

class ResponsiveEnhancer {
    constructor() {
        this.isMobile = window.innerWidth <= 768;
        this.isTablet = window.innerWidth > 768 && window.innerWidth <= 1024;
        this.init();
    }

    init() {
        this.setupMobileNavigation();
        this.setupTouchEnhancements();
        this.setupResponsiveCards();
        this.setupMobileForms();
        this.setupResponsiveTables();
        this.handleResizeEvents();
        this.setupAccessibilityFeatures();
    }

    /**
     * Enhanced mobile navigation
     */
    setupMobileNavigation() {
        const navToggler = document.querySelector('.navbar-toggler');
        const navCollapse = document.querySelector('.navbar-collapse');
        
        if (navToggler && navCollapse) {
            // Close mobile nav when clicking outside
            document.addEventListener('click', (e) => {
                if (this.isMobile && 
                    navCollapse.classList.contains('show') && 
                    !navCollapse.contains(e.target) && 
                    !navToggler.contains(e.target)) {
                    
                    const bsCollapse = new bootstrap.Collapse(navCollapse, {
                        toggle: false
                    });
                    bsCollapse.hide();
                }
            });

            // Close mobile nav when clicking on links
            const navLinks = navCollapse.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    if (this.isMobile && navCollapse.classList.contains('show')) {
                        const bsCollapse = new bootstrap.Collapse(navCollapse, {
                            toggle: false
                        });
                        bsCollapse.hide();
                    }
                });
            });
        }
    }

    /**
     * Touch enhancements for mobile devices
     */
    setupTouchEnhancements() {
        if (!this.isMobile) return;

        // Add touch feedback to buttons
        const buttons = document.querySelectorAll('.btn, .nav-link, .dropdown-item');
        buttons.forEach(btn => {
            btn.addEventListener('touchstart', function() {
                this.classList.add('touch-active');
            });
            
            btn.addEventListener('touchend', function() {
                setTimeout(() => {
                    this.classList.remove('touch-active');
                }, 150);
            });
        });

        // Prevent double-tap zoom on form elements
        const formElements = document.querySelectorAll('input, select, textarea');
        formElements.forEach(element => {
            element.addEventListener('touchend', (e) => {
                e.preventDefault();
                element.focus();
            });
        });

        // Add swipe gestures for cards (if needed)
        this.setupSwipeGestures();
    }

    /**
     * Responsive card enhancements
     */
    setupResponsiveCards() {
        const cards = document.querySelectorAll('.card');
        
        cards.forEach(card => {
            // Add mobile-specific styling
            if (this.isMobile) {
                card.classList.add('mobile-card');
            }

            // Make cards collapsible on mobile if they have long content
            const cardBody = card.querySelector('.card-body');
            if (cardBody && this.isMobile && cardBody.scrollHeight > 300) {
                this.makeCardCollapsible(card, cardBody);
            }
        });
    }

    /**
     * Make card collapsible for better mobile UX
     */
    makeCardCollapsible(card, cardBody) {
        const cardHeader = card.querySelector('.card-header');
        if (!cardHeader) return;

        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'btn btn-sm btn-outline-secondary ms-2';
        toggleBtn.innerHTML = '<i class="fas fa-chevron-down"></i>';
        toggleBtn.type = 'button';
        
        cardHeader.appendChild(toggleBtn);
        cardBody.style.maxHeight = '200px';
        cardBody.style.overflow = 'hidden';
        cardBody.classList.add('collapsed');

        toggleBtn.addEventListener('click', () => {
            if (cardBody.classList.contains('collapsed')) {
                cardBody.style.maxHeight = cardBody.scrollHeight + 'px';
                cardBody.classList.remove('collapsed');
                toggleBtn.innerHTML = '<i class="fas fa-chevron-up"></i>';
            } else {
                cardBody.style.maxHeight = '200px';
                cardBody.classList.add('collapsed');
                toggleBtn.innerHTML = '<i class="fas fa-chevron-down"></i>';
            }
        });
    }

    /**
     * Mobile form enhancements
     */
    setupMobileForms() {
        if (!this.isMobile) return;

        // Enhance select dropdowns for mobile
        const selects = document.querySelectorAll('select');
        selects.forEach(select => {
            // Add native mobile styling
            select.classList.add('mobile-select');
            
            // Auto-focus and scroll to view when opened
            select.addEventListener('focus', () => {
                setTimeout(() => {
                    select.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                }, 100);
            });
        });

        // Enhance file inputs for mobile
        const fileInputs = document.querySelectorAll('input[type="file"]');
        fileInputs.forEach(input => {
            const wrapper = document.createElement('div');
            wrapper.className = 'mobile-file-input-wrapper';
            
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);

            const label = document.createElement('label');
            label.className = 'mobile-file-label btn btn-outline-primary';
            label.textContent = 'Choose File';
            label.htmlFor = input.id;
            
            wrapper.appendChild(label);

            input.addEventListener('change', () => {
                if (input.files.length > 0) {
                    label.textContent = `Selected: ${input.files[0].name}`;
                    label.classList.remove('btn-outline-primary');
                    label.classList.add('btn-success');
                }
            });
        });

        // Virtual keyboard handling
        this.handleVirtualKeyboard();
    }

    /**
     * Handle virtual keyboard on mobile
     */
    handleVirtualKeyboard() {
        const inputs = document.querySelectorAll('input, textarea, select');
        let initialViewportHeight = window.innerHeight;

        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                // Detect virtual keyboard
                setTimeout(() => {
                    if (window.innerHeight < initialViewportHeight * 0.8) {
                        document.body.classList.add('virtual-keyboard-open');
                        
                        // Scroll input into view
                        input.scrollIntoView({ 
                            behavior: 'smooth', 
                            block: 'center' 
                        });
                    }
                }, 300);
            });

            input.addEventListener('blur', () => {
                setTimeout(() => {
                    if (window.innerHeight >= initialViewportHeight * 0.8) {
                        document.body.classList.remove('virtual-keyboard-open');
                    }
                }, 300);
            });
        });
    }

    /**
     * Responsive table enhancements
     */
    setupResponsiveTables() {
        const tables = document.querySelectorAll('.table-responsive table');
        
        tables.forEach(table => {
            if (this.isMobile) {
                this.enhanceTableForMobile(table);
            }
        });
    }

    /**
     * Enhance table for mobile viewing
     */
    enhanceTableForMobile(table) {
        const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
        const rows = table.querySelectorAll('tbody tr');

        // Add data labels for mobile stacking
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            cells.forEach((cell, index) => {
                if (headers[index]) {
                    cell.setAttribute('data-label', headers[index]);
                }
            });
        });

        // Add mobile table class
        table.classList.add('mobile-enhanced-table');

        // Add horizontal scroll indicator
        const tableContainer = table.closest('.table-responsive');
        if (tableContainer) {
            const scrollIndicator = document.createElement('div');
            scrollIndicator.className = 'mobile-scroll-indicator';
            scrollIndicator.innerHTML = '← Scroll for more →';
            tableContainer.appendChild(scrollIndicator);

            // Hide indicator when not needed
            const checkScroll = () => {
                if (tableContainer.scrollWidth <= tableContainer.clientWidth) {
                    scrollIndicator.style.display = 'none';
                } else {
                    scrollIndicator.style.display = 'block';
                }
            };

            checkScroll();
            window.addEventListener('resize', checkScroll);
        }
    }

    /**
     * Setup swipe gestures for mobile
     */
    setupSwipeGestures() {
        let startX = 0;
        let startY = 0;

        document.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
        });

        document.addEventListener('touchmove', (e) => {
            if (!startX || !startY) return;

            const currentX = e.touches[0].clientX;
            const currentY = e.touches[0].clientY;

            const diffX = startX - currentX;
            const diffY = startY - currentY;

            // Horizontal swipe detection
            if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
                if (diffX > 0) {
                    // Swipe left - could be used for navigation
                    this.handleSwipeLeft();
                } else {
                    // Swipe right - could be used for navigation
                    this.handleSwipeRight();
                }
            }

            startX = 0;
            startY = 0;
        });
    }

    /**
     * Handle swipe left gesture
     */
    handleSwipeLeft() {
        // Could be used for navigation or card actions
        // For now, just log the action
        console.log('Swipe left detected');
    }

    /**
     * Handle swipe right gesture
     */
    handleSwipeRight() {
        // Could be used for navigation or showing mobile menu
        console.log('Swipe right detected');
    }

    /**
     * Handle window resize events
     */
    handleResizeEvents() {
        let resizeTimeout;
        
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                const wasMobile = this.isMobile;
                this.isMobile = window.innerWidth <= 768;
                this.isTablet = window.innerWidth > 768 && window.innerWidth <= 1024;

                // Reinitialize if device type changed
                if (wasMobile !== this.isMobile) {
                    this.handleDeviceTypeChange();
                }
            }, 250);
        });
    }

    /**
     * Handle device type change (mobile to desktop or vice versa)
     */
    handleDeviceTypeChange() {
        // Remove old classes
        document.body.classList.remove('mobile-enhanced', 'tablet-enhanced', 'desktop-enhanced');
        
        // Add appropriate class
        if (this.isMobile) {
            document.body.classList.add('mobile-enhanced');
        } else if (this.isTablet) {
            document.body.classList.add('tablet-enhanced');
        } else {
            document.body.classList.add('desktop-enhanced');
        }

        // Reinitialize components that are device-specific
        this.setupResponsiveCards();
        this.setupResponsiveTables();
    }

    /**
     * Setup accessibility features
     */
    // setupAccessibilityFeatures() {
    //     // Add skip to main content link
    //     const skipLink = document.createElement('a');
    //     skipLink.href = '#main-content';
    //     skipLink.className = 'skip-link';
    //     skipLink.textContent = 'Skip to main content';
    //     document.body.insertBefore(skipLink, document.body.firstChild);

    //     // Add main content ID if not exists
    //     const mainContent = document.querySelector('.page-wrapper') || document.querySelector('main') || document.querySelector('.container-xl');
    //     if (mainContent && !mainContent.id) {
    //         mainContent.id = 'main-content';
    //     }

    //     // Enhance focus management
    //     this.enhanceFocusManagement();
    // }

    /**
     * Enhance focus management for better accessibility
     */
    enhanceFocusManagement() {
        // Add visible focus indicators
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                document.body.classList.add('keyboard-navigation');
            }
        });

        document.addEventListener('mousedown', () => {
            document.body.classList.remove('keyboard-navigation');
        });

        // Trap focus in modals
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.addEventListener('shown.bs.modal', () => {
                const focusableElements = modal.querySelectorAll(
                    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
                );
                if (focusableElements.length) {
                    focusableElements[0].focus();
                }
            });
        });
    }
}

// CSS for responsive enhancements
const responsiveStyles = `
    <style>
        /* Touch feedback */
        .touch-active {
            transform: scale(0.98);
            opacity: 0.8;
        }

        /* Mobile file input styling */
        .mobile-file-input-wrapper input[type="file"] {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0,0,0,0);
            border: 0;
        }

        .mobile-file-label {
            width: 100%;
            cursor: pointer;
        }

        /* Virtual keyboard adjustments */
        .virtual-keyboard-open .page-wrapper {
            padding-bottom: 0;
        }

        /* Mobile table scroll indicator */
        .mobile-scroll-indicator {
            text-align: center;
            padding: 0.5rem;
            font-size: 0.8rem;
            color: var(--text-muted);
            background: var(--bg-secondary);
            border-top: 1px solid var(--border-color);
        }

        /* Focus management */
        .keyboard-navigation *:focus {
            outline: 2px solid var(--accent-color) !important;
            outline-offset: 2px !important;
        }

        body:not(.keyboard-navigation) *:focus {
            outline: none !important;
        }

        /* Mobile card enhancements */
        @media (max-width: 768px) {
            .mobile-card {
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                margin-bottom: 1rem;
            }

            .mobile-enhanced-table.table-stack {
                font-size: 0.9rem;
            }
        }
    </style>
`;

// Initialize responsive enhancements when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Add responsive styles
    document.head.insertAdjacentHTML('beforeend', responsiveStyles);
    
    // Initialize responsive enhancer
    new ResponsiveEnhancer();
    
    // Add device class to body
    const deviceClass = window.innerWidth <= 768 ? 'mobile-enhanced' : 
                       window.innerWidth <= 1024 ? 'tablet-enhanced' : 'desktop-enhanced';
    document.body.classList.add(deviceClass);
});

// Export for module usage if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ResponsiveEnhancer;
}