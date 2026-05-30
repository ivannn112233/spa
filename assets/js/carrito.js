/**
 * CARRITO DE COMPRAS
 * Para la tienda de productos
 */

document.addEventListener('DOMContentLoaded', function() {
    inicializarCarrito();
    inicializarBotonesProductos();
    actualizarContadorCarrito();
});

let carrito = [];

/**
 * Inicializar carrito desde localStorage o sesión
 */
function inicializarCarrito() {
    const carritoGuardado = localStorage.getItem('carrito_petspa');
    if(carritoGuardado) {
        carrito = JSON.parse(carritoGuardado);
        actualizarContadorCarrito();
        actualizarVistaCarrito();
    }
}

/**
 * Inicializar botones de agregar al carrito
 */
function inicializarBotonesProductos() {
    const botones = document.querySelectorAll('.btn-agregar-carrito, .producto-card');
    
    botones.forEach(boton => {
        boton.addEventListener('click', function(e) {
            if(this.classList.contains('producto-card') && !e.target.classList.contains('btn')) {
                const id = this.getAttribute('data-id');
                const nombre = this.getAttribute('data-nombre');
                const precio = this.getAttribute('data-precio');
                if(id && nombre && precio) {
                    agregarAlCarrito(id, nombre, precio, 1);
                }
            }
        });
    });
}

/**
 * Agregar producto al carrito
 */
function agregarAlCarrito(id, nombre, precio, cantidad) {
    const index = carrito.findIndex(item => item.id == id);
    
    if(index !== -1) {
        carrito[index].cantidad += cantidad;
    } else {
        carrito.push({
            id: id,
            nombre: nombre,
            precio: parseFloat(precio),
            cantidad: cantidad
        });
    }
    
    guardarCarrito();
    actualizarContadorCarrito();
    mostrarNotificacion(`${nombre} agregado al carrito`);
}

/**
 * Eliminar producto del carrito
 */
function eliminarDelCarrito(id) {
    carrito = carrito.filter(item => item.id != id);
    guardarCarrito();
    actualizarContadorCarrito();
    actualizarVistaCarrito();
    mostrarNotificacion('Producto eliminado del carrito', 'warning');
}

/**
 * Actualizar cantidad de un producto
 */
function actualizarCantidad(id, cantidad) {
    const index = carrito.findIndex(item => item.id == id);
    
    if(index !== -1) {
        if(cantidad <= 0) {
            eliminarDelCarrito(id);
        } else {
            carrito[index].cantidad = cantidad;
            guardarCarrito();
            actualizarContadorCarrito();
            actualizarVistaCarrito();
        }
    }
}

/**
 * Vaciar carrito completamente
 */
function vaciarCarrito() {
    if(confirm('¿Estás seguro de vaciar el carrito?')) {
        carrito = [];
        guardarCarrito();
        actualizarContadorCarrito();
        actualizarVistaCarrito();
        mostrarNotificacion('Carrito vaciado', 'info');
    }
}

/**
 * Guardar carrito en localStorage
 */
function guardarCarrito() {
    localStorage.setItem('carrito_petspa', JSON.stringify(carrito));
    
    // También enviar al servidor vía AJAX
    fetch('api/actualizar_carrito.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ carrito: carrito })
    }).catch(error => console.error('Error guardando carrito:', error));
}

/**
 * Actualizar contador del carrito (badge flotante)
 */
function actualizarContadorCarrito() {
    const totalItems = carrito.reduce((sum, item) => sum + item.cantidad, 0);
    const contadores = document.querySelectorAll('.cart-count');
    
    contadores.forEach(contador => {
        contador.textContent = totalItems;
        contador.style.display = totalItems > 0 ? 'flex' : 'none';
    });
    
    const badge = document.querySelector('.cart-badge');
    if(badge) {
        if(totalItems > 0) {
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }
}

/**
 * Actualizar vista del carrito en la página
 */
function actualizarVistaCarrito() {
    const container = document.getElementById('carrito-container');
    const resumen = document.getElementById('carrito-resumen');
    
    if(!container) return;
    
    if(carrito.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                <p>Tu carrito está vacío</p>
                <a href="index.php" class="btn btn-primary">Seguir comprando</a>
            </div>
        `;
        if(resumen) resumen.innerHTML = '';
        return;
    }
    
    let html = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Producto</th>
                        <th>Precio</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    let total = 0;
    
    carrito.forEach(item => {
        const subtotal = item.precio * item.cantidad;
        total += subtotal;
        
        html += `
            <tr>
                <td>${escapeHtml(item.nombre)}</td>
                <td>Bs. ${item.precio.toFixed(2)}</td>
                <td>
                    <div class="input-group" style="width: 120px;">
                        <button class="btn btn-sm btn-outline-secondary" onclick="actualizarCantidad(${item.id}, ${item.cantidad - 1})">-</button>
                        <input type="number" class="form-control form-control-sm text-center" value="${item.cantidad}" 
                               min="1" onchange="actualizarCantidad(${item.id}, parseInt(this.value))">
                        <button class="btn btn-sm btn-outline-secondary" onclick="actualizarCantidad(${item.id}, ${item.cantidad + 1})">+</button>
                    </div>
                </td>
                <td>Bs. ${subtotal.toFixed(2)}</td>
                <td>
                    <button class="btn btn-sm btn-danger" onclick="eliminarDelCarrito(${item.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                        <td colspan="2"><strong>Bs. ${total.toFixed(2)}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="d-flex justify-content-between mt-3">
            <button class="btn btn-secondary" onclick="vaciarCarrito()">
                <i class="fas fa-trash-alt"></i> Vaciar carrito
            </button>
            <a href="pedido_confirmar.php" class="btn btn-success">
                <i class="fas fa-credit-card"></i> Proceder al pago
            </a>
        </div>
    `;
    
    container.innerHTML = html;
}

/**
 * Mostrar notificación flotante
 */
function mostrarNotificacion(mensaje, tipo = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${tipo}`;
    toast.innerHTML = `
        <div class="toast-header">
            <i class="fas fa-${tipo === 'success' ? 'check-circle' : (tipo === 'warning' ? 'exclamation-triangle' : 'info-circle')}"></i>
            <strong class="me-auto">Carrito</strong>
            <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
        <div class="toast-body">${mensaje}</div>
    `;
    
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
        min-width: 250px;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Calcular total del carrito
 */
function calcularTotal() {
    return carrito.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
}

/**
 * Obtener número de items en el carrito
 */
function getTotalItems() {
    return carrito.reduce((sum, item) => sum + item.cantidad, 0);
}

/**
 * Escapar HTML para prevenir XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Estilos para notificaciones
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .toast-notification {
        animation: slideIn 0.3s ease;
    }
    
    .toast-success .toast-header {
        background: #d4edda;
        color: #155724;
    }
    
    .toast-warning .toast-header {
        background: #fff3cd;
        color: #856404;
    }
    
    .toast-info .toast-header {
        background: #d1ecf1;
        color: #0c5460;
    }
`;
document.head.appendChild(style);

// Exportar funciones para uso global
window.agregarAlCarrito = agregarAlCarrito;
window.eliminarDelCarrito = eliminarDelCarrito;
window.actualizarCantidad = actualizarCantidad;
window.vaciarCarrito = vaciarCarrito;
window.actualizarContadorCarrito = actualizarContadorCarrito;