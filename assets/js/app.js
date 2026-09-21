// assets/js/app.js

function showNotification(message, type = 'success') {
    const isDarkMode = document.documentElement.classList.contains('dark');
    // Fallback to native alert if SweetAlert2 isn't available
    if (typeof Swal === 'undefined' || !Swal.fire) {
        try {
            console.warn('SweetAlert2 not found. Falling back to alert for:', message);
        } catch (e) {}
        alert(message);
        return;
    }

    Swal.fire({
        icon: type,
        title: message,
        background: isDarkMode ? '#1f2937' : '#fff',
        color: isDarkMode ? '#f3f4f6' : '#111827',
        confirmButtonColor: '#3B82F6'
    });
}

function showConfirmation({
    title = 'Confirm Action',
    text = '',
    icon = 'question',
    confirmButtonText = 'Yes, proceed!',
    cancelButtonText = 'Cancel'
} = {}) {
    const isDarkMode = document.documentElement.classList.contains('dark');
    if (typeof Swal === 'undefined' || !Swal.fire) {
        const message = title ? `${title}\n\n${text}` : text;
        return Promise.resolve({ isConfirmed: window.confirm(message || 'Are you sure?') });
    }

    return Swal.fire({
        title,
        text,
        icon,
        showCancelButton: true,
        confirmButtonColor: '#3B82F6',
        cancelButtonColor: '#d33',
        confirmButtonText,
        cancelButtonText,
        background: isDarkMode ? '#1f2937' : '#fff',
        color: isDarkMode ? '#f3f4f6' : '#111827'
    });
}

