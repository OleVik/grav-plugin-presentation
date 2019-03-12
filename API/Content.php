<?php
/**
 * Presentation Plugin, Content API
 *
 * PHP version 7
 *
 * @category   API
 * @package    Grav\Plugin\PresentationPlugin
 * @subpackage Grav\Plugin\PresentationPlugin\API
 * @author     Ole Vik <git@olevik.net>
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 * @link       https://github.com/OleVik/grav-plugin-presentation
 */

namespace Grav\Plugin\PresentationPlugin\API;

use Grav\Common\Utils;
use Grav\Plugin\PresentationPlugin\API\Parser;
use Grav\Plugin\PresentationPlugin\API\Transport;
use Michelf\SmartyPants;

/**
 * Content API
 *
 * Content API for aggregating content
 *
 * @category Extensions
 * @package  Grav\Plugin\PresentationPlugin\API
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-presentation
 */
class Content implements ContentInterface
{
    /**
     * Regular expressions
     */
    const REGEX_FRAGMENT_SHORTCODE = '~\[fragment=*([a-zA-Z-]*)\](.*)\[\/fragment\]~im';
    const REGEX_P_EMPTY = '/<p[^>]*>\n* *<\/p[^>]*>/im';

    /**
     * Instantiate Content API
     *
     * @param Grav      $grav      Grav-instance
     * @param array     $config    Plugin configuration
     * @param Parser    $parser    Parser utility
     * @param Transport $transport Transport API
     */
    public function __construct($grav, $config, $parser, $transport)
    {
        $this->grav = $grav;
        $this->config = $config;
        $this->parser = $parser;
        $this->transport = $transport;
        $this->parsedown = new \Parsedown();
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
    public function buildTree(string $route, $mode = false, $depth = 0)
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
    public function buildContent(array $pages)
    {
        include_once __DIR__ . '/../vendor/autoload.php';
        $return = '';
        foreach ($pages as $route => $page) {
            ob_start();
            $styleIndex = 0;
            $styles = array();
            $config = $this->config;
            $config['route'] = $route;
            $content = $page['content'];
            if (preg_match(self::REGEX_FRAGMENT_SHORTCODE, $content)) {
                $content = $this->processFragments($content);
            }
            $content = $this->parsedown->text($content);
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
     * @param array $page   Grav Page-instance
     * @param array $config Plugin- and slide-configuration
     * @param array $breaks Slides from Markdown
     *
     * @return void
     */
    public function breakContent(array $page, array $config, array $breaks)
    {
        $header = (array) $page['header'];
        $horizontal = false;
        if (isset($config['horizontal'])) {
            $horizontal = $config['horizontal'];
        }
        if (isset($header['horizontal'])) {
            $horizontal = $header['horizontal'];
        }
        if ($horizontal != true) {
            echo '<section id="' . $page['slug'] . '" ';
            echo 'data-title="' . $page['title'] . '">';
        }
        $index = 0;
        foreach ($breaks as $break) {
            $config['id'] = $page['slug'] . '-' . $index;
            $config['class'] = '';
            $hide = false;
            $config['styles'] = array();
            if ($this->config['unwrap_images']) {
                $break = $this->parser::unwrapImage($break);
            }
            if (isset($page['header']->style)) {
                $config['styles'] = array_merge(
                    $config['styles'],
                    $page['header']->style
                );
            }
            if (isset($config['textsizing'])) {
                if (isset($config['textsize']['scale'])) {
                    $config['class'] .= ' textsizing';
                    $scale = $config['textsize']['scale'];
                }
                if (isset($page['header']->textsize['scale'])) {
                    $config['class'] .= ' textsizing';
                    $scale = $page['header']->textsize['scale'];
                }
                if (isset($config['textsize']['modifier'])) {
                    $modifier = (float) $config['textsize']['modifier'];
                }
                if (isset($page['header']->textsize['modifier'])) {
                    $modifier = (float) $page['header']->textsize['modifier'];
                }
                $this->parser->setModularScale(
                    $config['id'],
                    $scale ?? 1,
                    $modifier ?? 1
                );
            }
            if (isset($page['header']->class) && !empty($page['header']->class)) {
                foreach ($page['header']->class as $item) {
                    $config['class'] .= ' ' . $item;
                }
            }
            if ($hide !== true) {
                if ($horizontal == true) {
                    echo '<section>';
                }
                $this->buildSlide($page, $config, $break);
                if ($horizontal == true) {
                    echo '</section>';
                }
            }
            $index++;
        }
        if ($horizontal != true) {
            echo '</section>';
        }
    }

    /**
     * Create HTML for slides
     *
     * @param array  $page   Grav Page-instance
     * @param array  $config Plugin- and slide-configuration
     * @param string $break  Slides from Markdown
     *
     * @return void
     */
    public function buildSlide(array $page, array $config, string $break)
    {
        if ($config['shortcodes']) {
            $break = self::pushNotes($break);
            $shortcodes = $this->parser->interpretShortcodes($break, $config['id']);
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
        if (!empty($config['styles'])) {
            $processed = $this->parser->processStylesData(
                $config['styles'],
                $config['route'],
                $config['id']
            );
            $inline = ' style="' . $processed['style'] . '"' . $processed['data'];
        }
        if ($this->transport->getClasses($config['id'])) {
            $config['class'] .= ' ' . $this->transport->getClasses($config['id']);
        }
        echo '<section id="' . $config['id'] . '" ';
        echo 'class="' . $config['class'] . '" ';
        echo 'data-title="' . $page['title'] . '"';
        if (!empty($inline)) {
            echo $inline;
        }
        if (isset($page['header']->textsize['scale'])) {
            echo ' data-textsize-scale="' . (float) $page['header']->textsize['scale'] . '"';
            if (isset($page['header']->textsize['modifier'])) {
                echo ' data-textsize-modifier="' . (float) $page['header']->textsize['modifier'] . '"';
            }
        }
        if (!empty($this->transport->getAriaAttributes($config['id']))) {
            echo $this->transport->getAriaAttributes($config['id']);
        }
        if (!empty($this->transport->getDataAttributes($config['id']))) {
            echo $this->transport->getDataAttributes($config['id']);
        }
        echo '>';
        $break = preg_replace(self::REGEX_P_EMPTY, '', $break);
        echo $break;
        if (isset($page['footer'])) {
            echo $page['footer'];
        }
        echo '</section>';
    }

    /**
     * Create HTML for fragments
     *
     * @param string $content Markdown content in Page
     *
     * @return string Processed contents
     */
    public function processFragments(string $content)
    {
        $content = preg_replace(
            self::REGEX_FRAGMENT_SHORTCODE,
            '<span class="fragment \\1">\\2</span>',
            $content
        );
        return $content;
    }

    /**
     * Create HTML from Notes-shortcodes
     *
     * @param string $content Markdown content in Page
     *
     * @return string Processed content
     */
    public function pushNotes(string $content)
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
    public function buildMenu(array $tree)
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
}
