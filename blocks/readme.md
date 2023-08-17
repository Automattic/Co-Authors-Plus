## Layout

The Coauthors block supports two layouts:

### Inline

Inline is it's own layout. It applies `display: inline` to all its contents.

### Block

Block does not apply a specific layout. The coauthors stack vertically, and can be spaced apart using block spacing, but that's it. You can create your own layout using any of the other blocks like Group / Row / Stack and it will be applied to each coauthor, similar to laying our each post in a query loop.

## Context

### Post, Page, Query Loop

By default, blocks receive the post context. The job of the Coauthors Block is to use this context to find the relevant authors and provide context to its inner blocks.

### Author Archive

In author archive templates, the post context is the first post by the author returned in the main query. Since Co-Authors Plus exists to allow multiple attributed authors, we can't be sure which author of the first post matches the requested author archive.

If you want to display data about the author on their own archive, use the individual CoAuthor blocks directly without wrapping them in the CoAuthors Block.

The function `coauthors_blocks_provide_author_archive_context` filters the context using the `author_name` query variable to provide the correct author context.

### Extending

If you create a custom block that uses the namespace and prefix `cap/coauthor-`, the author archive context will be updated and will match the REST API response used in the editor.

If you have a differently named custom block, you can use the filter `coauthor_blocks_block_uses_author_context` to opt-in to the author archive context.

## Example Data

When working with Full Site Editing, or in the post editor before the authors are loaded, example data is used. The example data provided with the coauthor blocks replicates the standard REST API response.

### Extending

If you have written a plugin that modifies the REST API response, you can similarly modify the example data either on the server-side using the filter `coauthor_blocks_store_data` or the client-side using the filter `cap.author-placeholder`
