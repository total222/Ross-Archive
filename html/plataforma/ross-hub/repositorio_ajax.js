// Profile preview logic: fetch user data and show modal
(function(){
    const openBtns = document.querySelectorAll('.open_profile_preview');
    const modal = document.getElementById('profilePreview');

    // Check if modal exists on this page
    if(!modal) return;

    const ppmImg = modal.querySelector('.ppm_img');
    const ppmPlaceholder = modal.querySelector('.ppm_avatar_placeholder');
    const ppmName = modal.querySelector('.ppm_name');
    const ppmBio = modal.querySelector('.ppm_bio');
    const ppmEmail = modal.querySelector('.ppm_email');
    const ppmPhone = modal.querySelector('.ppm_phone');
    const closeBtn = modal.querySelector('.ppm_close');

    function openModal(){ modal.setAttribute('aria-hidden','false'); }
    function closeModal(){ modal.setAttribute('aria-hidden','true'); }

    async function fetchProfile(){
        try{
            console.log('Fetching profile data...');
            const res = await fetch('../../profile/api_profile.php', { credentials: 'same-origin' });
            console.log('Response status:', res.status);
            if(!res.ok) throw new Error(`HTTP ${res.status}: ${res.statusText}`);
            const data = await res.json();
            console.log('Profile data received:', data);
            // expected { name, email, phone, bio, img }
            ppmName.textContent = data.name || 'No hay información todavia.';
            ppmBio.textContent = data.bio || 'No hay información todavia.';
            ppmEmail.textContent = data.email || 'No hay información todavia.';
            ppmPhone.textContent = data.phone || 'No hay información todavia.';
            if(data.img && data.img.toString().trim() !== ''){
                // modal image
                ppmImg.src = data.img; ppmImg.style.display='block'; ppmPlaceholder.style.display='none';
                // header image(s) - there may be one in the header
                const headerImgs = document.querySelectorAll('.home_header .profile_image, .profile_header .profile_image');
                headerImgs.forEach(img => { try{ img.src = data.img; img.style.display = ''; }catch(e){} });
                // also any other profile images on the page
                document.querySelectorAll('img.profile_image').forEach(img => { if(!img.closest('#profilePreview')){ try{ img.src = data.img; img.style.display=''; }catch(e){} } });
                // hide any placeholders near header (if present)
                document.querySelectorAll('.profile_image_placeholder').forEach(ph => { ph.style.display = 'none'; });
            } else {
                ppmImg.style.display='none'; ppmPlaceholder.style.display='flex';
                // hide header images if no profile image
                document.querySelectorAll('.home_header .profile_image, .profile_header .profile_image, img.profile_image').forEach(img => { img.style.display = 'none'; });
                document.querySelectorAll('.profile_image_placeholder').forEach(ph => { ph.style.display = 'flex'; });
            }
        }catch(err){
            console.error('Error fetching profile:', err);
            ppmName.textContent = 'Error al cargar perfil';
            ppmBio.textContent = 'Error al cargar perfil';
            ppmEmail.textContent = 'Error al cargar perfil';
            ppmPhone.textContent = 'Error al cargar perfil';
            ppmImg.style.display='none'; ppmPlaceholder.style.display='flex';
        }
    }

    openBtns.forEach(b=> b.addEventListener('click', (e)=>{ fetchProfile().then(openModal); }));
    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (e)=>{ if(e.target === modal) closeModal(); });
    document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape') closeModal(); });
})();

