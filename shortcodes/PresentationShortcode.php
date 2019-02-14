<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

/**
 * Presentation Plugin Shortcode
 *
 * Generate iframe from shortcode
 *
 * @category Extensions
 * @package  Grav\Plugin\PresentationPlugin\API
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-presentation
 */
class PresentationShortcode extends Shortcode
{
    /**
     * Create shortcode
     *
     * @return void
     */
    public function init()
    {
        $this->shortcode->getHandlers()->add(
            'presentation',
            function (ShortcodeInterface $sc) {
                $src = $sc->getParameter('src', $sc->getContent());
                return '<iframe src="' . $src . '" class="presentation-iframe" frameborder="0" allowfullscreen></iframe>';
            }
        );
    }
}
