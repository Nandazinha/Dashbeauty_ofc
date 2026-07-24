// ============================================
// DASHBEAUTY - API SERVICE
// ============================================

const API = {
    baseUrl: window.location.hostname === 'dashbeauty.test' 
        ? 'http://dashbeauty.test/api' 
        : '/api',
    
    token: localStorage.getItem('auth_token'),
    
    setToken(token) {
        this.token = token;
        if (token) {
            localStorage.setItem('auth_token', token);
        } else {
            localStorage.removeItem('auth_token');
        }
    },
    
    getHeaders() {
        return {
            'Content-Type': 'application/json',
            'Authorization': this.token ? `Bearer ${this.token}` : ''
        };
    },
    
    async request(endpoint, method = 'GET', data = null) {
        const options = {
            method,
            headers: this.getHeaders()
        };
        
        if (data && (method === 'POST' || method === 'PUT')) {
            options.body = JSON.stringify(data);
        }
        
        try {
            const response = await fetch(`${this.baseUrl}/${endpoint}`, options);
            const result = await response.json();
            
            if (response.status === 401) {
                this.setToken(null);
                localStorage.removeItem('user_type');
                localStorage.removeItem('user_name');
                window.location.href = '/login.html';
            }
            
            return result;
        } catch (error) {
            return { success: false, message: 'Erro de conexão' };
        }
    },
    
    // ============================================
    // AUTENTICAÇÃO
    // ============================================
    
    async login(email, password) {
        const result = await this.request('auth/login', 'POST', { email, password });
        if (result.success) {
            this.setToken(result.data.token);
            localStorage.setItem('user_id', result.data.user_id);
            localStorage.setItem('user_type', result.data.user_type);
            localStorage.setItem('user_name', result.data.name);
            localStorage.setItem('business_name', result.data.business_name || '');
        }
        return result;
    },
    
    async register(userData) {
        const result = await this.request('auth/register', 'POST', userData);
        if (result.success) {
            this.setToken(result.data.token);
            localStorage.setItem('user_id', result.data.user_id);
            localStorage.setItem('user_type', result.data.user_type);
            localStorage.setItem('user_name', result.data.name);
        }
        return result;
    },
    
    // ============================================
    // EMPRESAS
    // ============================================
    
    async getBusinesses(search = '') {
        return this.request(`businesses${search ? `?search=${encodeURIComponent(search)}` : ''}`);
    },
    
    async getBusiness(id) {
        return this.request(`businesses/${id}`);
    },
    
    async updateBusiness(id, data) {
        return this.request(`businesses/${id}`, 'PUT', data);
    },
    
    // ============================================
    // SERVIÇOS
    // ============================================
    
    async getServices(businessId) {
        return this.request(`services?business_id=${businessId}`);
    },
    
    async createService(data) {
        return this.request('services', 'POST', data);
    },
    
    async deleteService(id) {
        return this.request(`services/${id}`, 'DELETE');
    },
    
    // ============================================
    // AGENDAMENTOS
    // ============================================
    
    async getAppointments() {
        return this.request('appointments');
    },
    
    async createAppointment(data) {
        return this.request('appointments', 'POST', data);
    },
    
    // ============================================
    // FAVORITOS
    // ============================================
    
    async getFavorites() {
        return this.request('favorites');
    },
    
    async addFavorite(businessId) {
        return this.request('favorites', 'POST', { business_id: businessId });
    },
    
    async removeFavorite(businessId) {
        return this.request(`favorites/${businessId}`, 'DELETE');
    }
};