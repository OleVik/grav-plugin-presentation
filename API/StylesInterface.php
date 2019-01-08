<?php
/**
 * Presentation Plugin, Styles API Interface
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
 * Styles API Interface
 *
 * Styles API Interface for setting or getting styles
 *
 * @category Extensions
 * @package  Grav\Plugin\PresentationPlugin\API
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-presentation
 */
interface StylesInterface
{
    /**
     * Set style
     *
     * @param string $id       Slide id-attribute
     * @param string $style    CSS Style
     * @param string $elements Elements to iterate through
     *
     * @return void
     */
    public function setStyle(string $id, string $style, string $elements);

    /**
     * Get styles
     *
     * @param string $id Slide id-attribute
     *
     * @return string Styles for slide
     */
    public function getStyle(string $id);

    /**
     * Get styles
     *
     * @return string Aggregated styles
     */
    public function getStyles();
}
