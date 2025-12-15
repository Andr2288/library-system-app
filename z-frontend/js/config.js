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
                throw new Error(data.message || 'API request failed');
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
        // TODO: Implement toast notifications
        console.log(`[${type.toUpperCase()}] ${message}`);
    },
    
    showError(error) {
        this.showNotification(error.message || 'Сталася помилка', 'error');
    }
};
