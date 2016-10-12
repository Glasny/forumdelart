$(document).ready(function(){
	// Réinitialisation filtres et tri
	$("#resetMenu").click(function() {
		var loc = $('#listeOeuvresMenu');
		loc.find('select').find(':selected').prop('selected',false);
        loc.find('select option:eq(0)').prop('selected',true);
        loc.find('form').submit();
	});
});
