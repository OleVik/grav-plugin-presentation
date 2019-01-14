<?php
/**
 * Presentation Plugin, Parser API Interface
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

/**
 * Parser API Interface
 *
 * Parser API Interface for parsing content
 *
 * @category Extensions
 * @package  Grav\Plugin\PresentationPlugin\API
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-presentation
 */
interface ParserInterface
{
    /**
     * Parse shortcodes
     *
     * @param string $content Markdown content in Page
     * @param string $id      Slide id-attribute
     *
     * @return array Processed contents and properties
     */
    public function interpretShortcodes(string $content, string $id);

    /**
     * Create HTML for fragments
     *
     * @param string $content Markdown content in Page
     *
     * @return string Processed contents
     */
    public function processFragments(string $content);
    
    /**
     * Process styles and data-attributes
     *
     * @param array  $styles List of key-value pairs
     * @param string $route  Route to Page for relative assets
     * @param string $id     Slide id-attribute
     *
     * @return string Processed styles, in inline string
     */
    public function processStylesData(array $styles, string $route, string $id);

    /**
     * Remove wrapping paragraph from img-element
     *
     * @param string $content Markdown content in Page
     *
     * @return string Processed content
     */
    public static function unwrapImage(string $content);
}