// Upload modal logic: show/hide and license validation
(function(){
    const openBtns = document.querySelectorAll('.open_upload_modal');
    const modal = document.getElementById('uploadModal');

    // Check if modal exists on this page
    if(!modal) return;

    const closeBtn = modal.querySelector('.upload_close');
    const cancelBtn = document.getElementById('cancelUpload');
    const licenciaSelect = document.getElementById('upload_licencia');
    const permisoContainer = document.getElementById('permiso_container');
    const permisoInput = document.getElementById('upload_permiso');
    const formatoSelect = document.getElementById('upload_formato');
    const categoriaContainer = document.getElementById('categoria_container');
    const categoriaInput = document.getElementById('upload_categoria');
    const idiomaContainer = document.getElementById('idioma_container');
    const idiomaInput = document.getElementById('upload_idioma');

    function openModal(){
        modal.setAttribute('aria-hidden','false');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(){
        modal.setAttribute('aria-hidden','true');
        document.body.style.overflow = '';
        // Reset form
        document.getElementById('uploadForm').reset();
        permisoContainer.style.display = 'none';
        if(permisoInput) permisoInput.removeAttribute('required');
        // Reset conditional fields
        if(categoriaContainer) categoriaContainer.style.display = 'flex';
        if(idiomaContainer) idiomaContainer.style.display = 'flex';
        if(categoriaInput) categoriaInput.setAttribute('required', 'required');
        if(idiomaInput) idiomaInput.setAttribute('required', 'required');
    }

    // Show/hide fields based on format selection
    if(formatoSelect){
        formatoSelect.addEventListener('change', function(){
            const formato = this.value.trim();

            if(formato === 'image'){
                // Hide categoria and idioma for images
                if(categoriaContainer) categoriaContainer.style.display = 'none';
                if(idiomaContainer) idiomaContainer.style.display = 'none';
                if(categoriaInput) categoriaInput.removeAttribute('required');
                if(idiomaInput) idiomaInput.removeAttribute('required');
            } else if(formato === 'audio'){
                // Hide categoria for audio, show idioma
                if(categoriaContainer) categoriaContainer.style.display = 'none';
                if(idiomaContainer) idiomaContainer.style.display = 'flex';
                if(categoriaInput) categoriaInput.removeAttribute('required');
                if(idiomaInput) idiomaInput.setAttribute('required', 'required');
            } else if(formato === 'document' || formato === 'video'){
                // Show all fields for documents and videos
                if(categoriaContainer) categoriaContainer.style.display = 'flex';
                if(idiomaContainer) idiomaContainer.style.display = 'flex';
                if(categoriaInput) categoriaInput.setAttribute('required', 'required');
                if(idiomaInput) idiomaInput.setAttribute('required', 'required');
            } else {
                // Default: show all fields
                if(categoriaContainer) categoriaContainer.style.display = 'flex';
                if(idiomaContainer) idiomaContainer.style.display = 'flex';
                if(categoriaInput) categoriaInput.setAttribute('required', 'required');
                if(idiomaInput) idiomaInput.setAttribute('required', 'required');
            }
        });
    }

    // Show/hide permission file input based on license selection
    licenciaSelect.addEventListener('change', function(){
        const value = this.value.trim();
        if(value === '' || value === null){
            // No license selected, show permission input and make it required
            permisoContainer.style.display = 'block';
            if(permisoInput) permisoInput.setAttribute('required', 'required');
        } else {
            // License selected, hide permission input and make it not required
            permisoContainer.style.display = 'none';
            if(permisoInput) permisoInput.removeAttribute('required');
        }
    });

    // Open modal on button clicks
    openBtns.forEach(btn => btn.addEventListener('click', openModal));

    // Close modal on close button
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);

    // Close modal on backdrop click
    modal.addEventListener('click', (e)=>{ if(e.target === modal) closeModal(); });

    // Close modal on Escape key
    document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') closeModal(); });

    // File input: show selected filename
    const fileInputs = document.querySelectorAll('.form_file');
    fileInputs.forEach(input => {
        input.addEventListener('change', function(e){
            const wrapper = this.closest('.file_input_wrapper');
            const fileNameSpan = wrapper.querySelector('.file_name');
            if(this.files && this.files.length > 0){
                const fileName = this.files[0].name;
                const fileSize = (this.files[0].size / 1024).toFixed(2); // KB
                fileNameSpan.textContent = `${fileName} (${fileSize} KB)`;
            } else {
                fileNameSpan.textContent = '';
            }
        });
    });
})();

