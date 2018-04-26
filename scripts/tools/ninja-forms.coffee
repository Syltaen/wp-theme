###
  * Controller for all Ninja Forms
  * @use Plugin : Ninja Forms ^3.0.0
###

import $ from "jquery"
import "select2"
import Dropzone from "dropzone"


if typeof Marionette isnt "undefined" then new (Marionette.Object.extend(


    initialize: ->

        # nfRadio.DEBUG = true
        # console.log nfRadio._channels

        @listenTo nfRadio.channel("submit"),               "validate:field",        @validateRequired
        @listenTo nfRadio.channel("fields"),               "change:modelValue",     @validateRequired

        @listenTo nfRadio.channel("listselect"),           "render:view",           @listselectRender
        @listenTo nfRadio.channel("advancedlistselect"),   "render:view",           @listselectRender
        @listenTo nfRadio.channel("listmultiselect"),      "render:view",           @listselectRenderer
        @listenTo nfRadio.channel("rolesfield"),           "render:view",           @listselectRender
        @listenTo nfRadio.channel("projectfield"),         "render:view",           @listselectRender

        @listenTo nfRadio.channel("fileuploadfield"),      "render:view",           @dropzoneRender

        @listenTo nfRadio.channel("form"),                 "render:view",           @bindConditionalCheck


    # ==================================================
    # > CONDITIONAL RENDERING
    # ==================================================
    shouldHide: (field) ->

        shouldHide = false
        if field.attributes.has_conditional_display
            for i, condition of field.attributes.conditional_display
                for i, f of field.collection.models
                    if f.attributes.key == condition.label
                        fieldValue = f.attributes.value || f.attributes.default
                        pass = false
                        switch condition.calc
                            when "!="   then pass = true if fieldValue != condition.value
                            when "==="  then pass = true if fieldValue is condition.value
                            when "!=="  then pass = true if fieldValue isnt condition.value
                            when "in"   then pass = true if fieldValue.indexOf(condition.value) > -1
                            else             pass = true if fieldValue == condition.value
                        unless pass then shouldHide = true

        # Disable requirement if the field is hidden
        if shouldHide
            nfRadio.channel("fields").request("remove:error", field.id, "required-error")
            field.attributes.required = 0
        else
            field.attributes.required = field.attributes.required_base

        return shouldHide

    checkConditional: (form) ->

        for i, field of form.model.attributes.fields.models
            $container = $("#nf-field-#{field.id}-container")

            if @shouldHide field
                if $container.is ":visible" then $container.hide()
            else
                unless $container.is ":visible"
                    $container.show()
                    if field.attributes.type == "bpostpointfield"
                        $(document).trigger("bpostpointfield_display")

    bindConditionalCheck: (form) ->


        for i, field of form.model.attributes.fields.models
            field.attributes.required_base = field.attributes.required

        form.$el.find("input, select").each (i, el) =>
            $(el).change =>
                setTimeout =>
                    @checkConditional form
                , 100

        @checkConditional form


    # ==================================================
    # > VALIDATION
    # ==================================================
    validateRequired: (field) ->

        value = field.get("value")
        id    = field.get("id")

        switch field.get("type")
            # ========== LOGIN FIELD ========== #
            when "login"
                if @validateEmail value
                    nfRadio.channel("fields").request("remove:error", id, "login-error")
                else
                    nfRadio.channel("fields").request("add:error", id, "login-error", "Please provide a valid email address.")

    # ==================================================
    # > RENDERERS
    # ==================================================
    # SELECT 2
    listselectRender: (view) ->

        $(view.el).find("select").each ->
            $(@).select2
                minimumResultsForSearch: 8,
                placeholder: "Cliquez pour choisir"
            .change ->
                view.model.attributes.value = $(@).val()

                if view.model.attributes.value
                    nfRadio.channel("fields").request("remove:error", view.model.id, "required-error")

            view.model.attributes.value = $(@).val()

    # DROPZONE
    dropzoneRender: (view) ->

        $hidden = $(view.el).find(".ninja-forms-field")
        $input  = $(view.el).find("label")

        console.log $input

        new Dropzone $input[0],
            url: ajaxurl + "?action=syltaen_ajax_upload"
            paramName: view.model.attributes.key
            acceptedFiles: view.model.attributes.filetypes
            uploadMultiple: false
            maxFilesize: view.model.attributes.maxupload
            clickable: true
            dictDefaultMessage: view.model.attributes.label
            dictFileTooBig: "This file is too heavy ({{filesize}}Mb) - Max. authorised : {{maxFilesize}}Mb"
            dictInvalidFileType: "Ce type de fichier n'est pas autorisé."

            accept: (file, done) ->
                $input.addClass "loading"
                $input.closest(".nf-form-cont").addClass "loading"
                done()

            success: (file, uploaded) ->
                $input.removeClass "loading"
                $input.closest(".nf-form-cont").removeClass "loading"
                console.log uploaded
                view.model.attributes.value = uploaded[0].url
                $hidden.val uploaded[0].url
                $hidden.change()

            error: ->
                console.log("erreur")

            init: ->
                @on "addedfile", (file) ->
                    console.log file
                    if $input.find(".dz-preview").length > 1
                        $input.find(".dz-preview").first().remove()


        $input.on "click", "div", (e) ->
            e.stopPropagation()
            $input.click()

    # ==================================================
    # > UTILITY
    # ==================================================
    validateEmail: (email) ->
        re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
        return re.test(email)



))