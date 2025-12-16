{{-- Global Material UI Component Styles --}}
<style>
    /* ============================================
       MATERIAL UI DESIGN SYSTEM - GLOBAL STYLES
       ============================================ */
    
    /* Typography */
    .material-headline {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        font-size: 2rem;
        font-weight: 400;
        line-height: 2.5rem;
        letter-spacing: 0;
        color: #202124;
        margin: 0;
    }
    
    .material-subtitle {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        font-size: 0.875rem;
        font-weight: 400;
        line-height: 1.25rem;
        color: #5f6368;
        margin: 0.5rem 0 0 0;
    }
    
    /* Cards - Material UI Elevation System */
    .card-material {
        background: #ffffff !important;
        border-radius: 4px;
        box-shadow: 0px 2px 1px -1px rgba(0, 0, 0, 0.2), 
                    0px 1px 1px 0px rgba(0, 0, 0, 0.14), 
                    0px 1px 3px 0px rgba(0, 0, 0, 0.12);
        transition: box-shadow 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
        height: 100%;
    }
    
    .card-material:hover {
        background: #ffffff !important;
        box-shadow: 0px 2px 4px -1px rgba(0, 0, 0, 0.2), 
                    0px 4px 5px 0px rgba(0, 0, 0, 0.14), 
                    0px 1px 10px 0px rgba(0, 0, 0, 0.12);
    }
    
    .card-material.elevation-2 {
        box-shadow: 0px 3px 1px -2px rgba(0, 0, 0, 0.2), 
                    0px 2px 2px 0px rgba(0, 0, 0, 0.14), 
                    0px 1px 5px 0px rgba(0, 0, 0, 0.12);
    }
    
    .card-material.elevation-4 {
        box-shadow: 0px 2px 4px -1px rgba(0, 0, 0, 0.2), 
                    0px 4px 5px 0px rgba(0, 0, 0, 0.14), 
                    0px 1px 10px 0px rgba(0, 0, 0, 0.12);
    }
    
    /* Buttons - Material UI Design System */
    .btn-material,
    button.btn-material,
    a.btn-material {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        font-size: 0.875rem;
        font-weight: 500;
        letter-spacing: 0.0892857143em;
        text-transform: uppercase;
        padding: 0.625rem 1.5rem;
        border-radius: 4px;
        border: none;
        box-shadow: 0px 3px 1px -2px rgba(0, 0, 0, 0.2), 
                    0px 2px 2px 0px rgba(0, 0, 0, 0.14), 
                    0px 1px 5px 0px rgba(0, 0, 0, 0.12);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        min-width: 64px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        text-decoration: none;
        cursor: pointer;
        line-height: 1;
    }
    
    .btn-material:hover,
    button.btn-material:hover,
    a.btn-material:hover {
        box-shadow: 0px 2px 4px -1px rgba(0, 0, 0, 0.2), 
                    0px 4px 5px 0px rgba(0, 0, 0, 0.14), 
                    0px 1px 10px 0px rgba(0, 0, 0, 0.12);
        transform: translateY(-1px);
    }
    
    .btn-material:active,
    button.btn-material:active,
    a.btn-material:active {
        box-shadow: 0px 5px 5px -3px rgba(0, 0, 0, 0.2), 
                    0px 8px 10px 1px rgba(0, 0, 0, 0.14), 
                    0px 3px 14px 2px rgba(0, 0, 0, 0.12);
        transform: translateY(0);
    }
    
    .btn-material:disabled,
    button.btn-material:disabled,
    a.btn-material:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        box-shadow: none;
    }
    
    /* Button Variants */
    .btn-material-primary {
        background-color: #1976d2;
        color: #fff;
    }
    
    .btn-material-primary:hover {
        background-color: #1565c0;
        color: #fff;
    }
    
    .btn-material-secondary {
        background-color: #757575;
        color: #fff;
    }
    
    .btn-material-secondary:hover {
        background-color: #616161;
        color: #fff;
    }
    
    .btn-material-success {
        background-color: #4caf50;
        color: #fff;
    }
    
    .btn-material-success:hover {
        background-color: #43a047;
        color: #fff;
    }
    
    .btn-material-danger {
        background-color: #d32f2f;
        color: #fff;
    }
    
    .btn-material-danger:hover {
        background-color: #c62828;
        color: #fff;
    }
    
    .btn-material-warning {
        background-color: #f57c00;
        color: #fff;
    }
    
    .btn-material-warning:hover {
        background-color: #ef6c00;
        color: #fff;
    }
    
    .btn-material-outlined {
        background-color: transparent;
        border: 1px solid rgba(0, 0, 0, 0.12);
        color: #1976d2;
        box-shadow: none;
    }
    
    .btn-material-outlined:hover {
        background-color: rgba(25, 118, 210, 0.04);
        border-color: #1976d2;
        box-shadow: none;
    }
    
    .btn-material-text {
        background-color: transparent;
        border: none;
        color: #1976d2;
        box-shadow: none;
        padding: 0.5rem 1rem;
    }
    
    .btn-material-text:hover {
        background-color: rgba(25, 118, 210, 0.04);
        box-shadow: none;
    }
    
    /* Button Sizes */
    .btn-material-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.8125rem;
        min-width: auto;
        height: 32px;
    }
    
    .btn-material-lg {
        padding: 0.875rem 2rem;
        font-size: 0.9375rem;
        min-width: 80px;
        height: 42px;
    }
    
    /* Navigation Links - Material UI Style */
    .nav-link-material {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        font-size: 0.875rem;
        font-weight: 500;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        color: #5f6368;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }
    
    .nav-link-material:hover {
        background-color: #f1f3f4;
        color: #1a73e8;
    }
    
    .nav-link-material.active {
        background-color: #1976d2;
        color: #fff;
    }
    
    .nav-link-material.active:hover {
        background-color: #1565c0;
        color: #fff;
    }
    
    /* Tables - Material UI Style */
    .table-material {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        width: 100%;
        border-collapse: collapse;
    }
    
    .table-material thead th {
        font-size: 0.75rem;
        font-weight: 500;
        color: rgba(0, 0, 0, 0.6);
        text-transform: uppercase;
        letter-spacing: 0.03333em;
        border-bottom: 1px solid rgba(0, 0, 0, 0.12);
        padding: 1rem;
        text-align: left;
        background: #fafafa;
    }
    
    .table-material tbody td {
        padding: 1rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.12);
        color: rgba(0, 0, 0, 0.87);
    }
    
    .table-material tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.04);
    }
    
    /* Form Controls - Material UI Style */
    .form-control-material {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        font-size: 0.875rem;
        width: 100%;
        padding: 0.75rem;
        border: 1px solid rgba(0, 0, 0, 0.12);
        border-radius: 4px 4px 0 0;
        border-bottom: 2px solid rgba(0, 0, 0, 0.42);
        background: #f5f5f5;
        color: rgba(0, 0, 0, 0.87);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .form-control-material:focus {
        outline: none;
        background: #fff;
        border-bottom-color: #1976d2;
        box-shadow: 0 1px 0 0 #1976d2;
    }
    
    /* Badges - Material UI Style */
    .badge-material {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        font-size: 0.75rem;
        font-weight: 500;
        padding: 0.25rem 0.75rem;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .material-headline {
            font-size: 1.5rem;
            line-height: 2rem;
        }
        
        .btn-material {
            font-size: 0.8125rem;
            padding: 0.5rem 1rem;
            min-width: 56px;
            height: 32px;
        }
    }
</style>

