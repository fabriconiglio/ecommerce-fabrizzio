<?php
/**
 * Integración con Laravel API
 * Funciones para conectar WordPress con el backend Laravel
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// URL base de tu API Laravel
define('LARAVEL_API_BASE', 'http://192.168.2.169:8000/api');

/**
 * Hacer petición GET a la API Laravel
 */
function laravel_api_request($endpoint, $params = []) {
    $url = LARAVEL_API_BASE . $endpoint;
    
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    $args = [
        'timeout' => 15,
        'headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ]
    ];
    
    $response = wp_remote_get($url, $args);
    
    if (is_wp_error($response)) {
        error_log('Laravel API Error: ' . $response->get_error_message());
        return false;
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    if ($status_code !== 200) {
        error_log('Laravel API HTTP Error: ' . $status_code);
        return false;
    }
    
    return json_decode($body, true);
}

/**
 * Obtener productos desde Laravel API
 */
function get_laravel_products($params = []) {
    $default_params = [
        'per_page' => 12,
        'sort_by' => 'created_at',
        'sort_order' => 'desc'
    ];
    
    $params = array_merge($default_params, $params);
    
    $response = laravel_api_request('/products', $params);
    
    if (!$response || !isset($response['data'])) {
        return [];
    }
    
    return $response['data'];
}

/**
 * Obtener categorías desde Laravel API
 */
function get_laravel_categories() {
    $response = laravel_api_request('/categories');
    
    if (!$response || !isset($response['data'])) {
        return [];
    }
    
    return $response['data'];
}

/**
 * Obtener producto específico desde Laravel API
 */
function get_laravel_product($id) {
    $response = laravel_api_request('/products/' . $id);
    
    if (!$response || !isset($response['data'])) {
        return null;
    }
    
    return $response['data'];
}

/**
 * Obtener productos relacionados desde Laravel API
 */
function get_laravel_related_products($id) {
    $response = laravel_api_request('/products/' . $id . '/related');
    
    if (!$response || !isset($response['data'])) {
        return [];
    }
    
    return $response['data'];
}

/**
 * Verificar conexión con Laravel API
 */
function test_laravel_connection() {
    $response = laravel_api_request('/health');
    
    if (!$response) {
        return [
            'status' => 'error',
            'message' => 'No se pudo conectar con la API Laravel'
        ];
    }
    
    return [
        'status' => 'success',
        'message' => 'Conexión exitosa',
        'data' => $response
    ];
}

/**
 * Shortcode para probar conexión API
 */
function laravel_api_test_shortcode() {
    $test = test_laravel_connection();
    
    if ($test['status'] === 'success') {
        $service = $test['data']['service'] ?? 'Laravel API';
        $timestamp = $test['data']['timestamp'] ?? 'N/A';
        
        return '<div class="api-status success">
                <h3>✅ Conexión con Laravel API Exitosa</h3>
                <p><strong>Servicio:</strong> ' . esc_html($service) . '</p>
                <p><strong>Timestamp:</strong> ' . esc_html($timestamp) . '</p>
                <p><strong>Estado:</strong> Conectado y funcionando</p>
                </div>';
    } else {
        return '<div class="api-status error">
                <h3>❌ Error de Conexión con Laravel API</h3>
                <p>' . esc_html($test['message']) . '</p>
                <p><strong>Sugerencia:</strong> Verificar que Laravel esté ejecutándose en puerto 8000</p>
                </div>';
    }
}
add_shortcode('test_laravel_api', 'laravel_api_test_shortcode');

/**
 * Shortcode para mostrar productos de Laravel - ACTUALIZADO CON DETALLE
 */
function laravel_products_shortcode($atts) {
    $atts = shortcode_atts([
        'limit' => 8,
        'category' => '',
        'columns' => 4
    ], $atts);
    
    $params = [
        'per_page' => intval($atts['limit'])
    ];
    
    if (!empty($atts['category'])) {
        $params['category_id'] = intval($atts['category']);
    }
    
    $products = get_laravel_products($params);
    
    if (empty($products)) {
        return '<div class="no-products">
                <h3>No se pudieron cargar productos</h3>
                <p>Verificar conexión con Laravel API</p>
                </div>';
    }
    
    $columns_class = 'columns-' . $atts['columns'];
    
    $html = '<div class="laravel-products-grid ' . $columns_class . '">';
    
    foreach ($products as $product) {
        $html .= '<div class="laravel-product-card">';
        
        // Imagen del producto - clickeable para ir al detalle
        $image = $product['main_image'] ?? 'https://via.placeholder.com/300x300?text=Sin+Imagen';
        $html .= '<div class="product-image">';
        $html .= '<a href="/producto/?product_id=' . esc_attr($product['id']) . '">';
        $html .= '<img src="' . esc_url($image) . '" alt="' . esc_attr($product['title']) . '" loading="lazy">';
        $html .= '</a>';
        $html .= '</div>';
        
        // Información del producto
        $html .= '<div class="product-info">';
        
        // Título clickeable
        $html .= '<h3 class="product-title">';
        $html .= '<a href="/producto/?product_id=' . esc_attr($product['id']) . '" style="text-decoration: none; color: inherit;">';
        $html .= esc_html($product['title']);
        $html .= '</a>';
        $html .= '</h3>';
        
        // Precio
        $price = $product['formatted_price'] ?? '$' . $product['price'];
        $html .= '<div class="product-price">' . esc_html($price) . '</div>';
        
        // Categoría
        if (isset($product['category']['name'])) {
            $html .= '<div class="product-category">Categoría: ' . esc_html($product['category']['name']) . '</div>';
        }
        
        // Stock
        if (isset($product['stock']) && $product['stock'] > 0) {
            $html .= '<div class="product-stock">Stock: ' . esc_html($product['stock']) . ' unidades</div>';
        } else {
            $html .= '<div class="product-stock out-of-stock">Sin stock</div>';
        }
        
        // Botones de acción
        $html .= '<div class="product-actions">';
        
        // Botón agregar al carrito
        $html .= '<button class="add-to-cart-btn" onclick="addToCartFromGrid(\'' . esc_attr($product['id']) . '\', \'' . esc_attr($product['title']) . '\', ' . esc_attr($product['price']) . ', \'' . esc_url($image) . '\')">';
        $html .= 'Agregar al Carrito';
        $html .= '</button>';
        
        // Botón ver detalle
        $html .= '<a href="/producto/?product_id=' . esc_attr($product['id']) . '" class="view-detail-btn">Ver Detalle</a>';
        
        $html .= '</div>'; // .product-actions
        $html .= '</div>'; // .product-info
        $html .= '</div>'; // .laravel-product-card
    }
    
    $html .= '</div>'; // .laravel-products-grid
    
    // Agregar JavaScript para el carrito
    $html .= '<script>
   function addToCartFromGrid(id, title, price, image) {
    // Verificar si el usuario está autenticado
    const token = localStorage.getItem("auth_token");
    
    if (!token) {
        // Si no está autenticado, usar localStorage como fallback
        const cart = JSON.parse(localStorage.getItem("laravel_cart") || "[]");
        const existingItem = cart.find(item => item.id === id);
        
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            cart.push({
                id: id,
                title: title,
                price: price,
                image: image,
                quantity: 1
            });
        }
        
        localStorage.setItem("laravel_cart", JSON.stringify(cart));
        
        showNotification("Producto agregado al carrito local. Inicia sesión para guardarlo.", "info");
        return;
    }

    // Si está autenticado, usar la API
    if (window.addToCartAuth) {
        window.addToCartAuth(id, 1);
    } else {
        // Fallback si la función no está disponible
        showNotification("Error: Sistema de carrito no disponible", "error");
    }
    }

    function showNotification(message, type = "success") {
        const notification = document.createElement("div");
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === "success" ? "#27ae60" : type === "error" ? "#e74c3c" : type === "info" ? "#3498db" : "#f39c12"};
            color: white;
            padding: 15px 25px;
            border-radius: 6px;
            z-index: 10000;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 4000);
    }
    </script>';
    
    return $html;
}
add_shortcode('laravel_products', 'laravel_products_shortcode');

