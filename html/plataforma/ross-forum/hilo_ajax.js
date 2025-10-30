document.addEventListener('DOMContentLoaded', () => {
    const hiloContent = document.querySelector('.hilo_content');
    const urlParams = new URLSearchParams(window.location.search);
    const hiloId = urlParams.get('id');
    const csrfToken = document.getElementById('csrf_token').value;

    if (!hiloId) {
        hiloContent.innerHTML = '<p class="error">No se especificó un ID de hilo.</p>';
        return;
    }

    const fetchHilo = async () => {
        try {
            const response = await fetch(`api_get_hilo.php?id=${hiloId}`);
            if (!response.ok) {
                throw new Error('La respuesta de la red no fue correcta.');
            }
            const data = await response.json();
            if (data.error) {
                throw new Error(data.error);
            }
            renderHilo(data.hilo, data.comentarios);
        } catch (error) {
            console.error('Error al obtener el hilo:', error);
            hiloContent.innerHTML = `<p class="error">Error al cargar el hilo: ${error.message}</p>`;
        }
    };

    const renderHilo = (hilo, comentarios) => {
        // Limpiar el contenido existente
        hiloContent.innerHTML = '';

        // Renderizar el hilo principal
        const postContainer = document.createElement('div');
        postContainer.classList.add('post_container');

        const profileImageHtml = hilo.autor_perfil
            ? `<img src="${hilo.autor_perfil}" alt="Perfil de ${hilo.autor}" class="post_profile_image">`
            : `<span class="material-symbols-outlined">account_circle</span>`;

        const likeButtonClass = hilo.user_has_liked ? 'liked' : '';

        postContainer.innerHTML = `
            <div class="post">
                <div class="post_header">
                     <div class="post_author_info">
                        <div class="post_profile">
                            ${profileImageHtml}
                        </div>
                        <div class="post_user_details">
                            <span class="post_username">${hilo.autor}</span>
                            <span class="post_timestamp">${hilo.fecha}</span>
                        </div>
                    </div>
                </div>
                <div class="post_content">
                    <h1 class="post_title">${hilo.titulo}</h1>
                    <div id="quill-viewer"></div>
                </div>
                <div class="post_footer">
                    <div class="post_interactions">
                        <button class="interaction_button like_button ${likeButtonClass}" data-id="${hilo.id}" title="Me gusta">
                            <span class="material-symbols-outlined">thumb_up</span>
                            <span class="like_count">${hilo.likes}</span>
                        </button>
                        <div class="interaction_button comment_button" title="Comentarios">
                            <span class="material-symbols-outlined">comment</span>
                            <span class="comment_count">${comentarios.length}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
        hiloContent.appendChild(postContainer);

        // Renderizar el contenido de Quill
        const quillViewer = new Quill('#quill-viewer', {
            theme: 'snow',
            modules: {
                toolbar: false
            },
            readOnly: true
        });
        quillViewer.setContents(hilo.contenido);

        // Renderizar el formulario de comentarios
        const commentForm = document.createElement('div');
        commentForm.classList.add('comment_form_container');
        commentForm.innerHTML = `
            <textarea id="new_comment_textarea" class="comment_textarea" placeholder="Escribe un comentario..."></textarea>
            <div class="comment_form_actions">
                <button id="cancel_comment_button" class="btn btn-secondary">Cancelar</button>
                <button id="submit_comment_button" class="btn btn-primary">Enviar</button>
            </div>
        `;
        hiloContent.appendChild(commentForm);

        // Renderizar la sección de comentarios
        const commentsSection = document.createElement('div');
        commentsSection.classList.add('comments_section');
        hiloContent.appendChild(commentsSection);
        renderComments(comentarios, commentsSection);
    };

    const renderComments = (comments, container) => {
        const commentsById = {};
        comments.forEach(c => { commentsById[c.id] = c; c.replies = []; });
        comments.forEach(c => { if (c.respuesta_a) commentsById[c.respuesta_a]?.replies.push(c); });

        const renderComment = (comment) => {
            console.log("Comment fecha:", comment.fecha); // Debugging line
            const commentElement = document.createElement('div');
            commentElement.classList.add('comment');
            commentElement.dataset.id = comment.id; // Add data-id to the main comment element
            if (comment.respuesta_a) commentElement.classList.add('nested');

            const profileImage = comment.autor_perfil
                ? `<img src="${comment.autor_perfil}" alt="${comment.autor}" class="comment_profile_image">`
                : `<span class="material-symbols-outlined">account_circle</span>`;

            commentElement.innerHTML = `
                <div class="comment_header">
                    <div class="comment_profile">${profileImage}</div>
                    <div class="comment_author_info">
                        <span class="comment_username">${comment.autor}</span>
                        <span class="comment_timestamp">${comment.fecha}</span>
                        <span class="comment_time">${comment.hora}</span>
                    </div>
                </div>
                <div class="comment_body">${comment.contenido}</div>
                <div class="comment_footer">
                    <button class="interaction_button like_comment_button" data-id="${comment.id}">
                        <span class="material-symbols-outlined">thumb_up</span>
                        <span class="like_count">${comment.likes}</span>
                    </button>
                    <button class="interaction_button reply_button" data-id="${comment.id}">Responder</button>
                    ${ window.currentUser === comment.autor ?
                        `<div class="dropdown">
                            <button class="interaction_button more_button">...</button>
                            <div class="dropdown-content">
                                <button class="edit_button" data-id="${comment.id}">Editar</button>
                                <button class="delete_button" data-id="${comment.id}">Eliminar</button>
                            </div>
                        </div>` : ''
                    }
                </div>
                <div class="replies_container"></div>
            `;
            
            // Render replies
            const repliesContainer = commentElement.querySelector('.replies_container');
            comment.replies.forEach(reply => repliesContainer.appendChild(renderComment(reply)));

            return commentElement;
        };

        comments.filter(c => !c.respuesta_a).forEach(c => container.appendChild(renderComment(c)));
    };

        // Event Delegation for comments and likes

        hiloContent.addEventListener('click', async (e) => {

            const target = e.target;

            const csrfToken = document.getElementById('csrf_token').value;

    

            // --- Main Thread Like ---

            if (target.closest('.post > .post_footer .like_button')) {

                // Logic for liking the main thread will be added later

            }

    

            // --- New Comment Submission ---

            if (target.id === 'submit_comment_button') {

                const textarea = document.getElementById('new_comment_textarea');

                const content = textarea.value.trim();

                if (!content) return;

    

                const formData = new FormData();

                formData.append('action', 'add_comentario');

                formData.append('data[hilo_id]', hiloId);

                formData.append('data[contenido]', content);

                formData.append('csrf_token', csrfToken);

    

                try {

                    const response = await fetch('api_acciones_hilo.php', { method: 'POST', body: formData });

                    const result = await response.json();

                    if (result.success) {

                        fetchHilo(); // Re-fetch to show new comment

                    } else {

                        alert('Error al añadir comentario: ' + (result.message || result.error || 'Error desconocido.'));

                    }

                } catch (err) {

                    alert('Error de conexión al comentar.');

                }

            }

            

            // --- Comment Actions ---

            const commentElement = target.closest('.comment');

            if (!commentElement) return;

    

            // Toggle More Options Dropdown

            if (target.closest('.more_button')) {

                const dropdown = commentElement.querySelector('.dropdown-content');

                dropdown.classList.toggle('show');

            }

    

            // Like a comment

            if (target.closest('.like_comment_button')) {

                const commentId = commentElement.dataset.id;

                const likeCountSpan = target.closest('.like_comment_button').querySelector('.like_count');

                

                const formData = new FormData();

                formData.append('action', 'like_comentario');

                formData.append('data[id]', commentId);

                formData.append('csrf_token', csrfToken);

    

                try {

                    const response = await fetch('api_acciones_hilo.php', { method: 'POST', body: formData });

                    const result = await response.json();

                    if (result.success) {

                        likeCountSpan.textContent = result.new_likes;

                    } else {

                        alert('Error al dar like al comentario.');

                    }

                } catch (err) {

                    alert('Error de conexión.');

                }

            }

    

                    // Delete a comment

    

                    if (target.closest('.delete_button')) {

    

                        const commentId = commentElement.dataset.id;

    

                        if (confirm('¿Estás seguro de que quieres eliminar este comentario?')) {

    

                            const formData = new FormData();

    

                            formData.append('action', 'delete_comentario');

    

                            formData.append('data[id]', commentId);

    

                            formData.append('csrf_token', csrfToken);

    

            

    

                            try {

    

                                const response = await fetch('api_acciones_hilo.php', { method: 'POST', body: formData });

    

                                const result = await response.json();

    

                                if (result.success) {

    

                                    commentElement.remove();

    

                                } else {

    

                                    alert('No se pudo eliminar el comentario.');

    

                                }

    

                            } catch (err) {

    

                                alert('Error de conexión.');

    

                            }

    

                        }

    

                    }

    

            

    

                    // Reply to a comment

    

                    if (target.closest('.reply_button')) {

    

                        // Remove any existing reply forms

    

                        const existingReplyForm = commentElement.querySelector('.reply_form_container');

    

                        if (existingReplyForm) {

    

                            existingReplyForm.remove();

    

                            return; // Toggle off if already open

    

                        }

    

            

    

                        const replyForm = document.createElement('div');

    

                        replyForm.className = 'reply_form_container';

    

                        replyForm.innerHTML = `

    

                            <textarea class="comment_textarea" placeholder="Escribe tu respuesta..."></textarea>

    

                            <div class="comment_form_actions">

    

                                <button class="btn btn-secondary cancel_reply_button">Cancelar</button>

    

                                <button class="btn btn-primary submit_reply_button">Responder</button>

    

                            </div>

    

                        `;

    

                        commentElement.appendChild(replyForm);

    

                    }

    

            

    

                    // Cancel a reply

    

                    if (target.classList.contains('cancel_reply_button')) {

    

                        target.closest('.reply_form_container').remove();

    

                    }

    

            

    

                    // Submit a reply

    

                    if (target.classList.contains('submit_reply_button')) {

    

                        const parentCommentId = commentElement.dataset.id;

    

                        const textarea = target.closest('.reply_form_container').querySelector('textarea');

    

                        const content = textarea.value.trim();

    

                        if (!content) return;

    

            

    

                        const formData = new FormData();

    

                        formData.append('action', 'add_comentario');

    

                        formData.append('data[hilo_id]', hiloId);

    

                        formData.append('data[contenido]', content);

    

                        formData.append('data[respuesta_a]', parentCommentId);

    

                        formData.append('csrf_token', csrfToken);

    

            

    

                        try {

    

                            const response = await fetch('api_acciones_hilo.php', { method: 'POST', body: formData });

    

                            const result = await response.json();

    

                            if (result.success) {

    

                                fetchHilo(); // Re-fetch all to show nested comment

    

                            } else {

    

                                alert('Error al enviar la respuesta.');

    

                            }

    

                        } catch (err) {

    

                            alert('Error de conexión.');

    

                        }

    

                    }

    

            

    

                    // Edit a comment

    

                    if (target.closest('.edit_button')) {

    

                        const commentBody = commentElement.querySelector('.comment_body');

    

                        const originalContent = commentBody.textContent;

    

                        

    

                        commentBody.innerHTML = `

    

                            <div class="edit_form_container">

    

                                <textarea class="comment_textarea">${originalContent}</textarea>

    

                                <div class="comment_form_actions">

    

                                    <button class="btn btn-secondary cancel_edit_button">Cancelar</button>

    

                                    <button class="btn btn-primary save_edit_button">Guardar</button>

    

                                </div>

    

                            </div>

    

                        `;

    

                    }

    

            

    

                    // Cancel an edit

    

                    if (target.classList.contains('cancel_edit_button')) {

    

                        const commentBody = commentElement.querySelector('.comment_body');

    

                        const originalContent = commentBody.querySelector('textarea').defaultValue;

    

                        commentBody.innerHTML = originalContent;

    

                    }

    

            

    

                    // Save an edit

    

                    if (target.classList.contains('save_edit_button')) {

    

                        const commentId = commentElement.dataset.id;

    

                        const textarea = target.closest('.edit_form_container').querySelector('textarea');

    

                        const newContent = textarea.value.trim();

    

                        if (!newContent) return;

    

            

    

                        const formData = new FormData();

    

                        formData.append('action', 'edit_comentario');

    

                        formData.append('data[id]', commentId);

    

                        formData.append('data[contenido]', newContent);

    

                        formData.append('csrf_token', csrfToken);

    

            

    

                        try {

    

                            const response = await fetch('api_acciones_hilo.php', { method: 'POST', body: formData });

    

                            const result = await response.json();

    

                            if (result.success) {

    

                                commentElement.querySelector('.comment_body').innerHTML = newContent;

    

                            } else {

    

                                alert('No se pudo editar el comentario.');

    

                            }

    

                        } catch (err) {

    

                            alert('Error de conexión.');

    

                        }

    

                    }

    

                });

    fetchHilo();
});