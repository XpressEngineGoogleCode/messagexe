/* vi:set ts=4 sw=4 expandtab enc=utf8: */
var whohasfocus=0;
var str;
function saveCursorPosition(obj){
    if (obj.createTextRange){
        obj.selection = document.selection.createRange().duplicate();
    }
    return true;
}

function insertAtCursorPosition(myTextArea, smilie) {
    if (whohasfocus!=3) {
        myTextArea.value += smilie;
    }

    if (whohasfocus==3){
        str = smilie;
        if (myTextArea.createTextRange && myTextArea.selection) {
            var objTxtRange = myTextArea.selection;
            objTxtRange2=objTxtRange.text;
            objTxtRange.text = (objTxtRange.text.charAt(objTxtRange.text.length - 1) == ' ') ? str + ' ' : str;
            myTextArea.selection = null;
        }
        else {
            myTextArea.value += str;
        }
        myTextArea.focus();
        return true;
        whohasfocus=0;
    }
}

