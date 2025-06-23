<?php
/**
 * Tema hijo E-commerce Fabrizzio
 * Funciones y personalizaciones
 */
// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Encolar estilos del tema padre e hijo
 */
function ecommerce_fabrizzio_enqueue_styles() {
    // Estilo del tema padre
    wp_enqueue_style(
        'parent-style',
        get_template_directory_uri() . '/style.css',
        [],
        wp_get_theme()->get('Version')
    );
    
    // Estilo del tema hijo
    wp_enqueue_style(
        'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        ['parent-style'],
        wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'ecommerce_fabrizzio_enqueue_styles');

/**
 * Agregar soporte para WooCommerce
 */
function ecommerce_fabrizzio_add_woocommerce_support() {
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
}
add_action('after_setup_theme', 'ecommerce_fabrizzio_add_woocommerce_support');

/**
 * Personalizar título del sitio
 */
function ecommerce_fabrizzio_custom_title() {
    return 'E-commerce Fabrizzio - Integrado con Laravel API';
}

/**
 * Agregar meta description personalizada
 */
function ecommerce_fabrizzio_add_meta_description() {
    if (is_home() || is_front_page()) {
        echo '<meta name="description" content="E-commerce desarrollado por Fabrizzio Coniglio con WordPress + WooCommerce y Laravel API backend">' . "\n";
    }
}
add_action('wp_head', 'ecommerce_fabrizzio_add_meta_description');

/**
 * Personalizar el footer
 */
function ecommerce_fabrizzio_custom_footer() {
    echo '<div class="custom-footer-text">';
    echo '<p>&copy; ' . date('Y') . ' E-commerce Fabrizzio - Desarrollado con WordPress + Laravel API</p>';
    echo '</div>';
}

/**
 * Agregar clase CSS personalizada al body
 */
function ecommerce_fabrizzio_body_class($classes) {
    $classes[] = 'ecommerce-fabrizzio-theme';
    
    if (is_woocommerce() || is_cart() || is_checkout()) {
        $classes[] = 'woocommerce-active';
    }
    
    return $classes;
}
add_filter('body_class', 'ecommerce_fabrizzio_body_class');

/**
 * Optimizar carga de scripts
 */
function ecommerce_fabrizzio_optimize_scripts() {
    // Solo cargar scripts de WooCommerce en páginas relevantes
    if (!is_woocommerce() && !is_cart() && !is_checkout() && !is_account_page()) {
        wp_dequeue_script('wc-cart-fragments');
        wp_dequeue_script('woocommerce');
    }
}
add_action('wp_enqueue_scripts', 'ecommerce_fabrizzio_optimize_scripts', 99);

/**
 * Configuración de thumbnail personalizada
 */
function ecommerce_fabrizzio_setup_theme() {
    // Agregar soporte para imágenes destacadas
    add_theme_support('post-thumbnails');
    
    // Tamaños de imagen personalizados
    add_image_size('product-thumb', 300, 300, true);
    add_image_size('product-large', 600, 600, true);
}
add_action('after_setup_theme', 'ecommerce_fabrizzio_setup_theme');

/**
 * Modificar número de productos por página
 */
function ecommerce_fabrizzio_products_per_page() {
    return 12;
}
add_filter('loop_shop_per_page', 'ecommerce_fabrizzio_products_per_page', 20);

/**
 * Agregar información del desarrollador
 */
function ecommerce_fabrizzio_developer_info() {
    echo '<!-- E-commerce desarrollado por Fabrizzio Coniglio - WordPress + Laravel API -->' . "\n";
}
add_action('wp_head', 'ecommerce_fabrizzio_developer_info');

/**
 * Shortcode de autenticación Laravel - Versión mejorada con dashboard
 */
function laravel_auth_shortcode_improved() {
    ob_start();
    ?>
    <div class="auth-container">
        <!-- Dashboard del usuario logueado (oculto por defecto) -->
        <div id="user-dashboard" style="display: none;">
            <div class="user-welcome">
                <h3 id="welcome-message">¡Bienvenido!</h3>
                <div class="user-info">
                    <p><strong>Email:</strong> <span id="user-email"></span></p>
                    <p><strong>Nombre:</strong> <span id="user-name"></span></p>
                </div>
                <div class="dashboard-actions">
                    <a href="/tienda/" class="auth-btn" style="display: inline-block; text-align: center; text-decoration: none; margin-right: 10px;">Ir a la Tienda</a>
                    <a href="/carrito/" class="auth-btn" style="display: inline-block; text-align: center; text-decoration: none; background: #27ae60; margin-right: 10px;">Ver Carrito</a>
                    <button onclick="logout()" class="auth-btn" style="background: #e74c3c;">Cerrar Sesión</button>
                </div>
            </div>
        </div>

        <!-- Formularios de autenticación (se ocultan cuando está logueado) -->
        <div id="auth-forms">
            <div class="auth-tabs">
                <button class="tab-btn active" data-tab="login">Iniciar Sesión</button>
                <button class="tab-btn" data-tab="register">Registrarse</button>
            </div>

            <!-- Formulario de Login -->
            <div id="login-tab" class="auth-form-tab active">
                <h3>Iniciar Sesión</h3>
                <form id="login-form">
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
            <div id="register-tab" class="auth-form-tab">
                <h3>Registrarse</h3>
                <form id="register-form">
                    <div class="form-group">
                        <label for="register_name">Nombre:</label>
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
                    <div class="form-group">
                        <label for="register_password_confirmation">Confirmar Contraseña:</label>
                        <input type="password" id="register_password_confirmation" name="password_confirmation" required>
                    </div>
                    <button type="submit" class="auth-btn register-btn">Registrarse</button>
                </form>
            </div>
        </div>

        <div id="auth-messages" style="display:none;"></div>
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
    .auth-form-tab {
        display: none;
    }
    .auth-form-tab.active {
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
    #auth-messages {
        margin-top: 15px;
        padding: 10px;
        border-radius: 4px;
    }
    #auth-messages.success {
        background: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }
    #auth-messages.error {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }
    /* Estilos del dashboard */
    .user-welcome {
        text-align: center;
    }
    .user-info {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin: 20px 0;
        text-align: left;
    }
    .dashboard-actions {
        margin-top: 20px;
    }
    .dashboard-actions .auth-btn {
        width: auto;
        padding: 10px 20px;
        margin: 5px;
        display: inline-block;
    }
    </style>

    <script>
    // Función global para logout
    window.logout = function() {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user_data');
        showMessage('Sesión cerrada correctamente', 'success');
        
        setTimeout(function() {
            // Ocultar dashboard y mostrar formularios
            document.getElementById('user-dashboard').style.display = 'none';
            document.getElementById('auth-forms').style.display = 'block';
            
            // Limpiar formularios
            document.getElementById('login-form').reset();
            document.getElementById('register-form').reset();
            
            // Ocultar mensaje después de un tiempo
            setTimeout(function() {
                document.getElementById('auth-messages').style.display = 'none';
            }, 3000);
        }, 1500);
    };

    document.addEventListener('DOMContentLoaded', function() {
        console.log('Laravel Auth script loaded');

        // Verificar si ya está logueado al cargar
        checkAuthStatus();

        // Manejar cambio de tabs
        document.querySelectorAll('.tab-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var tab = this.getAttribute('data-tab');
                
                // Remover clase active de todos los botones y tabs
                document.querySelectorAll('.tab-btn').forEach(function(b) {
                    b.classList.remove('active');
                });
                document.querySelectorAll('.auth-form-tab').forEach(function(t) {
                    t.classList.remove('active');
                });
                
                // Activar el botón y tab seleccionado
                this.classList.add('active');
                document.getElementById(tab + '-tab').classList.add('active');
            });
        });

        // Función para mostrar mensajes
        function showMessage(message, type) {
            var messagesDiv = document.getElementById('auth-messages');
            messagesDiv.textContent = message;
            messagesDiv.className = type;
            messagesDiv.style.display = 'block';
            
            setTimeout(function() {
                messagesDiv.style.display = 'none';
            }, 5000);
        }

        // Función para verificar estado de autenticación
        function checkAuthStatus() {
            var token = localStorage.getItem('auth_token');
            var userData = localStorage.getItem('user_data');
            
            if (token && userData) {
                try {
                    var user = JSON.parse(userData);
                    showUserDashboard(user);
                } catch (error) {
                    console.error('Error parsing user data:', error);
                    // Si hay error, limpiar localStorage
                    localStorage.removeItem('auth_token');
                    localStorage.removeItem('user_data');
                }
            }
        }

        // Función para mostrar dashboard del usuario
        function showUserDashboard(user) {
            // Ocultar formularios y mostrar dashboard
            document.getElementById('auth-forms').style.display = 'none';
            document.getElementById('user-dashboard').style.display = 'block';
            
            // Llenar información del usuario
            document.getElementById('welcome-message').textContent = '¡Bienvenido, ' + (user.name || user.email) + '!';
            document.getElementById('user-email').textContent = user.email;
            document.getElementById('user-name').textContent = user.name || 'No especificado';
        }

        // Manejar formulario de login
        document.getElementById('login-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            var btn = this.querySelector('.login-btn');
            var originalText = btn.textContent;
            btn.textContent = 'Iniciando sesión...';
            btn.disabled = true;
            
            var formData = {
                email: document.getElementById('login_email').value,
                password: document.getElementById('login_password').value
            };

            fetch('http://192.168.2.169:8000/api/auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success && data.data) {
                    showMessage('¡Login exitoso!', 'success');
                    
                    // Guardar datos en localStorage
                    localStorage.setItem('auth_token', data.data.token);
                    localStorage.setItem('user_data', JSON.stringify(data.data.user));
                    
                    // Mostrar dashboard después de 1 segundo
                    setTimeout(function() {
                        showUserDashboard(data.data.user);
                    }, 1000);
                } else {
                    showMessage(data.message || 'Error en el login', 'error');
                }
            })
            .catch(function(error) {
                console.error('Login error:', error);
                showMessage('Error de conexión con el servidor', 'error');
            })
            .finally(function() {
                btn.textContent = originalText;
                btn.disabled = false;
            });
        });

        // Manejar formulario de registro
        document.getElementById('register-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            var btn = this.querySelector('.register-btn');
            var originalText = btn.textContent;
            btn.textContent = 'Registrando...';
            btn.disabled = true;
            
            var password = document.getElementById('register_password').value;
            var passwordConfirmation = document.getElementById('register_password_confirmation').value;
            
            if (password !== passwordConfirmation) {
                showMessage('Las contraseñas no coinciden', 'error');
                btn.textContent = originalText;
                btn.disabled = false;
                return;
            }
            
            var formData = {
                name: document.getElementById('register_name').value,
                email: document.getElementById('register_email').value,
                password: password,
                password_confirmation: passwordConfirmation
            };

            fetch('http://192.168.2.169:8000/api/auth/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success && data.data) {
                    showMessage('¡Registro exitoso! Puedes iniciar sesión ahora.', 'success');
                    document.getElementById('register-form').reset();
                    
                    setTimeout(function() {
                        document.querySelector('[data-tab="login"]').click();
                    }, 2000);
                } else {
                    showMessage(data.message || 'Error en el registro', 'error');
                }
            })
            .catch(function(error) {
                console.error('Register error:', error);
                showMessage('Error de conexión con el servidor', 'error');
            })
            .finally(function() {
                btn.textContent = originalText;
                btn.disabled = false;
            });
        });

        // Hacer funciones disponibles globalmente
        window.showMessage = showMessage;
        window.showUserDashboard = showUserDashboard;
        window.checkAuthStatus = checkAuthStatus;
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('laravel_auth', 'laravel_auth_shortcode_improved');
?>