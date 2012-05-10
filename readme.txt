=== Co-Authors Plus ===
Contributors: batmoo, danielbachhuber, automattic
Donate link: http://digitalize.ca/donate
Tags: authors, users, multiple authors, coauthors, multi-author, publishing
Tested up to: 3.3.2
Requires at least: 3.1
Stable tag: 2.6.4

Allows multiple authors to be assigned to posts, pages, and custom post types via a search-as-you-type input box

== Description ==

Allows multiple authors to be assigned to posts, pages, or custom post types via the search-as-you-type inputs. Template tags allow listing of co-authors anywhere you'd normally list the author. Co-authored posts appear on a co-author's archive page and in their feed. Additionally, co-authors may edit the posts they are associated with, and co-authors who are contributors may only edit posts if they have not been published (as is core behavior).

This plugin is an almost complete rewrite of the Co-Authors plugin originally developed at [Shepherd Interactive](http://www.shepherd-interactive.com/) (2007). The original plugin was inspired by the 'Multiple Authors' plugin by Mark Jaquith (2005).

> *See "Other Notes" section for Template Tags and usage information*

== Changelog ==

= 2012-05-07 / 2.6.4 =
* Bug fix: Properly filter the user query so users can AJAX search against the display name field again
* If https is used for the admin, also use the secure Gravatar URL. Props [rmcfrazier](https://github.com/rmcfrazier)

= 2012-04-30 / 2.6.3 =
* AJAX user search is back to searching against user login, display name, email address and user ID. The method introduced in v2.6.2 didn't scale well
* French translation courtesy of Sylvain Bérubé
* Spanish translation courtesy of Alejandro Arcos
* Bug fix: Resolved incorrect caps check against user editing an already published post. [See forum thread](http://wordpress.org/support/topic/multiple-authors-cant-edit-pages?replies=17#post-2741243)

= 2012-03-06 / 2.6.2 =
* AJAX user search matches against first name, last name, and nickname fields too, in addition to display name, user login, and email address
* Comment moderation and approved notifications are properly sent to all co-authors with the correct caps
* Filter required capability for user to be returned in an AJAX search with 'coauthors_edit_author_cap'
* Filter out administrators and other non-authors from AJAX search with 'coauthors_edit_ignored_authors'
* Automatically adds co-authors to Edit Flow's story budget and calendar views
* Bug fix: Don't set post_author value to current user when quick editing a post. This doesn't appear in the UI anywhere, but adds the post to the current user's list of posts
* Bug fix: Properly cc other co-authors on new comment email notifications
* Bug fix: If a user has already been added as an author to a post, don't show them in the AJAX search again
* Bug fix: Allow output constants to be defined in a theme's functions.php file and include filters you can use instead

= 2011-12-30 / 2.6.1 =

* Fix mangled usernames because of sanitize_key http://wordpress.org/support/topic/plugin-co-authors-plus-26-not-working-with-wp-33

= 2011-12-22 / 2.6 =

* Sortable authors: Drag and drop the order of the authors as you'd like them to appear ([props kingkool68](http://profiles.wordpress.org/users/kingkool68/))
* Search for authors by display name (instead of nicename which was essentially the same as user_login)
* Option to remove the first author when there are two or more so it's less confusing
* Bumped requirements to WordPress 3.1
* Bug fix: Update the published post count for each user more reliably

= 2011-08-14 / 2.5.3 =

* Bug fix: Removed extra comma when only two authors were listed. If you used the COAUTHORS_DEFAULT_BETWEEN_LAST constant, double-check what you have

= 2011-04-23 / 2.5.2 =

* Bug: Couldn't query terms and authors at the same time (props nbaxley)
* Bug: Authors with empty fields (e.g. first name) were displaying blank in some cases
* Bug: authors with spaces in usernames not getting saved (props MLmsw, Ruben S. and others!)
* Bug: revisions getting wrong user attached (props cliquenoir!)

= 2011-03-26 / 2.5.1 =

* Fix with author post count (throwing errors)

= 2011-03-26 / 2.5 =

* Custom Post Type Support
* Compatibility with WP 3.0 and 3.1
* Gravatars
* Lots and lots and lots of bug fixes
* Thanks to everyone who submitted bugs, fixes, and suggestions! And for your patience!

= 2009-10-16 / 2.1.1 =

* Fix for coauthors not being added if their username is different from display name
* Fixes to readme.txt (fixes for textual and punctuation errors, language clarification, minor formatting changes) courtesy of [Waldo Jaquith](http://www.vqronline.org)

= 2009-10-11 / 2.1 =

* Fixed issues related to localization. Thanks to Jan Zombik <zombik@students.uni-mainz.de> for the fixes.
* Added set_time_limit to update function to get around timeout issues when upgrading plugin

= 2009-10-11 / 2.0 =

* Plugin mostly rewritten to make use of taxonomy instead of post_meta
* Can now see all authors of a post under the author column from Edit Posts page
* All authors of a post are now notified on a new comment
* Various javascript enhancements
* New option to allow subscribers to be added as authors
* All Authors can edit they posts of which they are coauthors
* FIX: Issues with wp_coauthors_list function
* FIX: Issues with coauthored posts not showing up on author archives

= 2009-06-16 / 1.2.0 =

* FIX: Added compatibility for WordPress 2.8
* FIX: Added new template tags (get_the_coauthor_meta & the_coauthor_meta) to fix issues related to displaying author info on author archive pages. See [Other Notes](http://wordpress.org/extend/plugins/co-authors-plus/other_notes/) for details.
* FIX: Plugin should now work for plugins not using the 'wp_' DB prefix 
* FIX: Coauthors should no longer be alphabetically reordered when the post is updated  
* FIX: Plugin now used WordPress native AJAX calls to tighten security
* DOCS: Added details about the new template tags

= 2009-04-26 / 1.1.5 =

* FIX: Not searching Updated SQL query for autosuggest to search through first name, last name, and nickname
* FIX: When editing an author, and clicking on a suggested author, the original author was not be removed
* DOCS: Added code comments to javascript; more still to be added
* DOCS: Updated readme information

= 2009-04-25 / 1.1.4 =

* Disabled "New Author" output in suggest box, for now
* Hopefully fixed SVN issue (if you're having trouble with the plugin, please delete the plugin and reinstall)

= 2009-04-23 / 1.1.3 =

* Add blur event to disable input box
* Limit only one edit at a time.
* Checked basic cross-browser compatibility (Firefox 3 OS X, Safari 3 OS X, IE7 Vista).
* Add suggest javascript plugin to Edit Page.

= 2009-04-19 / 1.1.2 =

* Disabled form submit when enter pressed.

= 2009-04-15 / 1.1.1 =

* Changed SQL query to return only contributor-level and above users.

= 2009-04-14: 1.1.0 =

* Initial beta release.


== Installation ==

1. IMPORTANT: Please disable the original Co-Authors plugin (if you are using it) before installing Co-Authors Plus
1. Extract the coauthors-plus.zip file and upload its contents to the `/wp-content/plugins/` directory. Alternately, you can install directly from the Plugin directory within your WordPress Install.
1. Activate the plugin through the "Plugins" menu in WordPress.
1. Place the appropriate coauthors template tags in your template.
1. Add co-authors to your posts and pages.


== Basic Usage and Other Notes ==

* Contributor-level and above can be added as co-authors.
* As per WordPress design, when an editor creates a new Post or Page, they are by default added as an author. However, they can be replaced by clicking on their name and typing in the name of the new author.
* The search-as-you-type box starts searching once two letters have been added, and executes a new search with every subsequent letter.
* The search-as-you-type box searches through the following user fields: a) user login; b) user nicename; c) display name; d) user email; e) first name; f) last name; and g) nickname. 


