<?php
/**
 * Presentation Plugin
 *
 * PHP version 7
 *
 * @category   Extensions
 * @package    Grav
 * @subpackage Presentation
 * @author     Ole Vik <git@olevik.net>
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 * @link       https://github.com/OleVik/grav-plugin-presentation
 */
namespace Grav\Plugin;

use Grav\Common\Grav;
use Grav\Common\Utils;
use Grav\Common\Plugin;
use Grav\Common\Inflector;
use Grav\Common\Page\Page;
use Grav\Common\Page\Pages;
use Grav\Common\Page\Media;
use Grav\Common\Page\Collection;
use RocketTheme\Toolbox\Event\Event;

use Grav\Plugin\PresentationPlugin\API\Content;
use Grav\Plugin\PresentationPlugin\API\Poll;
use Grav\Plugin\PresentationPlugin\Utilities;

/**
 * Creates slides using Reveal.js
 *
 * Class PresentationPlugin
 *
 * @category Extensions
 * @package  Grav\Plugin
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-presentation
 */
class PresentationPlugin extends Plugin
{

    /**
     * Grav cache setting
     *
     * @var [type]
     */
    protected $cache;
    protected $route = 'presentationapi';

    /**
     * Register intial event
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }

    /**
     * Declare config from plugin-config
     *
     * @return array Plugin configuration
     */
    public function config()
    {
        $pluginsobject = (array) $this->config->get('plugins');
        if (isset($pluginsobject) && $pluginsobject['presentation']['enabled']) {
            $config = $pluginsobject['presentation'];
        } else {
            return;
        }
        return $config;
    }

    /**
     * Initialize the plugin and events
     *
     * @param Event $event RocketTheme events
     *
     * @return void
     */
    public function onPluginsInitialized(Event $event)
    {
        if ($this->isAdmin()) {
            $this->enable(
                [
                    'onGetPageTemplates' => ['onGetPageTemplates', 0]
                ]
            );
        }
        $this->grav['config']->set('system.cache.enabled', false);
        $this->enable(
            [
                'onPageContentProcessed' => ['pageIteration', 0],
                'onTwigExtensions' => ['onTwigExtensions', 0],
                'onTwigTemplatePaths' => ['templates', 0],
                'onPagesInitialized' => ['onPagesInitialized', 0],
                'onShutdown' => ['onShutdown', 0]
            ]
        );
    }

    /**
     * Handle Poll API
     *
     * @return void
     */
    public function onPagesInitialized()
    {
        $uri = $this->grav['uri'];
        $page = $this->grav['page'];
        $url = $page->url(true, true, true);
        $config = $this->config();
        if ($config['sync'] == 'poll' && $uri->path() == '/' . $this->route) {
            set_time_limit(0);
            header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
            header('Pragma: no-cache');
            if (!isset($_GET['mode'])) {
                header('HTTP/1.1 400 Bad Request');
                exit('400 Bad Request');
            }
            $res = Grav::instance()['locator'];
            $target = $res->findResource('cache://') . '/presentation';
            include_once __DIR__ . '/API/Poll.php';
            $poll = new Poll($target, 'Poll.json');
            if ($_GET['mode'] == 'set' && isset($_GET['data'])) {
                $poll->remove();
                header('Content-Type:text/plain');
                header('HTTP/1.1 202 Accepted');
                $poll->set(urldecode($_GET['data']));
            } elseif ($_GET['mode'] == 'get') {
                header('Content-Type: application/json');
                header('HTTP/1.1 200 OK');
                $poll->get();
            } elseif ($_GET['mode'] == 'remove') {
                header('Content-Type:text/plain');
                header('HTTP/1.1 200 OK');
                $poll->remove();
            }
            unset($poll);
            clearstatcache();
            exit();
        }
    }

    /**
     * Construct the page
     *
     * @return void
     */
    public function pageIteration()
    {
        $grav = $this->grav;
        $config = $this->config();
        include_once __DIR__ . '/API/Content.php';
        include_once __DIR__ . '/Utilities.php';
        if ($config['enabled'] && $grav['page']->template() == 'presentation') {
            if (!isset($this->grav['twig']->twig_vars['reveal_init'])) {
                $content = new Content($grav, $config);
                $tree = $content->buildTree($grav['page']->route());
                // dump($tree);
                $slides = $content->buildContent($tree);
                $grav['page']->setRawContent($slides);
                $menu = $content->buildMenu($tree);
                $menu = Utilities::flattenArray($menu, 1);
                $options = Utilities::parseAmbiguousArrayValues($config['options']);
                $options = json_encode($options, JSON_PRETTY_PRINT);
                $this->grav['twig']->twig_vars['reveal_init'] = $options;
                $this->grav['twig']->twig_vars['presentation_menu'] = $options;
            }
        }
    }

