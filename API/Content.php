<?php
/**
 * Presentation Plugin, Content API
 *
 * PHP version 7
 *
 * @category   API
 * @package    Grav\Plugin\PresentationPlugin
 * @subpackage Grav\Plugin\PresentationPlugin\Push
 * @author     Ole Vik <git@olevik.net>
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 * @link       https://github.com/OleVik/grav-plugin-presentation
 */

namespace Grav\Plugin\PresentationPlugin\API;

use Michelf\SmartyPants;

/**
 * Content API
 *
 * Simple REST API for communicating commands between pages
 *
 * @category Extensions
 * @package  Grav\Plugin\PresentationPlugin
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-presentation
 */
class Content
{
    /**
     * Regular expressions
     */
    const REGEX_IMG = "/(<img(?:(\s*(class)\s*=\s*\x22([^\x22]+)\x22*)+|[^>]+?)*>)/";
    const REGEX_IMG_P = "/<p>\s*?(<a .*<img.*<\/a>|<img.*)?\s*<\/p>/";
    const REGEX_IMG_TITLE = "/<img[^>]*?title[ ]*=[ ]*[\"](.*?)[\"][^>]*?>/";
    const REGEX_IMG_WRAPPING_LINK = '/\[(?\'image\'\!.*)\]\((?\'url\'https?:\/\/.*)\)/';

    /**
     * Instantiate Presentation Utilities
     *
     * @param object $grav   Grav-instance
     * @param array  $config Plugin configuration
     */
    public function __construct($grav, $config)
    {
        $this->grav = $grav;
        $this->config = $config;
    }

    /**
     * Creates page-structure recursively
     *
     * @param string  $route Route to page
     * @param string  $mode  Reserved collection-mode for handling child-pages
     * @param integer $depth Reserved placeholder for recursion depth
     *
     * @return array Page-structure with children
     */
    public function buildTree($route, $mode = false, $depth = 0)
    {
        $page = $this->grav['page'];
        $depth++;
        $mode = '@page.self';

        $config = $this->config;
        if (isset($page->header()->Reveal)) {
            $config = array_merge($config, $page->header()->Reveal);
        }
        if ($depth > 1) {
            $mode = '@page.children';
        }
        $pages = $page->evaluate([$mode => $route]);
        $pages = $pages->published()->order(
            $config['order']['by'],
            $config['order']['dir']
        );
        $paths = array();
        foreach ($pages as $page) {
            $route = $page->rawRoute();
            $paths[$route]['depth'] = $depth;
            $paths[$route]['title'] = $page->title();
            $paths[$route]['menu'] = array(
                'anchor' => $page->slug(),
                'title' => $page->title()
            );
            $paths[$route]['route'] = $route;
            $paths[$route]['slug'] = $page->slug();
            $paths[$route]['header'] = $page->header();
            if (isset($page->header()->type)) {
                $paths[$route]['type'] = $page->header()->type;
            }
            if (isset($config['footer'])) {
                $paths[$route]['footer'] = $config['footer'];
            }
            if (isset($page->header()->footer)) {
                $paths[$route]['footer'] = $page->header()->footer;
            }
            if (!empty($paths[$route]['footer'])) {
                $paths[$route]['footer'] = $this->grav['twig']->processTemplate(
                    $paths[$route]['footer'],
                    ['page' => $page]
                );
            }
            if (isset($page->header()->horizontal)) {
                $paths[$route]['horizontal'] = $page->header()->horizontal;
            }
            if (isset($page->header()->styles)) {
                $paths[$route]['style'] = $page->header()->styles;
            } elseif (isset($config['style'])) {
                $paths[$route]['style'] = $config['style'];
            }
            $paths[$route]['content'] = $page->content();

            if (!empty($paths[$route])) {
                $children = $this->buildTree($route, $mode, $depth);
                if (!empty($children)) {
                    $paths[$route]['children'] = $children;
                }
            }
        }
        if (!empty($paths)) {
            return $paths;
        } else {
            return null;
        }
    }

    /**
     * Create HTML to use with Reveal.js
     *
     * @param array $pages Page-structure with children
     *
     * @return string HTML-structure
     */
    public function buildContent($pages)
    {
        $parsedown = new \Parsedown();
        include_once __DIR__ . '/../vendor/autoload.php';
        $return = '';
        foreach ($pages as $route => $page) {
            ob_start();
            $styleIndex = 0;
            $styles = array();
            $config = $this->config;
            $config['route'] = $route;
            $content = $parsedown->text($page['content']);
            $content = str_replace('<p></p>', '', $content);
            $breaks = explode('<hr />', $content);
            if (count($breaks) > 0) {
                $this->breakContent($page, $config, $breaks);
            } else {
                echo '<section>';
                echo $content;
                if (isset($page['footer'])) {
                    echo $page['footer'];
                }
                echo '</section>';
            }

            if (isset($page['header']->Reveal)) {
                $config = array_merge($config, $page['header']->Reveal);
            }
            $return .= ob_get_contents();
            ob_end_clean();
            if (isset($page['children'])) {
                $return .= $this->buildContent($page['children']);
            }
        }
        return $return;
    }

