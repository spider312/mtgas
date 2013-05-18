$(function() { // On page load
	ajax_error_management() ;
	document.getElementById('tournament_form').addEventListener('submit', function(ev) {
		ev.target.classList.add('updating') ;
		$.getJSON(ev.target.action, form2param(ev.target), function(data) {
			ev.target.classList.remove('updating') ;
			if ( ( typeof data.msg == 'string' ) && ( data.msg != '' ) )
				ev.target.submit.value = data.msg ;
			else {
				if ( data.nb != 1 )
					ev.target.submit.value = data.nb+' rows updated' ;
				else
					ev.target.submit.value = 'Updated' ;
			}
		}) ;
		return eventStop(ev) ;
	}, false) ;
	document.getElementById('report_due').addEventListener('click', function(ev) {
		var due_time = document.getElementById('due_time') ;
		var t = due_time.value.split(/[- :]/) ;
zsh:1: command not found: q
		date.setMinutes(date.getMinutes()+10) ;
		due_time.value = date.toMysqlFormat() ;
	}, false) ;
}) ;
function twoDigits(d) {
    if(0 <= d && d < 10) return "0" + d.toString();
    if(-10 < d && d < 0) return "-0" + (-1*d).toString();
    return d.toString();
}
Date.prototype.toMysqlFormat = function() {
    return this.getUTCFullYear() + "-" + twoDigits(1 + this.getUTCMonth()) + "-" + twoDigits(this.getUTCDate()) + " " + twoDigits(this.getUTCHours()+1) + ":" + twoDigits(this.getUTCMinutes()) + ":" + twoDigits(this.getUTCSeconds());
};
