document.addEventListener('DOMContentLoaded', () => {
    const postList = document.querySelector('.post_list');
    const searchForm = document.querySelector('.search_form');
    const searchInput = document.getElementById('search');

    let offset = 0;
    const limit = 10;
    let isLoading = false;
    let hasMore = true;
    let currentSearchQuery = '';


    // Function to fetch threads from the API
    const fetchThreads = async (searchQuery = '', newSearch = false) => {
        if (isLoading || (!hasMore && !newSearch)) return;
        isLoading = true;

        if (newSearch) {
            offset = 0;
            hasMore = true;
            postList.innerHTML = ''; // Clear current posts
        }

        try {
            const response = await fetch(`api_hilos.php?limit=${limit}&offset=${offset}&search=${encodeURIComponent(searchQuery)}`);
            if (!response.ok) {
                throw new Error('Network response was not ok.');
            }
            const threads = await response.json();
            console.log('API Response:', threads);

            if (threads.length > 0) {
                console.log('Threads data:', threads);
                renderThreads(threads);
                offset += limit;
            } else {
                hasMore = false;
                if (newSearch || postList.innerHTML === '') { // Only show 'no results' if it's a new search or initial load
                    postList.innerHTML = '<p class="no-results">No se encontraron hilos.</p>';
                } else if (offset > 0) {
                     postList.insertAdjacentHTML('beforeend', '<p class="no-results">¡Oops! No hay más hilos.</p>');
                }
            }
        } catch (error) {
            console.error('Error fetching threads:', error);
            postList.innerHTML = '<p class="error">Error al cargar los hilos. Inténtalo de nuevo más tarde.</p>';
        } finally {
            isLoading = false;
        }
    };

    const renderThreads = (threads) => {
        threads.forEach(thread => {
            const postElement = document.createElement('div');
            postElement.classList.add('post');
            postElement.dataset.id = thread.id;

            // Limitar la descripción a un fragmento
            const descriptionSnippet = thread.descripcion.length > 150 
                ? thread.descripcion.substring(0, 150) + '...'
                : thread.descripcion;

            const profileImageHtml = thread.autor_perfil
                ? `<img src="${thread.autor_perfil}" alt="Perfil de ${thread.autor}" class="post_profile_image">`
                : `<span class="material-symbols-outlined">account_circle</span>`;

            const likeButtonClass = thread.user_has_liked ? 'liked' : '';

            postElement.innerHTML = `
                <div class="post_header">
                    <div class="post_author_info">
                        <div class="post_profile">
                            ${profileImageHtml}
                        </div>
                        <div class="post_user_details">
                            <span class="post_username">${thread.autor}</span>
                            <span class="post_timestamp">${thread.fecha}</span>
                        </div>
                    </div>
                </div>
                <div class="post_content">
                    <h3 class="post_title"><a href="hilo.php?id=${thread.id}">${thread.titulo}</a></h3>
                    <p class="post_text">${descriptionSnippet}</p>
                </div>
                <div class="post_footer">
                    <div class="post_interactions">
                        <button class="interaction_button like_button ${likeButtonClass}" data-id="${thread.id}" title="Me gusta">
                            <span class="material-symbols-outlined">thumb_up</span>
                            <span class="like_count">${thread.likes}</span>
                        </button>
                        <a href="hilo.php?id=${thread.id}" class="interaction_button comment_button" title="Comentarios">
                            <span class="material-symbols-outlined">comment</span>
                            <span class="comment_count">${thread.comentarios}</span>
                        </a>
                    </div>
                </div>
            `;
            postList.appendChild(postElement);
        });
    };

    // Search functionality
    searchForm.addEventListener('submit', (e) => {
        e.preventDefault();
        currentSearchQuery = searchInput.value.trim();
        fetchThreads(currentSearchQuery, true);
    });

    // Infinite Scroll
    const observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting && hasMore && !isLoading) {
            fetchThreads(currentSearchQuery);
        }
    }, {
        rootMargin: '0px 0px 400px 0px' //- Load more when 400px from bottom
    });

    // Function to start observing the last post
    const startObserver = () => {
        const lastPost = document.querySelector('.post:last-child');
        if (lastPost) {
            observer.observe(lastPost);
        }
    };
    
    // Re-observe after each render
    const mutationObserver = new MutationObserver(startObserver);
    mutationObserver.observe(postList, { childList: true });


    // Like button functionality (delegation)
    postList.addEventListener('click', async (e) => {
        const likeButton = e.target.closest('.like_button');
        if (likeButton) {
            if(isLoading) return; // Prevent multiple clicks while loading
            isLoading = true;

            const threadId = likeButton.dataset.id;
            const likeCountSpan = likeButton.querySelector('.like_count');
            const csrfToken = document.getElementById('csrf_token').value;

            const formData = new FormData();
            formData.append('action', 'like_hilo');
            formData.append('data[id]', threadId);
            formData.append('csrf_token', csrfToken);
            
            try {
                const response = await fetch('api_acciones_hilo.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) throw new Error('Like request failed');
                
                const result = await response.json();

                if (result.success) {
                    likeCountSpan.textContent = result.new_likes;
                    likeButton.classList.toggle('liked', result.liked);
                } else {
                    console.warn(result.message || 'Could not update like count.');
                }

            } catch (error) {
                console.error('Error liking thread:', error);
                alert('Error al dar like. Inténtalo de nuevo.');
            } finally {
                isLoading = false;
            }
        }
    });

    // Initial fetch
    fetchThreads();

    // Profile Preview Modal
    const profilePreviewModal = document.getElementById('profilePreview');
    if (profilePreviewModal) {
        const openProfilePreviewButton = document.querySelector('.open_profile_preview');
        const closeProfilePreviewButton = profilePreviewModal.querySelector('.ppm_close');

        const showModal = () => {
            profilePreviewModal.style.display = 'block';
            setTimeout(() => {
                profilePreviewModal.classList.add('visible');
            }, 10);
        };

        const hideModal = () => {
            profilePreviewModal.classList.remove('visible');
            setTimeout(() => {
                profilePreviewModal.style.display = 'none';
            }, 300);
        };

        if (openProfilePreviewButton) {
            openProfilePreviewButton.addEventListener('click', async () => {
                try {
                    const response = await fetch('../../profile/api_profile.php');
                    if (!response.ok) {
                        throw new Error('Could not fetch profile data.');
                    }
                    const data = await response.json();

                    const nameEl = profilePreviewModal.querySelector('.ppm_name');
                    const bioEl = profilePreviewModal.querySelector('.ppm_bio');
                    const emailEl = profilePreviewModal.querySelector('.ppm_email');
                    const phoneEl = profilePreviewModal.querySelector('.ppm_phone');
                    const imgEl = profilePreviewModal.querySelector('.ppm_img');
                    const avatarPlaceholder = profilePreviewModal.querySelector('.ppm_avatar_placeholder');

                    if (nameEl) nameEl.textContent = data.name || 'N/A';
                    if (bioEl) bioEl.textContent = data.bio || 'No bio provided.';
                    if (emailEl) emailEl.textContent = data.email || '';
                    if (phoneEl) phoneEl.textContent = data.phone || '';
                    
                    if (data.img) {
                        if (imgEl) {
                            imgEl.src = data.img;
                            imgEl.style.display = 'block';
                        }
                        if (avatarPlaceholder) avatarPlaceholder.style.display = 'none';
                    } else {
                        if (imgEl) imgEl.style.display = 'none';
                        if (avatarPlaceholder) avatarPlaceholder.style.display = 'block';
                    }

                    showModal();

                } catch (error) {
                    console.error('Error fetching profile for preview:', error);
                    alert('Error al cargar el perfil.');
                }
            });
        }

        if(closeProfilePreviewButton) {
            closeProfilePreviewButton.addEventListener('click', hideModal);
        }

        profilePreviewModal.addEventListener('click', (e) => {
            if (e.target === profilePreviewModal) {
                hideModal();
            }
        });
    }
});