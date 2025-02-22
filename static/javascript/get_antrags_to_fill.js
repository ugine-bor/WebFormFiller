function process_links(antrag_to_fill, afterdata){

    for (const [key, value] of Object.entries(afields['fields' + curstate + '-after']['links']['spec'])){
    console.log(key, afterdata[key], afterdata[key] == true, Number(afterdata[key]));
        if (afterdata[key] == true || Number(afterdata[key])>0){
            if (afterdata[key] ==true){
                afterdata[key] = 1
            }
            if(value[0].indexOf('*') !== -1){
                    for (var i = 0; i < afterdata[key]; i++){
                        antrag_to_fill[value[0].split('*')[0]] = (antrag_to_fill[value[0].split('*')[0]] || 0) + 1;
                        console.log('added link from spec haafter', value[0].split('*')[0], antrag_to_fill[value[0].split('*')[0]]);
                    }
            }
            else{
            antrag_to_fill[value[0].split('*')[0]] = (antrag_to_fill[value[0].split('*')[0]] || 0) + 1;
            console.log('added link from comm haafter', value[0].split('*')[0], antrag_to_fill[value[0].split('*')[0]]);
            }
        }
    }
    return antrag_to_fill;
}


function get_from_after(antrag, data, antrag_to_fill) {
    console.log("-------------------------")
    for (const [key, value] of Object.entries(afields['fields' + antrag]['links']['comm'])) {
        if (data[key] == true) {
            antrag_to_fill[value[0]] = (antrag_to_fill[value[0]] || 0) + 1;
            console.log('added link from comm ha', value[0], antrag_to_fill[value[0]]);
        }
    }

    antrag_to_fill = process_links(antrag_to_fill, collectdata(antrag + '-after'));
    return antrag_to_fill
}
