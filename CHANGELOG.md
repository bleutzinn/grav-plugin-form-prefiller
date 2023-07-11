# v2.0.0
##  07/11/2023

1. [](#new)
    * Added a workaround to get a working form with one or more required file fields
    * Added support for reading external data from JSON encoded files
1. [](#improved)
    * Added form fields attributes to the `twig_vars` variable
1. [](#bugfix)
    * Fixed a bug which caused the variables `twig_vars.prefill_params` and `twig_vars.prefill_frontmatter` to be available only when the frontmatter variable `prefill_data` was set
    * Applied the `raw` Twig filter to the YAML output in `pizzas.yaml.twig`

# v1.1.5
##  05/18/2020

1. [](#bugfix)
    * Fixed (included) missing 'vendor' directory

# v1.1.4
##  05/17/2020

1. [](#improved)
    * Restricted response of all data-*@ functions to page requests only
    * Enriched Debug Bar messages with the requested variable name

# v1.1.3
##  05/15/2020

1. [](#improved)
    * Improved stability by catching and logging errors
    * Restored data-*@ functions to static
    * Renamed the function "getParameter" to "getURLParameter" ("getParameter" still works for backwards compatibility)
    * Updated to new devtools plugin standards
    * Renamed "demo" to the more appropriate "example-prefill-form-page"
    * Changed some examples so that they are clearer
    * Updated documentation

# v1.1.2
##  05/02/2020

1. [](#improved)
    * Updated documentation
    
# v1.1.1
##  05/02/2020

1. [](#bugfix)
    * Fixed a problem in detecting YAML type templates.
1. [](#improved)
    * Restricted examples to form fields only.
    
# v1.1.0
##  05/02/2020

1. [](#bugfix)
    * Fixed issue [#2](https://github.com/bleutzinn/grav-plugin-form-prefiller/issues/2) about an error when 'prefill_data' was missing in the frontmatter; thanks to [thekenshow](https://github.com/thekenshow) for reporting an the fix.
    * Fixed a problem causing a Page not found error immediately after a page save.
1. [](#improved)
    * Improved errors in and inconsistencies between the provided example files and the documentation.
    
# v1.0.1
##  01/29/2020

1. [](#improved)
    * Corrected the link to the `README.md` file in `blueprints.yaml` file.

# v1.0.0
##  06/23/2019

1. [](#improved)
    * Updated documentation in `README.md`
    * Included more complete demo page

# v0.2.0
##  06/23/2019

1. [](#new)
    * Added custom processing through Twig templates ("getTwigRender")
    * Added a call to view all available Twig variables via `getTwig` with parameter `@ALL`
1. [](#improved)
    * Fixed a problem with extra Twig variables not being added

# v0.1.0
##  06/16/2019

1. [](#new)
    * ChangeLog started...
    * First release
