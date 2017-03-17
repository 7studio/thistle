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
* Removes "Open Sans" webfont loaded via Google Fonts from WP core;
* Translates the base for the author and the search permalink structure depending on the locale used;
* Discourages search engines from indexing some pages (e.g.: paged/search results)
* …

**Helpful** things:

* Adds a "Thumbnail" column for post types that support the thumbnail feature;
* Moves the excerpt meta box above the editor permanently;
* Renders the specific WordPress.com shortcodes for SoundCloud and SlideShare;
* Allows users to see a live preview of embedded content within the visual editor;
* …

**Tricky** things:

* Retrieves permalink for attachment following an unique permalink structure `media/%postame%`;
* Handles the output of embedded content to be responsive;
* Constructs `<meta>` tags needed by Open Graph and Twitter Card;
* Builds a new Gallery shortcode output to interact easily with PhotoSwipe;
* …

Almost all the lines of code are commented so I let you look through Thistle to find other tips and features for your joy.

Please, note that Thistle is still under development.