// Repository items loading and rendering
(function(){
    const itemContainer = document.querySelector('.item_container');
    if(!itemContainer) return;

    // Load items on page load
    loadItems();

    // Search form submission
    const searchForm = document.querySelector('.upload_form');
    if(searchForm){
        searchForm.addEventListener('submit', function(e){
            e.preventDefault();
            const searchValue = document.getElementById('search')?.value || '';
            loadItems({ search: searchValue });
        });
    }

    // Filter form: auto-submit on change (no submit button needed)
    const filterForm = document.querySelector('.filter_form');
    if(filterForm){
        // Hide submit button if exists
        const submitBtn = filterForm.querySelector('.apply_filters_button');
        if(submitBtn) submitBtn.style.display = 'none';

        // Function to collect and apply filters
        const applyFilters = function(){
            const formData = new FormData(filterForm);
            const filters = {
                autor: formData.get('file_name'),
                categoria: formData.getAll('materia').join(','),
                formato: formData.get('file_type'),
                items_per_page: formData.get('items_per_page')
            };

            console.log('Applying filters:', filters);
            loadItems(filters);
        };

        // Listen to all filter inputs
        filterForm.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('change', applyFilters);
        });

        // Keep submit handler for manual submission if needed
        filterForm.addEventListener('submit', function(e){
            e.preventDefault();
            applyFilters();
        });
    }

    async function loadItems(filters = {}){
        try {
            // Build query string
            const params = new URLSearchParams();
            if(filters.search) params.append('search', filters.search);
            if(filters.categoria) params.append('categoria', filters.categoria);
            if(filters.formato) params.append('formato', filters.formato);
            if(filters.autor) params.append('autor', filters.autor);

            const url = 'api_repositorio.php' + (params.toString() ? '?' + params.toString() : '');
            console.log('Loading items from:', url);

            const response = await fetch(url, {
                credentials: 'same-origin'
            });

            console.log('Response status:', response.status);

            if(!response.ok){
                const errorText = await response.text();
                console.error('Response error:', errorText);
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const responseText = await response.text();
            console.log('Response text:', responseText.substring(0, 200));

            let data;
            try {
                data = JSON.parse(responseText);
            } catch(e) {
                console.error('JSON parse error:', e);
                console.error('Response was:', responseText);
                throw new Error('Respuesta del servidor no es JSON válido');
            }

            console.log('Parsed data:', data);

            if(data.success && data.items){
                console.log('Items count:', data.items.length);
                renderItems(data.items);
            } else if(data.error) {
                console.error('API error:', data.error);
                showError(data.error);
            } else {
                console.log('No items or success=false, showing no items message');
                showNoItems();
            }

        } catch(error){
            console.error('Error loading items:', error);
            showError('Error al cargar los archivos: ' + error.message);
        }
    }

    function renderItems(items){
        // Clear container
        itemContainer.innerHTML = '';

        if(!items || items.length === 0){
            showNoItems();
            return;
        }

        // Group items by format
        const documentItems = items.filter(item => item.formato === 'document' || !item.formato);
        const imageItems = items.filter(item => item.formato === 'image');
        const audioItems = items.filter(item => item.formato === 'audio');
        const videoItems = items.filter(item => item.formato === 'video');

        // Render documents and videos
        [...documentItems, ...videoItems].forEach(item => {
            itemContainer.appendChild(createDocumentCard(item));
        });

        // Render images as gallery
        if(imageItems.length > 0){
            const gallery = createImageGallery(imageItems);
            itemContainer.appendChild(gallery);
        }

        // Render audio items
        audioItems.forEach(item => {
            itemContainer.appendChild(createAudioCard(item));
        });
    }

    function createDocumentCard(item){
        const card = document.createElement('div');
        card.className = 'item_card document_card';
        card.dataset.itemId = item.ID_item;

        // Determine icon based on format
        let iconHtml = '';
        if(item.formato === 'document'){
            iconHtml = '<span class="material-symbols-outlined preview_icon">description</span>';
        } else if(item.formato === 'video'){
            iconHtml = '<span class="material-symbols-outlined preview_icon">movie</span>';
        }

        card.innerHTML = `
            <div class="document_info">
                <h3 class="item_title">${escapeHtml(item.titulo)}</h3>
                <p class="item_author">Autor: ${escapeHtml(item.autor)}</p>
                <p class="item_date">Fecha: ${formatDate(item.fecha)}</p>
                ${item.categoria && item.categoria !== 'N/A' ? `<p class="item_category">${escapeHtml(item.categoria)}</p>` : ''}
                ${item.idioma && item.idioma !== 'N/A' ? `<p class="item_language">${escapeHtml(item.idioma)}</p>` : ''}
            </div>
            <div class="document_preview">
                ${iconHtml}
            </div>
        `;

        // Click to go to preview page
        card.addEventListener('click', () => {
            window.location.href = `preview.php?id=${item.ID_item}`;
        });

        return card;
    }

    function createImageGallery(items){
        const gallery = document.createElement('div');
        gallery.className = 'image_gallery';

        items.forEach(item => {
            const imgCard = document.createElement('div');
            imgCard.className = 'image_card';
            imgCard.dataset.itemId = item.ID_item;

            imgCard.innerHTML = `
                <img src="${escapeHtml(item.download_url)}" alt="${escapeHtml(item.titulo)}" class="gallery_image">
                <div class="image_overlay">
                    <h3 class="overlay_title">${escapeHtml(item.titulo)}</h3>
                    <p class="overlay_author">${escapeHtml(item.autor)}</p>
                    <p class="overlay_date">${formatDate(item.fecha)}</p>
                </div>
            `;

            imgCard.addEventListener('click', () => {
                window.location.href = `preview.php?id=${item.ID_item}`;
            });

            gallery.appendChild(imgCard);
        });

        return gallery;
    }

    function createAudioCard(item){
        const card = document.createElement('div');
        card.className = 'item_card audio_card';
        card.dataset.itemId = item.ID_item;

        card.innerHTML = `
            <div class="audio_player_wrapper">
                <audio class="plyr_audio" controls>
                    <source src="${escapeHtml(item.download_url)}" type="audio/mpeg">
                    Tu navegador no soporta el elemento de audio.
                </audio>
            </div>
            <div class="audio_info">
                <h3 class="item_title">${escapeHtml(item.titulo)}</h3>
                <p class="item_author">${escapeHtml(item.autor)}</p>
                ${item.idioma && item.idioma !== 'N/A' ? `<p class="item_language">${escapeHtml(item.idioma)}</p>` : ''}
            </div>
        `;

        // Initialize Plyr for this audio element
        const audioElement = card.querySelector('.plyr_audio');
        if(window.Plyr && audioElement){
            new Plyr(audioElement, {
                controls: ['play', 'progress', 'current-time', 'mute', 'volume']
            });
        }

        // Click on info to go to preview (not on player)
        card.querySelector('.audio_info').addEventListener('click', () => {
            window.location.href = `preview.php?id=${item.ID_item}`;
        });

        return card;
    }


    function showNoItems(){
        itemContainer.innerHTML = `
            <div class="no_items_message">
                <span class="material-symbols-outlined no_items_icon">folder_off</span>
                <h2>No hay publicaciones</h2>
                <p>No se encontraron archivos en el repositorio.</p>
            </div>
        `;
    }

    function showError(message){
        itemContainer.innerHTML = `
            <div class="error_message">
                <span class="material-symbols-outlined error_icon">error</span>
                <p>${escapeHtml(message)}</p>
            </div>
        `;
    }

    function formatDate(dateString){
        if(!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });
    }

    function escapeHtml(text){
        if(!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();

// Filter collapse logic
(function() {
    const filters = document.querySelector('.filters');
    const filterTitle = document.querySelector('.filter_title');

    if (filters && filterTitle) {
        filterTitle.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                filters.classList.toggle('collapsed');
            }
        });
    }
})();
