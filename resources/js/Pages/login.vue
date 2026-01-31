<!-- <template>
  <div class="auth-wrapper">
    <div class="auth-container">
      <div class="auth-card shadow-lg">
        <div class="text-center mb-4">
          <div class="brand-icon-wrapper mb-2">
             <i class="fas fa-boxes"></i>
          </div>
          <h2 class="auth-title">IMS</h2>
          <p class="text-muted small">Welcome back! Please login.</p>
        </div>

        <div v-if="error" class="alert alert-custom mb-3" role="alert">
          {{ error }}
          <button type="button" class="close-btn" @click="error = null">
            &times;
          </button>
        </div>

        <form @submit.prevent="handleLogin">
          <div class="form-group mb-3">
            <label for="email">Email Address</label>
            <input 
              type="email" 
              class="form-control custom-input" 
              id="email" 
              v-model="form.email" 
              placeholder="name@company.com"
              required 
            />
          </div>

          <div class="form-group mb-3">
            <label for="password">Password</label>
            <input 
              type="password" 
              class="form-control custom-input" 
              id="password" 
              v-model="form.password"
              placeholder="Enter your password" 
              required 
            />
          </div>

          <button type="submit" class="btn btn-dark-custom w-100" :disabled="loading">
            <span v-if="loading">
              <span class="spinner-border spinner-border-sm mr-2"></span>
              Authenticating...
            </span>
            <span v-else>
              Login to Dashboard
            </span>
          </button>
        </form>

        <div class="text-center mt-4">
          <p class="text-muted small">
            Don't have an account?
            <router-link to="/register" class="register-link">Register here</router-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import authApi from '@/services/authApi.js'; 
