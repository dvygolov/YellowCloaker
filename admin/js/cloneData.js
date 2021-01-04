/**
 * Instructions: Call $(selector).cloneData(options) on an element with a jQuery type selector
 * defined in the attribute "rel" tag. This defines the DOM element to copy.
 *
 * @CreadtedBY Rajneesh Gautam
 * @CreadtedOn 24/07/2019
 *
 @example:
 $('a#add-education').cloneData({
            mainContainerId: 'main-container', // Main container Should be ID
            cloneContainer: 'clone-container', // Which you want to clone
            removeButtonClass: 'remove-education', // Remove button for remove cloned HTML
            removeConfirm: true, // default true confirm before delete clone item
            removeConfirmMessage: 'Are you sure want to delete?', // confirm delete message
            minLimit: 1, // Default 1 set minimum clone HTML required
            maxLimit: 5, // Default unlimited or set maximum limit of clone HTML
            append: '<div>Hi i am appended</div>', // Set extra HTML append to clone HTML
            excludeHTML: ".exclude", // remove HTML from cloned HTML
            defaultRender: 1, // Default 1 render clone HTML
            init: function() {
                console.info(':: Initialize Plugin ::');
            },
            beforeRender: function() {
                console.info(':: Before rendered callback called');
            },
            afterRender: function() {
                console.info(':: After rendered callback called'); // Return clone object
            },
            afterRemove: function() {
                console.warn(':: After remove callback called');
            },
            beforeRemove: function() {
                console.warn(':: Before remove callback called');
            }
        });
 *
 *
 * @param: string	excludeHTML - A jQuery selector used to exclude an element and its children
 * @param: integer	maxLimit - The number of allowed copies. Default: 0 is unlimited
 * @param: string	append - HTML to attach at the end of each copy. Default: remove link
 * @param: string	copyClass - A class to attach to each copy
 * @param: boolean	clearInputs - Option to clear each copies text input fields or textarea
 *
 */

