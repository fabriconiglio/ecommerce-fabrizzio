<?php
/**
 * Integración de autenticación con Laravel API
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode para formulario de login
 */
function laravel_login_form_shortcode() {
    ob_start();
    ?>
    <div class="auth-container">
        <div class="auth-tabs">
            <button class="tab-btn active" onclick="showTab('login')">Iniciar Sesión</button>
            <button class="tab-btn" onclick="showTab('register')">Registrarse</button>
        </div>

        <!-- Formulario de Login -->
        <div id="login-tab" class="auth-form active">
            <h3>Iniciar Sesión</h3>
            <form method="post" class="login-form">
                <div class="form-group">
                    <label for="login_email">Email:</label>
                    <input type="email" id="login_email" name="email" required autocomplete="email">
                </div>
                
                <div class="form-group">
                    <label for="login_password">Contraseña:</label>
                    <input type="password" id="login_password" name="password" required autocomplete="current-password">
                </div>
                
                <button type="submit" class="auth-btn login-btn">Iniciar Sesión</button>
            </form>
        </div>

        <!-- Formulario de Registro -->
        <div id="register-tab" class="auth-form">
            <h3>Registrarse</h3>
            <form method="post" class="register-form">
                <div class="form-group">
                    <label for="register_name">Nombre completo:</label>
                    <input type="text" id="register_name" name="name" required autocomplete="name">
                </div>
                
                <div class="form-group">
                    <label for="register_email">Email:</label>
                    <input type="email" id="register_email" name="email" required autocomplete="email">
                </div>
                
                <div class="form-group">
                    <label for="register_password">Contraseña:</label>
                    <input type="password" id="register_password" name="password" required autocomplete="new-password" minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="register_password_confirmation">Confirmar Contraseña:</label>
                    <input type="password" id="register_password_confirmation" name="password_confirmation" required autocomplete="new-password" minlength="6">
                </div>
                
                <button type="submit" class="auth-btn register-btn">Registrarse</button>
            </form>
        </div>
    </div>

    <style>
    .auth-container {
        max-width: 400px;
        margin: 20px auto;
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        border: 1px solid #e0e0e0;
    }
    
    .auth-tabs {
        display: flex;
        margin-bottom: 20px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .tab-btn {
        flex: 1;
        padding: 12px 20px;
        border: none;
        background: transparent;
        cursor: pointer;
        font-weight: 600;
        color: #666;
        transition: all 0.3s ease;
    }
    
    .tab-btn.active {
        color: #3498db;
        border-bottom: 3px solid #3498db;
    }
    
    .auth-form {
        display: none;
    }
    
    .auth-form.active {
        display: block;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }
    
    .form-group input {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 16px;
        transition: border-color 0.3s ease;
        box-sizing: border-box;
    }
    
    .auth-btn {
        width: 100%;
        padding: 15px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 10px;
    }
    
    .login-btn {
        background: #3498db;
        color: white;
    }
    
    .register-btn {
        background: #27ae60;
        color: white;
    }
    
    .auth-message {
        padding: 12px 15px;
        margin: 15px 0;
        border-radius: 8px;
        font-weight: 500;
    }
    
    .auth-message.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .auth-message.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .auth-message.info {
        background: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }
    </style>

    <script type="text/javascript">
    // Variables globales
    window.showTab = function(tab) {
        // Ocultar todos los tabs
        document.querySelectorAll('.auth-form').forEach(function(form) {
            form.classList.remove('active');
        });
        document.querySelectorAll('.tab-btn').forEach(function(btn) {
            btn.classList.remove('active');
        });
        
        // Mostrar tab seleccionado
        document.getElementById(tab + '-tab').classList.add('active');
        event.target.classList.add('active');
    };

    // Configuración de la API
    const API_BASE_URL = 'http://localhost:8000/api/auth';

    // Función para mostrar mensajes
    function showMessage(message, type) {
        type = type || 'info';
        
        // Remover mensaje anterior si existe
        const existingMessage = document.querySelector('.auth-message');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        // Crear nuevo mensaje
        const messageDiv = document.createElement('div');
        messageDiv.className = 'auth-message ' + type;
        messageDiv.textContent = message;
        
        // Insertar mensaje
        const authContainer = document.querySelector('.auth-container');
        if (authContainer) {
            authContainer.insertBefore(messageDiv, authContainer.firstChild);
            
            // Auto-remover después de 5 segundos
            setTimeout(function() {
                if (messageDiv.parentNode) {
                    messageDiv.remove();
                }
            }, 5000);
        }
    }

    // Función para hacer peticiones a la API
    async function apiRequest(endpoint, data, method) {
        method = method || 'POST';
        
        try {
            const response = await fetch(API_BASE_URL + endpoint, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.message || 'HTTP error! status: ' + response.status);
            }
            
            return result;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    // Manejar envío del formulario de login
    async function handleLogin(event) {
        event.preventDefault();
        
        const submitBtn = event.target.querySelector('.login-btn');
        const originalText = submitBtn.textContent;
        
        // Mostrar loading
        submitBtn.textContent = 'Iniciando sesión...';
        submitBtn.disabled = true;
        
        try {
            const formData = {
                email: document.getElementById('login_email').value,
                password: document.getElementById('login_password').value
            };
            
            console.log('Login data:', formData);
            
            const response = await apiRequest('/login', formData);
            
            console.log('Login response:', response);
            
            if (response.success && response.data) {
                showMessage('¡Login exitoso! Redirigiendo...', 'success');
                
                // Guardar token y datos del usuario
                localStorage.setItem('auth_token', response.data.token);
                localStorage.setItem('user_data', JSON.stringify(response.data.user));
                
                // Redirigir después de 1.5 segundos
                setTimeout(function() {
                    window.location.reload();
                }, 1500);
                
            } else {
                showMessage(response.message || 'Error en el login', 'error');
            }
            
        } catch (error) {
            console.error('Login error:', error);
            showMessage(error.message || 'Error de conexión con el servidor', 'error');
        } finally {
            // Restaurar botón
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }
    }

    // Manejar envío del formulario de registro
    async function handleRegister(event) {
        event.preventDefault();
        
        const submitBtn = event.target.querySelector('.register-btn');
        const originalText = submitBtn.textContent;
        
        // Mostrar loading
        submitBtn.textContent = 'Registrando...';
        submitBtn.disabled = true;
        
        try {
            const password = document.getElementById('register_password').value;
            const passwordConfirmation = document.getElementById('register_password_confirmation').value;
            
            // Validación de contraseñas
            if (password !== passwordConfirmation) {
                throw new Error('Las contraseñas no coinciden');
            }
            
            if (password.length < 6) {
                throw new Error('La contraseña debe tener al menos 6 caracteres');
            }
            
            const formData = {
                name: document.getElementById('register_name').value,
                email: document.getElementById('register_email').value,
                password: password,
                password_confirmation: passwordConfirmation
            };
            
            console.log('Register data:', formData);
            
            const response = await apiRequest('/register', formData);
            
            console.log('Register response:', response);
            
            if (response.success && response.data) {
                showMessage('¡Registro exitoso! Puedes iniciar sesión ahora.', 'success');
                
                // Limpiar formulario
                event.target.reset();
                
                // Cambiar a tab de login después de 2 segundos
                setTimeout(function() {
                    window.showTab('login');
                }, 2000);
                
            } else {
                showMessage(response.message || 'Error en el registro', 'error');
            }
            
        } catch (error) {
            console.error('Register error:', error);
            showMessage(error.message || 'Error de conexión con el servidor', 'error');
        } finally {
            // Restaurar botón
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }
    }

    // Verificar si el usuario ya está logueado
    function checkAuthStatus() {
        const token = localStorage.getItem('auth_token');
        const userData = localStorage.getItem('user_data');
        
        if (token && userData) {
            try {
                const user = JSON.parse(userData);
                showMessage('¡Bienvenido de vuelta, ' + (user.name || user.email) + '!', 'info');
            } catch (error) {
                console.error('Error parsing user data:', error);
            }
        }
    }

    // Inicializar cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Auth integration loaded');
        
        // Verificar estado de autenticación
        checkAuthStatus();
        
        // Agregar event listeners a los formularios
        const loginForm = document.querySelector('.login-form');
        const registerForm = document.querySelector('.register-form');
        
        if (loginForm) {
            loginForm.addEventListener('submit', handleLogin);
            console.log('Login form listener added');
        }
        
        if (registerForm) {
            registerForm.addEventListener('submit', handleRegister);
            console.log('Register form listener added');
        }
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('laravel_auth', 'laravel_login_form_shortcode');
