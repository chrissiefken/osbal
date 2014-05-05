/* Validate functions */
function firstNameMatch() {
	return $('input[name=fname]').val().match(/^([a-z|A-Z]+\s?([a-z|A-Z]*)\.?)+$/);
}
function lastNameMatch() {
	return $('input[name=lname]').val().match(/^(([a-z|A-Z]+)\s?'?\s?([a-z|A-Z]*)\-?[a-z|A-Z]*)+$/);
}
function emailMatch() {
	return $('input[name=email]').val().match(/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i);
}
function validate() {
	var valid = true;

	if (!firstNameMatch()) {
		$('#first-name-validator').show();
		valid = false;
	} else {
		$('#first-name-validator').hide();
	} 
	if (!lastNameMatch()) {
		$('#last-name-validator').show();
		valid = false;
	} else {
		$('#last-name-validator').hide();
	}
	if (!emailMatch()) {
		$('#email-validator').show();
		valid = false;
	} else {
		$('#email-validator').hide();
	} 
	if ($('input[name=email]') != 'input[name=email]') {
		//
	}
}

$(function() {

	$('.btn-success').click(function(e) {

		e.preventDefault();
	});
});