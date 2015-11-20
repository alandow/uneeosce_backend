/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
function isie() {
    return (/MSIE (\d+\.\d+);/.test(navigator.userAgent));
}

function createCookie(name, value, days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        var expires = "; expires=" + date.toGMTString();
    }
    else
        var expires = "";
    document.cookie = name + "=" + value + expires + "; path=/";
}

function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ')
            c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0)
            return c.substring(nameEQ.length, c.length);
    }
    return null;
}

function eraseCookie(name) {
    createCookie(name, "", -1);
}

// Allow HTML in dialogs
$.widget("ui.dialog", $.extend({}, $.ui.dialog.prototype, {
    _title: function(title) {
        if (!this.options.title) {
            title.html("&#160;");
        } else {
            title.html(this.options.title);
        }
    }
}));

// allow select2 interaction within dialog boxes
$.ui.dialog.prototype._allowInteraction = function(e) {
    return !!$(e.target).closest('.ui-dialog, .ui-datepicker, .select2-drop').length;
};

//$.widget( "ui.dialog", $.ui.dialog, {
//	_allowInteraction: function( event ) {
//		return !!$( event.target ).closest( ".other-popups" ).length || this._super( event );
//	}
//});





