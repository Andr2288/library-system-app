// API Configuration
const API_CONFIG = {
    BASE_URL: 'http://localhost:8000/api',
    ENDPOINTS: {
        BOOKS: '/books',
        READERS: '/readers',
        CATEGORIES: '/categories',
        LOANS: '/loans',
        LOANS_ACTIVE: '/loans/active',
        LOANS_OVERDUE: '/loans/overdue',
        CATEGORIES_POPULAR: '/categories/popular'
    }
};

// API Helper Functions
const API = {
    async request(endpoint, options = {}) {
        try {
            const response = await fetch(`${API_CONFIG.BASE_URL}${endpoint}`, {
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                ...options
            });

            const data = await response.json();

            if (!response.ok) {
                const error = new Error(data.message || 'API request failed');
                error.errors = data.errors || null;
                throw error;
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },

    async get(endpoint) {
        return this.request(endpoint, { method: 'GET' });
    },

    async post(endpoint, data) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    async put(endpoint, data) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },

    async delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    }
};

// Utility Functions
const Utils = {
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('uk-UA', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    },

    formatDateTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('uk-UA', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },

    showNotification(message, type = 'success') {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 z-[9999] px-6 py-4 rounded-lg shadow-lg text-white transform transition-all duration-300 ${
            type === 'success' ? 'bg-green-500' : 'bg-red-500'
        }`;
        toast.innerHTML = `
            <div class="flex items-center space-x-2">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
            </div>
        `;

        document.body.appendChild(toast);

        // Animate in
        setTimeout(() => toast.style.transform = 'translateY(0)', 10);

        // Remove after 3 seconds
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    },

    showError(error) {
        this.showNotification(error.message || 'Сталася помилка', 'error');
    }
};