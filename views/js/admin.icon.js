
if (window.attachEvent) {
    window.attachEvent('onload', setAFASAdminIcon);
} else {
    if (window.onload) {
        var curronload = window.onload;
        var newonload = function (evt) {
            curronload(evt);
            setAFASAdminIcon(evt);
        };
        window.onload = newonload;
    } else {
        window.onload = setAFASAdminIcon;
    }
}

function setAFASAdminIcon() {
    try {

        if (typeof afsa_plugin_base_url === 'undefined')
            return;

        var
                M = document.getElementById('subtab-AFSAMenu'),
                A = null,
                I = null
                ;

        for (var i = 0; i < M.childNodes.length; i++) {
            if (M.childNodes[i].tagName == 'A') {
                A = M.childNodes[i];
                break;
            }
        }

        for (var i = 0; i < A.childNodes.length; i++) {
            if (A.childNodes[i].tagName == 'I') {
                I = A.childNodes[i];
                break;
            }
        }
        if (I)
            I.innerHTML = '<img src="' + afsa_plugin_base_url + '/views/img/icon.small.png" class=afsa_admin_menu_icon>';

    } catch (e) {

    }
}