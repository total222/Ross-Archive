// Profile preview logic: fetch user data and show modal
(function(){
    const openBtns = document.querySelectorAll('.open_profile_preview');
    const modal = document.getElementById('profilePreview');
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
            const res = await fetch('../profile/api_profile.php', { credentials: 'same-origin' });
            if(!res.ok) throw new Error('Network error');
            const data = await res.json();
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
            ppmName.textContent = 'No hay información todavia.';
            ppmBio.textContent = 'No hay información todavia.';
            ppmEmail.textContent = 'No hay información todavia.';
            ppmPhone.textContent = 'No hay información todavia.';
            ppmImg.style.display='none'; ppmPlaceholder.style.display='flex';
        }
    }

    openBtns.forEach(b=> b.addEventListener('click', (e)=>{ fetchProfile().then(openModal); }));
    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (e)=>{ if(e.target === modal) closeModal(); });
    document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape') closeModal(); });
})();

// Home feed logic
(function(){
    const feedSelector = document.getElementById('select_header');
    const feedContainer = document.getElementById('homeFeed');

    if(!feedSelector || !feedContainer) return;

    const renderRepoItem = (item) => {
        const card = document.createElement('a');
        card.href = `ross-hub/preview.php?id=${item.ID_item}`;
        card.className = 'feed_card repo_card';

        let previewHTML = '';

            previewHTML = `<div class="feed_card_img_placeholder"><span class="material-symbols-outlined">folder</span></div>`;
        

        card.innerHTML = `
            ${previewHTML}
            <div class="feed_card_content">
                <h3 class="feed_card_title">${item.titulo}</h3>
                <p class="feed_card_desc">${item.descripcion}</p>
                <div class="feed_card_meta">
                    <span class="feed_card_author">${item.autor}</span>
                    <span class="feed_card_date">${new Date(item.fecha).toLocaleDateString()}</span>
                </div>
            </div>
        `;
        return card;
    };

    const renderForumThread = (thread) => {
        const card = document.createElement('a');
        card.href = `ross-forum/hilo.php?id=${thread.id}`;
        card.className = 'feed_card forum_card';

        let authorHTML = '';
        if (thread.autor_perfil) {
            authorHTML = `<img src="${thread.autor_perfil}" alt="${thread.autor}" class="feed_card_author_img">`;
        }

        card.innerHTML = `
            <div class="feed_card_content">
                 <div class="feed_card_forum_author">
                    ${authorHTML}
                    <span>${thread.autor}</span>
                </div>
                <h3 class="feed_card_title">${thread.titulo}</h3>
                <p class="feed_card_desc">${thread.descripcion}</p>
                <div class="feed_card_meta">
                    <span class="feed_card_stats">
                        <span class="material-symbols-outlined">thumb_up</span> ${thread.likes}
                        <span class="material-symbols-outlined">comment</span> ${thread.comentarios}
                    </span>
                    <span class="feed_card_date">${thread.fecha}</span>
                </div>
            </div>
        `;
        return card;
    };

    const fetchFeed = async (feedType) => {
        let url = '';
        if (feedType === 'repositorio') {
            url = 'ross-hub/api_repositorio.php';
        } else if (feedType === 'foro') {
            url = 'ross-forum/api_hilos.php';
        } else {
            return;
        }

        feedContainer.innerHTML = '<div class="loader"></div>'; // Show loader

        try {
            const res = await fetch(url);
            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
            const data = await res.json();

            feedContainer.innerHTML = ''; // Clear loader

            if (feedType === 'repositorio' && data.items) {
                data.items.forEach(item => {
                    feedContainer.appendChild(renderRepoItem(item));
                });
            } else if (feedType === 'foro' && Array.isArray(data)) {
                data.forEach(thread => {
                    feedContainer.appendChild(renderForumThread(thread));
                });
            }
        } catch (error) {
            feedContainer.innerHTML = '<p class="error_msg">Error al cargar el contenido.</p>';
            console.error('Error fetching feed:', error);
        }
    };

    // Initial load
    fetchFeed('repositorio');

    // Event listener for selector
    feedSelector.addEventListener('change', (e) => {
        fetchFeed(e.target.value);
    });

})();
