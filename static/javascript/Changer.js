function confirmAntrag() {
    if (!curstate.includes('-after')) {
        var afterform = document.getElementById('after-antrag-form');
        afterform.style.display = 'block';

        document.body.style.overflow = 'hidden';
        document.documentElement.style.overflow = 'hidden';
        afterform.style.overflow = 'auto';

        console.log('Changing curstate from ' + curstate + ' to ' + curstate + '-after');
        curstate = curstate+'-after';
        var afterantrag = document.getElementById(`${curstate}_table`);
        afterantrag.style.display = 'block';
        hideElementsWithMatchingName(afields['fields' + curstate]['tree']);
        hideRowsWithTdElements();
    }
    else{
        console.log('already in afterform')
    }
}

function collectdata(antrag){
    var elements = document.querySelectorAll('[id^="' + antrag + '_field_"]');
    var data = {};
    elements.forEach(element => {
        var inputfield = element.querySelector('input');
        if (!inputfield) inputfield = element.querySelector('select');

        if (inputfield){
            //if checkbox
            if (inputfield.type == 'checkbox'){
                data[inputfield.name] = inputfield.checked;
            }
            else{
                data[inputfield.name] = inputfield.value;
            }
        }
    });
    return data
}

function orderedStringify(obj) {
    const replacer = (key, value) => {
        if (value instanceof Map) {
            return [...value];
        }
        return value;
    };
    return JSON.stringify(obj, replacer);
}

function sendata(data) {
    //init data: {"curstate":{"1":true,"2":false,"3":false,"4":false,"5":false,"6":false,"7":false,"8":false,"9":false,"10":false,"11":false,"12":false,"1.1":"qwe","1.2":"e","1.3":"","1.4":"","1.5":"","1.6":"","1.7":"","1.8":"","1.9":"","1.10":"","1.11":false,"1.12":"","1.13":"","1.14":"","1.15":"","1.16":"","1.17":"","2.1":"","2.1.1":false,"2.1.2":false,"2.1.3":"","2.1.4":false,"2.1.5":"","3.1":"","3.1.1":false,"3.1.2":false,"3.1.3":false,...}
    //sort data by id in key {"curstate": {'1': true, '1.1': 'qwe', '1.2': 'e', '1.3': '', '1.4': '', '1.5': '', '1.6': '', '1.7': '', '1.8': '', '1.9': '', '1.10': '', '1.11': false, '1.12': '', '1.13': '', '1.14': '', '1.15': '', '1.16': '', '1.17': '', '2': false, '2.1': '', '2.1.1': false,...}
    const sortedKeys = Object.keys(data).sort((a, b) => {
        const partsA = a.split('.');
        const partsB = b.split('.');

        for (let i = 0; i < Math.max(partsA.length, partsB.length); i++) {
            const numA = parseFloat(partsA[i]) || 0;
            const numB = parseFloat(partsB[i]) || 0;

            if (numA !== numB) {
                return numA - numB;
            }
        }

        return 0;
    });

    const sortedData = sortedKeys.map(key => ({ [key]: data[key] }));

    const toSend = { [curstate]: sortedData };

    const toSendStr = JSON.stringify(toSend);

    fetch('/api/userinput', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: toSendStr
    })
    .then(response => response.text())
    .then(answer => {
        console.log('Answer:', answer);
        return answer;
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function confirmAfter(){

    console.log('Changing curstate from ' + curstate + ' to ' + curstate.replace('-after',''));
    curstate = curstate.replace('-after','');
    jsondata = collectdata(curstate);
    console.log("LOOK I FOUND USERDATA", jsondata);
    sendata(jsondata);

    antrag_to_fill = get_from_after(curstate,jsondata,antrag_to_fill);

    table = document.getElementById('aftercontainer');
    table.parentNode.removeChild(table);

    const txt = document.getElementById('after_table_title');
    newtext = "";
    for (let key in antrag_to_fill) {
        if (antrag_to_fill.hasOwnProperty(key)) {
            newtext += key + ": " + antrag_to_fill[key] + 'раза/раз' + "<br>";
        }
    }
    txt.innerHTML = "Далее необходимо заполнить следующие антраги:<br>" + newtext;

    const tofinalbutton = document.getElementById('after_table_close');
    tofinalbutton.onclick = function(){closeafter(antrag_to_fill)}
    tofinalbutton.textContent = "Завершить";

}

function closeafter(tofill){
    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';
    window.scrollTo(0, 0);

    var afterform = document.getElementById('after-antrag-form');
    afterform.style.overflow = '';
    afterform.style.display = 'none';
    tofill=[];
}
