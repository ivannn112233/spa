/**
 * CALENDARIO CON DRAG & DROP
 * Para la gestión de citas en recepción
 */

document.addEventListener('DOMContentLoaded', function() {
    inicializarDragAndDrop();
    inicializarFiltrosCalendario();
    inicializarVistaCalendario();
});

/**
 * Inicializa funcionalidad drag & drop para reprogramar citas
 */
function inicializarDragAndDrop() {
    const citas = document.querySelectorAll('.cita-item');
    const slots = document.querySelectorAll('.slot-vacio');
    
    // Configurar elementos arrastrables
    citas.forEach(cita => {
        cita.setAttribute('draggable', 'true');
        
        cita.addEventListener('dragstart', function(e) {
            dragCitaId = this.getAttribute('data-id');
            e.dataTransfer.setData('text/plain', dragCitaId);
            this.classList.add('dragging');
        });
        
        cita.addEventListener('dragend', function(e) {
            this.classList.remove('dragging');
            dragCitaId = null;
        });
    });
    
    // Configurar zonas de destino
    slots.forEach(slot => {
        slot.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('drag-over');
        });
        
        slot.addEventListener('dragleave', function(e) {
            this.classList.remove('drag-over');
        });
        
        slot.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
            
            const citaId = e.dataTransfer.getData('text/plain');
            if(citaId) {
                // Obtener nueva fecha y hora del slot
                const fila = this.closest('tr');
                const hora = fila.querySelector('.hora-label')?.innerText;
                const groomerId = this.closest('td').parentElement.querySelector('th')?.innerText;
                const fecha = document.getElementById('fechaActual')?.value || new Date().toISOString().split('T')[0];
                
                if(hora) {
                    mostrarModalReprogramar(citaId, fecha, hora, groomerId);
                }
            }
        });
    });
}

let dragCitaId = null;

/**
 * Muestra modal para reprogramar cita
 */
function mostrarModalReprogramar(citaId, fecha, hora, groomerId) {
    document.getElementById('reprogramar_cita_id').value = citaId;
    document.getElementById('nueva_fecha').value = fecha;
    document.getElementById('nueva_hora').value = hora;
    if(document.getElementById('nuevo_groomer')) {
        document.getElementById('nuevo_groomer').value = groomerId;
    }
    
    const modal = new bootstrap.Modal(document.getElementById('reprogramarModal'));
    modal.show();
}

/**
 * Cambiar vista del calendario (día/semana)
 */
function cambiarVista(vista) {
    const fecha = document.getElementById('fechaActual')?.value || new Date().toISOString().split('T')[0];
    window.location.href = `calendario.php?vista=${vista}&fecha=${fecha}`;
}

/**
 * Navegar a fecha anterior/siguiente
 */
function navegarFecha(direccion) {
    let fecha = document.getElementById('fechaActual')?.value;
    if(!fecha) {
        fecha = new Date().toISOString().split('T')[0];
    }
    
    const nuevaFecha = new Date(fecha);
    nuevaFecha.setDate(nuevaFecha.getDate() + direccion);
    const fechaStr = nuevaFecha.toISOString().split('T')[0];
    
    const vista = document.querySelector('.btn-group .active')?.innerText.toLowerCase() || 'dia';
    window.location.href = `calendario.php?vista=${vista}&fecha=${fechaStr}`;
}

/**
 * Ir a hoy en el calendario
 */
function irHoy() {
    const hoy = new Date().toISOString().split('T')[0];
    const vista = document.querySelector('.btn-group .active')?.innerText.toLowerCase() || 'dia';
    window.location.href = `calendario.php?vista=${vista}&fecha=${hoy}`;
}

/**
 * Inicializar filtros del calendario
 */
function inicializarFiltrosCalendario() {
    const filtroGroomer = document.getElementById('filtro_groomer');
    const filtroEstado = document.getElementById('filtro_estado');
    
    if(filtroGroomer) {
        filtroGroomer.addEventListener('change', function() {
            aplicarFiltros();
        });
    }
    
    if(filtroEstado) {
        filtroEstado.addEventListener('change', function() {
            aplicarFiltros();
        });
    }
}

/**
 * Aplicar filtros a las citas del calendario
 */
function aplicarFiltros() {
    const groomerId = document.getElementById('filtro_groomer')?.value;
    const estado = document.getElementById('filtro_estado')?.value;
    const citas = document.querySelectorAll('.cita-item');
    
    citas.forEach(cita => {
        let mostrar = true;
        
        if(groomerId && groomerId !== 'todos') {
            const citaGroomer = cita.getAttribute('data-groomer-id');
            if(citaGroomer !== groomerId) mostrar = false;
        }
        
        if(estado && estado !== 'todos') {
            if(!cita.classList.contains(estado)) mostrar = false;
        }
        
        cita.style.display = mostrar ? 'block' : 'none';
        const slotPadre = cita.closest('td');
        if(slotPadre && mostrar) {
            slotPadre.style.backgroundColor = '#e8f4fd';
        } else if(slotPadre) {
            slotPadre.style.backgroundColor = '';
        }
    });
}

/**
 * Inicializar vista del calendario
 */
function inicializarVistaCalendario() {
    const hoy = new Date();
    const fechaInput = document.getElementById('fechaActual');
    if(fechaInput && !fechaInput.value) {
        fechaInput.value = hoy.toISOString().split('T')[0];
    }
    
    // Resaltar hora actual
    const horaActual = new Date().getHours();
    const minutosActual = new Date().getMinutes();
    const horaActualStr = `${horaActual.toString().padStart(2,'0')}:${minutosActual.toString().padStart(2,'0')}`;
    
    const filas = document.querySelectorAll('.hora-fila');
    filas.forEach(fila => {
        const horaLabel = fila.querySelector('.hora-label')?.innerText;
        if(horaLabel && horaLabel <= horaActualStr) {
            fila.style.backgroundColor = '#f0f8ff';
        }
    });
}

/**
 * Recargar citas del calendario vía AJAX
 */
function recargarCitas() {
    const fecha = document.getElementById('fechaActual')?.value;
    if(!fecha) return;
    
    fetch(`api/obtener_citas.php?fecha=${fecha}`)
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                actualizarCalendario(data.citas);
            }
        })
        .catch(error => console.error('Error:', error));
}

/**
 * Actualizar el calendario con nuevas citas
 */
function actualizarCalendario(citas) {
    // Limpiar slots existentes
    document.querySelectorAll('.cita-item').forEach(item => item.remove());
    
    // Insertar nuevas citas
    citas.forEach(cita => {
        const slot = document.querySelector(`td[data-hora="${cita.hora}"][data-groomer="${cita.groomer_id}"]`);
        if(slot) {
            const citaDiv = document.createElement('div');
            citaDiv.className = `cita-item ${cita.estado}`;
            citaDiv.setAttribute('data-id', cita.id);
            citaDiv.setAttribute('draggable', 'true');
            citaDiv.innerHTML = `
                <strong>${cita.mascota.substring(0, 15)}</strong><br>
                <small>${cita.servicio}</small>
            `;
            slot.appendChild(citaDiv);
        }
    });
    
    // Reinicializar drag & drop
    inicializarDragAndDrop();
}

// Exportar funciones para uso global
window.cambiarVista = cambiarVista;
window.navegarFecha = navegarFecha;
window.irHoy = irHoy;
window.aplicarFiltros = aplicarFiltros;