    /**
     * Create HTML from content
     *
     * @param object $page   Grav Page-instance
     * @param array  $config Plugin- and slide-configuration
     * @param array  $breaks Slides from Markdown
     *
     * @return void
     */
    public function breakContent($page, $config, $breaks)
    {
        echo '<section id="' . $page['slug'] . '" ';
        echo 'data-title="' . $page['title'] . '">';
        $index = 0;
        foreach ($breaks as $break) {
            $config['id'] = $page['slug'] . '-' . $index;
            $config['class'] = '';
            $hide = false;
            $config['styles'] = array();
            if ($this->config['unwrap_images']) {
                $break = self::unwrapImage($break);
            }
            if (isset($page['header']->style)) {
                $config['styles'] = array_merge(
                    $config['styles'],
                    $page['header']->style
                );
            }
            if ($config['shortcodes']) {
                $break = self::pushNotes($break);
                $shortcodes = $this->interpretShortcodes($break);
                $break = $shortcodes['content'];
                $break = SmartyPants::defaultTransform($break);
                if (isset($shortcodes['props']['class'])) {
                    $config['class'] = $shortcodes['props']['class'];
                }
                if (isset($shortcodes['hide'])) {
                    $hide = true;
                }
                if (isset($shortcodes['props']['styles'])) {
                    $config['styles'] = array_merge(
                        $config['styles'],
                        $shortcodes['props']['styles']
                    );
                }
            }
            if (isset($page['header']->fontscale) && $page['header']->fontscale == true) {
                $config['class'] .= ' fontscale';
            }
            if (isset($page['header']->class) && !empty($page['header']->class)) {
                foreach ($page['header']->class as $item) {
                    $config['class'] .= ' ' . $item;
                }
            }
            if (strpos($break, '<p>+++</p>') !== false) {
                $fragments = explode('<p>+++</p>', $break);
                $this->buildFragments($page, $config, $fragments);
            } elseif ($hide !== true) {
                $this->buildSlide($page, $config, $break);
            }
            $index++;
        }
        echo '</section>';
    }

    /**
     * Create HTML for slides
     *
     * @param object $page   Grav Page-instance
     * @param array  $config Plugin- and slide-configuration
     * @param array  $break  Slides from Markdown
     *
     * @return void
     */
    public function buildSlide($page, $config, $break)
    {
        echo '<section id="' . $config['id'] . '" ';
        echo 'class="' . $config['class'] . '" ';
        echo 'data-title="' . $page['title'] . '"';
        if (!empty($config['styles'])) {
            echo ' style="';
            echo self::inlineStyles($config['styles'], $config['route']);
            echo '"';
        }
        if (isset($page['header']->textsize['scale'])) {
            echo ' data-textsize-scale="' . $page['header']->textsize['scale'] . '"';
            if (isset($page['header']->textsize['header']) && is_int($page['header']->textsize['header'])) {
                echo ' data-textsize-header="' . $page['header']->textsize['header'] . '"';
            }
            if (isset($page['header']->textsize['text']) && is_int($page['header']->textsize['text'])) {
                echo ' data-textsize-text="' . $page['header']->textsize['text'] . '"';
            }
        }
        if ($config['fontscale'] == true || isset($page['header']->fontscale)) {
            if (isset($page['header']->fontratio)) {
                $config['fontratio'] = $page['header']->fontratio;
            }
            echo ' data-fontratio="' . $config['fontratio'] . '"';
        }
        echo '>';
        echo str_replace('<p></p>', '', $break);
        if (isset($page['footer'])) {
            echo $page['footer'];
        }
        echo '</section>';
    }
    
    /**
     * Create HTML for fragments
     *
     * @param object $page      Grav Page-instance
     * @param array  $config    Plugin- and slide-configuration
     * @param array  $fragments Fragments from Markdown
     *
     * @return void
     */
    public function buildFragments($page, $config, $fragments)
    {
        echo '<section id="' . $config['id'] . ' ';
        echo 'class="' . $config['class'] . '" ';
        echo 'data-title="' . $page['title'] . '">';
        foreach ($fragments as $fragment) {
            echo '<span class="fragment fade-in-then-out">';
            echo $fragment;
            echo '</span>';
        }
        echo '</section>';
    }

