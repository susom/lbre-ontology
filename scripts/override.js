
$(document).ready(() => {
    OverrideHelper.truncateRoomID();
    OverrideHelper.verifyActionTags();
})

const OverrideHelper = {
    truncateRoomID: () => {
        const observe = $('.ui-autocomplete-input');
        observe.each(function () {
            var target = this;
            var observer = new MutationObserver(function (e) {
                const results = $('.ui-autocomplete').find('a');
                for (let a of results) {
                    let parsedTag = OverrideHelper.parseTag($(a).text())
                    if (parsedTag)
                        $(a).text(parsedTag);
                }
            })
            observer.observe(target, {
                attributes: true,
                attributeFilter: ['class'],
            })
        })
    },



    // Truncates tag to just the room ID but preserves value
    parseTag: (text) => {
        try {
            let tag = text.substring(text.lastIndexOf('[')+1, text.lastIndexOf(']'));
            let rest = text.substring(text.lastIndexOf(']') + 1 , text.length - 1);
            let roomID = tag.split('-')[3];
            if(roomID)
                return `[${roomID}] ${rest}`;
            return text
        } catch (e) {
            return null;
        }
    },

    verifyActionTags: () => {
        //Injected via php before page load
        if(actionTagTable){
            for(const key of Object.keys(actionTagTable)){
                let roomPageElement = $(`#${key}-autosuggest`);
                let buildingPageElement = $(`#${actionTagTable[key]}-autosuggest`);
                if(roomPageElement.length && buildingPageElement.length) { //both fields exist on the page, bind event handler
                    buildingPageElement.on('change', function(){
                        OverrideHelper.overwriteAutocomplete(actionTagTable[key], roomPageElement);
                    })
                    OverrideHelper.overwriteAutocomplete(actionTagTable[key], roomPageElement);
                }
            }
        }
    },

    overwriteAutocomplete: (buildingField, roomPageElement) => {
        let buildingID = ($(`input[name=${buildingField}]`).val());
        let sourceUrl = $(roomPageElement).autocomplete("option", "source");
        $(roomPageElement).autocomplete( "option", "source", `${sourceUrl}&clientFilter=${buildingID}`)
    }

}


