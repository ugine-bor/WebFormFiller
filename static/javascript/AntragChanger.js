var formContaineru = document.getElementById("AntragSelectUpperContainer");
var formContainerl = document.getElementById("AntragSelectLowerContainer");

function createButton(fid, link) {
    var newButtonU = document.createElement("button");
    newButtonU.type = "button";
    newButtonU.className = "btn btn-secondry btn-default";
    newButtonU.innerText = link;
    newButtonU.name = link;
    newButtonU.id = fid + 'U';
    newButtonU.onclick = function() {
        handleButtonClick(newButtonU.name);
    };

    var newButtonL = document.createElement("button");
    newButtonL.type = "button";
    newButtonL.className = "btn btn-secondry btn-default";
    newButtonL.innerText = link;
    newButtonL.name = link;
    newButtonL.id = fid + 'L';
    newButtonL.onclick = function() {
        handleButtonClick(newButtonL.name);
    };

    formContaineru.appendChild(newButtonU);
    formContainerl.appendChild(newButtonL);
    console.log('added:',link, 'id:',fid);
}

function deleteButton(link) {
    var buttonU = formContaineru.querySelector('button[name="' + link + '"]');
    if (buttonU) {
        formContaineru.removeChild(buttonU);
    }

    var buttonL = formContainerl.querySelector('button[name="' + link + '"]');
    if (buttonL) {
        formContainerl.removeChild(buttonL);
    }
    console.log('deleted:', link);
}

function deletetable(state){
    var table = document.getElementById(`${state}_table`);
    table.style.display = 'none';
}

function showtable(state){
    var table = document.getElementById(`${state}_table`);
    table.style.display = 'block';
    hideElementsWithMatchingName(afields['fields' + curstate]['tree']);
    hideRowsWithTdElements();
}

function handleButtonClick(buttonName) {
    console.log(curstate);
    deletetable(curstate);
    curstate = buttonName;
    showtable(curstate);

    console.log('переключено на ' + curstate);
}