    /**
     * Parse shortcodes
     *
     * @param string $content Markdown content in Page
     *
     * @return array Processed contents and properties
     */
    public function interpretShortcodes($content)
    {
        $return = array();
        $re = '~((?:\[\s*(?<name>[a-zA-Z0-9-_]+)\s*(?:\=\s*(?<bbCode>\"(?:[^\"\\\\]*(?:\\\\.[^\"\\\\]*)*)\"|((?:(?!=\s*|\]|\/\])[^\s])+)))?\s*(?<parameters>(?:\s*(?:\w+(?:\s*\=\s*\"(?:[^\"\\\\]*(?:\\\\.[^\"\\\\]*)*)\"|\s*\=\s*((?:(?!=\s*|\]|\/\])[^\s])+)|(?=\s|\]|\/\s*\]|$))))*)\s*(?:\](?<content>.*?)\[\s*(?<markerContent>\/)\s*(\k<name>)\s*\]|\]|(?<marker>\/)\s*\])))~u';
        preg_match_all($re, $content, $matches, PREG_SET_ORDER, 0);
        if (!empty($matches)) {
            foreach ($matches as $match) {
                $name = $match['name'];
                $value = $match['bbCode'];
                $content = str_replace($match[0], '', $content);
                if ($name == 'class') {
                    $return['class'] = $value;
                } elseif ($name == 'hide') {
                    $return['hide'] = true;
                } else {
                    $return['styles'][$name] = $value;
                }
            }
        }
        return ['content' => $content, 'props' => $return];
    }

    /**
     * Create HTML from Notes-shortcodes
     *
     * @param string $content Markdown content in Page
     *
     * @return string Processed content
     */
    public function pushNotes($content)
    {
        $content = str_replace('[notes]', '<aside class="notes">', $content);
        $content = str_replace('[/notes]', '</aside>', $content);
        return $content;
    }

    /**
     * Generate menu with anchors and titles from pages
     *
     * @param array $tree Page-structure with children
     *
     * @return array Slide-anchors with titles
     */
    public function buildMenu($tree)
    {
        $items = array();
        foreach ($tree as $key => $value) {
            if (is_array($value['menu'])) {
                $items[$value['menu']['anchor']] = $value['menu']['title'];
            }
            if (isset($value['children'])) {
                $items[] = $this->buildMenu($value['children']);
            }
        }
        return $items;
    }

    /**
     * Process styles
     *
     * @param array  $styles List of key-value pairs
     * @param string $route  Route to Page for relative assets
     *
     * @return string Processed styles, in inline string
     */
    public static function inlineStyles(array $styles, string $route)
    {
        $return = '';
        foreach ($styles as $property => $value) {
            if ($property == 'background-image') {
                $return .= $property . ': url(' . $route . '/' . $value . ');';
            } else {
                $return .= $property . ': ' . $value . ';';
            }
        }
        return $return;
    }

    /**
     * Format styles for inlining
     *
     * @param array  $styles Array of quote-enclosed properties and values
     * @param string $mode   'background' or 'font'
     *
     * @return string CSS-styles
     *
     * @deprecated 0.0.3 Needs backporting
     */
    public function applyStyles($styles, $mode = 'background')
    {
        if (empty($styles)) {
            return false;
        }
        if (isset($config['color_function'])) {
            $function = $config['color_function'];
        } else {
            $function = '50';
        }

        if ($mode == 'text') {
            if (array_key_exists('color', $styles)) {
                return $styles['color'];
            } elseif (array_key_exists('background', $styles)) {
                if ($function == '50') {
                    return $this->getContrast50($styles['background']);
                } elseif ($function == 'YIQ') {
                    return $this->getContrastYIQ($styles['background']);
                }
            } else {
                return false;
            }
        } elseif ($mode == 'background') {
            if (array_key_exists('background', $styles)) {
                return $styles['background'];
            } elseif (array_key_exists('color', $styles)) {
                if ($function == '50') {
                    return $this->getContrast50($styles['color']);
                } elseif ($function == 'YIQ') {
                    return $this->getContrastYIQ($styles['color']);
                }
            } else {
                return false;
            }
        }
        /*foreach ($styles as $key => $value) {
            // If background is defined, and color is not, try to find a suitable contrast
            if (array_key_exists('background', $styles) && !array_key_exists('color', $styles)) {
                if (isset($config['color_function'])) {
                    if ($config['color_function'] == '50') {
                        $color = $this->getContrast50($styles['background']);
                    } elseif ($config['color_function'] == 'YIQ') {
                        $color = $this->getContrastYIQ($styles['background']);
                    }
                } else {
                    $color = $this->getContrast50($styles['background']);
                }
                $return .= 'color: ' . $color . ';';
            }
            $return .= $key . ': ' . $value . ';';
        }
        return $return;*/
    }

    /**
     * Remove wrapping paragraph from img-element
     *
     * @param string  $content Markdown content in Page
     * @param boolean $figure  Optional wrapping in figure-element
     *
     * @return string Processed content
     */
    public static function unwrapImage($content, $figure = false)
    {
        $unwrap = self::REGEX_IMG_P;
        $content = preg_replace($unwrap, "$1", $content);
        if ($figure) {
            $wrap = self::REGEX_IMG;
            $content = preg_replace(
                $wrap,
                '<figure role="group" $2>$1</figure>',
                $content
            );
            $title = self::REGEX_IMG_TITLE;
            $content = preg_replace(
                $title,
                "$0<figcaption>$1</figcaption>",
                $content
            );
        }
        return $content;
    }
}
