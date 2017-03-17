This work is a try to understand a bit more WordPress and to make me happier when I work with it.

I hesitated during a long time before starting my personal WordPress theme 
because I knew it would be a huge job and a long time investment. 
But I was tired to undo half of theme features that I would use and lose hours of development.

Thistle is a WordPress Parent Theme which cannot live alone. It needs a bit of love
through a Child Theme to construct beautiful experiences.

Here are many reasons why Thistle can/should not be used as it is:

* It doesn't provide any default design;
* It doesn't offer critical features (e.g.: portfolio, project, etc);
* Its template hierarchy is incomplete;
* Its `function.php` is already wellfilled;
* I myself decided on this first requirement.

With the help of Thistle, I tried to collect interesting things that I judge vital
to work with WordPress and that I need the most :

**Basic** things:

* Cleans `<head>` HTML element;
* Translates the base for the author and the search permalink structure depending on the locale used;
* Discourages search engines from indexing some pages (e.g.: paged/search results);
* Adds custom post type counts in "Right now" Dashboard widget; [8bc363](https://github.com/7studio/thistle/commit/8bc363785f5d020ab17995879d845b556456202d)
* Transforms the default version of dependencies to hide the WP version in URLs; [601cb1](https://github.com/7studio/thistle/commit/601cb1b28e14527ba6648bf9406b820f178094c7)
* Use the site information on login page; [c66554](https://github.com/7studio/thistle/commit/c66554a9b3c7f05ded5f1e8ce7cbd08e11835fce)
* Disables the REST API except for the oEmebed endpoint; [8c04ed](https://github.com/7studio/thistle/commit/8c04edb5a5911d843e2ef80fbe329b34661fc491)
* …

**Helpful** things:

* Adds a "Thumbnail" column for post types that support the thumbnail feature;
* Moves the excerpt meta box above the editor permanently;
* Renders the specific WordPress.com shortcodes for SoundCloud and SlideShare;
* Allows users to see a live preview of embedded content within the visual editor;
* Redirects the user to the current page or the home page on logout; [c579eb](https://github.com/7studio/thistle/commit/c579ebbd13af6395442d5c37c37288a733a8dda5)
* Offers the right behaviour to `exclude_from_search`; [18acb9](https://github.com/7studio/thistle/commit/18acb9d1ca4e5e02999e198bf56a24045298b4b0)
* Adds a new role to WordPress for customers (Editor++); [67767b](https://github.com/7studio/thistle/commit/67767b83e5b2596e8846f77217e992829142dd11)
* …

**Tricky** things:

* Retrieves permalink for attachment following an unique permalink structure `media/%postame%`;
* Handles the output of embedded content to be responsive;
* Constructs `<meta>` tags needed by Open Graph and Twitter Card;
* Builds a new Gallery shortcode output to interact easily with PhotoSwipe;
* Enqueues assets automaticaly following the template hierarchy behaviour; [05caf1](https://github.com/7studio/thistle/commit/05caf17f9970df81417086876cce5d98b8888367)
* Autoloads TinyMCE templates for post types; [d1bd7c](https://github.com/7studio/thistle/commit/d1bd7c302071e97b0a5aa8beb65ab5b27b34e4d0)
* Allows to use only the post type archive page; [712b0a](https://github.com/7studio/thistle/commit/712b0a04a2dcd6c1f56060c0d91e1f3d8819caf1)
* …

Almost all the lines of code are commented so I let you look through Thistle to find other tips and features for your joy.

Please, note that Thistle is still under development.