    /**
     * Add templates-directory to Twig paths
     *
     * @return void
     */
    public function templates()
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
    }

    /**
     * Reset cache on shutdown
     *
     * @return void
     */
    public function onShutdown()
    {
        $this->grav['config']->set('system.cache.enabled', $this->cache);
    }

    /**
     * Add Twig Extensions
     *
     * @return void
     */
    public function onTwigExtensions()
    {
        include_once __DIR__ . '/twig/CallStaticExtension.php';
        $this->grav['twig']->twig->addExtension(new CallStaticTwigExtension());
        include_once __DIR__ . '/twig/FileFinderExtension.php';
        $this->grav['twig']->twig->addExtension(new FileFinderTwigExtension());
    }

    /**
     * Register Page templates
     *
     * @param RocketTheme\Toolbox\Event\Event $event Event hooked into
     *
     * @return void
     */
    public function onGetPageTemplates($event)
    {
        $types = $event->types;
        $locator = Grav::instance()['locator'];
        $path = $locator->findResource('plugin://' . $this->name . '/blueprints');
        $types->scanBlueprints($path);
    }

    /**
     * Get list of modular scales
     *
     * @return array List of modular scales
     */
    public static function getModularScale()
    {
        return array(
            ['name' => 'minor second', 'ratio' => '15:16', 'numerical' => 1.067],
            ['name' => 'major second', 'ratio' => '8:9', 'numerical' => 1.125],
            ['name' => 'minor third', 'ratio' => '5:6', 'numerical' => 1.2],
            ['name' => 'major third', 'ratio' => '4:5', 'numerical' => 1.25],
            ['name' => 'perfect fourth', 'ratio' => '3:4', 'numerical' => 1.333],
            ['name' => 'aug. fourth / dim. fifth', 'ratio' => '1:√2', 'numerical' => 1.414],
            ['name' => 'perfect fifth', 'ratio' => '2:3', 'numerical' => 1.5],
            ['name' => 'minor sixth', 'ratio' => '5:8', 'numerical' => 1.6],
            ['name' => 'golden section', 'ratio' => '1:1.618', 'numerical' => 1.618],
            ['name' => 'major sixth', 'ratio' => '3:5', 'numerical' => 1.667],
            ['name' => 'minor seventh', 'ratio' => '9:16', 'numerical' => 1.778],
            ['name' => 'major seventh', 'ratio' => '8:15', 'numerical' => 1.875],
            ['name' => 'octave', 'ratio' => '1:2', 'numerical' => 2],
            ['name' => 'major tenth', 'ratio' => '2:5', 'numerical' => 2.5],
            ['name' => 'major eleventh', 'ratio' => '3:8', 'numerical' => 2.667],
            ['name' => 'major twelfth', 'ratio' => '1:3', 'numerical' => 3],
            ['name' => 'double octave', 'ratio' => '1:4', 'numerical' => 4]
        );
    }

    /**
     * Parse modular scales for blueprints
     *
     * @return array Blueprint-friendly list of modular scales
     */
    public static function getModularScaleBlueprintOptions()
    {
        $options = ['' => 'None'];
        foreach (self::getModularScale() as $scale) {
            $options[(string) $scale['numerical']] = ucwords($scale['name']) . ' (' . $scale['ratio'] . ')';
        }
        return $options;
    }


    /**
     * Get reveal.js themes
     *
     * @return array Associative array of styles
     */
    public static function getRevealThemes()
    {
        $inflector = new Inflector();
        $themes = array('none' => 'None');
        include_once 'Utilities.php';
        $path = 'user://plugins/presentation/node_modules/reveal.js/css/theme';
        $location = Grav::instance()['locator']->findResource($path, true);
        $files = Utilities::filesFinder($location, ['css']);
        foreach ($files as $file) {
            $key = $file->getBasename('.' . $file->getExtension());
            $themes[$key] = $inflector->titleize($key);
        }
        return $themes;
    }
}
