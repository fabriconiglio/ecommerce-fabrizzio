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
                    <input type="email" id="login_email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="login_password">Contraseña:</label>
                    <input type="password" id="login_password" name="password" required>
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
                    <input type="text" id="register_name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="register_email">Email:</label>
                    <input type="email" id="register_email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="register_password">Contraseña:</label>
                    <input type="password" id="register_password" name="password" required>
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
    </style>

    <script>
    function showTab(tab) {
        // Ocultar todos los tabs
        document.querySelectorAll('.auth-form').forEach(form => {
            form.classList.remove('active');
        });
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Mostrar tab seleccionado
        document.getElementById(tab + '-tab').classList.add('active');
        event.target.classList.add('active');
    }
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('laravel_auth', 'laravel_login_form_shortcode');
