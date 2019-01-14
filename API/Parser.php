<?php
/**
 * Presentation Plugin, Parser API
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

/**
 * Parser API
 *
 * Parser API for parsing content
 *
 * @category Extensions
 * @package  Grav\Plugin\PresentationPlugin\API
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-presentation
 */
class Parser implements ParserInterface
{
    /**
     * Regular expressions
     */
    const REGEX_FRAGMENT_SHORTCODE = '~\[fragment=*([a-zA-Z-]*)\](.*)\[\/fragment\]~im';
    const REGEX_SHORTCODES = '~((?:\[\s*(?<name>[a-zA-Z0-9-_]+)\s*(?:\=\s*(?<bbCode>\"(?:[^\"\\\\]*(?:\\\\.[^\"\\\\]*)*)\"|((?:(?!=\s*|\]|\/\])[^\s])+)))?\s*(?<parameters>(?:\s*(?:\w+(?:\s*\=\s*\"(?:[^\"\\\\]*(?:\\\\.[^\"\\\\]*)*)\"|\s*\=\s*((?:(?!=\s*|\]|\/\])[^\s])+)|(?=\s|\]|\/\s*\]|$))))*)\s*(?:\](?<content>.*?)\[\s*(?<markerContent>\/)\s*(\k<name>)\s*\]|\]|(?<marker>\/)\s*\])))~u';
    const REGEX_MEDIA_P = '/<p>\s*(<a .*>\s*<img.*\s*<\/a>|\s*<img.*|<img.*\s*|<video.*|<audio.*)\s*<\/p>/i';

    /**
     * Instantiate Parser API
     *
     * @param Transport $transport Transport API
     */
    public function __construct($transport)
    {
        $this->transport = $transport;
    }

    /**
     * Parse shortcodes
     *
     * @param string $content Markdown content in Page
     * @param string $id      Slide id-attribute
     *
     * @return array Processed contents and properties
     */
    public function interpretShortcodes(string $content, string $id)
    {
        $return = array();
        preg_match_all(
            self::REGEX_SHORTCODES,
            $content,
            $matches,
            PREG_SET_ORDER,
            0
        );
        if (!empty($matches)) {
            foreach ($matches as $match) {
                $name = $match['name'];
                $value = $match['bbCode'];
                $content = str_replace($match[0], '', $content);
                if (Utils::startsWith($name, 'class')) {
                    $return['class'] = $value;
                } elseif (Utils::startsWith($name, 'hide')) {
                    $return['hide'] = true;
                } elseif (Utils::startsWith($name, 'style')) {
                    $name = str_replace('style-', '', $name);
                    $return['styles'][$name] = $value;
                } elseif (Utils::startsWith($name, 'data')) {
                    $return['styles'][$name] = $value;
                }
            }
        }
        return ['content' => $content, 'props' => $return];
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
     * Process styles and data-attributes
     *
     * @param array  $styles List of key-value pairs
     * @param string $route  Route to Page for relative assets
     * @param string $id     Slide id-attribute
     *
     * @return string Processed styles, in inline string
     */
    public function processStylesData(array $styles, string $route, string $id)
    {
        $inline = $data = '';
        foreach ($styles as $property => $value) {
            if ($property == 'background-image') {
                $inline .= $property . ': url(' . $route . '/' . $value . ');';
            } elseif (Utils::startsWith($property, 'data')) {
                $data .= ' ' . $property . '="' . $value . '"';
                if ($property == 'data-textsize-scale') {
                    $this->transport->setClass($id, 'textsizing');
                }
            } elseif ($property == 'header-font-family') {
                $this->transport->setStyle($id, "{\nfont-family:$value;\n}", 'h1,h2,h3,h4,h5,h6');
            } elseif ($property == 'header-color') {
                $this->transport->setStyle($id, "{\ncolor:$value;\n}", 'h1,h2,h3,h4,h5,h6');
            } elseif ($property == 'block-font-family') {
                $this->transport->setStyle($id, "{\nfont-family:$value;\n}");
            } elseif ($property == 'block-color') {
                $this->transport->setStyle($id, "{\ncolor:$value;\n}");
            } else {
                $inline .= $property . ': ' . $value . ';';
            }
        }
        return ' style="' . $inline . '"' . $data;
    }

    /**
     * Remove wrapping paragraph from img-element
     *
     * @param string  $content Markdown content in Page
     * @param boolean $figure  Optional wrapping in figure-element
     *
     * @return string Processed content
     */
    public static function unwrapImage(string $content)
    {
        $unwrap = self::REGEX_MEDIA_P;
        $content = preg_replace($unwrap, "$1", $content);
        return $content;
    }
}
