# Presentation Plugin

The **Presentation** Plugin is an extension for [Grav CMS](http://github.com/getgrav/grav), and provides a simple way of creating fullscreen slideshows that can be navigated two-dimensionally, using the [Reveal.js](https://github.com/hakimel/reveal.js/)-library.

At its core the plugin facilitates efficient handling of content for use with the library. You can utilize Reveal.js however you want through custom initialization, and still leverage the plugin's content-handling.

## CURRENTLY A WORK IN PROGRESS

## Features

- Presentations through two-dimensional slideshows
- Responsive, multi-device capable styling
- Granular control over presentation and slides
  - Cascading-styles and options with Shortcodes and the Admin-plugin
- Portable content: Everything is contained in Markdown, including settings
- Flexible, ambigious, recursive Page-structure
- Extendable through your own theme or plugin
- Print-friendly presentations, with or without notes or in text-only mode
- Presenter-mode: A modern, powerful, easy-to-use alternative to PowerPoint
  - Include notes with your presentation
  - Synchronize Presenter-mode and the Presentation locally or remotely

## Installation

Installing the presentation-plugin can be done in one of two ways. The GPM (Grav Package Manager) installation method enables you to quickly and easily install the plugin with a simple terminal command, while the manual method enables you to do so via a zip file.

### GPM Installation (Preferred)

The simplest way to install this plugin is via the [Grav Package Manager (GPM)](http://learn.getgrav.org/advanced/grav-gpm) through your system's terminal (also called the command line). From the root of your Grav install type:

    bin/gpm install presentation

This will install the Presentation-plugin into your `/user/plugins` directory within Grav. Its files can be found under `/your/site/grav/user/plugins/presentation`.

### Manual Installation

To install this plugin, just download the zip version of this repository and unzip it under `/your/site/grav/user/plugins`. Then rename the folder to `presentation`. You can find these files on [GitHub](https://github.com/ole-vik/grav-plugin-presentation) or via [GetGrav.org](http://getgrav.org/downloads/plugins#extras).

You should now have all the plugin files under

    /your/site/grav/user/plugins/presentation
	
> NOTE: This plugin is a modular component for Grav which requires [Grav](http://github.com/getgrav/grav) and the [Error](https://github.com/getgrav/grav-plugin-error) and [Problems](https://github.com/getgrav/grav-plugin-problems) to operate.

## Configuration

Before configuring this plugin, you should copy the `user/plugins/presentation/presentation.yaml` to `user/config/plugins/presentation.yaml` and only edit that copy.

Here is the default configuration and an explanation of available options:

```yaml
enabled: true
order:
  by: folder
  dir: asc
theme_css: true
builtin_css: true
builtin_js: true
fontscale: true
fontratio: 30
sync: 'browser'
color_function: "50"
header_font: "'Helvetica Neue', Helvetica, Arial, sans-serif"
block_font: "Palatino, 'Palatino Linotype', 'Palatino LT STD', 'Book Antiqua', Georgia, serif"
change_titles: true
footer: "partials/presentation_footer.html.twig"
shortcodes: true
options:
  ...
```

All configuration-options available to the Reveal.js-library can be configured through `options`, see its [documentation for available options](https://github.com/hakimel/reveal.js#configuration). For example:

```yaml
options:
  controls: true
  controlsTutorial: true
  controlsLayout: 'bottom-right'
```

In addition to options for the Reveal.js-library, you can define the order of the of how the pages are rendered through `order.by` and `order.dir`, and whether to use the plugin's built-in CSS and JS with `builtin_css` and `builtin_js`.

### Page-specific configuration

Any configuration set in `presentation.yaml` can be overridden through a page's FrontMatter, like this:

```yaml
---
title: Alice’s Adventures in Wonderland
presentation:
  order:
    by: date
    dir: desc
  options:
    navigation: true
---
```

## Usage

The page-structure used in presentation is essentially the same as normally in Grav, with a few notable exceptions: Any horizontal rule, `---` in Markdown and `<hr />` in HTML, is treated as a _thematic break_, as it is defined in HTML5. This means that if you separate content with a horizontal rule within a page, the plugin treats this as a new section. This is equivalent to using child-pages for new sections, which work recursively: You can have as many pages below the root-page as you want, each of them will be treated as a section. Further, these methods can be mixed by some pages using horizontal rules, and some not.

### Nomenclature

With Reveal.js the presentation is not entirely linear. Rather, it has a linear, left-to-right set of sections that each make up a slide, and can have additional slides going dowards. Thus you can progress through the presentation linearly starting at each section, moving downwards until the end, and continuing onto the next section, or move between them as you choose.

Further, there are [Fragments](https://github.com/hakimel/reveal.js#fragments) that can be used within each slide. These reveal linearly like slides, but make one element appear at a time rather than the full contents of the slide.

### Example structure:

```
/user/pages/book
├── presentation.md
├── 01.down-the-rabbit-hole
│   └── default.md
├── 02.advice-from-a-caterpillar
│   └── default.md
├── 03.were-all-mad-here
│   └── default.md
├── 04.a-mad-tea-party
│   └── default.md
├── 05.the-queens-crocquet-ground
│   └── default.md
├── 06.postscript
└───└── default.md
```

As seen in this example structure, only the initial page uses the `presentation.html.twig`-template. The template used for child-pages is irrelevant, as only the content of these pages are processed. The plugin defines the `presentation.html.twig`-template, but you can override it through your theme.

### Styling

The plugin emulates the logic of Cascading Style Sheets (CSS), in that pages can be assigned a class, style-property, or be hidden using FrontMatter or shortcodes. This is as simple as using `class: slide1` in FrontMatter or `[class=slide1]` with a shortcode in the Markdown-content. Styles are applied the same way, where FrontMatter accepts CSS-properties like this:

```
style:
  color: green
```

That is, mapping each property to a value, not as a list. The same could be set for any single slide using `[color=green]`, as described below. Styles are given precedence by where they appear, so the plugins looks for them in this order:

1. Plugin-settings
2. Page FrontMatter
3. Child page FrontMatter
4. Page Content (Markdown) as shortcodes

The properties are gathered cumulatively, and a property farther down the chain takes precedence over a property further up.


The `styles`-property is defined by a list of `property: value`'s and processed by the plugin. If the amount of pages exceed the amount of styles, they will be reused in the order they are defined. If the `background`-property is defined, but `color` is not, the plugin tries to estimate a suitable text-color to apply. The equations available to estimate this color is either `50` or `YIQ`, set by `color_function`.

You can of course also style the plugin using your theme's /css/custom.css-file, by targeting the `.reveal`-selector which wraps around all of the plugin's content. This behavior can be enabled or disabled with the `theme_css`-setting. All slides have an `id`-attribute set on their sections, which can be utilized by CSS like this:

```css
.reveal #down-the-rabbit-hole-0 {
  background: red;
}
```

#### Using section- or slide-specific styles

If configured with `shortcodes: true` any section or slide can use shortcodes to declare specific styles. These take the format of `[property=value]` and are defined in multiples, eg:

```
[background=#195b69]
[color=cyan]
```

If the shortcode is found and applied, it is stripped from the further evaluated content. This method uses regular expressions for speed, and takes precedence over plugin- or page-defined `styles`.

**Note**: The syntax is restricted to `[property=value]`. Quotes or other unexpected characters not conforming to alphanumerics or dashes will make the expression fail to pick up the shortcode. The `property` or `value` must basically conform to the [a-zA-Z0-9-]+ regular expression, separated by an equal-character (`=`) and wrapped in square brackets (`[]`). For testing, use [Regex101](https://regex101.com/r/GlH65o/1).

#### Full background image or video with Reveal.js, through data-attributres

Reveal.js supports easy usage of background images or videos for slides, with their [Slide background](https://github.com/hakimel/reveal.js/#slide-backgrounds). As well as inline styles through shortcodes, any property that begins with `data` is passed as a data-attribute to the slide, so you can do things like add a background video, like this:

```
[data-background-video=https://dl3.webmfiles.org/big-buck-bunny_trailer.webm]
[data-background-video-loop=true]
[data-background-video-muted=true]
[data-background-size=contain]
```

### Injecting Twig

Using the `footer`-setting you can append a Twig-template to each section globally, or a specific page's section. For example, `footer: "partials/presentation_footer.html.twig"` will render the theme's `partials/presentation_footer.html.twig`-template and append it to the section(s). If the element was constructed like this: `<div class="footer">My footer</div>`, you could style it like this:

```css
.reveal .slides .footer {
  display: block;
  position: absolute;
  bottom: 2em;
}
```

You can also arbitrarily execute Twig within a page's Markdown by enabling it in the FrontMatter with:

```yaml
twig_first: true
process:
  twig: true
```

For example, `<p>{{ site.author.name }}</p>` will render the name of the author defined in site.yaml.

### Creating a menu

The plugin makes a `presentation_menu`-variable available through Twig on pages which use the fullscreen-template, which can be used to construct an overall menu of pages. It is an array with anchors and titles for each page, and a list of them with links to sections can be constructed like this:

```
<ul id="menu" class="menu">
{% for anchor, title in presentation_menu %}
  <li>
    <a href="#{{ anchor }}">{{ title }}</a>
  </li>
{% endfor %}
</ul>
```

Each slide is assigned an `id`-attribute based on the page's slug and its index, as well as a `data-title`-attribute containing the title of the page. A menu could also be made using this data with JavaScript: `document.getElementById('presentation').querySelectorAll('*[id]')`.

### Notes

Each slide can have notes associated with it, like a PowerPoint-presentation would. These can be set on any slide using `[notes] ... [/notes]`, where the shortcodes should envelop the Markdown-content that makes up your notes. Eg:

```
[notes]

- Rabbits don't lay eggs
- Porpoises don't tell lies
- Camels don't smoke cigarettes

[/notes]
```

### Presenting

The plugin, like the Reveal.js-library, makes available a Presenter-mode. There are two modes available for using this: Locally, with `sync: 'browser'`, or remotely, with `sync: 'poll'`. When running locally, you need to access your presentation with a special URL -- `http://yourgrav.tld/book?admin=yes&showNotes=true` -- **and in a new window from the same browser** open the same URL without these parameters -- `http://yourgrav.tld/book`. 

The synchronization between Presenter-mode and the Presentation happens by sending data from one browser-window to the other, requiring JavaScript. When running remotely, the synchronization happens by polling and checking if the presentation has changed.

**Note:** The polling approach needs a stable server to work, more so than Grav itself. It has been tested extensively with PHP 7.1 and 7.2, running on Caddy Server and with PHP's built-in server, with fairly standard production-setups of PHP. If your server-connection crashes with a 502 error -- usually with the error "No connection could be made because the target machine actively refused it.", it is because PHP is set up to forcibly time out despite being long-polled.

## Contributing

### PHP Code Standards

This plugin follows PSR-1, PSR-2, and PEAR coding standards (use CodeSniffer), as well as PSR-4.

### Style-compilation

Use a SCSS-compiler, like [LibSass](https://github.com/sass/libsass), eg. [node-sass](https://github.com/sass/node-sass) and compiled `scss/presentation.scss` to `css/presentation.css` in the theme-folder. For example: `node-sass --watch --source-map true scss/presentation.scss css/presentation.css`. Requires Node-modules to be installed first.

## TODO

- Test fontsizing
  - Decide on FlowType or Modular Scaling
    - Modular Scaling: Use ElementQueries on slide
- Test responsive
- Test print
  - Consider blueprint addition to presentation.yaml for quick-linking
- Replace `+++`-fragment with shortcodes
- Token-auth polling (take from Stat API)

## Credits

- Grav [presentation](https://github.com/OleVik/grav-plugin-presentation)-plugin is written by [Ole Vik](https://github.com/OleVik)
- [Reveal.js](https://github.com/hakimel/reveal.js)-plugin
- Both are MIT-licensed