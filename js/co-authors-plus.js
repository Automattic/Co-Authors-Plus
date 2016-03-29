jQuery( document ).ready(function () {

	/*
	 * Click handler for the delete button
	 * @param event
	 */
	var coauthors_delete_onclick = function( e ) {
		if ( confirm( coAuthorsPlusStrings.confirm_delete ) ) {
			return coauthors_delete( this );
		}
		return false;
	};

	var $coauthors_loading;

	function coauthors_delete( elem ) {

		var $coauthor_row = jQuery( elem ).closest( '.coauthor-row' );
		$coauthor_row.remove();

		// Hide the delete button when there's only one Co-Author
		if ( jQuery( '#coauthors-list .coauthor-row .coauthor-tag' ).length <= 1 )
			jQuery( '#coauthors-list .coauthor-row .coauthors-author-options' ).addClass( 'hidden' );

		return true;
	}

	var coauthors_edit_onclick = function( event ) {
		var $tag = jQuery( this );

		var $co = $tag.prev();

		$tag.hide();
		$co.show()
			.focus()
			;

		$co.previousAuthor = $tag.text();
	}

	/*
	 * Save coauthor
	 * @param int Author ID
	 * @param string Author Name
	 * @param object The autosuggest input box
	 */
	function coauthors_save_coauthor( author, co ) {

		var avatar = jQuery( '<img/>' )
			.attr( 'src', author.avatar )
			.addClass( 'avatar' )
			.attr( 'width', coAuthorsPlusStrings.avatar_size + 'px' )
			.attr( 'height', coAuthorsPlusStrings.avatar_size + 'px' )
			.css( 'float', 'left' )
			.css( 'cursor', 'move' )
			.css( 'margin', '-5px 8px 0 0' );

		// get sibling <span> and update
		co.siblings( '.coauthor-tag' )
			.html('')
			.append( author.name )
			.append( avatar )
			.show();

		// Update the value of the hidden input
		co.siblings( 'input[name="coauthors[]"]' ).val( author.nicename );
	}


	/*
	 * Add coauthor
	 * @param string Author Name
	 * @param object The autosuggest input box
	 * @param boolean Initial set up or not?
	 */
	function coauthors_add_coauthor( author, co, init, count ){

		// Check if editing
		if ( co && co.siblings( '.coauthor-tag' ).length ) {
			coauthors_save_coauthor( author, co );
		} else {
			// Not editing, so we create a new author entry
			if ( count == 0 ) {
				var coName = ( count == 0 ) ? 'coauthors-main' : '';
				// Add new author to <select>
				//coauthors_select_author( author );
			}
			var options = { addDelete: true, addEdit: false };

			// Create autosuggest box and text tag
			if ( ! co ) var co = coauthors_create_autosuggest( author, coName )
			var tag = coauthors_create_author_tag( author );
			var input = coauthors_create_author_hidden_input( author );

			var avatar = jQuery( '<img/>' )
				.attr( 'src', author.avatar )
				.addClass( 'avatar' )
				.attr( 'width', coAuthorsPlusStrings.avatar_size + 'px' )
				.attr( 'height', coAuthorsPlusStrings.avatar_size + 'px' )
				.css( 'float', 'left' )
				.css( 'cursor', 'move' )
				.css( 'margin', '-5px 8px 0 0' )
				.appendTo( tag );

			coauthors_add_to_table( co, tag, input, options );

			if ( ! init ) {
				// Create new author-suggest and append it to a new row
				var newCO = coauthors_create_autosuggest( '', false );
				coauthors_add_to_table( newCO );
				move_loading( newCO );
			}
		}

		co.bind( 'blur', coauthors_stop_editing );

		// Set the value for the auto-suggest box to the Author's name and hide it
		co.val( unescape( author.name ) )
			.hide()
			.unbind( 'focus' )
			;

		return true;
	}

	/*
	 * Add the autosuggest box and text tag to the Co-Authors table
	 * @param object Autosuggest input box
	 * @param object Text tag
	 * @param
	 */
	function coauthors_add_to_table( co, tag, input, options ) {
		if ( co ) {
			var $div = jQuery( '<div/>' )
						.addClass( 'suggest' )
						.addClass( 'coauthor-row' )
						.append( co )
						.append( tag )
						.append( input )
						;

			//Add buttons to row
			if ( tag ) coauthors_insert_author_edit_cells( $div, options );

			jQuery( '#coauthors-list' ).append( $div );
		}
	}

	/*
	 * Adds a delete and edit button next to an author
	 * @param object The row to which the new author should be added
	 */
	function coauthors_insert_author_edit_cells( $div, options ){
		var $options = jQuery( '<div/>' )
			.addClass( 'coauthors-author-options' )
			;

		if ( options.addDelete ) {
			var deleteBtn = jQuery( '<span/>' )
								.addClass( 'delete-coauthor' )
								.text( coAuthorsPlusStrings.delete_label )
								.bind( 'click', coauthors_delete_onclick )
								;
			$options.append( deleteBtn );
		}

		$div.append( $options );
		return $div;
	}

	/*
	 * Creates autosuggest input box
	 * @param string [optional] Name of the author
	 * @param string [optional] Name to be applied to the input box
	 */
	function coauthors_create_autosuggest( authorName, inputName ) {

		if ( ! inputName ) inputName = 'coauthorsinput[]';

		var $co = jQuery( '<input/>' );

		// Create the autocomplete field
		$co.attr( 'class', 'coauthor-suggest' )
			.attr( 'name', inputName )
			.appendTo( $coauthors_div )

			// Do not submit on enter key
			.keydown( coauthors_autosuggest_keydown )
			.autocomplete({
				minLength: 1,

				// Source data from AJAX hook
				source: function( request, response ) {
					// Make an array of existing author usernames
					var existing_authors = jQuery( 'input[name="coauthors[]"]' )
						.map( function() { 
							return jQuery( this ).val();
						}).get();

					jQuery.post( ajaxurl, {
						action: 'coauthors_ajax_suggest',
						q: $co.val(),
						exclude: existing_authors,
						nonce: coAuthorsPlusStrings.nonce
					}, function( data ) {
						response( data.data );
					});
				},
				select: function( event, ui ) {
					coauthors_autosuggest_select( ui, $co );
					return false;
				},
				open: function() {
					jQuery( this ).removeClass( 'ui-corner-all' )
						.addClass( 'ui-corner-top' );
				},
				close: function() {
					jQuery( this ).removeClass( 'ui-corner-top' )
						.addClass( 'ui-corner-all' );
				}

			// Extend autocomplete render to display data in our own format
			}).data( 'uiAutocomplete' )._renderItem = function( ul, item ) {
				var displayname = jQuery( '<div/>' )
					.css( 'margin-bottom', '-3px' )
					.append( item.displayname );

				var email = jQuery( '<div/>' )
					.css( 'font-size', '.75em' )
					.append( ' ' + item.email );

				var left = jQuery( '<img/>' )
					.css( 'display', 'inline-block' )
					.css( 'margin-right', '8px' )
					.css( 'width', coAuthorsPlusStrings.avatar_size + 'px' )
					.attr( 'src', item.avatar );

				var right = jQuery( '<div/>' )
					.css( 'display', 'inline-block' )
					.append( displayname )
					.append( email );

				var li = jQuery( '<li/>' )
					.data( 'item.autocomplete', item )
					.append( left )
					.append( right );

				li.appendTo( ul );

				return li;
			};

		if ( authorName )
			$co.attr( 'value', unescape( authorName ) );
		else
			$co.attr( 'value', coAuthorsPlusStrings.search_box_text )
				.focus( function(){ $co.val( '' ) } )
				.blur( function(){ $co.val( coAuthorsPlusStrings.search_box_text ) } );

		return $co;
	}

	// Callback for when a user selects an author
	function coauthors_autosuggest_select( ui, $this ) {
		var author = {}
		author.id = ui.item.id;
		author.login = ui.item.login;
		author.name = ui.item.displayname;
		author.email = ui.item.email;
		author.nicename = ui.item.nicename;
		author.avatar = ui.item.avatar;

		if ( author.id=='New' ) {
			coauthors_new_author_display( name );
		} else {
			coauthors_add_coauthor( author, $this );
			// Show the delete button if we now have more than one co-author
			if ( jQuery( '#coauthors-list .coauthor-row .coauthor-tag' ).length > 1 )
				jQuery( '#coauthors-list .coauthor-row .coauthors-author-options' ).removeClass( 'hidden' );
		}
	}

	// Prevent the enter key from triggering a submit
	function coauthors_autosuggest_keydown( e ) {
		if ( e.keyCode == 13 ) {return false;}
	}

	/*
	 * Blur handler for autosuggest input box
	 * @param event
	 */
	function coauthors_stop_editing( event ) {

		var co = jQuery( this );
		var tag = jQuery( co.next() );

		co.attr( 'value',tag.text() );

		co.hide();
		tag.show();

	//	editing = false;
	}

	/*
	 * Creates the text tag for an author
	 * @param string Name of the author
	 */
	function coauthors_create_author_tag( author ) {

		var $tag = jQuery( '<span></span>' )
							.html( unescape( author.name ) )
							.attr( 'title', coAuthorsPlusStrings.input_box_title )
							.addClass( 'coauthor-tag' )
							// Add Click event to edit
							.click( coauthors_edit_onclick );
		return $tag;
	}

	/*
	 * Creates the text tag for an author
	 * @param string Name of the author
	 */
	function coauthors_create_author_hidden_input ( author ) {
		var input = jQuery( '<input />' )
						.attr({
							'type': 'hidden',
							'id': 'coauthors_hidden_input',
							'name': 'coauthors[]',
							'value': unescape( author.nicename )
							})
						;

		return input;
	}

	/**
	 * Create the "New Guest Author" button so that guest authors can be created from 
	 * post.php when a post is being edited or created
	 * 
	 * @return bool True if link added, false if user lacks permissions
	 */
	function coauthors_add_create_guest_author_button() {
		// Bail if the current user does not have the required capabilities
		if ( ! coAuthorsPlusStrings.allow_add_guest_authors )
			return false;

		var codiv = jQuery( '#coauthors-edit' );

		// Add the button - text link for now
		var btn = jQuery( '<a/>' )
			.attr( 'href', '#' )
			.attr( 'id', 'cap_add_field' )
			.append( coAuthorsPlusStrings.label_newguest )
			.css( 'display', 'inline-block' )
			.css( 'margin', '5px 0 0 5px' )
			.on( 'click', function( e ) {
				e.preventDefault();

				// Hide the link on click
				jQuery( this ).hide();

				// Section label
				var label = jQuery( '<h4/>' )
					.css( 'display', 'inline-block' )
					.append( 'Add New Guest Author' );

				// Display name field
				var dname_field = jQuery( '<input/>' )
					.css( 'display', 'block' )
					.attr( 'type', 'text' )
					.attr( 'id', 'cap_dname_field' )
					.attr( 'placeholder', coAuthorsPlusStrings.label_displayname );

				// Email field
				var email_field = jQuery( '<input/>' )
					.css( 'display', 'block' )
					.attr( 'type', 'email' )
					.attr( 'id', 'cap_email_field' )
					.attr( 'placeholder', coAuthorsPlusStrings.label_email );

				// Cancel button
				var cancel_field = jQuery( '<a/>' )
					.attr( 'href', '#' )
					.attr( 'id', 'cap_cancel_field' )

					.append( coAuthorsPlusStrings.label_cancel )
					.on( 'click', function( e ) {
						e.preventDefault();
						jQuery( '#cap_add_field' ).show();
						jQuery( '#coauthors-addguest' ).remove();
					});

				// Submit button
				var submit_field = jQuery( '<input/>' )
					.addClass( 'button' )
					.attr( 'type', 'button' )
					.attr( 'value', coAuthorsPlusStrings.label_create )
					.attr( 'id', 'cap_submit_field' )
					.on( 'click', function( e ) {
						// Remove any error messages
						jQuery( '#coauthors-addguest .error' ).remove();
						
						// Disable the buttons
						jQuery( '#coauthors-addguest input' ).prop( 'disabled', true );
						jQuery( '#coauthors-addguest a' ).hide();

						// Add loading icon
						var spinner = jQuery( '<img/>' )
							.addClass( 'loading-image' )
							.attr( 'src', coAuthorsPlusStrings.loading_image_url )
							.css( 'float', 'right' )
							.css( 'margin', '7px 10px 0 0' );

						jQuery( '#coauthors-addguest' ).append( spinner );

						// Send AJAX request to add guest author
						jQuery.post( ajaxurl, {
							nonce: coAuthorsPlusStrings.nonce,
							action: 'coauthors_add_guest_author',
							guest_dname: jQuery( '#cap_dname_field' ).val(), 
							guest_email: jQuery( '#cap_email_field' ).val()
						}, function( response ) {
							// Disable the buttons
							jQuery( '#coauthors-addguest input' ).prop( 'disabled', false );
							jQuery( '#coauthors-addguest a' ).show();
							jQuery( '#coauthors-addguest .loading-image' ).remove();

							if ( response.success ) {
								// Guest author has been successfully added				
								jQuery( '#cap_add_field' ).show();
								jQuery( '#coauthors-addguest' ).remove();

								// Create author object to pass to coauthors_add_coauthor
								var author = {};
								author.id = response.data.id;
								author.login = response.data.login;
								author.name = response.data.displayname;
								author.email = response.data.email;
								author.nicename = response.data.nicename;
								author.avatar = response.data.avatar;

								// Remove the blank autocomplete field, will readd it after
								// the new coauthor has been added to the list.
								jQuery( '.coauthor-row' ).last().remove();

								// Add the coauthor to the grid
								coauthors_add_coauthor( author, undefined, false );

								/**
								 * Manually add the delete button to the first row if there are
								 * only two authors assigned
								 *
								 * @todo This is a bit of a hack
								 */
								if ( 3 == jQuery( '.coauthor-row' ).length ) {
									var span = jQuery( '<span/>' )
										.addClass( 'delete-coauthor' )
										.append( 'Remove' )
										.bind( 'click', coauthors_delete_onclick );

									var div = jQuery( '<div/>' )
										.addClass( 'coauthors-author-options' )
										.append( span )
										.appendTo( jQuery( '.coauthor-row' ).first() );
								}
							} else {
								// There was a problem adding the guest author. Tell the 
								// user what the error was.
								switch ( response.data ) {
									// Nonse verification failed
									case 'nonce': 
										alert( coAuthorsPlusStrings.ajax_error_nonce );
										break;

									// User does not have the capability to add guest authors
									case 'notallowed':
										alert( coAuthorsPlusStrings.ajax_error_notallowed );
										break;

									// Display name field empty
									case 'nameempty':
										var err = jQuery( '<span/>' )
											.addClass( 'error' )
											.append( coAuthorsPlusStrings.ajax_error_nameempty );

										jQuery( '#cap_dname_field' )
											.after( err )
											.on( 'keyup', coauthors_remove_ajax_message );
										
										break;

									// Email field empty
									case 'emailempty':
										var err = jQuery( '<span/>' )
											.addClass( 'error' )
											.append( coAuthorsPlusStrings.ajax_error_emailempty );

										jQuery( '#cap_email_field' )
											.after( err )
											.on( 'keyup', coauthors_remove_ajax_message );

										break;

									// Name did not pass sanitization
									case 'nameinvalid':
										var err = jQuery( '<span/>' )
											.addClass( 'error' )
											.append( coAuthorsPlusStrings.ajax_error_nameinvalid );

										jQuery( '#cap_dname_field' )
											.after( err )
											.on( 'keyup', coauthors_remove_ajax_message );
										
										break;

									// Email did not pass sanitization
									case 'emailinvalid':
										var err = jQuery( '<span/>' )
											.addClass( 'error' )
											.append( coAuthorsPlusStrings.ajax_error_emailinvalid );

										jQuery( '#cap_email_field' )
											.after( err )
											.on( 'keyup', coauthors_remove_ajax_message );
										
										break;

									// Email address is associated with a WP user
									case 'emailregistered':
										var err = jQuery( '<span/>' )
											.addClass( 'error' )
											.append( coAuthorsPlusStrings.ajax_error_emailregistered );

										jQuery( '#cap_email_field' )
											.after( err )
											.on( 'keyup', coauthors_remove_ajax_message );

										break;

									// Email address is already associated with a guest author
									case 'emailisguest':
										var err = jQuery( '<span/>' )
											.addClass( 'error' )
											.append( coAuthorsPlusStrings.ajax_error_emailisguest );

										jQuery( '#cap_email_field' )
											.after( err )
											.on( 'keyup', coauthors_remove_ajax_message );
										
										break;

									// General failure creating guest author (edge case)
									case 'guestnotcreated':
										alert( coAuthorsPlusStrings.ajax_error_guestnotcreated );
										break;

									// Some other failure
									default:
										alert( coAuthorsPlusStrings.ajax_error_guestnotcreated );
								}
							}
						});
					});

				// Append the fields to the bottom of #coauthors-edit
				jQuery( '<div/>' )
					.attr( 'id', 'coauthors-addguest' )
					.append( label )
					.append( dname_field )
					.append( email_field )
					.append( cancel_field )
					.append( submit_field )
					.appendTo( codiv );

				jQuery( '#cap_dname_field' ).focus();
			});
	
		// Append the button to the bottom of #coauthors-edit
		codiv.append( btn );

		// Button has been added, return true
		return true;
	}

	/**
	 * Remove a stale error message 
	 * Called for "add guest author" form validation
	 */
	function coauthors_remove_ajax_message() {
		jQuery( this ).next( 'span' ).remove();
	}

	var $coauthors_div = null;

	/**
	 * Initialize the Coauthors UI.
	 *
	 * @param array List of coauthors objects.
	 *  Each coauthor object should have the (string) properties:
	 *    login
	 *    email
	 *    name
	 *    nicename
	 */
	function coauthors_initialize( post_coauthors ) {
		// Add the controls to add co-authors

		$coauthors_div = jQuery( '#coauthors-edit' );

		if ( $coauthors_div.length ) {
			// Create the co-authors table
			var table = jQuery( '<div/>' )
				.attr( 'id', 'coauthors-list' )
				;
			$coauthors_div.append( table );
		}

		// Select authors already added to the post
		var addedAlready = [];
		//jQuery('#the-list tr').each(function(){
		var count = 0;
		jQuery.each( post_coauthors, function() {
			coauthors_add_coauthor( this, undefined, true, count );
			count++;
		});

		// Hide the delete button if there's only one co-author
		if ( jQuery( '#coauthors-list .coauthor-row .coauthor-tag' ).length < 2 )
			jQuery( '#coauthors-list .coauthor-row .coauthors-author-options' ).addClass( 'hidden' );


		// Create new author-suggest and append it to a new row
		var newCO = coauthors_create_autosuggest( '', false );
		coauthors_add_to_table( newCO );

		$coauthors_loading = jQuery( '#ajax-loading' ).clone().attr( 'id', 'coauthors-loading' );
		move_loading( newCO );


		// Make co-authors sortable so an editor can control the order of the authors
		jQuery( '#coauthors-edit' ).ready(function( $ ) {
			$( '#coauthors-list' ).sortable({
				axis: 'y',
				handle: '.coauthor-tag',
				placeholder: 'ui-state-highlight',
				items: 'div.coauthor-row:not(div.coauthor-row:last)',
				containment: 'parent',
			});
		});

	}


	function show_loading() {
		$coauthors_loading.css( 'visibility', 'visible' );
	}
	function hide_loading() {
		$coauthors_loading.css( 'visibility', 'hidden' );
	}
	function move_loading( $input ) {
		$coauthors_loading.insertAfter( $input );
	}
	// Show laoding cursor for autocomplete ajax requests
	jQuery( document ).ajaxSend(function( e, xhr, settings ) {
		if ( settings.url.indexOf( coAuthorsPlus_ajax_suggest_link ) != -1 ) {
			// Including existing authors on the AJAX suggest link
			// allows us to filter them out of the search request
			var existing_authors = jQuery( 'input[name="coauthors[]"]' ).map(function(){return jQuery( this ).val();}).get();
			settings.url = settings.url.split( '&existing_authors' )[0];
			settings.url += '&existing_authors=' + existing_authors.join( ',' );
			show_loading();
		}
	});
	// Hide laoding cursor when autocomplete ajax requests are finished
	jQuery( document ).ajaxComplete(function( e, xhr, settings ) {
		if ( settings.url.indexOf( coAuthorsPlus_ajax_suggest_link ) != -1 )
			hide_loading();
	});

	if ( 'post-php' == adminpage || 'post-new-php' == adminpage ) {
		var $post_coauthor_logins = jQuery( 'input[name="coauthors[]"]' );
		var $post_coauthor_names = jQuery( 'input[name="coauthorsinput[]"]' );
		var $post_coauthor_emails = jQuery( 'input[name="coauthorsemails[]"]' );
		var $post_coauthor_nicenames = jQuery( 'input[name="coauthorsnicenames[]"]' );
		var $post_coauthor_avatars = jQuery( 'input[name="coauthorsavatars[]"]' );

		var post_coauthors = [];

		for ( var i = 0; i < $post_coauthor_logins.length; i++ ) {
			post_coauthors.push({
				login: $post_coauthor_logins[i].value,
				name: $post_coauthor_names[i].value,
				email: $post_coauthor_emails[i].value,
				nicename: $post_coauthor_nicenames[i].value,
				avatar: $post_coauthor_avatars[i].value
			});
		}

		// Remove the read-only coauthors so we don't get craziness
		jQuery( '#coauthors-readonly' ).remove();
		coauthors_initialize( post_coauthors );
		coauthors_add_create_guest_author_button();
	}
	else if ( 'edit-php' == adminpage ) {

		var wpInlineEdit = inlineEditPost.edit;

		inlineEditPost.edit = function( id ) {

			wpInlineEdit.apply( this, arguments )

			// get the post ID
			var postId = 0
			if ( typeof( id ) == 'object' )
				postId = parseInt( this.getId( id ) )

			if ( postId > 0 ) {

				var $postRow = jQuery( '#post-' + postId )

				// Move the element to the appropriate position in the view
				// JS hack for core bug: https://core.trac.wordpress.org/ticket/26982
				jQuery( '.quick-edit-row .inline-edit-col-left .inline-edit-col' ).find( '.inline-edit-coauthors' ).remove() // remove any previously added elements
				var el = jQuery( '.inline-edit-group.inline-edit-coauthors', '#edit-' + postId );
				el.detach().appendTo( '.quick-edit-row .inline-edit-col-left .inline-edit-col' ).show();

				// initialize coauthors
				var post_coauthors = jQuery.map( jQuery( '.column-coauthors a', $postRow ), function( el ) {
					return {
						login: jQuery( el ).data( 'user_login' ),
						name: jQuery( el ).data( 'display_name' ),
						email: jQuery( el ).data( 'user_email' ),
						nicename: jQuery( el ).data( 'user_nicename' ),
						avatar: jQuery( el ).data( 'avatar' )
					}
				})
				coauthors_initialize( post_coauthors );

			}
		}
	}

});

if ( typeof( console ) === 'undefined' ) {
	var console = {}
	console.log = console.error = function() {};
}
