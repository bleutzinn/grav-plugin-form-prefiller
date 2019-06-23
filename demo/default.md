---
title: 'Prefiller Plugin Demo Form'
template: form
cache_enable: false
process:
    twig: true
room_nr: '1054b'
user_email: '{{ grav.user.email }}'
monkey_label: '{{ ''PLUGIN_FORM_PREFILLER.DEMO_TEXTS.MONKEYS''|t(12) }}'
prefill_fixed_data:
    fix_var1: fix_val1
    fix_var2:
        fix_var2a: fix_val2a
        fix_var2b: fix_val2b
prefill_data:
    - 'user://data/test.yaml'
pizzas:
    0: 'Margarita'
    1: 'Salami'
    2: 'Rosita'
    3: 'Chef Special'
delivery_message: 'Delivery time (Please allow a preparation time of two days)' 
delivery_date: '{{ now|date_modify(''+2 day'')|date(''Y-m-d H:i'') }}'
form:
    name: addpage.prefill.order
    fields:
        -
            name: data_fixed
            label: 'data fixed'
            type: text
            data-default@: ['\Grav\Plugin\FormPrefillerPlugin::getFrontmatter', 'prefill_fixed_data.fix_var2.fix_var2b']
        -
            name: data_file
            label: 'data from file'
            type: text
            data-default@: ['\Grav\Plugin\FormPrefillerPlugin::getTwig', 'prefill_data.test.var1']
        -
            name: room
            label: 'Room number (hard coded in frontmatter)'
            type: text
            data-default@: ['\Grav\Plugin\FormPrefillerPlugin::getFrontmatter', 'room_nr', '42']
        -
            name: site_description
            label: 'Meta description from site config in dotnotation (Twig): site.metadata.description)'
            type: text
            data-default@: ['\Grav\Plugin\FormPrefillerPlugin::getTwig', 'site.metadata.description']
        -
            name: site_description_rev
            label: 'Twig template processing result'
            type: text
            data-default@: ['\Grav\Plugin\FormPrefillerPlugin::getTwigRender', 'to_upper_and_reverse', [ '{{ site.metadata.description }}', 'reversed text'] ]
        -
            name: get_pizzas_from_template
            label: 'Getting a list from a template ("pizzas.yaml.twig")'
            type: select
            classes: fancy
            default: 3
            data-options@: ['\Grav\Plugin\FormPrefillerPlugin::getTwigRender', 'pizzas.yaml.twig' ]
        -
            name: do_action
            label: 'Action (passed as a URL parameter)'
            type: text
            data-default@: ['\Grav\Plugin\FormPrefillerPlugin::getParameter', 'action']
        -
            name: email
            label: 'Your email address (requires a logged in user!)'
            type: text
            data-default@: ['\Grav\Plugin\FormPrefillerPlugin::getFrontmatter', 'user_email']
        -
            name: pizza
            data-label@: ['\Grav\Plugin\FormPrefillerPlugin::getTwig', PLUGIN_FORM_PREFILLER.DEMO_TEXTS.PIZZA_LABEL ]
            type: select
            classes: fancy
            default: 3
            data-options@: ['\Grav\Plugin\FormPrefillerPlugin::getFrontmatter', 'pizzas']
        -
            name: delivery_date
            data-label@: ['\Grav\Plugin\FormPrefillerPlugin::getFrontmatter', 'delivery_message']
            type: text
            data-default@: ['\Grav\Plugin\FormPrefillerPlugin::getFrontmatter', 'delivery_date']
        -
            name: content
            label: 'Any other requests'
            type: textarea
            size: long
        -
            name: honeypot
            type: honeypot
    buttons:
        -
            type: submit
            value: Submit
    process:
        -
            redirect: /
---

## {{ 'PLUGIN_FORM_PREFILLER.DEMO_TEXTS.PAGE_HEADER'|t }}

Plugin status: {{ 'PLUGINS.LANGUAGE_SELECTOR.PLUGIN_STATUS'|t }}

Logged in user email: {{ grav.user.email }}

var2.var2a = {{ page.header.prefill_data.test.var2.var2a }}

File test via Twig vars: {{ prefill_data.test.var2.var2a }}

Fixed data vars:   
fix_var2.fix_var2b = {{ page.header.prefill_fixed_data.fix_var2.fix_var2b }}

Loaded external data: {{ page.header.prefill_data.test|json_encode(constant('JSON_PRETTY_PRINT')) }}

Translated sentence: {{ 'PLUGIN_FORM_PREFILLER.DEMO_TEXTS.LANG_PREFIX'|t|capitalize }} **{{ native_name(language_selector.current) }}**: {{ 'PLUGIN_FORM_PREFILLER.DEMO_TEXTS.MONKEYS'|t(12) }} !

Value of 'room_nr' from frontmatter: {{ page.header.room_nr }}

Value of 'room_nr' from Twig prefill_frontmatter: {{ prefill_frontmatter.room_nr }}

Today's date: {{ now|date('Y-m-d H:i') }}

Delivery date straight from frontmatter: {{ page.header.delivery_date }}

Delivery date from Twig prefill_frontmatter: {{ prefill_frontmatter.delivery_date }}

Meta description: {{ site.metadata.description }}

HTML Lang = {{ html_lang }}

action via URL parameter = {{ prefill_params.action }}

## {{ 'PLUGIN_FORM_PREFILLER.DEMO_TEXTS.FORM_HEADER'|t }}
