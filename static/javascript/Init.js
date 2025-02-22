function hideElementsWithMatchingName(obj) {
    for (var key in obj) {
        if (obj.hasOwnProperty(key)) {
            var element = document.querySelector(`[name="${key}"]`);
            if (element && !element.closest('.immunity-table')) { // Check if the element is inside an immunity-table
                element.style.display = 'none';
                var container = document.getElementById(`${curstate}_row_${key}`);

                if (container) {
                    container.style.display = 'none';
                }
            }
            if (typeof obj[key] === 'object' && Object.keys(obj[key]).length > 0) {
                hideElementsWithMatchingName(obj[key]);
            }
        }
    }
    for (var key in afields['fields' + curstate]['tree']) {
        var checkbox = document.querySelector(`[name="${key}"]`);
        if (checkbox && !checkbox.closest('.immunity-table')) { // Check if the element is inside an immunity-table
            checkbox.style.display = 'block';

            var container = document.getElementById(`${curstate}_row_${key}`);
            if (container) {
                container.style.display = 'table-row';
            }
        }
    }
}


function hideTablesExcept(dontTouch, flag = true) {
    if (flag){
        var elements = document.querySelectorAll("[id$='_table']");

        for (var i = 0; i < elements.length; i++) {
            if (!(elements[i].id == dontTouch+'_table') || elements[i].classList.contains('immunity-table')) {
                elements[i].style.display = "none";
            }
            else {
                elements[i].style.display = "block";
            }
        }
    }
}

hideTablesExcept(curstate, true);
hideElementsWithMatchingName(afields['fields' + curstate]['tree']);
console.log('curstate: ' + curstate);