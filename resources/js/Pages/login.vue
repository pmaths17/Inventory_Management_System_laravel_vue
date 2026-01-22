<template>
  <div class="auth-wrapper">
    <div class="auth-container">
      <div class="auth-card">
        <div class="text-center mb-4">
          <h2 class="mb-2">
            <i class="fas fa-boxes text-primary"></i> IMS Pro
          </h2>
          <p class="text-muted">Login to your account</p>
        </div>

        <div v-if="error" class="alert alert-danger alert-dismissible fade show" role="alert">
          {{ error }}
          <button type="button" class="close" @click="error = null">
            <span>&times;</span>
          </button>
        </div>

        <form @submit.prevent="handleLogin">
          <div class="form-group mb-3">
            <label for="email">Email Address</label>
            <input type="email" class="form-control" id="email" v-model="form.email" placeholder="Enter your email"
              required />
          </div>

          <div class="form-group mb-3">
            <label for="password">Password</label>
            <input type="password" class="form-control" id="password" v-model="form.password"
              placeholder="Enter your password" required />
          </div>

          <button type="submit" class="btn btn-primary btn-block w-100" :disabled="loading">
            <span v-if="loading">
              <span class="spinner-border spinner-border-sm mr-2"></span>
              Logging in...
            </span>
            <span v-else>
              <i class="fas fa-sign-in-alt mr-2"></i> Login
            </span>
          </button>
        </form>

        <div class="text-center mt-4">
          <p class="mb-0">
            Don't have an account?
            <router-link to="/register" class="text-primary font-weight-bold">Register here</router-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import authApi from '@/services/authApi.js'; // make sure path is correct
import axios from 'axios';  // Add this
export default {
  name: 'Login',
  data() {
    return {
      form: {
        email: '',
        password: ''
      },
      loading: false,
      error: null
    };
  },
  methods: {
    async handleLogin() {
      this.loading = true;
      this.error = null;
      try {
        // 1️⃣ Get CSRF cookie
        await authApi.get('/sanctum/csrf-cookie');

        // 2️⃣ Login (session-based)
        await authApi.post('/login', {
          email: this.form.email,
          password: this.form.password,
        });

        

        // 3️⃣ Redirect (auth is now stored in cookies)
        this.$router.push('/dashboard');

      } catch (error) {
        console.error('Login error:', error);
        this.error =
          error.response?.data?.message ||
          'Invalid credentials. Please try again.';
      } finally {
        this.loading = false;
      }
      localStorage.setItem('user', JSON.stringify(user.data));
      // for personal sanctum tokens:
      // try {
      //   const response = await api.post('/login', this.form);

      //   // Save token and user data
      //   localStorage.setItem('token', response.data.token); // must match router
      //   localStorage.setItem('user', JSON.stringify(response.data.user));

      //   // Set axios default header for all requests
      //   api.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;

      //   // Redirect to dashboard
      //   this.$router.push('/dashboard');

      // } catch (error) {
      //   console.error('Login error:', error);
      //   this.error = error.response?.data?.message || 'Invalid credentials. Please try again.';
      // } finally {
      //   this.loading = false;
      // }
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

.auth-container {
  width: 100%;
  max-width: 450px;
}

.auth-card {
  background: white;
  padding: 40px;
  border-radius: 16px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
  animation: slideUp 0.4s ease;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.form-control {
  padding: 12px 16px;
  border-radius: 8px;
  border: 1px solid #dee2e6;
}

.form-control:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.btn-block {
  padding: 12px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 16px;
}

label {
  font-weight: 600;
  margin-bottom: 8px;
  color: #495057;
}
</style>