== Template Tags ==

New template tags enable listing of co-authors:

*   <code>coauthors()</code>
*   <code>coauthors_posts_links()</code>
*   <code>coauthors_firstnames()</code>
*   <code>coauthors_lastnames()</code>
*   <code>coauthors_nicknames()</code>
*   <code>coauthors_links()</code>
*   <code>coauthors_IDs()</code>

These template tags correspond to their "<code>the_author*</code>" equivalents; take special note of the pluralization.
Each of these template tags accept four optional arguments:

1.   <code>between</code>: default ", "
1.   <code>betweenLast</code>: default " and "
1.   <code>before</code>: default ""
1.   <code>after</code>: default ""

To use them, simply modify the code surrounding all instances of <code>the_author*()</code> to something like the following example:

    if(function_exists('coauthors_posts_links'))
        coauthors_posts_links();
    else
        the_author_posts_link();

The result of this would be formatted like "John Smith, Jane Doe and Joe Public".

Note that as of this writing, WordPress does provide a means of extending <code>wp_list_authors()</code>, so
included in this plugin is the function <code>coauthors_wp_list_authors()</code> modified
to take into account co-authored posts; the same arguments are accepted.

Sometimes you may need fine-grained control over the display of a posts's authors, and in this case you may use
the <code>CoAuthorsIterator</code> class. This class may be instantiated anywhere you may place <code>the_author()</code>
or everywhere if the post ID is provided to the constructor. The instantiated class has the following methods:

