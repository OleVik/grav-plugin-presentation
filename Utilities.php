<?php
/**
 * Presentation Plugin, Utilities
 *
 * PHP version 7
 *
 * @category   API
 * @package    Grav\Plugin\PresentationPlugin
 * @subpackage Grav\Plugin\PresentationPlugin\Utilities
 * @author     Ole Vik <git@olevik.net>
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 * @link       https://github.com/OleVik/grav-plugin-presentation
 */

namespace Grav\Plugin\PresentationPlugin;

use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\Page\Page;
use Grav\Common\Page\Collection;
use Michelf\SmartyPants;

/**
 * Utilities
 *
 * @category Extensions
 * @package  Grav\Plugin\PresentationPlugin
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-presentation
 */
class Utilities
{
    const REGEX_IMG = "/(<img(?:(\s*(class)\s*=\s*\x22([^\x22]+)\x22*)+|[^>]+?)*>)/";
    const REGEX_IMG_P = "/<p>\s*?(<a .*<img.*<\/a>|<img.*)?\s*<\/p>/";
    const REGEX_IMG_TITLE = "/<img[^>]*?title[ ]*=[ ]*[\"](.*?)[\"][^>]*?>/";
    const REGEX_IMG_WRAPPING_LINK = '/\[(?\'image\'\!.*)\]\((?\'url\'https?:\/\/.*)\)/';

    /**
     * Plugin configuration
     *
     * @var array
     */
    protected $config;

