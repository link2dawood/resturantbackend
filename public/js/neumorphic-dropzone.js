/**
 * Neumorphic Dropzone Component
 * Secure file upload with drag and drop functionality
 */

class NeumorphicDropzone {
    constructor(element, options = {}) {
        this.element = element;
        this.options = {
            maxFileSize: 2 * 1024 * 1024, // 2MB
            allowedTypes: ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'],
            maxFiles: 1,
            previewContainer: null,
            uploadUrl: '/profile/avatar',
            csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            ...options
        };
        
        this.files = [];
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.createFileInput();
        this.updateUI();
    }
    
    setupEventListeners() {
        // Drag and drop events
        this.element.addEventListener('dragover', this.handleDragOver.bind(this));
        this.element.addEventListener('dragleave', this.handleDragLeave.bind(this));
        this.element.addEventListener('drop', this.handleDrop.bind(this));
        this.element.addEventListener('click', this.handleClick.bind(this));
        
        // Prevent default drag behaviors on document
        document.addEventListener('dragenter', this.preventDefaults);
        document.addEventListener('dragover', this.preventDefaults);
        document.addEventListener('dragleave', this.preventDefaults);
        document.addEventListener('drop', this.preventDefaults);
    }
    
    createFileInput() {
        this.fileInput = document.createElement('input');
        this.fileInput.type = 'file';
        this.fileInput.accept = this.options.allowedTypes.join(',');
        this.fileInput.multiple = this.options.maxFiles > 1;
        this.fileInput.style.display = 'none';
        this.fileInput.addEventListener('change', this.handleFileSelect.bind(this));
        this.element.appendChild(this.fileInput);
    }
    
    preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    handleDragOver(e) {
        this.preventDefaults(e);
        this.element.classList.add('dragover');
    }
    
    handleDragLeave(e) {
        this.preventDefaults(e);
        if (!this.element.contains(e.relatedTarget)) {
            this.element.classList.remove('dragover');
        }
    }
    
    handleDrop(e) {
        this.preventDefaults(e);
        this.element.classList.remove('dragover');
        
        const files = Array.from(e.dataTransfer.files);
        this.processFiles(files);
    }
    
    handleClick() {
        this.fileInput.click();
    }
    
    handleFileSelect(e) {
        const files = Array.from(e.target.files);
        this.processFiles(files);
    }
    
    processFiles(files) {
        // Validate and filter files
        const validFiles = files.filter(file => this.validateFile(file));
        
        if (validFiles.length === 0) {
            return;
        }
        
        // Respect maxFiles limit
        if (this.options.maxFiles === 1) {
            this.files = [validFiles[0]];
        } else {
            this.files = [...this.files, ...validFiles].slice(0, this.options.maxFiles);
        }
        
        this.updateUI();
        this.createPreviews();
    }
    
    validateFile(file) {
        // Check file type
        if (!this.options.allowedTypes.includes(file.type)) {
            this.showError(`Invalid file type. Allowed types: ${this.options.allowedTypes.join(', ')}`);
            return false;
        }
        
        // Check file size
        if (file.size > this.options.maxFileSize) {
            this.showError(`File too large. Maximum size: ${this.formatFileSize(this.options.maxFileSize)}`);
            return false;
        }
        
        // Additional security checks
        if (!this.isSecureFile(file)) {
            this.showError('File failed security validation');
            return false;
        }
        
        return true;
    }
    
    isSecureFile(file) {
        // Check file extension matches MIME type
        const extension = file.name.split('.').pop().toLowerCase();
        const validExtensions = {
            'image/jpeg': ['jpg', 'jpeg'],
            'image/png': ['png'],
            'image/gif': ['gif']
        };
        
        const allowedExtensions = validExtensions[file.type];
        if (!allowedExtensions || !allowedExtensions.includes(extension)) {
            return false;
        }
        
        // Check for suspicious file names
        const suspiciousPatterns = [
            /\.php/i,
            /\.exe/i,
            /\.js/i,
            /\.html/i,
            /\.htm/i,
            /script/i
        ];
        
        return !suspiciousPatterns.some(pattern => pattern.test(file.name));
    }
    
