<template>
  <div class="auth-wrapper">
    <div class="auth-container">
      <div class="auth-card">
        <div class="text-center mb-4">
          <h2 class="mb-2">
            <i class="fas fa-boxes text-primary"></i> IMS Pro
          </h2>
          <p class="text-muted">Create your account</p>
        </div>

        <div v-if="error" class="alert alert-danger alert-dismissible fade show" role="alert">
          {{ error }}
          <button type="button" class="close" @click="error = null">
            <span>&times;</span>
          </button>
        </div>

        <form @submit.prevent="handleRegister">
          <div class="form-group mb-3">
            <label for="name">Full Name</label>
            <input type="text" class="form-control" id="name" v-model="form.name" placeholder="Enter your full name" required />
          </div>

          <div class="form-group mb-3">
            <label for="email">Email Address</label>
            <input type="email" class="form-control" id="email" v-model="form.email" placeholder="Enter your email" required />
          </div>

          <div class="form-group mb-3">
            <label for="role">Role</label>
            <select class="form-control" id="role" v-model="form.role">
              <option value="staff">Staff</option>
              <option value="admin">Admin</option>
            </select>
          </div>

          <div class="form-group mb-3">
            <label for="password">Password</label>
            <input type="password" class="form-control" id="password" v-model="form.password" placeholder="Enter your password" required minlength="6" />
          </div>

          <div class="form-group mb-3">
            <label for="password_confirmation">Confirm Password</label>
            <input type="password" class="form-control" id="password_confirmation" v-model="form.password_confirmation" placeholder="Confirm your password" required />
          </div>

          <button type="submit" class="btn btn-primary btn-block w-100" :disabled="loading">
            <span v-if="loading">
              <span class="spinner-border spinner-border-sm mr-2"></span>
              Creating account...
            </span>
            <span v-else>
              <i class="fas fa-user-plus mr-2"></i> Register
            </span>
          </button>
        </form>

        <div class="text-center mt-4">
          <p class="mb-0">
            Already have an account? 
            <router-link to="/login" class="text-primary font-weight-bold">Login here</router-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '@/services/api';

export default {
  name: 'Register',
  data() {
    return {
      form: {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: 'staff'
      },
      loading: false,
      error: null
    };
  },
  methods: {
    async handleRegister() {
      if (this.form.password !== this.form.password_confirmation) {
        this.error = 'Passwords do not match!';
        return;
      }

      this.loading = true;
      this.error = null;

      try {
        const response = await api.post('/register', this.form);

        // Save token and user info
        localStorage.setItem('token', response.data.token);
        localStorage.setItem('user', JSON.stringify(response.data.user));

        // Set default authorization header for future requests
        api.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;

        // Redirect to dashboard
        this.$router.push('/');

      } catch (err) {
        console.error(err);
        this.error = err.response?.data?.message || 'Registration failed.';

        // Show validation errors if present
        if (err.response?.data?.errors) {
          this.error = Object.values(err.response.data.errors).flat().join(', ');
        }
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style scoped>
.auth-wrapper {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
}

.auth-container { width: 100%; max-width: 450px; }

.auth-card {
  background: white;
  padding: 40px;
  border-radius: 16px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
  animation: slideUp 0.4s ease;
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}

.form-control { padding: 12px 16px; border-radius: 8px; border: 1px solid #dee2e6; }
.form-control:focus { border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25); }

.btn-block { padding: 12px; border-radius: 8px; font-weight: 600; font-size: 16px; }
label { font-weight: 600; margin-bottom: 8px; color: #495057; }
</style>