/**
 * Shortcode para mostrar detalle de producto
 */
function laravel_product_detail_shortcode($atts) {
    $atts = shortcode_atts([
        'product_id' => ''
    ], $atts);
    
    // Si no hay ID, intentar obtenerlo de la URL
    if (empty($atts['product_id'])) {
        $atts['product_id'] = isset($_GET['product_id']) ? $_GET['product_id'] : '';
    }
    
    if (empty($atts['product_id'])) {
        return '<div class="no-product">
                <h3>Producto no encontrado</h3>
                <p><a href="/tienda/">Volver a la tienda</a></p>
                </div>';
    }
    
    $product = get_laravel_product($atts['product_id']);
    
    if (!$product) {
        return '<div class="no-product">
                <h3>Producto no encontrado</h3>
                <p><a href="/tienda/">Volver a la tienda</a></p>
                </div>';
    }
    
    $related_products = get_laravel_related_products($atts['product_id']);
    
    $html = '<div class="product-detail-container">';
    
    // Detalle del producto
    $html .= '<div class="product-detail-content">';
    
    // Imagen
    $image = $product['main_image'] ?? 'https://via.placeholder.com/500x500?text=Sin+Imagen';
    $html .= '<div class="product-detail-image">';
    $html .= '<img src="' . esc_url($image) . '" alt="' . esc_attr($product['title']) . '">';
    $html .= '</div>';
    
    // Información
    $html .= '<div class="product-detail-info">';
    
    // Categoría
    if (isset($product['category']['name'])) {
        $html .= '<div class="product-detail-category">' . esc_html($product['category']['name']) . '</div>';
    }
    
    // Título
    $html .= '<h1 class="product-detail-title">' . esc_html($product['title']) . '</h1>';
    
    // Precio
    $price = $product['formatted_price'] ?? '$' . $product['price'];
    $html .= '<div class="product-detail-price">' . esc_html($price) . '</div>';
    
    // Descripción
    if (!empty($product['description'])) {
        $html .= '<div class="product-detail-description">';
        $html .= '<p>' . esc_html($product['description']) . '</p>';
        $html .= '</div>';
    }
    
    // Stock
    $html .= '<div class="product-detail-stock">';
    if (isset($product['stock']) && $product['stock'] > 0) {
        $html .= '<p><strong>Stock disponible:</strong> ' . esc_html($product['stock']) . ' unidades</p>';
    } else {
        $html .= '<p class="out-of-stock"><strong>Sin stock</strong></p>';
    }
    $html .= '</div>';
    
    // Acciones
    $html .= '<div class="product-detail-actions">';
    
    if (isset($product['stock']) && $product['stock'] > 0) {
        // Selector de cantidad
        $html .= '<div class="quantity-selector">';
        $html .= '<label for="quantity-' . $product['id'] . '">Cantidad:</label>';
        $html .= '<div class="quantity-controls">';
        $html .= '<button type="button" onclick="updateQuantity(' . $product['id'] . ', -1)">-</button>';
        $html .= '<input type="number" id="quantity-' . $product['id'] . '" value="1" min="1" max="' . $product['stock'] . '">';
        $html .= '<button type="button" onclick="updateQuantity(' . $product['id'] . ', 1)">+</button>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Botón agregar al carrito
        $html .= '<button class="add-to-cart-detail-btn" onclick="addToCartDetail(\'' . esc_attr($product['id']) . '\', \'' . esc_attr($product['title']) . '\', ' . esc_attr($product['price']) . ', \'' . esc_url($image) . '\')">';
        $html .= 'Agregar al Carrito';
        $html .= '</button>';
    } else {
        $html .= '<button class="add-to-cart-detail-btn" disabled>Sin stock</button>';
    }
    
    // Botón volver
    $html .= '<a href="/tienda/" class="back-to-shop-btn">Seguir comprando</a>';
    
    $html .= '</div>'; // .product-detail-actions
    $html .= '</div>'; // .product-detail-info
    $html .= '</div>'; // .product-detail-content
    
    // Productos relacionados
    if (!empty($related_products)) {
        $html .= '<div class="related-products-section">';
        $html .= '<h3>Productos relacionados</h3>';
        $html .= '<div class="related-products-grid">';
        
        foreach ($related_products as $related) {
            $related_image = $related['main_image'] ?? 'https://via.placeholder.com/200x200?text=Sin+Imagen';
            $related_price = $related['formatted_price'] ?? '$' . $related['price'];
            
            $html .= '<div class="related-product-card">';
            $html .= '<a href="/producto/?product_id=' . esc_attr($related['id']) . '">';
            $html .= '<img src="' . esc_url($related_image) . '" alt="' . esc_attr($related['title']) . '">';
            $html .= '<div class="related-product-info">';
            $html .= '<h4>' . esc_html($related['title']) . '</h4>';
            $html .= '<div class="related-product-price">' . esc_html($related_price) . '</div>';
            $html .= '</div>';
            $html .= '</a>';
            $html .= '</div>';
        }
        
        $html .= '</div>'; // .related-products-grid
        $html .= '</div>'; // .related-products-section
    }
    
    $html .= '</div>'; // .product-detail-container
    
    // JavaScript para funcionalidad del detalle
    $html .= '<script>
    function updateQuantity(productId, change) {
        const quantityInput = document.getElementById(\'quantity-\' + productId);
        if (!quantityInput) return;
        
        let currentValue = parseInt(quantityInput.value, 10);
        const max = parseInt(quantityInput.max, 10) || 99;
        
        let newValue = currentValue + change;
        
        if (newValue < 1) {
            newValue = 1;
        }
        if (newValue > max) {
            newValue = max;
        }
        
        quantityInput.value = newValue;
    }

    function addToCartDetail(id, title, price, image) {
    const quantityInput = document.getElementById("quantity-" + id);
    const quantity = parseInt(quantityInput.value);
    
    // Verificar si el usuario está autenticado
    const token = localStorage.getItem("auth_token");
    
    if (!token) {
        // Si no está autenticado, usar localStorage como fallback
        const cart = JSON.parse(localStorage.getItem("laravel_cart") || "[]");
        const existingItem = cart.find(item => item.id === id);
        
        if (existingItem) {
            existingItem.quantity += quantity;
        } else {
            cart.push({
                id: id,
                title: title,
                price: price,
                image: image,
                quantity: quantity
            });
        }
        
        localStorage.setItem("laravel_cart", JSON.stringify(cart));
        
        showNotification("Producto agregado al carrito local (" + quantity + " unidad" + (quantity > 1 ? "es" : "") + "). Inicia sesión para guardarlo.", "info");
        return;
    }

    // Si está autenticado, usar la API
    if (window.addToCartAuth) {
        window.addToCartAuth(id, quantity);
    } else {
        // Fallback si la función no está disponible
        showNotification("Error: Sistema de carrito no disponible", "error");
    }
}

function showNotification(message, type = "success") {
        const notification = document.createElement("div");
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === "success" ? "#27ae60" : type === "error" ? "#e74c3c" : type === "info" ? "#3498db" : "#f39c12"};
            color: white;
            padding: 15px 25px;
            border-radius: 6px;
            z-index: 10000;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 4000);
    }
    </script>';
    
    return $html;
}
add_shortcode('laravel_product_detail', 'laravel_product_detail_shortcode');

