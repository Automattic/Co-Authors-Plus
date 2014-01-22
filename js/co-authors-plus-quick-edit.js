/* Quick Edit support for Co-Authors-Plus */
(function($) {

	// we create a copy of the WP inline edit post function
	var wpInlineEdit = inlineEditPost.edit;

	// and then we overwrite the function with our own code
	inlineEditPost.edit = function( id ) {

		// "call" the original WP edit function
		// we don't want to leave WordPress hanging
		wpInlineEdit.apply( this, arguments );

		// get the post ID
		var postId = 0;
		if ( typeof( id ) == 'object' )
			postId = parseInt( this.getId( id ) );

		if ( postId > 0 ) {

			var $editRow = $( '#edit-' + postId );
			var $coauthorsSelect = $('[name="coauthors[]"]', $editRow)
			var $postRow = $( '#post-' + postId );

			// initialize coauthors
			$.each($('.column-coauthors a', $postRow), function(i, el) {
				var option = $('<option></option>')
				.text( $(el).text() )
				.attr('value', $(el).data('author-id'))
				.attr('selected', 'selected')
				$coauthorsSelect.append(option)
			})

			$coauthorsSelect.select2()
		}
	};

})(jQuery);