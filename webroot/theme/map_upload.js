function handle_map_upload(form) {
	
	// Check title
	if (!form.title.value.match(/^[a-zA-Z\-\_0-9\ ]+$/) || !form.title.value.length) {
		alert('Bad map title. I said only numbers, letters, dashes, and underscores');
		form.title.style.backgroundColor = 'pink';
		form.title.style.color = 'white';
		form.title.focus();
		return false;
	}

	// Check gametype
	if (!form.gametype.value.match(/^[0-9]+$/) || !form.gametype.value.match) {
		alert('Bad gametype. It is not optional and you must choose one. ');
		form.gametype.style.backgroundColor = 'pink';
		form.gametype.style.color = 'white';
		form.gametype.focus();
		return false;
	}

	// Overview is required
	if (!form.map_overview.value) {
		alert('Missing overview. You want us to see your map, right?');
		form.map_overview.style.backgroundColor = 'pink';
		form.map_overview.style.color = 'white';
		return false;
	}
	
	// And map file
	if (!form.map_file.value) {
		alert('Missing archive');
		form.map_file.style.backgroundColor = 'pink';
		form.map_file.style.color = 'white';
		return false;
	}

	// Assuming we weren't caught above, go
	return true;
}