    updateUI() {
        if (this.files.length > 0) {
            this.element.classList.add('has-files');
            const file = this.files[0];
            this.element.innerHTML = `
                <div class="dropzone-content">
                    <div class="file-info">
                        <svg class="file-icon" width="48" height="48" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                        </svg>
                        <div class="file-name">${file.name}</div>
                        <div class="file-size">${this.formatFileSize(file.size)}</div>
                    </div>
                    <button type="button" class="remove-file neuro-btn neuro-btn-danger" onclick="dropzone.removeFile(0)">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
                        </svg>
                        Remove
                    </button>
                </div>
            `;
        } else {
            this.element.classList.remove('has-files');
            this.element.innerHTML = `
                <div class="dropzone-content">
                    <svg class="upload-icon" width="64" height="64" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                    </svg>
                    <h4>Drop your avatar here</h4>
                    <p>or <strong>click to browse</strong></p>
                    <small>Supports: JPG, PNG, GIF (max 2MB)</small>
                </div>
            `;
        }
    }
    
    createPreviews() {
        if (!this.options.previewContainer) return;
        
        const container = document.querySelector(this.options.previewContainer);
        if (!container) return;
        
        container.innerHTML = '';
        
        this.files.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const preview = document.createElement('div');
                preview.className = 'neuro-card preview-item';
                preview.innerHTML = `
                    <img src="${e.target.result}" alt="Preview" style="width: 100%; height: 200px; object-fit: cover; border-radius: 12px;">
                    <p class="mt-2 mb-0">${file.name}</p>
                `;
                container.appendChild(preview);
            };
            reader.readAsDataURL(file);
        });
    }
    
    removeFile(index) {
        this.files.splice(index, 1);
        this.updateUI();
        if (this.options.previewContainer) {
            this.createPreviews();
        }
    }
    
    async upload() {
        if (this.files.length === 0) {
            this.showError('No files selected');
            return false;
        }
        
        const formData = new FormData();
        formData.append('avatar', this.files[0]);
        formData.append('_token', this.options.csrfToken);
        
        try {
            this.setLoading(true);
            
            const response = await fetch(this.options.uploadUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.options.csrfToken
                }
            });
            
            if (response.ok) {
                this.showSuccess('Avatar uploaded successfully!');
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
                return true;
            } else {
                const error = await response.text();
                this.showError(`Upload failed: ${error}`);
                return false;
            }
            
        } catch (error) {
            this.showError(`Upload error: ${error.message}`);
            return false;
        } finally {
            this.setLoading(false);
        }
    }
    
    setLoading(loading) {
        if (loading) {
            this.element.classList.add('loading');
            this.element.style.pointerEvents = 'none';
        } else {
            this.element.classList.remove('loading');
            this.element.style.pointerEvents = 'auto';
        }
    }
    
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    showError(message) {
        this.showNotification(message, 'error');
    }
    
    showSuccess(message) {
        this.showNotification(message, 'success');
    }
    
    showNotification(message, type = 'info') {
        // Remove existing notifications
        const existing = document.querySelectorAll('.neuro-notification');
        existing.forEach(el => el.remove());
        
        // Create new notification
        const notification = document.createElement('div');
        notification.className = `neuro-notification neuro-alert neuro-alert-${type === 'error' ? 'danger' : type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: neuroFadeIn 0.3s ease-out;
        `;
        notification.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; font-size: 18px; cursor: pointer;">&times;</button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }
}

// Auto-initialize dropzone elements
document.addEventListener('DOMContentLoaded', function() {
    const dropzoneElements = document.querySelectorAll('.neuro-dropzone[data-auto-init="true"]');
    dropzoneElements.forEach(element => {
        const options = {
            uploadUrl: element.dataset.uploadUrl || '/profile/avatar',
            previewContainer: element.dataset.previewContainer || null,
            maxFileSize: parseInt(element.dataset.maxFileSize) || 2 * 1024 * 1024
        };
        
        window[element.id + 'Dropzone'] = new NeumorphicDropzone(element, options);
    });
});