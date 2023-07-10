---
title: Form Prefiller Plugin - Mandatory File Field Demo
template: form
visible: false
cache_enable: false
process:
    twig: true
twig:
  debug: true  # Enable Twig debugger
name: Me
email: me@example.com

form:
    name: sign-up

    fields:
        name:
            data-label@:
                - '\Grav\Plugin\FormPrefillerPlugin::getTwig'
                - PLUGIN_FORM_PREFILLER.DEMO_TEXTS.NAME_LABEL
            autocomplete: on
            type: text
            data-default@: ['\Grav\Plugin\FormPrefillerPlugin::getFrontmatter', 'name']
            validate:
                required: true

        email:
            label: PLUGIN_FORM_PREFILLER.DEMO_TEXTS.EMAIL_LABEL
            type: email
            data-default@: ['\Grav\Plugin\FormPrefillerPlugin::getFrontmatter', 'email']
            validate:
                required: true

        images:
            data-label@:
                - '\Grav\Plugin\FormPrefillerPlugin::getTwigRender'
                - images-label.txt.twig
                - prefill_attributes.images.restrictions
            type: file
            multiple: true
            accept:
                - image/jpeg
                - image/png
            # validate:             Do not use on fields of type file
            #     required: true    Setting validate.required: true prevents the form to be submitted
            restrictions:
                required: true
                minNumberOfFiles: 2
                maxNumberOfFiles: 3

        get_pizzas_from_template:
            data-label@:
                - '\Grav\Plugin\FormPrefillerPlugin::getTwig'
                - PLUGIN_FORM_PREFILLER.DEMO_TEXTS.PIZZA_LABEL
            type: select
            classes: fancy
            default: 2
            data-options@:
                - '\Grav\Plugin\FormPrefillerPlugin::getTwigRender'
                - pizzas.yaml.twig

        honeypot:
            type: honeypot

    buttons:
        submit:
            type: submit
            value: Submit
        reset:
            type: reset
            value: Reset

    process:
        save:
            fileprefix: signup-
            dateformat: Ymd-His-u
            extension: txt
            body: "{% include 'forms/data.txt.twig' %}"
        message: "{{ 'PLUGIN_FORM_PREFILLER.DEMO_TEXTS.MESSAGE'|t }}"
        display: thankyou
---

## Form Prefiller Plugin Demo

{{ 'PLUGIN_FORM_PREFILLER.DEMO_TEXTS.CONTENT'|t }}