/**
 * Agregar estilos CSS para los productos de Laravel
 */
function laravel_products_styles() {
    echo '<style>
    /* Estilos para productos de Laravel API */
    .api-status {
        padding: 20px;
        margin: 20px 0;
        border-radius: 8px;
        border: 2px solid;
    }
    
    .api-status.success {
        background: #d4edda;
        border-color: #c3e6cb;
        color: #155724;
    }
    
    .api-status.error {
        background: #f8d7da;
        border-color: #f5c6cb;
        color: #721c24;
    }
    
    .laravel-products-grid {
        display: grid;
        gap: 20px;
        margin: 20px 0;
    }
    
    .laravel-products-grid.columns-2 { grid-template-columns: repeat(2, 1fr); }
    .laravel-products-grid.columns-3 { grid-template-columns: repeat(3, 1fr); }
    .laravel-products-grid.columns-4 { grid-template-columns: repeat(4, 1fr); }
    
    @media (max-width: 768px) {
        .laravel-products-grid { grid-template-columns: 1fr !important; }
    }
    
    @media (max-width: 1024px) and (min-width: 769px) {
        .laravel-products-grid.columns-4 { grid-template-columns: repeat(2, 1fr); }
        .laravel-products-grid.columns-3 { grid-template-columns: repeat(2, 1fr); }
    }
    
    .laravel-product-card {
        background: white;
        border: 1px solid #ddd;
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .laravel-product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .product-image {
        position: relative;
        overflow: hidden;
    }
    
    .product-image img {
        width: 100%;
        height: 250px;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .product-image a {
        display: block;
    }
    
    .laravel-product-card:hover .product-image img {
        transform: scale(1.05);
    }
    
    .product-info {
        padding: 15px;
    }
    
    .product-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin: 0 0 10px 0;
        color: #333;
        line-height: 1.4;
    }
    
    .product-title a:hover {
        color: #3498db !important;
    }
    
    .product-price {
        font-size: 1.3rem;
        font-weight: bold;
        color: #e74c3c;
        margin: 10px 0;
    }
    
    .product-category {
        font-size: 0.9rem;
        color: #666;
        margin: 5px 0;
    }
    
    .product-stock {
        font-size: 0.85rem;
        color: #27ae60;
        margin: 5px 0;
    }
    
    .product-stock.out-of-stock {
        color: #e74c3c;
    }
    
    .product-actions {
        margin-top: 15px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .add-to-cart-btn {
        width: 100%;
        background: #3498db;
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .add-to-cart-btn:hover {
        background: #2980b9;
        transform: translateY(-2px);
    }
    
    .view-detail-btn {
        display: block;
        width: 100%;
        background: #95a5a6;
        color: white;
        text-decoration: none;
        text-align: center;
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-sizing: border-box;
    }
    
    .view-detail-btn:hover {
        background: #7f8c8d;
        color: white;
        text-decoration: none;
        transform: translateY(-2px);
    }
    
    .no-products, .no-product {
        text-align: center;
        padding: 40px 20px;
        background: #f8f9fa;
        border-radius: 8px;
        color: #666;
    }
    
    /* Estilos para detalle de producto */
    .product-detail-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .product-detail-content {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
        margin-bottom: 40px;
    }
    
    .product-detail-image img {
        width: 100%;
        max-height: 500px;
        object-fit: cover;
        border-radius: 8px;
    }
    
    .product-detail-category {
        background: #f0f0f0;
        padding: 5px 10px;
        border-radius: 15px;
        display: inline-block;
        font-size: 12px;
        margin-bottom: 15px;
        color: #666;
    }
    
    .product-detail-title {
        font-size: 28px;
        margin-bottom: 15px;
        color: #333;
    }
    
    .product-detail-price {
        font-size: 32px;
        color: #27ae60;
        font-weight: bold;
        margin: 20px 0;
    }
    
    .product-detail-description {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin: 20px 0;
        line-height: 1.6;
    }
    
    .product-detail-stock {
        margin: 20px 0;
    }
    
    .quantity-selector {
        margin: 20px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .quantity-controls {
        display: flex;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .quantity-controls button {
        background: #f0f0f0;
        border: none;
        width: 35px;
        height: 35px;
        cursor: pointer;
    }
    
    .quantity-controls input {
        width: 50px;
        text-align: center;
        border: none;
        height: 35px;
    }
    
    .add-to-cart-detail-btn {
        background: #3498db;
        color: white;
        border: none;
        padding: 15px 30px;
        border-radius: 6px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        width: 100%;
        margin-bottom: 10px;
    }
    
    .add-to-cart-detail-btn:hover {
        background: #2980b9;
    }
    
    .add-to-cart-detail-btn:disabled {
        background: #bdc3c7;
        cursor: not-allowed;
    }
    
    .back-to-shop-btn {
        display: block;
        text-align: center;
        color: #666;
        text-decoration: none;
        margin-top: 10px;
    }
    
    .related-products-section {
        margin-top: 50px;
        border-top: 2px solid #f0f0f0;
        padding-top: 30px;
    }
    
    .related-products-section h3 {
        text-align: center;
        margin-bottom: 30px;
        font-size: 24px;
    }
    
    .related-products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
    }
    
    .related-product-card {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        overflow: hidden;
        transition: transform 0.3s ease;
    }
    
    .related-product-card:hover {
        transform: translateY(-5px);
    }
    
    .related-product-card a {
        text-decoration: none;
        color: inherit;
    }
    
    .related-product-card img {
        width: 100%;
        height: 150px;
        object-fit: cover;
    }
    
    .related-product-info {
        padding: 15px;
    }
    
    .related-product-info h4 {
        font-weight: 600;
        margin-bottom: 8px;
        color: #333;
        font-size: 14px;
    }
    
    .related-product-price {
        color: #27ae60;
        font-weight: bold;
    }
    
    @media (max-width: 768px) {
        .product-detail-content {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .product-detail-title {
            font-size: 24px;
        }
        
        .product-detail-price {
            font-size: 28px;
        }
        
        .related-products-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        }
    }
    </style>';
}
add_action('wp_head', 'laravel_products_styles');

function laravel_cart_auth_shortcode() {
    ob_start();
    ?>
    <div id="laravel-cart-auth-container">
        <!-- Mensaje de usuario no autenticado -->
        <div id="not-authenticated" style="display: none;">
            <div class="auth-required-message">
                <h3>🔐 Inicia sesión para ver tu carrito</h3>
                <p>Necesitas estar logueado para agregar productos al carrito y realizar compras.</p>
                <a href="/login/" class="auth-btn primary">Ir al Login</a>
                <a href="/tienda/" class="auth-btn secondary">Seguir Comprando</a>
            </div>
        </div>

        <!-- Carrito para usuarios autenticados -->
        <div id="authenticated-cart" style="display: none;">
            <div class="cart-header">
                <h2>Mi Carrito de Compras</h2>
                <div class="cart-summary">
                    <span id="cart-item-count">0 productos</span>
                    <span id="cart-total-display">$0</span>
                </div>
            </div>
            
            <div id="cart-loading" class="loading-message" style="display: none;">
                <p>Cargando carrito...</p>
            </div>
            
            <div id="cart-content">
                <div id="cart-items" class="cart-items-container">
                    <!-- Los items se cargarán via JavaScript -->
                </div>
                
                <div class="cart-actions">
                    <button id="clear-cart" class="cart-btn secondary">Vaciar Carrito</button>
                    <button id="continue-shopping" class="cart-btn tertiary" onclick="window.location.href='/tienda/'">Seguir Comprando</button>
                    <button id="checkout-btn" class="cart-btn primary">Finalizar Compra</button>
                </div>
            </div>
            
            <div id="empty-cart" class="empty-cart" style="display: none;">
                <div class="empty-cart-content">
                    <div class="empty-cart-icon">🛒</div>
                    <h3>Tu carrito está vacío</h3>
                    <p>¡Agrega algunos productos para comenzar a comprar!</p>
                    <a href="/tienda/" class="cart-btn primary">Ir a la Tienda</a>
                </div>
            </div>
        </div>
    </div>

    <style>
    #laravel-cart-auth-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 20px;
    }

    .auth-required-message {
        text-align: center;
        padding: 60px 20px;
        background: #f8f9fa;
        border-radius: 12px;
        border: 2px solid #e9ecef;
    }

    .auth-required-message h3 {
        color: #495057;
        margin-bottom: 15px;
        font-size: 24px;
    }

    .auth-required-message p {
        color: #6c757d;
        margin-bottom: 30px;
        font-size: 16px;
    }

    .cart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #e0e0e0;
    }

    .cart-header h2 {
        margin: 0;
        color: #333;
    }

    .cart-summary {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 5px;
        color: #666;
        font-weight: 600;
    }

    .cart-items-container {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-bottom: 30px;
    }

    .cart-item {
        display: grid;
        grid-template-columns: 100px 1fr auto auto auto;
        gap: 20px;
        align-items: center;
        padding: 20px;
        border-bottom: 1px solid #f0f0f0;
    }

    .cart-item:last-child {
        border-bottom: none;
    }

    .cart-item-image {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 8px;
    }

    .cart-item-info {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .cart-item-title {
        font-weight: 600;
        color: #333;
        font-size: 16px;
    }

    .cart-item-price {
        color: #27ae60;
        font-weight: 700;
        font-size: 18px;
    }

    .quantity-controls {
        display: flex;
        align-items: center;
        gap: 10px;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        padding: 5px;
    }

    .quantity-controls button {
        background: #f8f9fa;
        border: none;
        width: 30px;
        height: 30px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
    }

    .quantity-controls input {
        width: 50px;
        text-align: center;
        border: none;
        font-weight: 600;
    }

    .cart-item-subtotal {
        font-weight: 700;
        color: #333;
        font-size: 18px;
    }

    .remove-item {
        background: #e74c3c;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
    }

    .cart-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin: 30px 0;
        flex-wrap: wrap;
    }

    .cart-btn {
        padding: 15px 25px;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        font-size: 16px;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
        text-align: center;
    }

    .cart-btn.primary {
        background: #27ae60;
        color: white;
    }

    .cart-btn.primary:hover {
        background: #219a54;
    }

    .cart-btn.secondary {
        background: #e74c3c;
        color: white;
    }

    .cart-btn.secondary:hover {
        background: #c0392b;
    }

    .cart-btn.tertiary {
        background: #3498db;
        color: white;
    }

    .cart-btn.tertiary:hover {
        background: #2980b9;
    }

    .auth-btn {
        padding: 15px 30px;
        margin: 0 10px;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        font-size: 16px;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
    }

    .auth-btn.primary {
        background: #3498db;
        color: white;
    }

    .auth-btn.secondary {
        background: #95a5a6;
        color: white;
    }

    .empty-cart {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .empty-cart-icon {
        font-size: 80px;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .empty-cart h3 {
        color: #333;
        margin-bottom: 10px;
    }

    .empty-cart p {
        color: #666;
        margin-bottom: 30px;
    }

    .loading-message {
        text-align: center;
        padding: 40px;
        font-size: 18px;
        color: #666;
    }

    @media (max-width: 768px) {
        .cart-item {
            grid-template-columns: 80px 1fr;
            gap: 15px;
        }

        .cart-item-controls {
            grid-column: 1 / -1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }

        .cart-actions {
            flex-direction: column;
        }

        .cart-header {
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 15px;
        }
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const API_BASE_URL = '<?php echo LARAVEL_API_BASE; ?>';
        
        checkAuthAndLoadCart();

        function checkAuthAndLoadCart() {
            const token = localStorage.getItem('auth_token');
            
            if (!token) {
                showNotAuthenticated();
                return;
            }

            showAuthenticatedCart();
            loadCart();
        }

        function showNotAuthenticated() {
            document.getElementById('not-authenticated').style.display = 'block';
            document.getElementById('authenticated-cart').style.display = 'none';
        }

        function showAuthenticatedCart() {
            document.getElementById('not-authenticated').style.display = 'none';
            document.getElementById('authenticated-cart').style.display = 'block';
        }

        async function loadCart() {
            const token = localStorage.getItem('auth_token');
            showLoading(true);

            try {
                const response = await fetch(API_BASE_URL + '/cart', {
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    renderCart(data.data);
                } else {
                    showError('Error al cargar el carrito');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Error de conexión');
            } finally {
                showLoading(false);
            }
        }

        function renderCart(cartData) {
            const { items, summary } = cartData;
            const cartItems = document.getElementById('cart-items');
            const emptyCart = document.getElementById('empty-cart');
            const cartContent = document.getElementById('cart-content');

            // Actualizar resumen
            document.getElementById('cart-item-count').textContent = summary.items_count + ' producto' + (summary.items_count !== 1 ? 's' : '');
            document.getElementById('cart-total-display').textContent = summary.formatted_total;

            if (items.length === 0) {
                cartContent.style.display = 'none';
                emptyCart.style.display = 'block';
                return;
            }

            cartContent.style.display = 'block';
            emptyCart.style.display = 'none';

            let itemsHTML = '';
            items.forEach(item => {
                itemsHTML += `
                    <div class="cart-item">
                        <img src="${item.product.main_image}" alt="${item.product.title}" class="cart-item-image">
                        <div class="cart-item-info">
                            <div class="cart-item-title">${item.product.title}</div>
                            <div class="cart-item-price">${item.formatted_subtotal}</div>
                            <small>Precio: $${item.product.current_price}</small>
                        </div>
                        <div class="quantity-controls">
                            <button onclick="updateCartQuantity('${item.id}', ${item.quantity - 1})">-</button>
                            <input type="number" value="${item.quantity}" min="0" max="${item.product.stock}" 
                                   onchange="updateCartQuantity('${item.id}', this.value)">
                            <button onclick="updateCartQuantity('${item.id}', ${item.quantity + 1})">+</button>
                        </div>
                        <div class="cart-item-subtotal">${item.formatted_subtotal}</div>
                        <button onclick="removeCartItem('${item.id}')" class="remove-item">Eliminar</button>
                    </div>
                `;
            });

            cartItems.innerHTML = itemsHTML;
        }

        function showLoading(show) {
            document.getElementById('cart-loading').style.display = show ? 'block' : 'none';
        }

        function showError(message) {
            showNotification(message, 'error');
        }

        // Funciones globales
        window.updateCartQuantity = async function(itemId, newQuantity) {
            const token = localStorage.getItem('auth_token');
            
            if (newQuantity < 0) return;

            try {
                const response = await fetch(API_BASE_URL + '/cart/' + itemId, {
                    method: 'PUT',
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ quantity: parseInt(newQuantity) })
                });

                const data = await response.json();

                if (data.success) {
                    loadCart(); // Recargar carrito
                    showNotification(data.message, 'success');
                } else {
                    showNotification(data.message || 'Error al actualizar', 'error');
                }
            } catch (error) {
                showNotification('Error de conexión', 'error');
            }
        };

        window.removeCartItem = async function(itemId) {
            const token = localStorage.getItem('auth_token');

            if (!confirm('¿Eliminar este producto del carrito?')) return;

            try {
                const response = await fetch(API_BASE_URL + '/cart/' + itemId, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    loadCart(); // Recargar carrito
                    showNotification('Producto eliminado', 'success');
                } else {
                    showNotification(data.message || 'Error al eliminar', 'error');
                }
            } catch (error) {
                showNotification('Error de conexión', 'error');
            }
        };

        // Event listeners
        document.getElementById('clear-cart').addEventListener('click', async function() {
            const token = localStorage.getItem('auth_token');

            if (!confirm('¿Vaciar todo el carrito?')) return;

            try {
                const response = await fetch(API_BASE_URL + '/cart', {
                    method: 'DELETE',
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    loadCart();
                    showNotification('Carrito vaciado', 'success');
                } else {
                    showNotification(data.message || 'Error', 'error');
                }
            } catch (error) {
                showNotification('Error de conexión', 'error');
            }
        });

        document.getElementById('checkout-btn').addEventListener('click', async function() {
            const token = localStorage.getItem('auth_token');

            if (!confirm('¿Confirmar compra?')) return;

            try {
                const response = await fetch(API_BASE_URL + '/cart/checkout', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert(`¡Compra exitosa!\\nNúmero de orden: ${data.data.order_number}\\nTotal: ${data.data.formatted_total}`);
                    loadCart();
                } else {
                    showNotification(data.message || 'Error en la compra', 'error');
                }
            } catch (error) {
                showNotification('Error de conexión', 'error');
            }
        });

        function showNotification(message, type = "success") {
    const notification = document.createElement("div");
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#27ae60' : type === 'error' ? '#e74c3c' : type === 'info' ? '#3498db' : '#f39c12'};
        color: white;
        padding: 15px 25px;
        border-radius: 6px;
        z-index: 10000;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 4000);
}

        // Función global para agregar al carrito desde otros lugares
        window.addToCartAuth = async function(productId, quantity = 1) {
            const token = localStorage.getItem('auth_token');
            
            if (!token) {
                showNotification('Debes iniciar sesión para agregar productos al carrito', 'error');
                setTimeout(() => {
                    window.location.href = '/login/';
                }, 2000);
                return;
            }

            try {
                const response = await fetch(API_BASE_URL + '/cart', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: quantity
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showNotification('Producto agregado al carrito', 'success');
                    // Si estamos en la página del carrito, recargar
                    if (document.getElementById('laravel-cart-auth-container')) {
                        loadCart();
                    }
                } else {
                    showNotification(data.message || 'Error al agregar al carrito', 'error');
                }
            } catch (error) {
                showNotification('Error de conexión', 'error');
            }
        };
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('laravel_cart_auth', 'laravel_cart_auth_shortcode');
?>