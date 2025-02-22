
function hideBranchElements(obj) {
    for (var key in obj) {
        if (obj.hasOwnProperty(key)) {
            var element = document.querySelector(`[name="${key}"]`);
            if (element) {
                element.style.display = 'none';
            }

            var container = document.getElementById(`${curstate}_row_${key}`);
            if (container) {
                container.style.display = 'none';
            }

            if (typeof obj[key] === 'object' && Object.keys(obj[key]).length > 0) {
                hideBranchElements(obj[key]);
            }
        }
    }
}


function showBranchElements(obj, parentKey) {
    for (var key in obj) {
        if (obj.hasOwnProperty(key)) {
            var element = document.querySelector(`[name="${key}"]`);
            if (element) {
                element.style.display = 'block';

                var container = document.getElementById(`${curstate}_row_${key}`);
                if (container) {
                    container.style.display = 'table-row';
                }
                if (element.type === "checkbox") {
                    if (element.checked) {
                        showBranchElements(findElementInTree(afields['fields' + curstate]['tree'], element.name),element.name);
                    }
                } else if (!(element.value === "" || element.value === element.defaultValue)) {
                    if (element.type === 'select-one') {
                        const selectedValue = element.value;
                        const ways = choose[element.name]
                        const destination = ways[selectedValue];
                        var elem = findElementInTree(afields['fields' + curstate]['tree'], destination)

                        for (var way in ways) {
                            var dest = ways[way]
                            hideBranchElements(findElementInTree(afields['fields' + curstate]['tree'], dest), dest);
                        }
                        showBranchElements(findElementInTree(afields['fields' + curstate]['tree'], destination),destination);
                    } else {
                        showBranchElements(findElementInTree(afields['fields' + curstate]['tree'], element.name),element.name);
                    }
                }
            }
        }
    }
}

function findElementInTree(obj, name) {
    for (var key in obj) {
        if (obj.hasOwnProperty(key)) {
            if (key === name) {
                return obj[key];
            } else if (typeof obj[key] === 'object') {
                var result = findElementInTree(obj[key], name);
                if (result !== undefined) {
                    return result;
                }
            }
        }
    }
    return undefined;
}


function toggleBranchVisibility(self,name){
    var element = findElementInTree(afields['fields' + curstate]['tree'], name.toString());
    if (self.innerText === '+'){
        self.innerText = '-'
        showBranchElements(element, name);
    }
    else{
        self.innerText = '+'
        hideBranchElements(element, name.toString());
    }

}

for (var i = 0; i < inputElements.length; i++) {
    var inputElement = inputElements[i];
    inputElements[i].addEventListener("input", function (event) {
        var inputElement = event.target;

        if (inputElement.type === 'checkbox') {
            var checkbox = event.target;
            var checkboxName = checkbox.name;
            var element = findElementInTree(afields['fields' + curstate]['tree'], checkboxName);

            if (inputElement.checked) {
                showBranchElements(element, checkboxName);
            } else {
                hideBranchElements(element, checkboxName);
            }
        } else if (inputElement.type === 'select-one') {
            const selectedOption = event.target.options[event.target.selectedIndex];
            const selectedValue = selectedOption.value;
            const selectName = event.target.name;
            const selectedText = selectedOption.text;
            choose = afields['fields' + curstate]['appear']['select'];
            toshow = choose[selectName][selectedValue];

            const combinedList = [];

            for (const key in choose[selectName]) {
              if (Object.hasOwnProperty.call(choose[selectName], key)) {
                const list = choose[selectName][key];
                combinedList.push(...list);
              }
            }

            for (var i = 0; i < combinedList.length; i++){
                var element = findElementInTree(afields['fields' + curstate]['tree'], combinedList[i])
                hideBranchElements(element, combinedList[i]);
            }

            if (toshow){
                for (var i = 0; i < toshow.length; i++){
                    var element = findElementInTree(afields['fields' + curstate]['tree'], toshow[i])
                    showBranchElements(element, toshow[i]);
                }
            }
        } else {
            var field = event.target;
            var fieldname = field.name;
            var element = findElementInTree(afields['fields' + curstate]['tree'], fieldname);
            if (inputElement.value.trim() !== '') {
                showBranchElements(element, fieldname);
            } else {
                hideBranchElements(element, fieldname);
            }
        }
    });
}