<?php
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Grav;
use Grav\Common\Language\Language;
use Grav\Common\Plugin;
use Grav\Common\Utils;
use Symfony\Component\Yaml\Yaml;

/**
 * Class FormPrefillerPlugin
 * @package Grav\Plugin
 */
class FormPrefillerPlugin extends Plugin
{
    // Store current page
    protected static $page;

    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => [
                ['autoload', 100000], // TODO: Remove when plugin requires Grav >=1.7
                ['onPluginsInitialized', 0]
            ]
        ];
    }

    /**
    * Composer autoload.
    *is
    * @return ClassLoader
    */
    public function autoload(): ClassLoader
    {
        return require __DIR__ . '/vendor/autoload.php';
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized()
    {
        // Don't proceed if we are in the admin plugin
        if ($this->isAdmin()) {
            return;
        }

        // Enable the main events we are interested in
        $this->enable([
            'onPageInitialized' => ['onPageInitialized', 0],
            'onPagesInitialized' => ['onPagesInitialized', 0],
            'onTwigTemplatePaths' => ['onTwigTemplatePaths', 0],
        ]);
    }

    /**
     * Store the page object for use in the static data-default@ call handler functions
     */
    public function onPageInitialized($event)
    {
        self::$page = $event['page'];
    }

    /**
     * 
     */
    public function onPagesInitialized()
    {

        if (property_exists($this->grav['page']->header(), 'prefill_data')) {

            $prefill_data = $this->grav['page']->header()->prefill_data;
            $data = [];

            if (is_array($prefill_data)) {
                foreach ($prefill_data as $file) {
                        $key = basename($file, '.' . pathinfo($file)['extension']);
                        $data[$key] = $this->getFileData($file);
                }
            } else {
                $file = $prefill_data;
                $data = $this->getFileData($file);
            }
            
            // Add data to page header/frontmatter
            // Usage: {{ page.header.prefill_data.<file>.<var-in-dot-notation> }}
            $this->grav['page']->header()->prefill_data = $data;

            // Add extra Twig variables to be used via `getTwig` call

            // Add any URL parameters
            // Usage: Regular (?q=123) or Grav style (/q:123) parameter
            // Note: Mixing styles is allowed; in case of conflicts the Grav
            // style parameter gets precedence over the regular style one
            $twig = Grav::instance()['twig'];
            $params = self::getUriParams();
            unset($twig->twig_vars['prefill_params']);
            $twig->twig_vars['prefill_params'] = $params;

            // Add page header/frontmatter
            // Usage: {{ prefill_frontmatter.<var-in-dot-notation> }}
            $frontmatter = (array) Grav::instance()['page']->header();
            unset($twig->twig_vars['prefill_frontmatter']);
            $twig->twig_vars['prefill_frontmatter'] = $frontmatter;

            // Add data from external YAML or JSON file
            // Usage: {{ prefill_data.<file>.<var-in-dot-notation> }}
            $prefill_data = Grav::instance()['page']->header()->prefill_data;
            unset($twig->twig_vars['prefill_data']);
            $twig->twig_vars['prefill_data'] = $prefill_data;

        }
    }

    /**
     * Get file path
     *
     * @param string $filename
     *
     * @return array $data
     * 
     */
    public function getFileData($file)
    {
        try {
            $data = null;

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
        } catch (\Exception $e) {
            $msg = 'FormPrefillerPlugin: Error reading data from "' . $file . '": ' . $e->getMessage();
            $this->grav['debugger']->addMessage($msg);
            $this->grav['log']->error($msg);
        }

        return $data;
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
    public static function getFrontmatter($key, $default = null)
    {
        if (!isset(self::$page)) {
            return null;
        }

        $frontmatter = (array) self::$page->header();

        $value = Utils::getDotNotation($frontmatter, $key);

        if ($value == null) {
            $msg = 'FormPrefillerPlugin: Warning: the function getFrontmatter returned the default value: "';
            if ($default == null) {
                $msg .= 'null';
            }
            else {
                $msg .= $default;
            }
            $msg .= '"';
            Grav::instance()['debugger']->addMessage($msg);
            return $default;
        } else {
            return $value;
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
    public static function getTwig($var, $default = null)
    {
        $twig_vars = (array) Grav::instance()['twig']->twig_vars;
        
        // Return requested value
        if ($var == strtoupper($var)) {
            /** @var Language $language */
            $language = Grav::instance()['language'];
            $value = $language->translate($var);

        } else {
            $value = Utils::getDotNotation($twig_vars, $var);
        }

        if ($value == null) {
            $msg = 'FormPrefillerPlugin: Warning: the function getTwig returned the default value: "';
            if ($default == null) {
                $msg .= 'null';
            }
            else {
                $msg .= $default;
            }
            $msg .= '"';
            Grav::instance()['debugger']->addMessage($msg);
            return $default;
        } else {
            return $value;
        }
    }

    /**
     * Return the result of processing a Twig template
     *
     * @param string $template
     * @param array $params
     *
     * @return mixed $value
     *
     */
    public static function getTwigRender($template, $params = null, $default = null)
    {
        if (!isset(self::$page)) {
            return null;
        }

        if (pathinfo($template, PATHINFO_EXTENSION) != 'twig') {
            $template .= '.twig';
        }

        $twig_vars = (array) Grav::instance()['twig']->twig_vars;

        $params = (array) $params;

        // Convert Twig variables to literals
        foreach ($params as $p_key => $param) {

            preg_match_all('/{{ *(.*?) *}}/m', $param, $matches);
            $vars = $matches[1];

            foreach ($vars as $key => $var) {
                $val = Utils::getDotNotation($twig_vars, $var);
                $param = str_replace($matches[0][$key], $val, $param);
            }
            $params[$p_key] = $param;
        }

        try {
            // Render the template and return the result
            $value = Grav::instance()['twig']->twig->render($template, array('params' => (array) $params));
        }
        catch (\Exception $e) {
            $msg = 'FormPrefillerPlugin: Template "' . basename($template) . '" can\'t be found';
            Grav::instance()['debugger']->addMessage($msg);
            Grav::instance()['log']->error($msg);
            return null;
        }

        // Check for a YAML type template (".yaml.twig")
        if (strpos(strtolower($template), '.yaml.twig') !== false) {
            // Try to parse YAML
            try {
                $value = YAML::parse($value);
            }
            catch (\Exception $e) {
                $msg = 'FormPrefillerPlugin: YAML Parser error when reading data from: "' . basename($template) . '": ' . $e->getMessage();
                Grav::instance()['debugger']->addMessage($msg);
                Grav::instance()['log']->error($msg);
                return null;
            }
        }

        if ($value == null) {
            $msg = 'FormPrefillerPlugin: Warning: the function getTwigRender returned default value: "';
            if ($default == null) {
                $msg .= 'null';
            }
            else {
                $msg .= $default;
            }
            $msg .= '"';
            Grav::instance()['debugger']->addMessage($msg);
            return $default;
        } else {
            // Return parsed value (could be an array)
            return $value;
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
    public static function getURLParameter($key, $default = null)
    {
        $params = self::getUriParams();

        if (isset($params[$key])) {
            $value = $params[$key];

            if ($value == null) {
                $msg = 'FormPrefillerPlugin: Warning: the function getURLParameter returned the default value: "';
                if ($default == null) {
                    $msg .= 'null';
                }
                else {
                    $msg .= $default;
                }
                $msg .= '"';
                Grav::instance()['debugger']->addMessage($msg);
                return $default;
            } else {
                return $value;
            }

        } else {
            $msg = 'FormPrefillerPlugin: Warning: the function getURLParameter returned "null" since no matching URL parameter was found in the URL';
            Grav::instance()['debugger']->addMessage($msg);
            return null;
        }
    }

    /**
     * Deprecated starting from v1.1.3
     */
    public static function getParameter($key, $default = null)
    {
        return self::getURLParameter($key, $default);
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
    public static function getUriParams()
    {

        $grav_params = Grav::instance()['uri']->extractParams(Grav::instance()['uri'], ':')[1];
        $query_params = Grav::instance()['uri']->query(null, true);

        return array_merge($query_params, $grav_params);
    }

    /**
     * Add current directory to twig lookup paths
     *
     */
    public function onTwigTemplatePaths()
    {
        // Add plugin templates path
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates/partials';

    }

}
