# Co-Authors Plus

* Contributors: batmoo, danielbachhuber, automattic
* Tags: authors, users, multiple authors, co-authors, multi-author, publishing
* Tested up to: 5.8
* Requires at least: 4.1
* Stable tag: 3.5.2

Assign multiple bylines to posts, pages, and custom post types via a search-as-you-type input box

## Description

Assign multiple bylines to posts, pages, and custom post types via a search-as-you-type input box. Co-authored posts appear on a co-author's archive page and in their feed. Co-authors may edit the posts they are associated with, and co-authors who are contributors may only edit posts if they have not been published (as is core behaviour).

Add writers as bylines without creating WordPress user accounts. Simply [create a guest author profile](http://vip.wordpress.com/documentation/add-guest-bylines-to-your-content-with-co-authors-plus/) for the writer and assign the byline as you normally would.

On the frontend, use the [Co-Authors Plus template tags](http://vip.wordpress.com/documentation/incorporate-co-authors-plus-template-tags-into-your-theme/) to list co-authors anywhere you'd normally list the author.

This plugin is an almost complete rewrite of the [Co-Authors](https://wordpress.org/plugins/co-authors/) plugin originally developed by Weston Ruter (2007). The original plugin was inspired by the '[Multiple Authors](https://txfx.net/2005/08/16/new-plugin-multiple-authors/)' plugin by Mark Jaquith (2005).

## Frequently Asked Questions

* How do I add Co-Authors Plus support to my theme?

If you've just installed Co-Authors Plus, you might notice that the bylines are being added in the backend but aren't appearing on the frontend. You'll need to [add the template tags to your theme](http://vip.wordpress.com/documentation/incorporate-co-authors-plus-template-tags-into-your-theme/) before the bylines will appear.

* What happens to posts and pages when I delete a user assigned to a post or page as a coauthor?

When a user is deleted from WordPress, they will be removed from all posts for which they are co-authors. If you chose to reassign their posts to another user, that user will be set as the coauthor instead.

* Can I use Co-Authors Plus with WordPress multisite?

Yep! Co-Authors Plus can be activated on a site-by-site basis, or network-activated. If you create guest authors, however, those guest authors will exist on a site-by-site basis.

* Who needs permission to do what?

To assign co-authors to posts, a WordPress user will need the `edit_others_posts` capability. This is typically granted to the Editor role, but can be altered with the `coauthors_plus_edit_authors` filter.

To create new guest author profiles, a WordPress will need the `list_users` capability. This is typically granted to the Administrator role, but can be altered with the `coauthors_guest_author_manage_cap` filter.

* Can I easily create a list of all co-authors?

Yep! There's a template tag called `coauthors_wp_list_authors()` that accepts many of the same arguments as `wp_list_authors()`. Look in template-tags.php for more details.

* I have a large database, will this make it slow?

If the site has a large database, you may run into issues with heavier than usual queries. You can work around this by disabling compat mode and force it to use simpler, tax-only queries by adding the following to your theme:

```php
// Use simple tax queries for CAP to improve performance
add_filter( 'coauthors_plus_should_query_post_author', '__return_false' );
```

Note that this requires the site(s) to have proper terms set up for all users. You can do this with the following wp-cli command:

```
# This is pretty long-running and can be expensive; be careful!
$ wp --url=example.com co-authors-plus create-terms-for-posts
```

* How do I use custom post types?

1. To ensure posts with your CPT are counted, use the `coauthors_count_published_post_types` filter.
```php
add_filter( 'coauthors_count_published_post_types', function( $post_types ) {
   $post_types[] = 'my_cpt_slug';
   return $post_types;
} );
```
2. To display the metabox on your CPT, either call `register_post_type()` with `$args['supports']` containing `'author'`, or call `add_post_type_support( 'my_cpt_slug', 'author' );`

## Installation

1. IMPORTANT: Please disable the original Co-Authors plugin (if you are using it) before installing Co-Authors Plus
2. Extract the coauthors-plus.zip file and upload its contents to the `/wp-content/plugins/` directory. Alternately, you can install directly from the Plugin directory within your WordPress Install.
3. Activate the plugin through the "Plugins" menu in WordPress.
4. Place the appropriate [co-authors template tags](http://vip.wordpress.com/documentation/incorporate-co-authors-plus-template-tags-into-your-theme/) in your template.
5. Add co-authors to your posts and pages.

## Screenshots

1. Multiple authors can be added to a Post, Page, or Custom Post Type using an auto-complete interface.
2. The order of your co-authors can be changed by drag and drop.
3. Guest authors allow you to assign bylines without creating WordPress user accounts. You can also override existing WordPress account meta by mapping a guest author to a WordPress user.

## Changelog

**3.5.2 (June 1, 2022)**
* Remove the `/wp/v2/coauthors` endpoint #851
* Remove CSS hack that hides the add author box from core in the block editor #847

For all previous changes, [view the Changelog](https://github.com/Automattic/Co-Authors-Plus/blob/master/CHANGELOG.md)
