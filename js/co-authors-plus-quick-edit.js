/* Quick Edit support for Co-Authors-Plus */
(function($) {

	// we create a copy of the WP inline edit post function
	var wpInlineEdit = inlineEditPost.edit

	// and then we overwrite the function with our own code
	inlineEditPost.edit = function( id ) {

		// "call" the original WP edit function
		// we don't want to leave WordPress hanging
		wpInlineEdit.apply( this, arguments )

		// get the post ID
		var postId = 0
		if ( typeof( id ) == 'object' )
			postId = parseInt( this.getId( id ) )

		if ( postId > 0 ) {

			var $editRow = $( '#edit-' + postId )
			var $coauthorsSelect = $('[name="inline-coauthors"]', $editRow)
			var $postRow = $( '#post-' + postId )

			// initialize coauthors
			var coauthors = $.map($('.column-coauthors a', $postRow), function(el) {
				return {id: $(el).data('author-id'), display_name: $(el).text() }
			})

			$coauthorsSelect.select2({
				multiple: true,
				minimumInputLength: 2,
				initSelection: function(element, callback) {
					return callback(coauthors)
				},
				ajax: {
					url: coAuthorsPlus_ajax_suggest_link,
					data: function(term, page) {
						return {
							q: term,
							json: true
						}
					},
					results: function(data, page) {
						return { results: data.data };
					}
				},
				formatResult: function(result) {
					return jQuery('<div></div>').text(
						[
						result.numerical_id,
						result.user_login,
						result.display_name,
						result.user_email,
						result.id
						].join(' | ')
						)
				},
				formatSelection: function(selection) {
					return selection.display_name
				}
			})
			$coauthorsSelect.select2("container").find("ul.select2-choices").sortable({
				containment: 'parent',
				start: function() { $coauthorsSelect.select2("onSortStart") },
				update: function() { $coauthorsSelect.select2("onSortEnd") }
			})
		}
	}

})(jQuery)