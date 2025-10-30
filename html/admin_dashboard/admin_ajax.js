// admin_ajax.js - Lógica del panel de administración

let currentType = 'usuarios';
let chartInstance = null;

document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin panel loaded');
    
    // Initialize
    loadCounts();
    loadTableData(currentType);
    loadChartData();

    // Sidebar navigation
    const navLinks = document.querySelectorAll('.nav_option');
    console.log('Found nav links:', navLinks.length);
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all links
            navLinks.forEach(l => l.classList.remove('active'));
            
            // Add active class to clicked link
            this.classList.add('active');
            
            // Get type from data attribute
            const type = this.getAttribute('data-type');
            console.log('Clicked type:', type);
            
            if(type) {
                currentType = type;
                loadTableData(currentType);
            }
        });
    });

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if(searchInput) {
        searchInput.addEventListener('input', debounce(function() {
            loadTableData(currentType);
        }, 500));
    }

    // Filter buttons
    const filterAZ = document.getElementById('filterAZ');
    const filterID = document.getElementById('filterID');
    const filterEstado = document.getElementById('filterEstado');

    if(filterAZ) {
        filterAZ.addEventListener('click', function() {
            loadTableData(currentType, 'az');
        });
    }

    if(filterID) {
        filterID.addEventListener('click', function() {
            loadTableData(currentType, 'id');
        });
    }

    if(filterEstado) {
        filterEstado.addEventListener('change', function() {
            loadTableData(currentType);
        });
    // Menu toggle for mobile
    const menuToggle = document.querySelector('.menu_toggle');
    const lateralBar = document.querySelector('.lateral_bar');
    const adminContent = document.querySelector('.admin_content');

    if(menuToggle && lateralBar && adminContent) {
        menuToggle.addEventListener('click', function() {
            lateralBar.classList.toggle('collapsed');
            adminContent.classList.toggle('collapsed');
        });
    }
};

// Load counts for stat cards
function loadCounts() {
    console.log('Loading counts...');
    fetch('api_admin.php?action=get_counts')
        .then(response => response.json())
        .then(data => {
            console.log('Counts data:', data);
            if(data.success) {
                const counts = data.counts;
                
                // Update stat cards
                const statCards = document.querySelectorAll('.stat_card');
                if(statCards[0]) {
                    statCards[0].querySelector('.stat_number').textContent = counts.hilos_pendientes || 0;
                }
                if(statCards[1]) {
                    statCards[1].querySelector('.stat_number').textContent = counts.items_pendientes || 0;
                }
                if(statCards[2]) {
                    statCards[2].querySelector('.stat_number').textContent = counts.total_usuarios || 0;
                }
                if(statCards[3]) {
                    statCards[3].querySelector('.stat_number').textContent = counts.total_items || 0;
                }
            }
        })
        .catch(error => console.error('Error loading counts:', error));
}

// Load table data
function loadTableData(type, orderBy = 'id') {
    console.log('Loading table data for type:', type);
    
    const searchInput = document.getElementById('searchInput');
    const filterEstado = document.getElementById('filterEstado');
    
    const search = searchInput ? searchInput.value : '';
    const estado = filterEstado ? filterEstado.value : 'all';
    
    const params = new URLSearchParams({
        action: 'get_data',
        type: type,
        search: search,
        orderBy: orderBy,
        filterEstado: estado
    });
    
    console.log('Fetching:', 'api_admin.php?' + params.toString());
    
    fetch('api_admin.php?' + params.toString())
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Table data:', data);
            if(data.success) {
                renderTable(data.data, type);
            } else {
                console.error('Error in response:', data);
            }
        })
        .catch(error => console.error('Error loading table data:', error));
}

