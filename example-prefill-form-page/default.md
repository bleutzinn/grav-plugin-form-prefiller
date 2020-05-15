---
title: 'Prefiller test'
template: form
cache_enable: false
process:
    twig: true
room_nr: 1054b
user_email: '{{ grav.user.email }}'
monkey_label: '{{ ''PLUGIN_FORM_PREFILLER.DEMO_TEXTS.MONKEYS''|t(12, ''Zoo in London'') }}'
prefill_fixed_data:
    fix_var1: fix_val1
    fix_var2:
        fix_var2a: fix_val2a
        fix_var2b: fix_val2b
prefill_data:
    - 'user://data/test.yaml'
pizzas:
    - Margarita
    - Salami
    - Rosita
    - 'Chef Special'
delivery_message: 'Delivery time (Please allow a preparation time of two days)'
delivery_date: '{{ now|date_modify(''+2 day'')|date(''Y-m-d H:i'') }}'
form:
    name: prefill.order
    fields:
        -
            name: data_fixed
            label: 'Data fixed from frontmatter'
            type: text
            data-default@:
                - '\Grav\Plugin\FormPrefillerPlugin::getFrontmatter'
                - prefill_fixed_data.fix_var2.fix_var2a
        -
            name: data_file
            label: 'Data from external file (`/user/data/test.yaml`)'
            type: text
            data-default@:
                - '\Grav\Plugin\FormPrefillerPlugin::getTwig'
                - prefill_data.test.var2.var2b
        -
            name: room
            label: 'Room number (set in frontmatter)'
            type: text
            data-default@:
                - '\Grav\Plugin\FormPrefillerPlugin::getFrontmatter'
                - room_nr
                - '42'
        -
            name: site_description
            label: 'Meta description from site config in dot notation (Twig)  `site.metadata.description`)'
            type: text
            data-default@:
                - '\Grav\Plugin\FormPrefillerPlugin::getTwig'
                - site.metadata.description
        -
            name: site_description_rev
            label: 'Twig template processing result'
            type: text
            data-default@:
                - '\Grav\Plugin\FormPrefillerPlugin::getTwigRender'
                - to_upper_and_reverse
                -
                    - '{{ site.metadata.description }}'
                    - 'reversed text'
        -
            name: get_pizzas_from_template
            label: 'Getting a list from a Twig template ("pizzas.yaml.twig")'
            type: select
            classes: fancy
            default: 0
            data-options@:
                - '\Grav\Plugin\FormPrefillerPlugin::getTwigRender'
                - pizzas.yaml.twig
        -
            name: get_url_parameter
            label: 'Get URL ''category'' parameter value (add ''?category=laptops'' or ''/category:laptops''; Or ''?category:'' to see the default)'
            type: text
            data-default@:
                - '\Grav\Plugin\FormPrefillerPlugin::getURLParameter'
                - category
                - 'Not specified'
        -
            name: email
            label: 'Your email address (requires a logged in frontend user!)'
            type: text
            data-default@:
                - '\Grav\Plugin\FormPrefillerPlugin::getFrontmatter'
                - user_email
        -
            name: pizza
            data-label@:
                - '\Grav\Plugin\FormPrefillerPlugin::getTwig'
                - PLUGIN_FORM_PREFILLER.DEMO_TEXTS.PIZZA_LABEL
            type: select
            classes: fancy
            default: 3
            data-options@:
                - '\Grav\Plugin\FormPrefillerPlugin::getFrontmatter'
                - pizzas
        -
            name: delivery_date
            data-label@:
                - '\Grav\Plugin\FormPrefillerPlugin::getFrontmatter'
                - delivery_message
            type: text
            data-default@:
                - '\Grav\Plugin\FormPrefillerPlugin::getFrontmatter'
                - delivery_date
        -
            name: content
            label: 'Any other requests (a not pre filled field)'
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

# {{ 'PLUGIN_FORM_PREFILLER.DEMO_TEXTS.FORM_HEADER'|t }}
