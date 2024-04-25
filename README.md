# Co-Authors Plus

Stable tag: 3.6.1  
Requires at least: 4.1  
Tested up to: 6.5  
Requires PHP: 5.6  
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  
Tags: authors, users, multiple authors, co-authors, multi-author, publishing  
Contributors: batmoo, danielbachhuber, automattic, GaryJ

Assign multiple bylines to posts, pages, and custom post types with a search-as-you-type input box.

## Description

Assign multiple bylines to posts, pages, and custom post types via a search-as-you-type input box. Co-authored posts appear on a co-author's archive page and in their feed. Co-authors may edit the posts they are associated with, and co-authors who are contributors may only edit posts if they have not been published (as is core behavior).

Add writers as bylines without creating WordPress user accounts. Simply [create a guest author profile](https://github.com/Automattic/Co-Authors-Plus/wiki/Creating-and-editing-guest-authors) for the writer and assign the byline as you normally would.

On the frontend, use the [Co-Authors Plus template tags](https://github.com/Automattic/Co-Authors-Plus/wiki/Template-tags) to list co-authors anywhere you'd normally list the author.

This plugin is an almost complete rewrite of the [Co-Authors](https://wordpress.org/plugins/co-authors/) plugin originally developed by Weston Ruter (2007). The original plugin was inspired by the '[Multiple Authors](https://txfx.net/2005/08/16/new-plugin-multiple-authors/)' plugin by Mark Jaquith (2005).

Refer to our [wiki](https://github.com/Automattic/Co-Authors-Plus/wiki) for detailed documentation.

## Installation

1. IMPORTANT: If you are using the original Co-Authors plugin, disable it before installing Co-Authors Plus.
2. Extract the coauthors-plus.zip file and upload its contents to the `/wp-content/plugins/` directory. Alternately, you can install directly from the Plugin directory within your WordPress Install.
3. Activate the plugin through the "Plugins" menu in WordPress.
4. Place [co-authors template tags](https://github.com/Automattic/Co-Authors-Plus/wiki/Template-tags) in your template.
5. Add co-authors to your posts and pages.

## Screenshots

1. Multiple authors can be added to a Post, Page, or Custom Post Type using an auto-complete interface.
2. Guest authors allow you to assign bylines without creating WordPress user accounts. You can also override existing WordPress account meta by mapping a guest author to a WordPress user.

## Frequently Asked Questions

### How do I add Co-Authors Plus support to my theme?

If you've just installed Co-Authors Plus, you might notice that the bylines are being added in the backend but aren't appearing on the front end. You'll need to [add the template tags to your theme](https://github.com/Automattic/Co-Authors-Plus/wiki/Template-tags) before the bylines will appear.

### What happens to posts and pages when I delete a user assigned to a post or page as a co-author?

When a user is deleted from WordPress, they will be removed from all posts for which they are co-authors. If you reassign their posts to another user, that user will be the co-author instead.

### Can I use Co-Authors Plus with WordPress multisite?

Yes! Co-Authors Plus can be activated on a site-by-site basis or network-activated. If you create guest authors, however, those guest authors will exist on a site-by-site basis.

### Who needs permission to do what?

A WordPress user will need the `edit_others_posts` capability to assign co-authors to posts. This is typically granted to the Editor role but can be altered with the `coauthors_plus_edit_authors` filter.

A WordPress user will need the `list_users` capability to create new guest author profiles. This is typically granted to the Administrator role but can be altered with the `coauthors_guest_author_manage_cap` filter.

### Can I easily create a list of all co-authors?

Yes! A template tag called `coauthors_wp_list_authors()` accepts many of the same arguments as `wp_list_authors()`. Look in `template-tags.php` for more details.

### Can I disable Guest Authors?

Yes! Guest authors can be disabled entirely through a filter. Having the following line load on `init` will do the trick:
`add_filter( 'coauthors_guest_authors_enabled', '__return_false' )`

## Change Log

[View the change log](https://github.com/Automattic/Co-Authors-Plus/blob/master/CHANGELOG.md).

## Blocks

### Co-Authors

Use this block to create a repeating template that displays the co-authors of a post. By default it contains the Co-Author Name block, but you can add any other block you want to the template. If you choose another Co-Author block like avatar, biography or image it will automatically be supplied the author `context` that it needs. This works similarly to creating a Post Template in a Query Loop block.

The Co-Authors Block supports two layouts:

#### Inline Layout

Use the inline layout to display co-authors in a list on a single wrapping line.

You can control the characters displayed before, between and after co-authors in the list using the block settings, or change the defaults using the following server-side filters:

```
coauthors_default_before
coauthors_default_between
coauthors_default_between_last
coauthors_default_after
```

#### Block Layout

Use the block layout to display co-authors in a vertical stack. While using the block layout you can use block spacing settings to control the vertical space between co-authors.

Then you can create your own layout using blocks like group, row or stack and it will be applied to each co-author, similar to applying a layout to each post in a query loop.

### Co-Author Name

This block displays a co-author's `Display Name` and optionally turns it into a link to their author archive.

Using the block's advanced settings you can select which HTML element is used to output the name. This is useful in contexts such as an author archive where you might want their name to be a heading.

### Co-Author Avatar

Like the post author avatar, or comment author avatar, this block displays a small scale square image of a co-author and utilizes the Gravatar default avatars as configured in your site's discussion options.

To customize the available sizes, use the [rest_avatar_sizes](https://developer.wordpress.org/reference/hooks/rest_avatar_sizes/) filter.

### Co-Author Biography

This block outputs the biographical information for a co-author based on either their user or guest author data.

The content is wrapped in paragraph elements using `wpautop` and is escaped using `wp_kses_post`.

### Co-Author Featured Image

This block requires the use of Guest Authors. Because guest author avatars are uploaded to the WordPress media library, there are more options for displaying these images.

This block utilizes the image sizes configured in your theme and your site's media settings to present a guest author's avatar at a larger scale or higher resolution. It does not support Gravatars.

## Block Context

### Post, Page, Query Loop

By default, all blocks receive the post context. The job of the Co-Authors Block is to use this context to find the relevant authors and provide context to its inner blocks.

### Author Archive

If you want to display data about the author on their own archive, use the individual co-author blocks directly without wrapping them in the Co-Authors Block. During requests for an author archive the correct context is derived from the `author_name` query variable and provided to all blocks that declare their use of the context `co-authors-plus/author`.

### Extending

If you make a custom block and want to use the author context, add `co-authors-plus/author` to the `usesContext` property in your block.json file.

Example:
```json
{
	"usesContext": ["co-authors-plus/author"]
}
```

## Block Example Data

When working with Full Site Editing, or in the post editor before the authors are loaded, example data is used. The example data provided with the co-author blocks resembles a response to the `/coauthors/v1/coauthors/:user-nicename` REST API endpoint.

### Extending

If you have written a plugin that modifies the REST API response, you can similarly modify the example data either on the server-side using the filter `coauthors_blocks_store_data` or the client-side using the filter `co-authors-plus.author-placeholder`.

## Block Non-support

To declare a lack of support for Co-Author Plus blocks on your site, use the filter `coauthors_plus_support_blocks` to return `false`.
