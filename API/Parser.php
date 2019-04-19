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

use Grav\Common\Uri;
use Grav\Common\Utils;
use Grav\Plugin\PresentationPlugin\Utilities;

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
     * @param array     $config    Plugin configuration
     * @param Transport $transport Transport API
     */
    public function __construct($config, $transport)
    {
        $this->config = $config;
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
                if (!Uri::isValidUrl($value)) {
                    $locations = array(
                        '',
                        'user/pages',
                        'user/pages/images',
                    );
                    $locations = Utilities::explodeFileLocations($locations, GRAV_ROOT, '/', '/');
                    $file = Utilities::fileFinder($value, $locations);
                    $file = str_ireplace(GRAV_ROOT, '', $file);
                    $value = $this->config['base_url'] . $file;
                }
                $inline .= $property . ': url(' . $value . ');';
            } elseif (Utils::startsWith($property, 'data')) {
                $data .= ' ' . $property . '="' . $value . '"';
                if ($property == 'data-textsize-scale') {
                    $this->transport->setClass($id, 'textsizing');
                }
                if ($property == 'data-background-iframe') {
                    $data .= ' data-background-interactive';
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
        return array(
            'style' => $inline,
            'data' => $data
        );
    }

    /**
     * Set modular scales in CSS
     *
     * @param string $id       Slide id-attribute
     * @param string $scale    Modular Scale Ratio
     * @param float  $modifier Optional multiplication-parameter
     *
     * @return void
     */
    public function setModularScale(string $id, string $scale, float $modifier = null)
    {
        $scale = (float) $scale;
        $steps = array(6, 5, 4, 3, 2, 1, 0);
        for ($i = 1; $i <= 6; $i++) {
            $value = self::modularScale($steps[$i], 16, $scale, true);
            $value = $modifier != null ? $value * $modifier : $value;
            $this->transport->setStyle($id, '{font-size:' . $value . 'em;}', 'h' . $i);
        }
    }

    /**
     * Get font-size in pixels
     *
     * @param integer $step     Step in scale
     * @param integer $base     Base font-size
     * @param float   $ratio    Rhythm
     * @param bool    $relative Output relative units
     *
     * @return float Modular Scale breakpoint
     */
    public static function modularScale(int $step, int $base, float $ratio, bool $relative = null)
    {
        if ($relative == true) {
            return round((pow($ratio, $step) * $base) / $base, 3);
        } else {
            return round((pow($ratio, $step) * $base), 2);
        }
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
