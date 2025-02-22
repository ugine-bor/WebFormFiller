(function($) {
    "use strict";
    
    $(document).ready(function () {
        console.log("table_listener.js started");
        var afields = JSON.parse(wpvars.afields);
        var choose = JSON.parse(wpvars.choose);
        var inputElements = document.querySelectorAll('input, select, textarea');
		
		function validateInput(inputElement) {
			
			inputElement = inputElement.type === 'hidden' 
        ? inputElement.closest('.field-container').querySelector('input:not([type="hidden"])')
        : inputElement;
		// Проверка в зависимости от типа элемента
		switch (inputElement.type) {
			case 'text':
			case 'textarea':
			case 'email':
			case 'tel':
				// Проверка на непустое текстовое поле
				return inputElement.value.trim() !== '';

			case 'checkbox':
				// Проверка чекбокса
				return inputElement.checked;

			case 'select-one':
				// Проверка селекта (не выбран дефолтный вариант)
				return inputElement.selectedIndex > 0;

			case 'date':
			case 'datetime-local':
			case 'month':
			case 'week':
				// Проверка заполненности полей даты
				return inputElement.value !== '';

			case 'number':
			case 'range':
				// Проверка числовых полей (не пустое и не NaN)
				return inputElement.value !== '' && !isNaN(inputElement.value);

			default:
				// Универсальная проверка для остальных типов
				return inputElement.value !== '';
		}
	}
    
        function clearelement(element) {
            if (element) {
                switch (element.type) {
                    case 'text':
                    case 'email':
                    case 'password':
                    case 'date':
                        element.value = '';
                        element = document.querySelector('[name="' + element.name + '_f"]');
                        if (element) {
                            element.value = '';
                        }
                        break;
                    case 'textarea':
                    case 'number':
                        element.value = '';
                        break;
                    case 'checkbox':
                    case 'radio':
                        element.checked = false;
                        break;
                    case 'select-one':
                    case 'select-multiple':
                        element.selectedIndex = 0;
                        break;
                    default:
                        break;
                }
            }
        }
    
        function getChildElement(element) {
            while (element) {
                if (element.classList && element.classList.contains('immunity-table')) {
                    return element;
                }
                element = element.parentElement;
            }
            return null;
        }
    
        function hideBranchElements(obj, triggeredByRoot = false) {
            for (var key in obj) {
                if (obj.hasOwnProperty(key)) {
        
                    var element = document.querySelector('[name="' + key + '"]') || 
                                  document.querySelector('[name="' + key.replace("s_", "") + '"]');
        
                    if (element) {
        
                        var elements = document.querySelectorAll('[name="' + key + '"]');
        
                        elements.forEach(element => {
                            let dotCount = (element.name.match(/\./g) || []).length;
                            if (dotCount > 1 && element.type !== 'hidden' && !triggeredByRoot) {
                                clearelement(element);
                            } else if(dotCount <= 1) {
                                triggeredByRoot = true;
                            }
                        });
        
                        var table = getChildElement(element) || element.closest('.row-container');
                        if (table) table.style.display = 'none';
                        element.style.display = 'none';
                    }
        
                    hideBranchElements(obj[key], triggeredByRoot);
                }
            }
        }

        function showBranchElements(obj) {
            for (var key in obj) {
                if (obj.hasOwnProperty(key)) {
                    var element = document.querySelector('[name="' + key + '"]');
                    if (element) {
                        element.style.display = 'block';
                        var container = element.closest('.row-container') || getChildElement(element);
                        if (container) container.style.display = 'table-row';
                        if (validateInput(element) && element.type != 'select-one') {
                            showBranchElements(findElementInTree(afields, element.name));
                        } else if (!(element.value === '' || element.value === element.defaultValue)) {
                            if (element.type === 'select-one') {
                                const ways = choose[element.name];
                                const destination = ways[element.value];
                                for (var way in ways) {
                                    hideBranchElements(findElementInTree(afields, ways[way]));
                                }
                                for (var el in destination) {
                                    showBranchElements(findElementInTree(afields, destination[el]));
                                }
    
                            } else {
                                showBranchElements(findElementInTree(afields, element.name));
                            }
                        }
                    }
                }
            }
        }
    
        function findElementInTree(obj, name, returnFullPath = false) {
            if (returnFullPath) {
                let result = {};
                for (var key in obj) {
                    if (obj.hasOwnProperty(key)) {
                        if (key === name) {
                            result[key] = obj[key];
                        } else if (typeof obj[key] === 'object' && obj[key] !== null) {
                            var nestedResult = findElementInTree(obj[key], name, true);
                            if (nestedResult !== undefined) {
                                Object.assign(result, nestedResult);
                            }
                        }
                    }
                }
                return Object.keys(result).length > 0 ? result : undefined;
            } else {
                for (var key in obj) {
                    if (obj.hasOwnProperty(key)) {
                        if (key === name) {
                            return obj[key];
                        } else if (typeof obj[key] === 'object' && obj[key] !== null) {
                            var result = findElementInTree(obj[key], name, false);
                            if (result !== undefined) {
                                return result;
                            }
                        }
                    }
                }
                return undefined;
            }
        }
    
    
    
        var tables = document.querySelectorAll('table');
    
        tables.forEach(function (table) {
            var ths = table.querySelectorAll('th');
            ths.forEach(function (th) {
                var width = th.offsetWidth;
                th.style.width = width + 'px';
            });
        });
    
    
    
        inputElements.forEach(function (inputElement) {
            inputElement.addEventListener('input', function (event) {
                var inputElement = event.target;
                if (inputElement.type === 'checkbox') {
                    var element = findElementInTree(afields, inputElement.name);
    
                    if (inputElement.checked) {
                        showBranchElements(element);
                    } else {
                        var elementsToHide = findElementInTree(afields, inputElement.name);
    
                        if (elementsToHide) {
                            hideBranchElements(elementsToHide);
                            clearAllElements(elementsToHide);
                        }
                    }
                } else if (inputElement.type === 'select-one') {
                    var toshow = choose[inputElement.name][inputElement.value];
                    var combinedList = Object.values(choose[inputElement.name]).flat();
                    combinedList.forEach(function (item) {
                        var elements = document.querySelectorAll('[name="' + item + '"]');
                        if (elements.length === 0) {
                            elements = document.querySelectorAll('[name="' + item.replace("s_", "") + '"]');
                        }
    
                        elements.forEach(function (element) {
                                clearelement(element);
                                if (element.type == 'date') {
									clearelement(document.querySelector(`[name="${element.name + '_f'}"]`));
                                }
                            hideBranchElements(findElementInTree(afields, item));
                        });
                    });
    
                    if (toshow) {
                        toshow.forEach(function (item) {
                            showBranchElements(findElementInTree(afields, item));
                        });
                    }
                }
            });
        });
    
        function clearAllElements(elementsToHide) {
            for (var key in elementsToHide) {
                if ((key.match(new RegExp("\\.", "g")) || []).length > 1) {
                    if (elementsToHide.hasOwnProperty(key)) {
                        var elements = document.querySelectorAll('[name="' + key + '"]') || document.querySelectorAll('[name="' + key.replace("s_", "") + '"]');
                        elements.forEach(function (element) {
                            clearelement(element);
                        });
    
                        if (typeof elementsToHide[key] === 'object' && elementsToHide[key] !== null) {
                            clearAllElements(elementsToHide[key]);
                        }
                    }
                }
            }
        }
    
        function hideAllExceptFirstLevel(treeData) {
            for (const key in treeData) {
                if (treeData.hasOwnProperty(key)) {
                    var selectors = document.querySelector('[name="' + key + '"]');
                    if (selectors && selectors.closest('.immunity-table') === null) {
                        hideBranchElements(treeData[key]);
                    }
                }
            }
        }
    
        function showAncestorElements(elementName) {
            let parts = elementName.split('.');
            while (parts.length > 0) {
                let currentName = parts.join('.');
                let currentElement = document.querySelector(`[name="${currentName}"]`);
                if (currentElement) {
                    let container = currentElement.closest('.row-container') || getChildElement(currentElement);
                    if (container) container.style.display = 'table-row';
                    currentElement.style.display = 'block';
                }
                parts.pop();
            }
        }
    
        function isSelectField(name) {
            return Object.keys(afields).some(key => key === 's_' + name);
        }
    
        function showInitialElements() {
            inputElements.forEach(function (inputElement) {
                if (!isSelectField(inputElement.name)) {
                    if (inputElement.type === 'checkbox' && inputElement.checked) {
                        showAncestorElements(inputElement.name);
                        showBranchElements(findElementInTree(afields, inputElement.name));
                    } else if (inputElement.type === 'select-one' && inputElement.selectedIndex !=0) {
                        showAncestorElements(inputElement.name);
                    }
                }
            });
        }
    
        hideAllExceptFirstLevel(afields);
        showInitialElements();
    });
    })(jQuery);