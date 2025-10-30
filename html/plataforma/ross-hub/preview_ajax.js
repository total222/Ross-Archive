// Preview page: Load and display item details
(function(){
    // Get item ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const itemId = urlParams.get('id');

    if(!itemId){
        showError('No se especificó un ID de archivo válido.');
        return;
    }

    // Load item data
    loadItemData(itemId);

    async function loadItemData(id){
        try {
            const response = await fetch(`api_get_item.php?id=${id}`, {
                credentials: 'same-origin'
            });

            if(!response.ok){
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            if(data.success && data.item){
                displayItem(data.item);
            } else {
                showError(data.error || 'No se encontró el archivo.');
            }

        } catch(error){
            console.error('Error loading item:', error);
            showError('Error al cargar el archivo. Por favor, intenta de nuevo.');
        }
    }

    function displayItem(item){
        // Hide loading indicator
        document.getElementById('loadingIndicator').style.display = 'none';

        // Show preview container
        document.getElementById('previewContainer').style.display = 'block';

        // Update metadata
        document.getElementById('itemTitle').textContent = item.titulo || 'Sin título';
        document.getElementById('itemAuthor').textContent = item.autor || 'Desconocido';
        document.getElementById('itemDate').textContent = formatDate(item.fecha);
        document.getElementById('itemCategory').textContent = item.categoria && item.categoria !== 'N/A' ? item.categoria : 'Sin categoría';
        document.getElementById('itemLanguage').textContent = item.idioma && item.idioma !== 'N/A' ? item.idioma : 'Sin especificar';
        document.getElementById('itemFormat').textContent = formatType(item.formato);
        document.getElementById('itemLicense').textContent = item.licencia || 'Sin licencia';
        document.getElementById('itemDescription').textContent = item.descripcion || 'Sin descripción';

        // Update download button
        const downloadBtn = document.getElementById('downloadButton');
        if(item.download_url && item.download_url !== null && item.download_url !== ''){
            downloadBtn.href = item.download_url;
            downloadBtn.download = item.titulo || 'archivo';
            downloadBtn.style.display = 'flex';
        } else {
            // If no URL, show error message but keep button visible
            downloadBtn.href = '#';
            downloadBtn.style.opacity = '0.5';
            downloadBtn.style.cursor = 'not-allowed';
            downloadBtn.onclick = (e) => {
                e.preventDefault();
                alert('No se pudo generar el enlace de descarga. Por favor, intenta más tarde.');
            };
        }

        // Render preview based on format
        renderPreview(item);
    }

    function renderPreview(item){
        const previewContainer = document.getElementById('elementPreview');

        switch(item.formato){
            case 'document':
                renderGenericPreview(previewContainer, item);
                break;
            case 'image':
                renderImagePreview(previewContainer, item);
                break;
            case 'video':
                renderVideoPreview(previewContainer, item);
                break;
            case 'audio':
                renderAudioPreview(previewContainer, item);
                break;
            default:
                renderGenericPreview(previewContainer, item);
        }
    }



    function renderImagePreview(container, item){
                container.innerHTML = `
            <div class="image_viewer_container">
                <img src="${escapeHtml(item.download_url)}" alt="${escapeHtml(item.titulo)}" class="preview_image">
            </div>
        `;
    }
    function renderGenericPreview(container, item){
        container.style.display = 'none';
    }

    function renderVideoPreview(container, item){
        container.innerHTML = `
            <div class="video_viewer_container">
                <video class="plyr_video" controls>
                    <source src="${escapeHtml(item.download_url)}" type="video/mp4">
                    Tu navegador no soporta la reproducción de video.
                </video>
            </div>
        `;

        // Initialize Plyr for video
        const videoElement = container.querySelector('.plyr_video');
        if(window.Plyr && videoElement){
            new Plyr(videoElement);
        }
    }

    function renderAudioPreview(container, item){
        container.innerHTML = `
            <div class="audio_viewer_container">
                <div class="audio_artwork">
                    <span class="material-symbols-outlined">music_note</span>
                </div>
                <audio class="plyr_audio" controls>
                    <source src="${escapeHtml(item.download_url)}" type="audio/mpeg">
                    Tu navegador no soporta la reproducción de audio.
                </audio>
            </div>
        `;

        // Initialize Plyr for audio
        const audioElement = container.querySelector('.plyr_audio');
        if(window.Plyr && audioElement){
            new Plyr(audioElement);
        }
    }




    function showError(message){
        document.getElementById('loadingIndicator').style.display = 'none';
        document.getElementById('errorContainer').style.display = 'flex';
        document.getElementById('errorMessage').textContent = message;
    }

    function formatDate(dateString){
        if(!dateString) return 'Sin fecha';
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });
    }

    function formatType(type){
        const types = {
            'document': 'Documento',
            'image': 'Imagen',
            'video': 'Video',
            'audio': 'Audio'
        };
        return types[type] || type;
    }

    function escapeHtml(text){
        if(!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();
