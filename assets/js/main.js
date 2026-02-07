/**
 * IdeaOne - Main JavaScript
 * Handles client-side interactions and AJAX requests
 */

// ===== GLOBAL VARIABLES =====
let currentUser = null;

// ===== DOM READY =====
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

// ===== INITIALIZE APP =====
function initializeApp() {
    loadCurrentUser();
    initializeModals();
    initializeForms();
    initializeNotifications();
}

// ===== LOAD CURRENT USER =====
function loadCurrentUser() {
    const userData = localStorage.getItem('currentUser');
    if (userData) {
        currentUser = JSON.parse(userData);
        updateAuthUI();
    }
}

// ===== UPDATE AUTH UI =====
function updateAuthUI() {
    const authButtons = document.querySelectorAll('.auth-button');
    const dashboardLinks = document.querySelectorAll('.dashboard-link');
    
    if (currentUser) {
        authButtons.forEach(btn => btn.classList.add('hidden'));
        dashboardLinks.forEach(link => link.classList.remove('hidden'));
        
        const userElements = document.querySelectorAll('.user-name');
        userElements.forEach(el => el.textContent = currentUser.name);
        
        const coinElements = document.querySelectorAll('.user-coins');
        coinElements.forEach(el => el.textContent = currentUser.coins);
    } else {
        authButtons.forEach(btn => btn.classList.remove('hidden'));
        dashboardLinks.forEach(link => link.classList.add('hidden'));
    }
}

// ===== AJAX HELPER =====
async function ajaxRequest(url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    };
    
    if (data) {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(url, options);
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.message || 'Request failed');
        }
        
        return result;
    } catch (error) {
        console.error('AJAX Error:', error);
        showNotification(error.message, 'danger');
        throw error;
    }
}

// ===== FORM SUBMISSION =====
function initializeForms() {
    const forms = document.querySelectorAll('form[data-ajax]');
    
    forms.forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';
            
            const formData = new FormData(form);
            const data = {};
            
            formData.forEach((value, key) => {
                data[key] = value;
            });
            
            try {
                const url = form.getAttribute('action');
                const method = form.getAttribute('method') || 'POST';
                
                const result = await ajaxRequest(url, method, data);
                
                if (result.success) {
                    showNotification(result.message, 'success');
                    
                    // Redirect if specified
                    const redirect = form.getAttribute('data-redirect');
                    if (redirect) {
                        setTimeout(() => {
                            window.location.href = redirect;
                        }, 1500);
                    }
                    
                    // Reset form if no redirect
                    if (!redirect) {
                        form.reset();
                    }
                } else {
                    showNotification(result.message, 'danger');
                }
            } catch (error) {
                showNotification(error.message, 'danger');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    });
}

// ===== NOTIFICATIONS =====
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.textContent = message;
    
    const container = document.querySelector('.notification-container') || document.body;
    container.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

function initializeNotifications() {
    // Create notification container if it doesn't exist
    if (!document.querySelector('.notification-container')) {
        const container = document.createElement('div');
        container.className = 'notification-container';
        container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
        document.body.appendChild(container);
    }
}

// ===== MODALS =====
function initializeModals() {
    const modals = document.querySelectorAll('.modal');
    
    modals.forEach(modal => {
        const closeButtons = modal.querySelectorAll('.modal-close, [data-dismiss="modal"]');
        
        closeButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                modal.classList.remove('active');
            });
        });
        
        // Close on outside click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
}

// ===== CONFIRMATION =====
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// ===== FILE UPLOAD =====
function handleFileUpload(input, callback) {
    const file = input.files[0];
    
    if (!file) {
        return;
    }
    
    const maxSize = 10 * 1024 * 1024; // 10MB
    const allowedTypes = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'image/jpeg', 'image/png', 'video/mp4'];
    
    if (file.size > maxSize) {
        showNotification('File size exceeds 10MB limit', 'danger');
        return;
    }
    
    if (!allowedTypes.includes(file.type)) {
        showNotification('Invalid file type', 'danger');
        return;
    }
    
    const formData = new FormData();
    formData.append('file', file);
    
    // Upload file
    fetch('/api/upload.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            callback(result);
        } else {
            showNotification(result.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Upload Error:', error);
        showNotification('File upload failed', 'danger');
    });
}

