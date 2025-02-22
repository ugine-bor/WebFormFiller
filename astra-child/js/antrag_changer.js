function fetchAntragData(antrag, rootdir) {
    return fetch(`${rootdir}/data/static/json/after/${antrag}-after.json`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            return data;
        })
        .catch(error => {
            console.error('There was a problem with the fetch operation:', error);
        });
}

function initializeNewAntrag(antragData) {
    const formContainer = document.getElementById('form-container');
    formContainer.innerHTML = '';

    antragData.forEach(field => {
        const fieldElement = document.createElement('div');
        fieldElement.className = 'form-field';
        fieldElement.innerHTML = `<label>${field.label}</label><input type="${field.type}" name="${field.name}" value="${field.value}">`;
        formContainer.appendChild(fieldElement);
    });
}

document.getElementById('antragSwitcher').addEventListener('change', function () {
    const selectedAntrag = this.value;

    fetchAntragData(selectedAntrag).then(data => {
        initializeNewAntrag(data);
    });
});
