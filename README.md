# Form Prefiller Plugin

The **Form Prefiller** Plugin is for [Grav CMS](http://github.com/getgrav/grav). It's purpose is to make prefilling form fields easier. As a kind of bonus the plugin uses a workaround for the Grav Form Plugin issue regarding failing to submit a form when a file field has the attribute `required`.

The main goal of this plugin is to prefill frontend form fields with default values by function calls using the `data-*@:` notation. For the full explanation see [Using Function Calls (data-*@)](https://learn.getgrav.org/16/forms/blueprints/advanced-features#using-function-calls-data-at) in the Grav documentation.

A second benefit is being able to create a form in which uploading of one or more files is mandatory. Instead of setting the attribute `required` to `true`, which prevents the form to be submitted at all, some alternative attributes are introduced. [Read more](#issue106-workaround)

## Preamble

Before starting to discover and use this plugin it's good to point out that there is a standard Grav way of prefilling or initialising a Grav Form with default values.

It uses the undocumented Twig function `setAllData`. A good working example was posted in the Grav Forum by Andy Miller in 2017, see: [Twig in form yaml, dynamic input values](https://discourse.getgrav.org/t/twig-in-form-yaml-dynamic-input-values/4145)

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

## Testing

To test the plugin create a new page using the [example form page](https://raw.githubusercontent.com/bleutzinn/grav-plugin-form-prefiller/master/example-prefill-form-page/default.md) in the `example-prefill-form-page` folder.

In order to experiment with language translations the [Grav LangSwitcher Plugin](https://github.com/getgrav/grav-plugin-langswitcher) can be of help.

## Required system and page configuration

To get the most out of this plugin a couple of system and page configuration settings are required.

1. To prevent the use of old cached values the page should be excluded from the Grav cache.

```
cache_enable: false
```

1. The processing of Twig in the page frontmatter should be enabled. To do this either set `Process frontmatter Twig` to `On` in the Content section of the System Configuration in the Admin Plugin or enable it in the `user/config/system.yaml` configuration file:

```
pages:
    frontmatter:
        process_twig: true
```

1. Using Twig variables in field labels (and in the page content if desired) requires setting `process.twig: true` in the page frontmatter:

```
process:
    twig: true
```


## Usage - Prefilling fields

### Available Function Calls (data-*@)

These function calls make filling form fields with values from different sources easy:

* `getFrontmatter` - gets the value of a variable in the page header or frontmatter
* `getTwig` - gets the value of a Twig variable
* `getTwigRender` - gets a result from processing parameters via a custom Twig template
* `getURLParameter` - gets a URL parameter value in either regular (?q=123) or Grav format (/q:123). If a parameter is specified in both formats the Grav formatted one gets precedence.


### Setting default return values

To control the return value in case a call returns `null` a default return value may be specified as an extra parameter, for example return `42` when the variable `the_answer_is` is missing in the page frontmatter (or is and equals to `null`):

```
data-default@: ['\Grav\Plugin\FormPrefillerPlugin::getFrontmatter', 'the_answer_is', '42']
```

### Twig variables

All frontmatter variables, any data loaded via `prefill_data` and any URL parameters are accessible as Twig variables.

All can be used with the `getTwig` function by using these dot notation prefixes:

* `prefill_frontmatter` for frontmatter variables
* `prefill_data` for data loaded from an external file/*
* `prefill_params` for URL parameters


### Prefilling dynamic field properties

The purpose of the plugin is to prefill form fields with default values. For this Grav uses the `data-*@:` notation as the key, where `*` is the name of the dynamic field property you want to fill with the result of the function call.

See the examples below how to prefill dynamic field properties.

For the full explanation see the [Using Function Calls (data-*@)](https://learn.getgrav.org/16/forms/blueprints/advanced-features#using-function-calls-data-at) in the Grav documentation.


### Examples

**Prefill a field with a URL parameter**

Given this URL: `https://example.com/search?query=something` (regular format) or `https://example.com/search/query:somethin` (Grav format), then the value of the `search` parameter can be prefilled using this function call:

```
data-default@: ['\Grav\Plugin\FormPrefillerPlugin::getURLParameter', 'search']
```

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
    data-options@:
        - '\Grav\Plugin\FormPrefillerPlugin::getFrontmatter'
        - pizzas
```

---

**Multi language forms**

Text from a language file can be used to translate labels into the current language. All that is required is to change:

```
label: Select your Pizza
```

into:

```
data-label@:
    - '\Grav\Plugin\FormPrefillerPlugin::getTwig'
    - PLUGIN_FORM_PREFILLER.DEMO_TEXTS.PIZZA_LABEL
```



---

**Using Twig functions and filters**

Twig can also be used in the frontmatter to set frontmatter variables dynamically. 

For example setting this in the frontmatter:

```
delivery_date: '{{ now|date_modify(''+2 day'')|date(''Y-m-d H:i'') }}'
```

and

```
data-default@:
    - '\Grav\Plugin\FormPrefillerPlugin::getFrontmatter'
    - delivery_date
```

in a form field will result in the prefilled field showing the date and time two days from now.

---

**Prefilling a select field from a template**

A `select` form field requires a list to enable the user to select one of the options.

To demonstrate this the plugin comes with an example template in it's `templates/partials` directory named `pizzas.yaml.twig`:

```
{# Set suitable data structure for the form field type #}
{# In this example a numeric array #}
{% set pizzas = {
  0: 'Margarita',
  1: 'Salami',
  2: 'Rosita',
  3: 'Chef Special'
} %}

{# To return anything other then a string apply the filter yaml_encode #}
{{ pizzas|yaml_encode }}
```

In order to get the list as an array instead of a string make sure to apply the `yaml_encode` filter in the template.

Using the data from the template in the form is simply a matter of calling the getTwigRender function and specifying the template name:

```
-
    name: get_pizzas_from_template
    label: 'Getting a list from a template ("pizzas.yaml.twig")'
    type: select
    classes: fancy
    default: 3
    data-options@:
        - '\Grav\Plugin\FormPrefillerPlugin::getTwigRender'
        - pizzas.yaml.twig
```

---

**Loading external data**

External data in YAML or JSON format can be loaded through a frontmatter variable named `prefill_data`. For example:

```
prefill_data:
    - 'user://data/test.yaml'
```

The content of the test file `user/data/test.yaml` is for example:

```
var1: ef_val1
var2:
    var2a: ef_val2a
    var2b: ef_val2b
```

The plugin then replaces the file reference with the file data itself.

To prefill a form field with the value of `var2b` use this function call:

```
data-default@:
    - '\Grav\Plugin\FormPrefillerPlugin::getTwig'
    - prefill_data.test.var2.var2b
```

Multipe files can be read by specifying a list. For more information see the [Import Plugin](https://github.com/Perlkonig/grav-plugin-import) documentation.

<a name="issue106-workaround"></a>
## Usage - Mandatory or required upload

Due to how the Form Plugin file field is implemented in HTML and Javascript the attribute `required: true` on a file field causes a Javascript error which in turn prevents submitting the form. Over the years this problem has been brought to attention through these issues:
- Form Pugin:
    - [#106](https://github.com/getgrav/grav-plugin-form/issues/106)
    - [#116](https://github.com/getgrav/grav-plugin-form/issues/116)
    - [#418](https://github.com/getgrav/grav-plugin-form/issues/418)
    - [#499](https://github.com/getgrav/grav-plugin-form/issues/499)
- Grav Core:
    - [#1217](https://github.com/getgrav/grav/issues/1217)
- Admin Plugin:
    - [#887](https://github.com/getgrav/grav-plugin-admin/issues/887)

This plugin creates a workaround by introducing an alternative attribute: `restrictions`. To set a file field to mandatory or required use:
```
restrictions:
    required: true
```
To be amended ...

## Troubleshooting

The plugin tries to silently cope with errors but does log them. When to your surprise form fields do not get prefilled take a look at the Grav log file ('logs/grav.log`). Also when the debugger is enabled error messages as well as warnings are displayed in the [Debug Bar](https://learn.getgrav.org/16/advanced/debugging#debug-bar).

An example of an error log message is:   
"FormPrefillerPlugin: Error reading data from "user://data/test.yaml": Indentation problem at line 4 (near " ef_val2a")"

An example of a Debug Bar warning message is:   
"FormPrefillerPlugin: Warning: the function getURLParameter returned "null" since the URL parameter "category" was not found"

## Credits

Credits go to Aaron Dalton ([Perlkonig](https://github.com/Perlkonig)) for his [Import Plugin](https://github.com/Perlkonig/grav-plugin-import) from which some code I have reused. 
