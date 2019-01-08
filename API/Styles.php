<?php
/**
 * Presentation Plugin, Styles API
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
 * Styles API
 *
 * Styles API for setting or getting styles
 *
 * @category Extensions
 * @package  Grav\Plugin\PresentationPlugin\API
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-presentation
 */
class Styles implements StylesInterface
{
    /**
     * Placeholder for slide styles
     *
     * @var array
     */
    protected $styles;

    /**
     * Set style
     *
     * @param string $id       Slide id-attribute
     * @param string $style    CSS Style
     * @param string $elements Elements to iterate through
     *
     * @return void
     */
    public function setStyle(string $id, string $style, string $elements = null)
    {
        if ($elements) {
            $elements = explode(',', $elements);
            if (count($elements) > 1) {
                foreach ($elements as $element) {
                    $this->styles[$id][] = $element . ' ' . $style;
                }
            } else {
                $this->styles[$id][] = $elements[0] . ' ' .  $style;
            }
        } else {
            $this->styles[$id][] = $style;
        }
    }

    /**
     * Get styles
     *
     * @param string $id Slide id-attribute
     *
     * @return string Styles for slide
     */
    public function getStyle(string $id)
    {
        $return = '';
        foreach ($this->styles[$id] as $style) {
            $return .= '#' . $id . ' ' . $style . "\n";
        }
        return $return;
    }

    /**
     * Get styles
     *
     * @return string Aggregated styles
     */
    public function getStyles()
    {
        $return = '';
        if (empty($this->styles)) {
            return false;
        }
        foreach ($this->styles as $style => $values) {
            $return .= $this->getStyle($style) . "\n";
        }
        return $return;
    }
}