function initApp() {
    // Handle server-side notifications passed from PHP via a hidden div
    const serverNotification = document.querySelector('.server-notification');
    if (serverNotification) {
        const message = serverNotification.dataset.notificationMessage;
        const type = serverNotification.dataset.notificationType;
        if (message && type) {
            showNotification(message, type);
        }
    }

    const themeToggleBtn = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');
    const html = document.documentElement;

    // Check for saved theme in localStorage, falling back to system preference
    const isDark = localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches);
    if (isDark) {
        html.classList.add('dark');
        if(themeIcon) themeIcon.setAttribute('name', 'sunny');
    } else {
        html.classList.remove('dark');
        if(themeIcon) themeIcon.setAttribute('name', 'moon');
    }

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
                if(themeIcon) themeIcon.setAttribute('name', 'moon');
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
                if(themeIcon) themeIcon.setAttribute('name', 'sunny');
            }
        });
    }

    // Modal handling
    const addAdminBtn = document.getElementById('addAdminBtn');
    const addUserBtn = document.getElementById('addUserBtn');
    const addUserBtnSA = document.getElementById('addUserBtnSA');
    const addAdminModal = document.getElementById('addAdminModal');
    const addUserModal = document.getElementById('addUserModal');
    const closeAddAdminModal = document.getElementById('closeAddAdminModal');
    const closeAddUserModal = document.getElementById('closeAddUserModal');

    if (addAdminBtn && addAdminModal) {
        addAdminBtn.addEventListener('click', () => {
            addAdminModal.classList.remove('hidden');
            addAdminModal.classList.add('flex');
        });
    }

    if (addUserBtn && addUserModal) {
        addUserBtn.addEventListener('click', () => {
            addUserModal.classList.remove('hidden');
            addUserModal.classList.add('flex');
        });
    }

    if (addUserBtnSA && addUserModal) {
        addUserBtnSA.addEventListener('click', () => {
            addUserModal.classList.remove('hidden');
            addUserModal.classList.add('flex');
        });
    }

    if (closeAddAdminModal && addAdminModal) {
        closeAddAdminModal.addEventListener('click', () => {
            addAdminModal.classList.add('hidden');
            addAdminModal.classList.remove('flex');
        });
    }

    if (closeAddUserModal && addUserModal) {
        closeAddUserModal.addEventListener('click', () => {
            addUserModal.classList.add('hidden');
            addUserModal.classList.remove('flex');
        });
    }

    // Close modal only when clicking directly on the backdrop (not on autofill dropdowns or inner content)
    let mousedownTarget = null;
    window.addEventListener('mousedown', (event) => {
        mousedownTarget = event.target;
    });
    window.addEventListener('click', (event) => {
        if (mousedownTarget == addAdminModal && event.target == addAdminModal) {
            addAdminModal.classList.add('hidden');
            addAdminModal.classList.remove('flex');
        }
        if (mousedownTarget == addUserModal && event.target == addUserModal) {
            addUserModal.classList.add('hidden');
            addUserModal.classList.remove('flex');
        }
    });

    // Track which submit button triggered each form submission, with fallback for browsers without event.submitter
    document.addEventListener('click', function (event) {
        const submitButton = event.target.closest('button[type="submit"], input[type="submit"]');
        if (submitButton && submitButton.form && submitButton.form.matches('form.confirm-submit-form, form.ajax-submit-form')) {
            submitButton.form._activeSubmitterName = submitButton.name;
            submitButton.form._activeSubmitterValue = submitButton.value || '';
        }

        const link = event.target.closest('a.confirm-delete-link');
        if (link) {
            event.preventDefault();
            const url = link.href;
            const message = link.dataset.confirmMessage || "This action cannot be undone!";

            showConfirmation({
                title: 'Are you sure?',
                text: message,
                icon: 'warning',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        }

        // Handle product image popup
        if (event.target.matches('.product-image-popup')) {
            const img = event.target;
            const src = img.src;
            const alt = img.alt;
            const isDarkMode = document.documentElement.classList.contains('dark');

            Swal.fire({
                title: alt,
                imageUrl: src,
                imageAlt: alt,
                confirmButtonText: 'Close',
                background: isDarkMode ? '#1f2937' : '#fff',
                color: isDarkMode ? '#f3f4f6' : '#111827',
                confirmButtonColor: '#3B82F6' // brand-blue
            });
        }
    });

    // Consolidated form submission handler using event delegation
    document.addEventListener('submit', function(event) {
        const form = event.target;

        // Handle modern AJAX forms (modals and dedicated create pages)
        if (form.matches('.ajax-submit-form')) {
            event.preventDefault();

            const modal = form.closest('.fixed.inset-0');
            const confirmMessage = form.dataset.confirmMessage;
            const confirmTitle = form.dataset.confirmTitle || "Confirm Action";

            const performAjaxSubmit = async () => {
                const formData = new FormData(form);
                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const result = await response.json();
                    if (result.success) {
                        if (result.credentials) {
                            const credText = "Username: " + result.credentials.username + "\nEmail: " + result.credentials.email + "\nTemporary Password: " + result.credentials.password;
                            Swal.fire({
                                title: 'Account Created!',
                                text: 'Please copy these temporary credentials.',
                                html: '<div class="text-left bg-gray-100 p-4 rounded mt-4 text-sm font-mono whitespace-pre-wrap select-all">' + credText + '</div>',
                                icon: 'success',
                                showCancelButton: true,
                                confirmButtonText: 'Copy & Close',
                                cancelButtonText: 'Close'
                            }).then((alertResult) => {
                                if (alertResult.isConfirmed) {
                                    navigator.clipboard.writeText(credText).catch(e => console.error(e));
                                }
                                setTimeout(() => { window.location.reload(); }, 500);
                            });
                        } else {
                            showNotification(result.message, 'success');
                            if (modal) {
                                modal.classList.add('hidden');
                                modal.classList.remove('flex');
                            }
                            form.reset();
                            setTimeout(() => { window.location.reload(); }, 1500);
                        }
                    } else {
                        showNotification(result.message || 'Something went wrong.', 'error');
                    }
                } catch (error) {
                    console.error("Form submission failed:", error);
                    showNotification('An unexpected error occurred. Check the console for details.', 'error');
                }
            };

            // If a confirmation message is set, show the dialog
            if (confirmMessage) {
                showConfirmation({
                    title: confirmTitle,
                    text: confirmMessage,
                    icon: 'question',
                    confirmButtonText: 'Yes, proceed!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        performAjaxSubmit();
                    }
                });
            } else {
                performAjaxSubmit();
            }
        }
        // Handle legacy forms that require confirmation before a standard page reload
                // Handle true AJAX toggles without reloading
        else if (form.matches('form.ajax-toggle-form')) {
            event.preventDefault();
            const message = form.dataset.confirmMessage || "Are you sure?";
            const title = form.dataset.confirmTitle || "Confirm Action";
            
            showConfirmation({
                title: title,
                text: message,
                icon: 'warning',
                confirmButtonText: 'Yes, proceed!'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const formData = new FormData(form);
                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: formData,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const data = await response.json();
                        
                        if (data.success) {
                            showNotification(data.message, 'success');
                            // Update the UI button based on new status
                            const btn = form.querySelector('button[type="submit"]');
                            const circle = btn.querySelector('span[aria-hidden="true"]');
                            const statusBadge = form.closest('tr').querySelector('span.inline-flex.rounded-full');
                            
                            if (data.new_status === 1) {
                                btn.classList.remove('bg-gray-300', 'dark:bg-gray-600');
                                btn.classList.add('bg-green-500');
                                circle.classList.remove('translate-x-0');
                                circle.classList.add('translate-x-5');
                                
                                if (statusBadge) {
                                    statusBadge.classList.remove('bg-red-100', 'text-red-800', 'dark:bg-red-900', 'dark:text-red-200');
                                    statusBadge.classList.add('bg-green-100', 'text-green-800', 'dark:bg-green-900', 'dark:text-green-200');
                                    statusBadge.innerText = 'Active';
                                }
                            } else {
                                btn.classList.remove('bg-green-500');
                                btn.classList.add('bg-gray-300', 'dark:bg-gray-600');
                                circle.classList.remove('translate-x-5');
                                circle.classList.add('translate-x-0');
                                
                                if (statusBadge) {
                                    statusBadge.classList.remove('bg-green-100', 'text-green-800', 'dark:bg-green-900', 'dark:text-green-200');
                                    statusBadge.classList.add('bg-red-100', 'text-red-800', 'dark:bg-red-900', 'dark:text-red-200');
                                    statusBadge.innerText = 'Disabled';
                                }
                            }
                        } else {
                            showNotification(data.message || 'Error updating status', 'error');
                        }
                    } catch (error) {
                        console.error(error);
                        showNotification('An error occurred', 'error');
                    }
                }
            });
        }
        else if (form.matches('form.confirm-submit-form')) {
            event.preventDefault();
            const message = form.dataset.confirmMessage || "Are you sure?";
            const title = form.dataset.confirmTitle || "Confirm Action";
            showConfirmation({
                title: title,
                text: message,
                icon: 'warning',
                confirmButtonText: 'Yes, proceed!'
            }).then((result) => {
                if (result.isConfirmed) {
                    let submitter = event.submitter;
                    if (!submitter && form._activeSubmitterName) {
                        submitter = {
                            name: form._activeSubmitterName,
                            value: form._activeSubmitterValue || ''
                        };
                    }

                    if (submitter && submitter.name) {
                        const hiddenSubmit = document.createElement('input');
                        hiddenSubmit.type = 'hidden';
                        hiddenSubmit.name = submitter.name;
                        hiddenSubmit.value = submitter.value || '';
                        form.appendChild(hiddenSubmit);
                    }

                    form._activeSubmitterName = null;
                    form._activeSubmitterValue = null;
                    form.submit();
                }
            });
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApp);
} else {
    initApp();
}