1.   <code>iterate()</code>: advances <code>$authordata</code> to the next co-author; returns <code>false</code> and restores the original <code>$authordata</code> if there are no more authors to iterate. 
1.   <code>get_position()</code>: returns the zero-based index of the current author; returns -1 if the iterator is invalid.
1.   <code>is_last()</code>: returns <code>true</code> if the current author is the last.
1.   <code>is_first()</code>: returns <code>true</code> if the current author is the first.
1.   <code>count()</code>: returns the total number of authors.
1.   <code>get_all()</code>: returns an array of all of the authors' user data.

For example:

    $i = new CoAuthorsIterator();
    print $i->count() == 1 ? 'Author: ' : 'Authors: ';
    $i->iterate();
    the_author();
    while($i->iterate()){
    	print $i->is_last() ? ' and ' : ', ';
    	the_author();
    }

= the coauthor meta =

*   <code>get_the_coauthor_meta( $field )</code> (2.8 only)
*   <code>the_coauthor_meta( $field )</code> (2.8 only)

Note: The $field variable corresponds with the same values accepted by the [the author meta](http://codex.wordpress.org/Template_Tags/the_author_meta) function.

= get coauthors =

*	<code>get_coauthors( [$post_id], [$args] )</code>

This function returns an array of coauthors for the specified post, or if used inside the Loop, the current post active in the Loop. the $args parameter is an array that allows you to specify the order in which the authors should be returned.

= is coauthor for post =

*	<code>is_coauthor_for_post( $user, $post_id )</code>

This function allows you to check whether the specified user is coauthor for a post. The $user attribute can be the user ID or username. 


== Frequently Asked Questions ==

= What is the main difference between Co-Authors and Co-Authors Plus? =

The most notable difference is the replacement of the standard WordPress authors drop-downs with search-as-you-type/auto-suggest/whatever-you-call-them input boxes. As a result, major bits of the JavaScript code was changed to be more jQuery-friendly. Eventually, I hope to include the ability to add new Users from within the Edit Post/Page screen and possibly Gravatar support.

= What happens to posts and pages when I delete a user assigned to a post or page as a coauthor? =

When a user is deleted from WordPress, they will be removed from all posts for which they are co-authors. If you chose to reassign their posts to another user, that user will be set as the coauthor instead.

== Screenshots ==
1.  "Post Author(s)" box with multiple authors added
2.  Search-as-you-type input box that looks up authors as you type in their name. Fields displayed are: ID, Display Name, and Email.