// Render table based on type
function renderTable(data, type) {
    console.log('Rendering table for type:', type, 'with', data.length, 'rows');
    
    const tbody = document.querySelector('.solicitudes_table tbody');
    const thead = document.querySelector('.solicitudes_table thead tr');
    
    if(!tbody || !thead) {
        console.error('Table elements not found');
        return;
    }
    
    // Clear table
    tbody.innerHTML = '';
    thead.innerHTML = '';
    
    if(type === 'usuarios') {
        // Headers for usuarios: ID USUARIO CORREO ESTADO ACCIONES
        thead.innerHTML = `
            <th>ID</th>
            <th>Usuario</th>
            <th>Correo</th>
            <th>Estado</th>
            <th>Acciones</th>
        `;
        
        // Rows
        data.forEach(row => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${row.ID_user}</td>
                <td>${escapeHtml(row.usuario)}</td>
                <td>${escapeHtml(row.correo)}</td>
                <td><span class="badge ${row.estado ? 'badge-active' : 'badge-inactive'}">${row.estado ? 'Activo' : 'Inactivo'}</span></td>
                <td>
                    <button class="btn btn-success btn-sm" onclick="updateEstado('${type}', ${row.ID_user}, 'true')">Activar</button>
                    <button class="btn btn-danger btn-sm" onclick="updateEstado('${type}', ${row.ID_user}, 'false')">Desactivar</button>
                </td>
            `;
            tbody.appendChild(tr);
        });
        
    } else if(type === 'items') {
        // Headers for items: ID USUARIO TITULO URL_ARCHIVO ESTADO ACCIONES
        thead.innerHTML = `
            <th>ID</th>
            <th>Usuario</th>
            <th>Título</th>
            <th>URL Archivo</th>
            <th>Estado</th>
            <th>Acciones</th>
        `;
        
        // Rows
        data.forEach(row => {
            const tr = document.createElement('tr');
            const urlDisplay = row.ruta_archivo ? 
                '<span class="url-preview" title="' + escapeHtml(row.ruta_archivo) + '">Ver archivo</span>' : 
                'N/A';
            
            tr.innerHTML = `
                <td>${row.ID_item}</td>
                <td>${escapeHtml(row.usuario)}</td>
                <td>${escapeHtml(row.titulo)}</td>
                <td>${urlDisplay}</td>
                <td><span class="badge ${row.visibilidad ? 'badge-active' : 'badge-inactive'}">${row.visibilidad ? 'Público' : 'Pendiente'}</span></td>
                <td>
                    <button class="btn btn-success btn-sm" onclick="updateEstado('${type}', ${row.ID_item}, 'true')">Aprobar</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteItem('${type}', ${row.ID_item})">Rechazar</button>
                    ${row.ruta_archivo ? '<button class="btn btn-info btn-sm" onclick="viewFile(\'' + escapeHtml(row.ruta_archivo) + '\')">Ver</button>' : ''}
                </td>
            `;
            tbody.appendChild(tr);
        });
        
    } else if(type === 'hilos') {
        // Headers for hilos: ID USUARIO TITULO BREVE_DESCRIPCION ESTADO ACCIONES
        thead.innerHTML = `
            <th>ID</th>
            <th>Usuario</th>
            <th>Título</th>
            <th>Breve Descripción</th>
            <th>Estado</th>
            <th>Acciones</th>
        `;
        
        // Rows
        data.forEach(row => {
            const tr = document.createElement('tr');
            
            // Extract brief description from contenido_hilo (JSON or text)
            let breveDesc = 'Sin descripción';
            if(row.contenido_hilo) {
                try {
                    const contenido = typeof row.contenido_hilo === 'string' ? 
                        JSON.parse(row.contenido_hilo) : row.contenido_hilo;
                    breveDesc = contenido.descripcion || contenido.text || 
                        JSON.stringify(contenido).substring(0, 100);
                } catch(e) {
                    breveDesc = String(row.contenido_hilo).substring(0, 100);
                }
                // Truncate to 100 chars
                if(breveDesc.length > 100) {
                    breveDesc = breveDesc.substring(0, 100) + '...';
                }
            }
            
            tr.innerHTML = `
                <td>${row.ID_hilo}</td>
                <td>${escapeHtml(row.autor_hilo)}</td>
                <td>${escapeHtml(row.titulo_hilo)}</td>
                <td>${escapeHtml(breveDesc)}</td>
                <td><span class="badge ${row.estado_hilo ? 'badge-active' : 'badge-inactive'}">${row.estado_hilo ? 'Aprobado' : 'Pendiente'}</span></td>
                <td>
                    <button class="btn btn-success btn-sm" onclick="updateEstado('${type}', ${row.ID_hilo}, 'true')">Aprobar</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteItem('${type}', ${row.ID_hilo})">Rechazar</button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }
}

// Update estado
function updateEstado(type, id, estado) {
    if(!confirm('¿Estás seguro de cambiar el estado?')) return;
    
    const formData = new FormData();
    formData.append('id', id);
    formData.append('estado', estado);
    
    const params = new URLSearchParams({
        action: 'update_estado',
        type: type
    });
    
    fetch('api_admin.php?' + params.toString(), {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert('Estado actualizado correctamente');
            loadTableData(currentType);
            loadCounts();
        } else {
            alert('Error al actualizar: ' + (data.error || 'Desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
    });
}

// Delete item or hilo
function deleteItem(type, id) {
    if(!confirm('¿Estás seguro de eliminar este elemento? Esta acción no se puede deshacer.')) return;
    
    const formData = new FormData();
    formData.append('id', id);
    
    const params = new URLSearchParams({
        action: 'delete',
        type: type
    });
    
    fetch('api_admin.php?' + params.toString(), {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert('Eliminado correctamente');
            loadTableData(currentType);
            loadCounts();
        } else {
            alert('Error al eliminar: ' + (data.error || 'Desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
    });
}

// View file (generate signed URL)
function viewFile(gcsPath) {
    alert('Ruta del archivo: ' + gcsPath);
}

// Load chart data
function loadChartData() {
    console.log('Loading chart data...');
    fetch('api_estadisticas.php')
        .then(response => response.json())
        .then(data => {
            console.log('Chart data:', data);
            if(data.success) {
                renderChart(data.stats);
            }
        })
        .catch(error => console.error('Error loading chart data:', error));
}

// Render Chart.js chart
function renderChart(stats) {
    const ctx = document.getElementById('statsChart');
    if(!ctx) {
        console.error('Chart canvas not found');
        return;
    }
    
    // Destroy previous chart if exists
    if(chartInstance) {
        chartInstance.destroy();
    }
    
    chartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Usuarios', 'Items Pendientes', 'Items Públicos', 'Hilos Pendientes'],
            datasets: [{
                data: [
                    stats.usuarios || 0,
                    stats.items_pendientes || 0,
                    stats.items_publicos || 0,
                    stats.hilos_pendientes || 0
                ],
                backgroundColor: [
                    '#4CAF50',
                    '#FF9800',
                    '#2196F3',
                    '#F44336'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: {
                            size: 12,
                            family: 'Akatab'
                        }
                    }
                },
                title: {
                    display: true,
                    text: 'Estadísticas del Sistema',
                    font: {
                        size: 16,
                        family: 'Akatab',
                        weight: 'bold'
                    },
                    padding: 20
                }
            }
        }
    });
}

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