(function ($) {

    $.fn.cloneData = function (options, callback) {

        var settings = jQuery.extend({
            mainContainerId: "clone-container",
            cloneContainer: "clone-item",
            excludeHTML: ".exclude",
            emptySelector: ".empty",
            copyClass: "clone-div",
            removeButtonClass: "remove-item",
            removeConfirm: false,
            removeConfirmMessage: 'Are you sure?',
            append: '',
            template: null,
            clearInputs: true,
            maxLimit: 0, // 0 = unlimited
            minLimit: 1, // 0 = unlimited
            minLimitAlert: '', // 0 = unlimited
            defaultRender: true, // true = render/initialize one clone
            counterIndex: 0,
            select2InitIds: [],
            ckeditorIds: [],
            regexID: /^(.+?)([-\d-]{1,})(.+)$/i,
            regexName: /(^.+?)([\[\d{1,}\]]{1,})(\[.+\]$)/i,
            init: function () { },
            complete: function () { },
            beforeRender: function () { },
            afterRender: function () { },
            beforeRemove: function () { },
            afterRemove: function () { },
        }, options);

        if (typeof callback === 'function') { // make sure the after callback is a function
            callback.call(this); // brings the scope to the after callback
        }

        // call the beforeRender and apply the scope:
        //console.log('init called from library'+ $('#' + settings.mainContainerId).find('.'+settings.cloneContainer).length);
        settings.init.call({ index: settings.counterIndex });

        var _addItem = function () {

            settings.counterIndex = $('#' + settings.mainContainerId + ' .' + settings.cloneContainer).length;
            settings.beforeRender.call(this);

            // stop append HTML if maximum limit exceed
            if (settings.maxLimit != 0 && settings.counterIndex >= settings.maxLimit) {
                alert("Max limit exceeded!");
                return false;
            }

            $('#' + settings.mainContainerId).append(settings.template.first()[0].outerHTML);

            _initializePlugins();
            _updateAttributes();

            // afterRender.apply(this, Array.prototype.slice.call(arguments, 1));
            //$(settings.template.first()[0].outerHTML).trigger('afterRender');
            ///$elem.closest('.' + widgetOptions.widgetContainer).triggerHandler(events.limitReached, widgetOptions.limit);

            settings.afterRender.call({ index: settings.counterIndex });
            return false;
        }

        var _updateAttributes = function () {

            $('#' + settings.mainContainerId + ' .' + settings.cloneContainer).each(function (index) {
                $(this).find('*').each(function () {
                    _updateAttrID($(this), index);
                    _updateAttrName($(this), index);
                });
            });

            $('#' + settings.mainContainerId).addClass('clone-data');
            $('#' + settings.mainContainerId + ' .' + settings.cloneContainer).each(function (parent_index, item) {
                $(this).attr('data-index', parent_index).addClass(settings.copyClass);
            });


            $('.' + settings.cloneContainer + '.' + settings.copyClass).each(function (parent_index, item) {
                $(item).find('[for]').each(function () {
                    $(this).attr('for', $(this).attr('for').replace(/.$/, parent_index));
                });

                settings.complete({ index: settings.counterIndex });

            });
        }

        var _updateAttrID = function ($elem, index) {
            //var widgetOptions = eval($elem.closest('div[data-dynamicform]').attr('data-dynamicform'));
            var id = $elem.attr('id');
            var newID = id;

            if (id !== undefined) {
                newID = _incrementLastNumber(id, index);
                $elem.attr('id', newID);
            }

            if (id !== newID) {
                $elem.closest('.' + settings.cloneContainer).find('.field-' + id).each(function () {
                    $(this).removeClass('field-' + id).addClass('field-' + newID);
                });
                // update "for" attribute
                $elem.closest('.' + settings.cloneContainer).find("label[for='" + id + "']").attr('for', newID);
            }

            return newID;
        }

        var _incrementLastNumber = function (string, index) {
            return string.replace(/[0-9]+(?!.*[0-9])/, function (match) {
                return index;
            });
        }

        var _updateAttrName = function ($elem, index) {
            var name = $elem.attr('name');

            if (name !== undefined) {
                var matches = name.match(settings.regexName);

                if (matches && matches.length === 4) {
                    matches[2] = matches[2].replace(/\]\[/g, "-").replace(/\]|\[/g, '');
                    var identifiers = matches[2].split('-');
                    identifiers[0] = index;

                    if (identifiers.length > 1) {
                        var widgetsOptions = [];
                        $elem.parents('.' + settings.mainContainerId).each(function (i) {
                            widgetsOptions[i] = eval($(this).find('#' + settings.mainContainerId));
                        });

                        widgetsOptions = widgetsOptions.reverse();
                        for (var i = identifiers.length - 1; i >= 1; i--) {
                            identifiers[i] = $elem.closest('.' + settings.cloneContainer).closest('#' + settings.mainContainerId).index();
                        }
                    }

                    name = matches[1] + '[' + identifiers.join('][') + ']' + matches[3];
                    $elem.attr('name', name);
                }
            }

            return name;
        };

        var _parseTemplate = function () {
            var template_clone = $('#' + settings.mainContainerId + ' .' + settings.cloneContainer + ":first");

            var $template = $(template_clone).clone(false, false);
            //console.log($template);

            $template.find('input, textarea, select').each(function () {
                if ($(this).is(':checkbox') || $(this).is(':radio')) {
                    var type = ($(this).is(':checkbox')) ? 'checkbox' : 'radio';
                    var inputName = $(this).attr('name');
                    var $inputHidden = $template.find('input[type="hidden"][name="' + inputName + '"]').first();
                    var count = $template.find('input[type="' + type + '"][name="' + inputName + '"]').length;

                    if ($inputHidden && count === 1) {
                        $(this).val(1);
                        $inputHidden.val(0);
                    }

                    //$(this).prop('checked', false);
                    $(this).removeAttr("checked");
                } else if ($(this).is('select')) {
                    $(this).find('option:selected').removeAttr("selected");
                } else if ($(this).is('file')) {
                    $(this).parents('.fileinput').find('.previewing').attr('src', SITE_CONSTANT['DEFAULT_IMAGE_ADMIN']);
                    $(this).parents('.fileinput').find('.fileinput-preview img').attr('src', SITE_CONSTANT['DEFAULT_IMAGE_ADMIN']);
                    $(this).parents('.fileinput').find('.check-file-remove').hide();
                    $(this).parents('.fileinput').find('.check-file-change').hide();
                    $(this).parents('.fileinput').find('.check-file-select').show();
                } else if ($(this).is('textarea')) {
                    $(this).html("");
                } else {
                    //$(this).val('');
                    $(this).removeAttr("value");
                }

            });

            /* Remove chosen extra html */
            $template.find('.chosen-container').each(function () {
                $(this).remove();
            });

            if ($template.find('.select2-container').length > 0) {
                $template.find('.select2-container').each(function () {
                    $(this).remove();
                });
            }

            $template.find('.select2-container').remove();

            //Remove Elements with excludeHTML
            if (settings.excludeHTML) {
                $(settings.template).find(settings.excludeHTML).remove();
            }

            //Empty Elements with emptySelector
            if (settings.emptySelector) {
                $(settings.template).find(settings.emptySelector).empty();
            }

            /* Render default HTML container */
            if (!settings.defaultRender) {
                /* html remove after store and remove extra HTML */
                $('.' + option.cloneContainer + ":first").remove();
            }

            //$template.find('input').find('input').val('');

            //console.log($template.first()[0].outerHTML);
            settings.template = $template;
        };

        var _initializePlugins = function () {
            /* Initialize again chosen dropdown after render HTML */
            if ($('.chosen-init').length > 0) {
                $('.chosen-init').each(function () {
                    $(this).chosen().trigger('chosen:update');
                });
            }

            if ($('.select2').length > 0) {
                $('.select2').each(function () {
                    $(this).select2({ width: '100%' }).trigger('select2:update');
                });
            }

            if ($.fn.datepicker && $('.datepicker-init').length > 0) {
                $('.datepicker-init').datepicker({ autoclose: true });
            }

            if ($.fn.datetimepicker && $('.datetimepicker-init').length > 0) {
                $('.datetimepicker-init').datetimepicker({ autoclose: true });
            }

            if ($.fn.select2 && settings.select2InitIds.length > 0) {
                //console.warn(settings.select2InitIds);
                $.each(settings.select2InitIds, function (index, id) {
                    $(id).select2({
                        placeholder: "Select",
                        width: "300px;",
                        allowClear: true
                    })

                });
                settings.select2InitIds = [];
            }

            if (window.CKEDITOR && settings.ckeditorIds.length > 0) {
                $.each(settings.ckeditorIds, function (index, id) {
                    CKEDITOR.replace(id);

                    var $ids = $('[id=cke_' + id + ']');
                    if ($ids.length > 0) {
                        //console.log($ids);
                        $ids.remove();
                    }
                });
                settings.ckeditorIds = [];
            }

            if (typeof $.material !== 'undefined') {
                $.material.init();
            }
        }

        var _deleteItem = function ($elem) {

            var count = _count();
            if (count > settings.minLimit) {
                if (settings.removeConfirm) {
                    if (confirm(settings.removeConfirmMessage)) {
                        $elem.parents('.' + settings.cloneContainer).slideUp(function () {
                            $(this).remove();
                            _updateAttributes();
                            settings.afterRemove.call(this);
                        });
                    }
                }
                else {
                    $elem.parents('.' + settings.cloneContainer).slideUp(function () {
                        $(this).remove();
                        _updateAttributes();
                        settings.afterRemove.call(this);
                    });
                }
            } else {
                alert('you must have at least one item.');
            }
        };

        var _count = function () {
            return $('.' + settings.cloneContainer).closest('#' + settings.mainContainerId).find('.' + settings.cloneContainer).length;
        };


        $(document).on('click', '.' + settings.removeButtonClass, function () {
            settings.beforeRemove.call(this);
            _deleteItem($(this));
        });


        // loop each element
        this.each(function () {
            $(this).click(function () {
                _addItem();
            });
            _parseTemplate();
            _updateAttributes();
        });

        return this; // return to jQuery
    };

})(jQuery);
