document.addEventListener('DOMContentLoaded', () => {
    const quill = new Quill('#editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote', 'code-block'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'indent': '-1'}, { 'indent': '+1' }],
                ['link', 'image', 'video'],
                ['clean']
            ]
        }
    });

    const form = document.getElementById('create_hilo_form');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const title = document.getElementById('titulo_hilo').value.trim();
        const content = quill.getContents(); // Get content as Delta object
        const csrfToken = document.getElementById('csrf_token').value;

        if (!title) {
            alert('Por favor, ingresa un título para el hilo.');
            return;
        }

        if (quill.getLength() < 2) { // Editor is empty if length is 1 (just a newline)
            alert('Por favor, escribe algo de contenido para el hilo.');
            return;
        }

        const formData = new FormData();
        formData.append('titulo', title);
        formData.append('contenido', JSON.stringify(content.ops)); // Send ops as JSON string
        formData.append('csrf_token', csrfToken);

        try {
            const response = await fetch('api_subir_hilo.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                window.location.href = `hilo.php?id=${result.hilo_id}`;
            } else {
               window.location.href = `foro.php`;
            }
        } catch (error) {
             window.location.href = `foro.php`;
        }
    });
});