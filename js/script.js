// Functions for the edit form

function hscheckImages(text1,text2,wwwroot,f){
    var res = jQuery.post(wwwroot+'/repository/draftfiles_ajax.php?action=list', {
        sesskey : f.sesskey.value,
        filepath : '/',
        itemid : f.image.value
    }, null, 'json').done(function(data) {
        if (data.filecount == 1) {
            hsopenEditor(wwwroot, f);
        } else {
            alert('You didnt say the magic word!');
        }
    }).fail(function() {
        alert('Ajax Fail');
    });
}

function hsopenEditor(wwwroot,f){
    var cmid = f.cmid.value;
    var courseid = f.courseid.value;
    var sesskey = f.sesskey.value;
    var imageid = f.image.value;
    var hseditor = window.open(wwwroot+'/question/type/ubhotspots/hseditor.php?cmid='+cmid+'&courseid='+courseid+'&imageid='+imageid+'&sesskey='+sesskey,'hseditor','height='+screen.height+',width='+screen.width+'top=0, left=0, resizable=yes');
    hseditor.focus();
}