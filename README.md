<!-- $theme: gaia -->

# Form Prefiller Plugin

The **Form Prefiller** Plugin is for [Grav CMS](http://github.com/getgrav/grav). It's purpose is to make prefilling form fields easier.

## Installation and Configuration

Typically the plugin should be installed via [GPM](http://learn.getgrav.org/advanced/grav-gpm) (Grav Package Manager):

```
$ bin/gpm install form-prefiller
```

Alternatively it can be installed via the [Admin Plugin](http://learn.getgrav.org/admin-panel/plugins). The benefit when using the admin plugin is that a file with your configuration, and named `form-prefiller.yaml` will be automatically saved in the `user/config/plugins/` folder once the configuration is saved in the admin. Then, there is no need to copy the configuration file manually.

Another option is to manualy install the plugin by [downloading](https://github.com/bleutzinn/grav-plugin-form-prefiller/archive/master.zip) the plugin as a zip file. Copy the zip file to your `/user/plugins` directory, unzip it there and rename the folder to `form-prefiller`.

### Configuration Defaults

Here is the default configuration in the configuration file `form-prefiller.yaml` plus an explanation of the settings:

```yaml
enabled: true
```
- `enabled: true|false` determines whether the plugin is active or not

### Configuration Changes

Simply edit the plugin options in the Admin panel, or, if you don't use the Admin panel, copy the `form-prefiller.yaml` default file to your `user/config/plugins` folder and use that copy to change configuration settings.

## Demo

Currently there's no online demo.

To demo and test the plugin you need a preferably clean Grav installation including this plugin and then use the [example form page](https://raw.githubusercontent.com/bleutzinn/grav-plugin-form-prefiller/master/demo/default.md) in the `demo` folder.

In order to experiment with language translations either the [Grav LangSwitcher Plugin](https://github.com/getgrav/grav-plugin-langswitcher) or the [Language Selector plugin with flags for Grav CMS](https://github.com/clemdesign/grav-plugin-language-selector) can be of help.


## Usage

### Activating the plugin

This plugin acts only when `prefill` is in the form name, e.g.:

```
form:
    name: addpage.prefill.order_form
```

### Available Function Calls (data-*@)

These function calls makes filling form fields with values from different sources easy:

* `getParameter` - gets a URL parameter value in either regular (?q=123) or Grav format (/q:123). If a parameter is specified in both formats the Grav formatted one gets precedence.
* `getFrontmatter` - gets the value of a variable in the page header or frontmatter.
* `getTwig` - gets the value of a Twig variable.
* `getTwigRender` - gets a result from processing parameters via a custom Twig template.


### Setting default return values

To control the return value in case a call returns `null` a default return value may be specified as an extra parameter, for example return `42` when the variable `unknown` is missing in the page frontmatter (or is and equals to `null`):

```
data-default@: ['\Grav\Plugin\FormPrefillerPlugin::getFrontmatter', 'unknown', '42']
```

### Twig variables

The plugin has access to a set of Twig variables which Grav supplies by default and can de used with `getTwig`.

Additionally, as an extra convenience, the frontmatter variables, data loaded with `prefill_data` and any URL parameters are also made accessible as Twig variables as well.

All can be used with the `getTwig` function by using these Dot notation prefixes:

* `prefill_params`
* `prefill_frontmatter` (acts as an alias of `page.header`)
* `prefill_data`

To see these variables set a `data-default@` property to get `@ALL`:

```
data-default@: ['\Grav\Plugin\FormPrefillerPlugin::getTwig', '@ALL' ]
```

This will simply dump all the accessible Twig variables to the screen and exits the plugin without any further processing.


### Prefilling dynamic field properties

The main purpose of the plugin is to prefill form fields with default values. For this Grav uses the `data-*@:` notation as the key, where `*` is the name of the dynamic field property you want to fill with the result of the function call.

See the examples below how to prefill dynamic field properties.

For the full explanation see the [Using Function Calls (data-*@)](https://learn.getgrav.org/16/forms/blueprints/advanced-features#using-function-calls-data-at) in the Grav documentation.

### Examples

**Prefill a field with a URL parameter**

Given this URL: `https://example.com/search?query=%40` (regular format) or `https://example.com/search/query:%40` (Grav format), then the value of the `search` parameter ("@") can be prefilled using this function call:

```
data-default@: ['\Grav\Plugin\FormPrefillerPlugin::getParameter', 'search']
```

Please see the remark at [Caveats](#caveats) !

---

**Multi language forms**

Text from a language file can be used to translate labels into the current language. Combined with using Twig in the frontmatter allows for translated forms.

To demonstrate this the plugin comes with this translation file `languages.yaml`:


```
# English
en:
  PLUGIN_FORM_PREFILLER:
    DEMO_TEXTS:
      MONKEYS: 'There are %d monkeys in the London Zoo'
      LANG_PREFIX: 'in'
      PIZZA_LABEL: 'Select your pizza'

# French
fr:
  PLUGIN_FORM_PREFILLER:
    DEMO_TEXTS:
      MONKEYS: 'Il y a %d singes dans le Zoo de Londres'
      LANG_PREFIX: 'en'
      PIZZA_LABEL: 'SÃ©lectionnez votre pizza'
```

When the variable `monkey_label` is defined in the page frontmatter as:
`monkey_label: '{{ ''PLUGIN_FORM_PREFILLER.DEMO_TEXTS.MONKEYS''|t(12) }}'`

then the label will be shown in the current language: in English: "There are 12 monkeys in the London Zoo" and en FranÃ§ais: "Il y a 12 singes dans le Zoo de Londres".

Likewise a field label can be translated, for example:

```
data-label@: ['\Grav\Plugin\FormPrefillerPlugin::getTwig', PLUGIN_FORM_PREFILLER.DEMO_TEXTS.PIZZA_LABEL ]
```


Of course form fields (using `data-default@`) can be prefilled in the current language as well.

---

**Using a list from frontmatter**

Any variable present in the page header or frontmatter can be used in the function call `getFrontmatter`. Suppose the form page frontmatter contains this list of pizza's:

```
pizzas:
    0: 'Margarita'
    1: 'Salami'
    2: 'Rosita'
    3: 'Chef Special'
```

The drop down form field can then be prefilled like this:

```
-
	name: pizza
	label: Select your Pizza
	type: select
	classes: fancy
	default: 3
	data-options@: ['\Grav\Plugin\FormPrefillerPlugin::getFrontmatter', 'pizzas']
```

---

**Using Twig functions and filters**

Twig can also be used in the frontmatter to set frontmatter variables dynamically. To do so the processing of Twig in the page frontmatter must be enabled.   
Either set Process frontmatter Twig to On in Admin Configuration Content in the Admin panel or set

```
pages:
    frontmatter:
        process_twig: true
```

in the `user/config/system.yaml` configuration file to enable frontmatter Twig processing.

For example setting this in the frontmatter:

```
delivery_date: '{{ now|date_modify(''+2 day'')|date(''Y-m-d H:i'') }}'
```   

and

```
data-default@: ['\Grav\Plugin\FormPrefillerPlugin::getFrontmatter', 'delivery_date']
```
   
in a form field will, at noon on January 1st, 2020 result in the prefilled field showing "2020-01-03 12:00".

---

**Prefilling a select field from a template**

A `select` form field requires a list to enable the user to select one of the options.

To demonstrate this the plugin comes with an example template in it's `templates/partials` directory named `pizzas.yaml.twig`:

```
{% set pizzas = {
  'pizzas': { 0: 'Margarita', 1: 'Salami', 2: 'Rosita', 3: 'Chef Special' }
}
%}

{# To return anything other then a string apply the filter yaml_encode #}
{{ pizzas|yaml_encode }}
```

In order to get the list as an array two requirements must be met:

1. the `yaml_encode` filter must be applied to the template output as shown below.
2. indicate the output format of the template in the filename, in this case `pizzas.yaml.twig` (actually this is not a requirement but considered a best practice).


```
-
    name: get_pizzas_from_template
    label: 'Getting a list from a template ("pizzas.yaml.twig")'
    type: select
    classes: fancy
    default: 3
    data-options@: ['\Grav\Plugin\FormPrefillerPlugin::getTwigRender', 'pizzas.yaml.twig' ]
```

---

**Loading external data**

External data in YAML or JSON format can be loaded through a frontmatter variable named `prefill_data`. For example:

```
prefill_data:
    - 'user://data/test.yaml'
```

The content of the test file in `user/data` is for example:

```
var1: val1
var2:
    var2a: val2a
    var2b: val2b
``` 

The plugin then replaces the file reference with the file data itself.

To prefill a form field with the value of `var2b` use this function call:

```
data-default@: ['\Grav\Plugin\FormPrefillerPlugin::getTwig', 'prefill_data.test.var2.var2b']
```

Multipe files can be read by specifying a list. For more information see the [Import Plugin](https://github.com/Perlkonig/grav-plugin-import) documentation. 


### Using Twig variables in the page content

As a kind of side effect all Twig variables can also be used in the page content by setting:

```
process:
    twig: true
```

For examples see the example form page in the demo folder.

## Caveats

To properly use URL parameters the caching of the form page (only) must be disabled in the page frontmatter with:

```
cache_enable: false
```


## Credits

Credits go to Aaron Dalton ([Perlkonig](https://github.com/Perlkonig)) for his [Import Plugin](https://github.com/Perlkonig/grav-plugin-import) from which some code I have reused. 


