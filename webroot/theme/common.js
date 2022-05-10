function shout_submit(form) {
	return true;
}

function toggle_show(element_id, fake_link, change) {
	document.getElementById(element_id).style.display = document.getElementById(element_id).style.display == 'none' ? '' : 'none';

	if (change != false)
		fake_link.innerHTML =  fake_link.innerHTML == '-' ? '+' : '-';


	var url = 'theme/save_col_pref.php?key='+element_id+'&val='+(fake_link.innerHTML == '-' ? 1 : 2);
	var ajax_handle = new XMLHttpRequest();
	ajax_handle.open('GET', url, true);
	ajax_handle.send(null);
}
