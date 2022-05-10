function set_width(width, save) {
	var width;
	var url = '?action=change_layout&sa=width&w='+width;
	var ajax_handle = new XMLHttpRequest();
		
	ajax_handle.open('GET', url, true);
	ajax_handle.send(null);
	
	document.getElementById('wrapper').style.width = (width == 'fixed') ? '770px' : '95%';
}
