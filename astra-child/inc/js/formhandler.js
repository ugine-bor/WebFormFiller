document.getElementById('myForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'my_form_handler');
    formData.append('security', my_ajax.nonce);

    fetch(my_ajax.ajax_url, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Успешно отправлено!');
        } else {
            alert('Ошибка: ' + data.data);
        }
    })
    .catch(error => console.error('Error:', error));
});