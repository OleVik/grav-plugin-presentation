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
     * @param string $id      Slide ID
     * @param array  $page    Page instance
     *
     * @return array Processed content and shortcodes
     */
    public function interpretShortcodes(string $content, string $id, array $page);
    
    /**
     * Process style
     *
     * @param string $id       Slide id-attribute
     * @param string $property CSS property name
     * @param string $value    CSS property value
     * @param array  $paths    Locations to search for asset in
     *
     * @return void
     */
    public function stylesProcessor(string $id, string $property, string $value, array $paths = []);

    /**
     * Remove wrapping paragraph from img-element
     *
     * @param string $content Markdown content in Page
     *
     * @return string Processed content
     */
    public static function unwrapImage(string $content);
}
