<?php

namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Grav;
use Grav\Common\Language\Language;
use Grav\Common\Plugin;
use Grav\Common\Utils;
use Grav\Framework\Psr7\Response;
use RuntimeException;
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
     * getFileFields
     * Get form file fields
     *
     * @return array|null
     */
    public function getFileFields(): mixed
    {
        $forms = $this->grav['page']->getForms();

        $results = [];
        foreach ($forms as $form) {
            if (isset($form['fields'])) {
                foreach ($form['fields'] as $name => $definition) {
                    if (isset($definition['type']) && $definition['type'] == 'file') {
                        $results[$name] = $definition;
                    }
                }
            }
        }

        if (!empty($results)) {
            return $results;
        } else {
            return null;
        }
    }

    /**
     * getFileData
     *
     * @param string file
     *
     * @return array
     */
    public function getFileData(string $file): array
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
     * getFilePath
     *
     * @param string filename
     *
     * @return string
     */
    public function getFilePath(string $filename): string
    {
        if (strpos($filename, '://') !== false) {
            $path = $this->grav['locator']->findResource($filename, true);
        } else {
            $path = $this->grav['page']->path() . DS . $filename;
        }

        return $path;
    }

    /**
     * onOutputGenerated
     * 
     * Replace each Grav form file field in the final HTML page by an Uppy instance
     *
     * @return void
     */
    public function onOutputGenerated()
    {
        include_once(__DIR__ . '/vendor/simple_html_dom/simple_html_dom.php');

        $file_fields = $this->grav['session']->__get('file_fields');
        $file_fields_values = array_values($file_fields);

        $html = str_get_html($this->grav->output);

        // 
        $i = 0;
        foreach ($html->find('.form-data') as $el) {
            if ($el->hasAttribute('data-grav-field') && $el->getAttribute('data-grav-field') == 'file') {
                if (isset($file_fields_values[$i]['restrictions'])) {
                    $restrictions = $file_fields_values[$i]['restrictions'];

                    // Add the 'field required' symbol to the field label when required 
                    if (isset($restrictions['required']) && $restrictions['required'] == true) {
                        $el->parentNode()->find('.form-label', 0)->innertext .= '&nbsp;<span class="required">*</span>';
                        $el->setAttribute('data-required', true);
                    }

                    // Add a custom attribute 'data-minNumberOfFiles' when a minimum number of files to be uploaded is set in the form file field
                    if (
                        isset($restrictions['minNumberOfFiles']) && is_numeric($restrictions['minNumberOfFiles'])
                        && $restrictions['minNumberOfFiles'] >= 1
                    ) {
                        $el->setAttribute('data-minNumberOfFiles', $restrictions['minNumberOfFiles']);
                    }

                    // Add a custom attribute 'data-maxNumberOfFiles' when a maximum number of files to be uploaded is set in the form file field
                    if (
                        isset($restrictions['maxNumberOfFiles']) && is_numeric($restrictions['maxNumberOfFiles'])
                        && $restrictions['maxNumberOfFiles'] >= 1
                    ) {
                        $el->setAttribute('data-maxNumberOfFiles', $restrictions['maxNumberOfFiles']);
                    }
                }
                $i++;
            }
        }

        // Create JS code containing translated strings for the current active language
        $languages_file = $this->grav['locator']->findResource('plugin://form-prefiller/languages.yaml');
        $translations = Yaml::parse(file_get_contents($languages_file));
        $active_language = $this->grav['language']->getLanguage();
        $js = $this->grav['twig']->processTemplate('partials/translations.js.twig', ['lang' => json_encode($translations[$active_language])]);

        // Wrap the JS code in a script element and add the JS inline at the bottom of the page
        $el = $html->createElement('script');
        $el->innertext = $js;

        $html->find('#gfpfp-validation', 0)->innertext = $js . $html->find('#gfpfp-validation', 0)->innertext;

        // Output processed HTML
        $this->grav->output = $html->save();
    }

    /**
     * onPageInitialized
     * Store the page object for use in the static data-default@ call handler functions
     * Add JS asset when the page contains a form including one or more file fields
     * If it does not cancel listening to the onOutputGenerated event
     *
     * @param mixed event
     *
     * @return void
     */
    public function onPageInitialized($event)
    {
        // 'Remember' which page
        self::$page = $event['page'];

        $file_fields = $this->getFileFields();
        if (is_array($file_fields)) {
            $this->grav['session']->__set('file_fields', $file_fields);
        }

        // If there is no form file field cancel handling of the onOutputGenerated event
        // If there is add assets
        if (empty($file_fields)) {
            $this->disable([
                'onOutputGenerated' => ['onOutputGenerated', 0],
            ]);
        } else {
            // Add validate-file-field-required.js code inline after the inline form code
            $this->grav['assets']->addJS('plugin://' . basename(__DIR__) .
                '/assets/validate-file-field-required.js', [
                'group' => 'bottom',
                'priority' => 5,
                'loading' => 'inline',
                'id' => 'gfpfp-validation',
            ]);
        }
    }

    /**
     * onPagesInitialized
     *
     * @return void
     */
    public function onPagesInitialized()
    {
        $twig = Grav::instance()['twig'];

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

            // Add data from external YAML or JSON file
            // Usage: {{ prefill_data.<file>.<var-in-dot-notation> }}
            $prefill_data = Grav::instance()['page']->header()->prefill_data;
            unset($twig->twig_vars['prefill_data']);
            $twig->twig_vars['prefill_data'] = $prefill_data;
        }

        // Add extra Twig variables to be used via `getTwig` call

        // Add any URL parameters
        // Usage: Regular (?q=123) or Grav style (/q:123) parameter
        // Note: Mixing styles is allowed; in case of conflicts the Grav
        // style parameter gets precedence over the regular style one
        $params = self::getUriParams();
        unset($twig->twig_vars['prefill_params']);
        $twig->twig_vars['prefill_params'] = $params;

        // Add page header/frontmatter
        // Usage: {{ prefill_frontmatter.<var-in-dot-notation> }}
        $frontmatter = (array) Grav::instance()['page']->header();
        unset($twig->twig_vars['prefill_frontmatter']);
        $twig->twig_vars['prefill_frontmatter'] = $frontmatter;

        $forms = $this->grav['page']->getForms();
        // dump($forms);

        unset($twig->twig_vars['prefill_attributes']);
        foreach ($forms as $form) {
            if (isset($form['fields'])) {
                $prefill_attributes = $form['fields'];
                $twig->twig_vars['prefill_attributes'] = $prefill_attributes;
            }
        }
    }

    /**
     * onPluginsInitialized
     * Initialize the plugin
     *
     * @return void
     */
    public function onPluginsInitialized()
    {
        // Don't proceed if we are in the admin plugin
        if ($this->isAdmin()) {
            return;
        }

        $url = $this->grav['uri']->url();
        $this->grav['log']->info($url);

        // If the API is being called
        // if (substr($url, 0, strlen(self::api)) === self::api) {
        // } else {
        // Enable the main events we are interested in
        $this->enable([
            'onOutputGenerated' => ['onOutputGenerated', 0],
            'onPageInitialized' => ['onPageInitialized', 0],
            'onPagesInitialized' => ['onPagesInitialized', 0],
            'onTwigTemplatePaths' => ['onTwigTemplatePaths', 0],
        ]);
        // }
    }

    /**
     * onTwigTemplatePaths
     * Add current directory to twig lookup paths
     *
     * @return void
     */
    public function onTwigTemplatePaths()
    {
        // Add plugin templates path
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates/partials';
    }

    public function translateJs()
    {
        // Create a new response object
        $response = new Response();

        // Render the translations.js.twig template and set it as the response content
        $js = $this->grav['twig']->processTemplate('partials/translations.js.twig');

        // Send js to the browser
        $response = new Response(200, ['Content-Type' => 'application/javascript'], $js);
        $this->grav->close($response);
    }

    /**
     * getFrontmatter
     * Return value from page header/frontmatter
     *
     * To be used with the data-default@ form field option
     * Nested variables must be requested in dot notation
     *
     * @param string $key
     *
     * @return mixed $value
     *
     */
    public static function getFrontmatter(string $key, string $default = null): mixed
    {
        if (!isset(self::$page)) {
            return null;
        }

        $frontmatter = (array) self::$page->header();

        $value = Utils::getDotNotation($frontmatter, $key);

        if ($value == null) {
            $msg = 'FormPrefillerPlugin: Warning: the function getFrontmatter returned the default value "';
            if ($default == null) {
                $msg .= 'null';
            } else {
                $msg .= $default;
            }
            $msg .= '" for the frontmatter variable "' . $key . '"';
            Grav::instance()['debugger']->addMessage($msg);
            return $default;
        } else {
            return $value;
        }
    }

    /**
     * getTwig
     * Return value of specified Twig variable
     *
     * To be used with the data-default@ form field option
     *
     * @param string $var
     *
     * @return string|null $value
     *
     */
    public static function getTwig(string $var, string $default = null): mixed
    {
        if (!isset(self::$page)) {
            return null;
        }

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
            $msg = 'FormPrefillerPlugin: Warning: the function getTwig returned the default value "';
            if ($default == null) {
                $msg .= 'null';
            } else {
                $msg .= $default;
            }
            $msg .= '" for the Twig variable "' . $var . '"';
            Grav::instance()['debugger']->addMessage($msg);
            return $default;
        } else {
            return $value;
        }
    }

    /**
     * getTwigRender
     * Return the result of processing a Twig template
     *
     * @param string $template
     * @param mixed $params
     *
     * @return mixed $value
     *
     */
    public static function getTwigRender(string $template, mixed $params = null, mixed $default = null): mixed
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

        // try {
        // Render the template and return the result
        $value = Grav::instance()['twig']->twig->render($template, ['params' => $params, 'twig_vars' => $twig_vars]);

        // } catch (\Exception $e) {
        //     $exception = new RuntimeException(sprintf('FormPrefillerPlugin: %s', $e->getMessage()), $e->getCode(), $e);

        //     /** @var Debugger $debugger */
        //     $debugger = Grav::instance()['debugger'];
        //     $debugger->addException($exception);
        //     return null;
        // }

        // Check for a YAML type template (".yaml.twig")
        if (strpos(strtolower($template), '.yaml.twig') !== false) {
            // Try to parse YAML
            try {
                $value = YAML::parse($value);
            } catch (\Exception $e) {
                $msg = 'FormPrefillerPlugin: YAML Parser error when reading data from: "' . basename($template) . '": ' . $e->getMessage();
                Grav::instance()['debugger']->addMessage($msg);
                Grav::instance()['log']->error($msg);
                return null;
            }
        }

        // Check for a JSON type template (".json.twig")
        if (strpos(strtolower($template), '.json.twig') !== false) {
            // Try to parse YAML
            try {
                $value = json_decode($value);
            } catch (\Exception $e) {
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
            } else {
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
     * getURLParameter
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
    public static function getURLParameter(string $key, string $default = null): mixed
    {
        if (!isset(self::$page)) {
            return null;
        }

        $params = self::getUriParams();

        if (isset($params[$key])) {
            $value = $params[$key];

            if ($value == null) {
                $msg = 'FormPrefillerPlugin: Warning: the function getURLParameter returned the default value "';
                if ($default == null) {
                    $msg .= 'null';
                } else {
                    $msg .= $default;
                }
                $msg .= '" for the URL parameter "' . $key . '"';
                Grav::instance()['debugger']->addMessage($msg);
                return $default;
            } else {
                return $value;
            }
        } else {
            $msg = 'FormPrefillerPlugin: Warning: the function getURLParameter returned "null" since the URL parameter "' . $key . '" was not found';
            Grav::instance()['debugger']->addMessage($msg);
            return null;
        }
    }

    /**
     */
    /**
     * getParameter
     * Deprecated starting from v1.1.3
     *
     * @param string key
     * @param string default
     *
     * @return mixed
     */
    public static function getParameter(string $key, string $default = null): mixed
    {
        return self::getURLParameter($key, $default);
    }

    /**
     * getUriParams
     * Return URI parameters
     *
     * Supports regular (?q=123) and Grav style (/q:123) parameters
     * If a parameter is specified twice the Grav style one gets precedence
     *
     * @return array
     *
     */
    public static function getUriParams(): array
    {

        $grav_params = Grav::instance()['uri']->extractParams(Grav::instance()['uri'], ':')[1];
        $query_params = Grav::instance()['uri']->query(null, true);

        return array_merge($query_params, $grav_params);
    }
}