    /**
     * Instantiate Presentation Utilities
     *
     * @param array $config Plugin configuration
     */
    public function __construct($config)
    {
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
        $page = Grav::instance()['page'];
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
                $paths[$route]['footer'] = Grav::instance()['twig']->processTemplate($paths[$route]['footer'], ['page' => $page]);
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
        include_once __DIR__ . '/vendor/autoload.php';
        $return = '';
        foreach ($pages as $route => $page) {
            ob_start();
            $title = $page['title'];
            $slug = $page['slug'];
            $content = $page['content'];
            $content = $parsedown->text($content);
            $content = str_replace('<p></p>', '', $content);
            
            $styleIndex = 0;
            $styles = array();
            $config = $this->config;
            $fontScale = $config['fontscale'];
            $fontRatio = $config['fontratio'];
            if (isset($page['header']->Reveal)) {
                $config = array_merge($config, $page['header']->Reveal);
            }
            $breaks = explode('<hr />', $content);
            if (count($breaks) > 0) {
                echo '<section id="' . $slug . '" data-title="' . $title . '">';
                $index = 0;
                foreach ($breaks as $break) {
                    $id = $slug . '-' . $index;
                    $class = '';
                    $hide = false;
                    $styles = array();
                    if ($this->config['unwrap_images']) {
                        $break = self::unwrapImage($break);
                    }
                    if (isset($page['header']->style)) {
                        $styles = array_merge($styles, $page['header']->style);
                    }
                    if ($config['shortcodes']) {
                        $break = self::pushNotes($break);
                        $shortcodes = $this->interpretShortcodes($break);
                        $break = $shortcodes['content'];
                        $break = SmartyPants::defaultTransform($break);
                        if (isset($shortcodes['class'])) {
                            $class = $shortcodes['style']['class'];
                        }
                        if (isset($shortcodes['hide'])) {
                            $hide = true;
                        }
                        if (isset($shortcodes['props']['styles'])) {
                            $styles = array_merge($styles, $shortcodes['props']['styles']);
                        }
                    }
                    if (isset($page['header']->fontscale) && $page['header']->fontscale == true) {
                        $class .= ' fontscale';
                    }
                    if (isset($page['header']->class) && !empty($page['header']->class)) {
                        foreach ($page['header']->class as $item) {
                            $class .= ' ' . $item;
                        }
                    }
                    if (strpos($break, '<p>+++</p>') !== false) {
                        $fragments = explode('<p>+++</p>', $break);
                        echo '<section id="' . $id . ' class="' . $class . '" data-title="' . $title . '">';
                        foreach ($fragments as $fragment) {
                            echo '<span class="fragment fade-in">';
                            echo '<span class="fragment fade-out">';
                            echo $fragment;
                            // echo str_replace('<p></p>', '', $fragment);
                            echo '</span>';
                            echo '</span>';
                        }
                        echo '</section>';
                    } elseif ($hide !== true) {
                        echo '<section id="' . $id . '" class="' . $class . '" data-title="' . $title . '"';
                        // Grav::instance()['debugger']->addMessage($styles);
                        if (!empty($styles)) {
                            echo ' style="';
                            echo self::inlineStyles($styles, $route);
                            echo '"';
                        }
                        if (isset($page['header']->textsize['scale'])) {
                            echo ' data-textsize-scale="' .$page['header']->textsize['scale'] . '"';
                            if (isset($page['header']->textsize['header']) && is_int($page['header']->textsize['header'])) {
                                echo ' data-textsize-header="' .$page['header']->textsize['header'] . '"';
                            }
                            if (isset($page['header']->textsize['text']) && is_int($page['header']->textsize['text'])) {
                                echo ' data-textsize-text="' .$page['header']->textsize['text'] . '"';
                            }
                        }
                        if ($fontScale == true || isset($page['header']->fontscale)) {
                            if (isset($page['header']->fontratio)) {
                                $fontRatio = $page['header']->fontratio;
                            }
                            echo ' data-fontratio="' . $fontRatio . '"';
                        }
                        echo '>';
                        echo str_replace('<p></p>', '', $break);
                        if (isset($page['footer'])) {
                            echo $page['footer'];
                        }
                        echo '</section>';
                    }
                    $index++;
                }
                echo '</section>';
            } else {
                echo '<section>';
                echo $content;
                if (isset($page['footer'])) {
                    echo $page['footer'];
                }
                echo '</section>';
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
            $content = preg_replace($wrap, '<figure role="group" $2>$1</figure>', $content);
            $title = self::REGEX_IMG_TITLE;
            $content = preg_replace($title, "$0<figcaption>$1</figcaption>", $content);
        }
        return $content;
    }

    /**
     * Search for a file in multiple locations
     *
     * @param string $file         Filename.
     * @param string $ext          File extension.
     * @param array  ...$locations List of paths.
     *
     * @return string
     */
    public static function fileFinder(string $file, string $ext, array ...$locations)
    {
        $return = false;
        foreach ($locations as $location) {
            if (file_exists($location . '/' . $file . $ext)) {
                $return = $location . '/' . $file . $ext;
                break;
            }
        }
        return $return;
    }

    /**
     * Search for files in multiple locations
     *
     * @param string $directory Folder-name.
     * @param string $types     File extensions.
     *
     * @return string
     */
    public static function filesFinder(string $directory, array $types)
    {
        $iterator = new \RecursiveDirectoryIterator(
            $directory,
            \RecursiveDirectoryIterator::SKIP_DOTS
        );
        $iterator = new \RecursiveIteratorIterator($iterator);
        $files = [];
        foreach ($iterator as $file) {
            if (in_array(pathinfo($file, PATHINFO_EXTENSION), $types)) {
                $files[] = $file;
            }
        }
        if (count($files) > 0) {
            return $files;
        } else {
            return false;
        }
    }

    /**
     * Find contrasting color from 50%-equation
     *
     * @param string $hexcolor Hexadecimal color-value
     *
     * @return string black|white
     *
     * @see https://24ways.org/2010/calculating-color-contrast
     */
    public function getContrast50($hexcolor)
    {
        return (hexdec($hexcolor) > 0xffffff/2) ? 'black':'white';
    }

    /**
     * Find contrasting color from YIQ-equation
     *
     * @param string $hexcolor Hexadecimal color-value
     *
     * @return string black|white
     *
     * @see https://24ways.org/2010/calculating-color-contrast
     */
    public function getContrastYIQ($hexcolor)
    {
        $r = hexdec(substr($hexcolor, 0, 2));
        $g = hexdec(substr($hexcolor, 2, 4));
        $b = hexdec(substr($hexcolor, 4, 6));
        $yiq = (($r*299)+($g*587)+($b*114))/1000;
        return ($yiq >= 128) ? 'black' : 'white';
    }

    /**
     * Flatten a multidimensional array to one dimension, optionally preserving keys
     *
     * @param array   $array        Array to flatten
     * @param integer $preserveKeys 0 to discard, 1 for strings only, 2 for all
     * @param array   $out          Internal parameter for recursion
     *
     * @return array Flattened array
     *
     * @see https://stackoverflow.com/a/7256477/603387
     */
    public function flattenArray($array, $preserveKeys = 0, &$out = array())
    {
        foreach ($array as $key => $child) {
            if (is_array($child)) {
                $out = $this->flattenArray($child, $preserveKeys, $out);
            } elseif ($preserveKeys + is_string($key) > 1) {
                $out[$key] = $child;
            } else {
                $out[] = $child;
            }
        }
        return $out;
    }

    /**
     * Insert string within string
     *
     * @param string $str    Original string
     * @param string $insert String to insert
     * @param int    $index  Position to insert to
     *
     * @return string Original string with new string inserted
     *
     * @see https://stackoverflow.com/a/30820401/603387
     */
    public function stringInsert($str, $insert, $index)
    {
        $str = substr($str, 0, $index) . $insert . substr($str, $index);
        return $str;
    }
}