import axios from 'axios';  

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
        await authApi.get('/sanctum/csrf-cookie');
        await authApi.post('/login', {
          email: this.form.email,
          password: this.form.password,
        });
        const userResponse = await authApi.get('/api/user');
        localStorage.setItem('user', JSON.stringify(userResponse.data));
        this.$router.push('/dashboard');
      } catch (error) {
        console.error('Login error:', error);
        if (error.response?.status === 419) {
          this.error = "Session expired. Please try clicking login again.";
        } else {
          this.error = error.response?.data?.message || 'Invalid credentials.';
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
  /* background: url('../../img/vector-conceptual-illustration-related-computer-260nw-2526268235.webp'); */
  background-color: #181818; 
  /* background-color: #f3f5ed;  */
  padding: 20px;
}

.auth-container {
  width: 100%;
  /* Increased width from 420px to 520px */
  max-width: 520px;
}

.auth-card {
  background-color: #f3f5ed; 
  /* Reduced padding from 50px 40px to 30px 40px to decrease height */
  padding: 30px 40px;
  border-radius: 24px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
  animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.auth-title {
  font-weight: 800;
  color: #1a1a1a;
  letter-spacing: -1px;
}

.brand-icon-wrapper {
  font-size: 2.5rem;
  color: #1a1a1a;
}

.custom-input {
  background-color: #ffffff;
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  /* Reduced vertical padding from 14px to 12px to decrease height */
  padding: 12px 18px;
  font-size: 15px;
  transition: all 0.2s;
}

.custom-input:focus {
  border-color: #1a1a1a;
  box-shadow: 0 0 0 4px rgba(0, 0, 0, 0.05);
  outline: none;
}

.btn-dark-custom {
  background-color: #1a1a1a;
  color: white;
  border: none;
  /* Reduced padding from 14px to 12px to decrease height */
  padding: 12px;
  border-radius: 12px;
  font-weight: 700;
  transition: transform 0.2s, background-color 0.2s;
}

.btn-dark-custom:hover {
  background-color: #333333;
  transform: translateY(-1px);
}

.btn-dark-custom:active {
  transform: translateY(0);
}

label {
  font-size: 14px;
  font-weight: 700;
  margin-bottom: 4px; /* Reduced margin from 8px to 4px */
  color: #1a1a1a;
  display: block;
}

.alert-custom {
  background: #fffcf0;
  border: 1px solid #ffeeba;
  color: #856404;
  padding: 12px 15px;
  border-radius: 12px;
  position: relative;
  font-size: 14px;
}

.close-btn {
  position: absolute;
  right: 10px;
  top: 8px; 
  background: none;
  border: none;
  font-size: 18px;
  cursor: pointer;
}

.register-link {
  color: #1a1a1a;
  font-weight: 800;
  text-decoration: underline;
}
</style>

 -->
<template>
  <div class="auth-wrapper">
    <div class="auth-container">
      <div class="auth-card shadow-lg">
        <div class="text-center mb-4">
          <div class="brand-icon-wrapper mb-2">
             <i class="fas fa-boxes"></i>
          </div>
          <h2 class="auth-title">IMS</h2>
          <p class="text-muted small">Welcome back! Please login.</p>
        </div>

        <div v-if="error" class="alert alert-custom mb-3" role="alert">
          {{ error }}
          <button type="button" class="close-btn" @click="error = null">
            &times;
          </button>
        </div>

        <form @submit.prevent="handleLogin">
          <div class="form-group mb-3">
            <label for="email">Email Address</label>
            <input 
              type="email" 
              class="form-control custom-input" 
              id="email" 
              v-model.trim="form.email" 
              placeholder="name@company.com"
              required 
              maxlength="255"
            />
          </div>

          <div class="form-group mb-3">
            <label for="password">Password</label>
            <input 
              type="password" 
              class="form-control custom-input" 
              id="password" 
              v-model="form.password"
              placeholder="Enter your password" 
              required 
              minlength="8"
            />
          </div>

          <button type="submit" class="btn btn-dark-custom w-100" :disabled="loading">
            <span v-if="loading">
              <span class="spinner-border spinner-border-sm mr-2"></span>
              Authenticating...
            </span>
            <span v-else>
              Login to Dashboard
            </span>
          </button>
        </form>

        <div class="text-center mt-4">
          <p class="text-muted small">
            Don't have an account?
            <router-link to="/register" class="register-link">Register here</router-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import authApi from '@/services/authApi.js'; 

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
    // Basic local validation check before hitting the API
    validateForm() {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      
      if (!emailRegex.test(this.form.email)) {
        this.error = "Please enter a valid email address.";
        return false;
      }
      if (this.form.password.length < 8) {
        this.error = "Password must be at least 8 characters long.";
        return false;
      }
      return true;
    },

    async handleLogin() {
      // 1. Run local checks first
      if (!this.validateForm()) return;

      this.loading = true;
      this.error = null;
      
      try {
        await authApi.get('/sanctum/csrf-cookie');
        await authApi.post('/login', {
          email: this.form.email,
          password: this.form.password,
        });
        const userResponse = await authApi.get('/api/user');
        localStorage.setItem('user', JSON.stringify(userResponse.data));
        this.$router.push('/dashboard');
      } catch (error) {
        console.error('Login error:', error);
        if (error.response?.status === 419) {
          this.error = "Session expired. Please try clicking login again.";
        } else if (error.response?.status === 422) {
          // Handle backend validation errors specifically
          this.error = error.response.data.message || "Validation failed.";
        } else {
          this.error = error.response?.data?.message || 'Invalid credentials.';
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
  /* background: url('../../img/vector-conceptual-illustration-related-computer-260nw-2526268235.webp'); */
  background-color: #181818; 
  /* background-color: #f3f5ed;  */
  padding: 20px;
}

.auth-container {
  width: 100%;
  /* Increased width from 420px to 520px */
  max-width: 520px;
}

.auth-card {
  background-color: #f3f5ed; 
  /* Reduced padding from 50px 40px to 30px 40px to decrease height */
  padding: 30px 40px;
  border-radius: 24px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
  animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.auth-title {
  font-weight: 800;
  color: #1a1a1a;
  letter-spacing: -1px;
}

.brand-icon-wrapper {
  font-size: 2.5rem;
  color: #1a1a1a;
}

.custom-input {
  background-color: #ffffff;
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  /* Reduced vertical padding from 14px to 12px to decrease height */
  padding: 12px 18px;
  font-size: 15px;
  transition: all 0.2s;
}

.custom-input:focus {
  border-color: #1a1a1a;
  box-shadow: 0 0 0 4px rgba(0, 0, 0, 0.05);
  outline: none;
}

.btn-dark-custom {
  background-color: #1a1a1a;
  color: white;
  border: none;
  /* Reduced padding from 14px to 12px to decrease height */
  padding: 12px;
  border-radius: 12px;
  font-weight: 700;
  transition: transform 0.2s, background-color 0.2s;
}

.btn-dark-custom:hover {
  background-color: #333333;
  transform: translateY(-1px);
}

.btn-dark-custom:active {
  transform: translateY(0);
}

label {
  font-size: 14px;
  font-weight: 700;
  margin-bottom: 4px; /* Reduced margin from 8px to 4px */
  color: #1a1a1a;
  display: block;
}

.alert-custom {
  background: #fffcf0;
  border: 1px solid #ffeeba;
  color: #856404;
  padding: 12px 15px;
  border-radius: 12px;
  position: relative;
  font-size: 14px;
}

.close-btn {
  position: absolute;
  right: 10px;
  top: 8px; 
  background: none;
  border: none;
  font-size: 18px;
  cursor: pointer;
}

.register-link {
  color: #1a1a1a;
  font-weight: 800;
  text-decoration: underline;
}
</style>


 