// ===== COIN CALCULATION =====
function calculateSubmissionCost() {
    const cost = 2; // Fixed cost
    return cost;
}

function checkUserCoins(requiredCoins) {
    if (!currentUser) {
        showNotification('Please login first', 'warning');
        return false;
    }
    
    if (currentUser.coins < requiredCoins) {
        showNotification('Insufficient coins. Please buy more coins.', 'danger');
        return false;
    }
    
    return true;
}

// ===== WALLET UPDATE =====
function updateWalletBalance() {
    if (!currentUser) return;
    
    fetch('/api/wallet-balance.php')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                currentUser.coins = result.coins;
                currentUser.wallet_balance = result.wallet_balance;
                localStorage.setItem('currentUser', JSON.stringify(currentUser));
                updateAuthUI();
            }
        })
        .catch(error => {
            console.error('Wallet Update Error:', error);
        });
}

// ===== LOGOUT =====
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        fetch('/auth/logout.php', {
            method: 'POST'
        })
        .then(() => {
            localStorage.removeItem('currentUser');
            currentUser = null;
            updateAuthUI();
            window.location.href = '/';
        })
        .catch(error => {
            console.error('Logout Error:', error);
            localStorage.removeItem('currentUser');
            currentUser = null;
            updateAuthUI();
            window.location.href = '/';
        });
    }
}

// ===== COIN PACKAGES =====
function buyCoins(coinPackage) {
    if (!currentUser) {
        showNotification('Please login first', 'warning');
        window.location.href = '/auth/login.php';
        return;
    }
    
    const packages = {
        'starter': { coins: 10, amount: 99 },
        'basic': { coins: 25, amount: 199 },
        'standard': { coins: 50, amount: 349 },
        'premium': { coins: 100, amount: 599 },
        'ultimate': { coins: 200, amount: 999 }
    };
    
    const selectedPackage = packages[coinPackage];
    if (!selectedPackage) {
        showNotification('Invalid package', 'danger');
        return;
    }
    
    // Create Razorpay order
    fetch('/api/create-order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            amount: selectedPackage.amount,
            coins: selectedPackage.coins
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Open Razorpay checkout
            const options = {
                key: result.key,
                amount: result.amount * 100,
                currency: 'INR',
                name: 'IdeaOne',
                description: `Buy ${selectedPackage.coins} coins`,
                order_id: result.order_id,
                handler: function(response) {
                    verifyPayment(response.razorpay_order_id, response.razorpay_payment_id, response.razorpay_signature);
                },
                prefill: {
                    name: currentUser.name,
                    email: currentUser.email
                }
            };
            
            const rzp = new Razorpay(options);
            rzp.open();
        } else {
            showNotification(result.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Order Creation Error:', error);
        showNotification('Failed to create order', 'danger');
    });
}

function verifyPayment(orderId, paymentId, signature) {
    fetch('/api/verify-payment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            order_id: orderId,
            payment_id: paymentId,
            signature: signature
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification(result.message, 'success');
            updateWalletBalance();
        } else {
            showNotification(result.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Payment Verification Error:', error);
        showNotification('Payment verification failed', 'danger');
    });
}

// ===== EXPORT FUNCTIONS =====
window.ajaxRequest = ajaxRequest;
window.showNotification = showNotification;
window.openModal = openModal;
window.closeModal = closeModal;
window.confirmAction = confirmAction;
window.handleFileUpload = handleFileUpload;
window.logout = logout;
window.buyCoins = buyCoins;
window.updateWalletBalance = updateWalletBalance;