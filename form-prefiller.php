<?php
namespace Grav\Plugin;

use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\Uri;
use Grav\Common\Utils;
use RocketTheme\Toolbox\Event\Event;
use Symfony\Component\Yaml\Yaml;

/**
 * Class FormPrefillerPlugin
 * @package Grav\Plugin
 */
class FormPrefillerPlugin extends Plugin
{

    /**
     * Initialize the plugin
     *
     */
    public function onPluginsInitialized()
    {
        // Don't proceed if we are in the admin plugin
        if ($this->isAdmin()) {
            return;
        };

    }

    /**
     *  The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPageInitialized' => ['onPageInitialized', 0],
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
            'onTwigSiteVariables' => ['onTwigSiteVariables', 0],
        ];
    }

    /**
     * Check whether this plugin should act
     *
     * Act only if 'prefill' is in the form name
     *
     * @return bool $result
     *
     */
    public static function pluginAct()
    {
        $frontmatter = (array) Grav::instance()['page']->header();
        $result = false;

        if (isset($frontmatter['form']['name'])) {
            if (array_search('prefill', explode('.', $frontmatter['form']['name'])) !== false) {
                $result = true;
            }
        }

        return $result;
    }

    public function onPageInitialized()
    {
        if ($this->pluginAct()) {

            if (property_exists($this->grav['page']->header(), 'prefill_data')) {

                $prefill_data = $this->grav['page']->header()->prefill_data;
                $data = [];

                if (is_array($prefill_data)) {
                    foreach ($prefill_data as $file) {

                        if (file_exists($this->getFilePath($file))) {
                            $type = pathinfo($file)['extension'];
                            $key = basename($file, '.' . $type);

                            switch ($type) {
                                case 'yaml':
                                    $data[$key] = Yaml::parse(file_get_contents($file));
                                    break;
                                case 'json':
                                    $data[$key] = json_decode(file_get_contents($file), true);
                                    break;
                            }
                        }
                    }
                } else {
                    $file = $prefill_data;

                    if (file_exists($this->getFilePath($file))) {
                        $type = pathinfo($file)['extension'];

                        switch ($type) {
                            case 'yaml':
                                $data = Yaml::parse(file_get_contents($file));
                                break;
                            case 'json':
                                $data = json_decode(file_get_contents($file), true);
                                break;
                        }
                    }
                }

                // Add data to page header/frontmatter
                // Usage: {{ page.header.prefill_data.<file>.<var-in-dot-notation> }}
                $this->grav['page']->header()->prefill_data = $data;

                // BTW This data is added as Twig variables by the onTwigSiteVariables event
                // handler in this plugin as the Grav processing order prevents
                // doing that here
            }
        }
    }

    /**
     * Get file path
     *
     * @param string $filename
     *
     * @return string $path
     *
     */
    public function getFilePath($filename)
    {
        if (strpos($filename, '://') !== false) {
            $path = $this->grav['locator']->findResource($filename, true);
        } else {
            $path = $this->grav['page']->path() . DS . $filename;
        }

        return $path;
    }

    /**
     * Return value from page header/frontmatter
     *
     * To be used with the data-default@ form field option
     * Nested variables must be requested in dot notation
     *
     * @param string $key
     *
     * @return string|null $value
     *
     */
    public static function getFrontmatter($key)
    {
        if (self::pluginAct()) {
            $frontmatter = (array) Grav::instance()['page']->header();

            $value = Utils::getDotNotation($frontmatter, $key);

            return $value;
        } else {
            return null;
        }
    }
    /**
     * Return value of specified URL parameter
     *
     * To be used with the data-default@ form field option
     * Supports regular (?q=123) and Grav style (/q:123) parameters
     *
     * @param string $key
     *
     * @return string|null $value
     *
     */
    public static function getParameter($key)
    {
        if (self::pluginAct()) {

            $params = self::getUriParams();

            if (isset($params[$key])) {
                $value = $params[$key];

                return $value;
            } else {
                return null;
            }
        }
    }

    /**
     * Return value of specified Twig variable
     *
     * To be used with the data-default@ form field option
     *
     * @param string $var
     *
     * @return string|null $value
     *
     */
    public function getTwig($var)
    {
        if (self::pluginAct()) {
            $twig_vars = (array) Grav::instance()['twig']->twig_vars;

            $value = Utils::getDotNotation($twig_vars, $var);

            return $value;
        } else {
            return null;
        }
    }

    /**
     * Set Twig variables
     *
     */
    public function onTwigSiteVariables()
    {
        if (self::pluginAct()) {

            // Add URI parameters
            // Usage: {{ prefill_params.<var-in-dot-notation> }}
            $params = $this->getUriParams();
            unset($this->grav['twig']->twig_vars['prefill_params']);
            $this->grav['twig']->twig_vars['prefill_params'] = $params;

            // Add page header/frontmatter
            // Usage: {{ prefill_frontmatter.<var-in-dot-notation> }}
            $frontmatter = (array) Grav::instance()['page']->header();
            unset($this->grav['twig']->twig_vars['prefill_frontmatter']);
            $this->grav['twig']->twig_vars['prefill_frontmatter'] = $frontmatter;

            // Add data from external YAML or JSON file
            // Usage: {{ prefill_data.<file>.<var-in-dot-notation> }}
            $prefill_data = $this->grav['page']->header()->prefill_data;
            unset($this->grav['twig']->twig_vars['prefill_data']);
            $this->grav['twig']->twig_vars['prefill_data'] = $prefill_data;
        }
    }

    /**
     * Return URI parameters
     *
     * Supports regular (?q=123) and Grav style (/q:123) parameters
     * If a parameter is specified twice the Grav style one gets precedence
     *
     * @return array
     *
     */
    public function getUriParams()
    {

        $grav_params = Grav::instance()['uri']->extractParams(Grav::instance()['uri'], ':')[1];
        $query_params = Grav::instance()['uri']->query(null, true);

        return array_merge($query_params, $grav_params);
    }

}
