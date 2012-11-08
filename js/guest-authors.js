jQuery(document).ready(function($){
	$('.reassign-option').on('click',function(){
		$('#wpbody-content input#submit').addClass('button-primary').removeAttr('disabled');
	});
	$('#leave-assigned-to').select2({
		minimumInputLength: 2,
		width: 'copy',
		multiple: false,
		ajax: {
			url: ajaxurl,
			dataType: 'json',
			data: function( term, page ) {
				return {
					q: term,
					action: 'search_coauthors_to_assign'
				};
			},
			results: function( data, page ) {
				return { results: data };
			}
		},
		formatResult: function( object, container, query ) {
			if ( object.display_name )
				return object.display_name;
			else
				return object.user_login;
		},
		formatSelection: function( object, container ) {
			return object.user_login;
		},
	});
});