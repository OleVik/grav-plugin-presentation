<?php
namespace Grav\Plugin;

use Grav\Common\Grav;
use Grav\Common\Utils;
use Grav\Common\Plugin;
use Grav\Common\Page\Page;
use Grav\Common\Page\Pages;
use Grav\Common\Page\Media;
use Grav\Common\Page\Collection;
use RocketTheme\Toolbox\Event\Event;

require __DIR__ . '/vendor/autoload.php';
use Michelf\SmartyPants;
require __DIR__ . '/API/Push.php';
use Grav\Plugin\PresentationPlugin\API\Push;

require 'Utilities.php';
use Presentation\Utilities;

/**
 * Creates slides using Reveal.js
 *
 * Class PresentationPlugin
 * 
 * @package Grav\Plugin
 * @return  void
 * @license MIT License by Ole Vik
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
        /*$userData = Grav::instance()['locator']->findResource('user://data/charts', true);
        $files = self::filesFinder($userData, ['json']);
        foreach ($files as $file) {
            $json = str_replace(
                array("\r\n", "\n", "\r"), 
                '',
                file_get_contents($file->getLinkTarget())
            );
            $output = 'var Grav = {Plugins: {Presentation: {' . pathinfo($file, PATHINFO_FILENAME) . ': ' . $json . '}}};';
            Grav::instance()['assets']->addInlineJs($output);
            Grav::instance()['assets']->addInlineJs('console.log(window.Grav);');
        }*/
    }

    /**
     * Push styles to via Assets Manager
     * 
     * @return void
     */
    public function onPagesInitialized()
    {
        $uri = $this->grav['uri'];
        $page = $this->grav['page'];
        $url = $page->url(true, true, true);
        $config = $this->config();
        // Grav::instance()['debugger']->addMessage($uri->path());
        if ($config['sync'] == 'api') {
            if ($uri->path() == '/' . $this->route) {
                header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
                header('Pragma: no-cache');
                if (!isset($_GET['mode'])) {
                    header('HTTP/1.1 400 Bad Request');
                    exit('HTTP/1.1 400 Bad Request');
                }
                $res = Grav::instance()['locator'];
                $target = $res->findResource('cache://') . '/presentation';
                $Push = new Push($target, 'PushCommand.json');
                if ($_GET['mode'] == 'set' && isset($_GET['command'])) {
                    $Push->set(urldecode($_GET['command']));
                } elseif ($_GET['mode'] == 'get') {
                    $Push->get();
                } elseif ($_GET['mode'] == 'remove') {
                    $Push->remove();
                } elseif ($_GET['mode'] == 'serve') {
                    $Push->serve();
                }
                exit();
            }
        }
        /*$userData = Grav::instance()['locator']->findResource('user://data/charts', false);
        $files = self::filesFinder($userData, ['js']);
        foreach ($files as $file) {
            Grav::instance()['assets']->addJs($userData . '/' . $file->getFilename(), 100, false, null, 'bottom');
        }*/
    }

    /**
     * Construct the page
     * 
     * @return void
     */
    public function pageIteration()
    {
        $page = $this->grav['page'];
        $config = $this->config();
        if ($config['enabled'] && $page->template() == 'presentation') {
            $utility = new Utilities($config);
            $tree = $utility->buildTree($page->route());
            $slides = $utility->buildContent($tree);
            $page->setRawContent($slides);
            // $menu = $utility->buildMenu($tree);
            // $menu = $utility->flattenArray($menu, 1);

            $options = json_encode($config['options'], JSON_PRETTY_PRINT);
            // $this->grav['debugger']->addMessage($options);
            $this->grav['twig']->twig_vars['reveal_init'] = $options;
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
     * Search for a file in multiple locations
     *
     * @param string $file         Filename.
     * @param string $ext          File extension.
     * @param array  ...$locations List of paths.
     * 
     * @return string
     */
    public static function fileFinder(String $file, String $ext, Array ...$locations)
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
     * @param string $directory    Filename.
     * @param string $types        File extension.
     * @param array  ...$locations List of paths.
     * 
     * @return string
     */
    public static function filesFinder(String $directory, Array $types)
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

    public function onGetPageTemplates($event)
    {
        $types = $event->types;
        $locator = Grav::instance()['locator'];
        $types->scanBlueprints($locator->findResource('plugin://' . $this->name . '/blueprints'));
    }

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

    public static function getModularScaleBlueprintOptions()
    {
        $options = ['' => 'None'];
        foreach (self::getModularScale() as $scale) {
            $options[(string) $scale['numerical']] = ucwords($scale['name']) . ' (' . $scale['ratio'] . ')';
        }
        return $options;
    }
}
