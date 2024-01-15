var ncore = ncore || {};

ncore.dynamicObjectLists = {};

(function ($) {
    ncore.dynamicObjectList = function(post_name,popup_id,popup_fields,line_preview_content,edit_button_html,delete_button_html,defaults) {
        var that = this;

        this.before_input_field = ncore.helpers.prefix;

        this.$list = null;
        this.$popup = null;

        this.popup_id = popup_id;
        this.post_name = post_name;

        this.edit_button_html = edit_button_html;
        this.delete_button_html = delete_button_html;

        this.popup_fields = popup_fields;
        this.line_preview_content = line_preview_content;
        this.in_edit = false;
        this.edit_id = null;

        this.data = [];
        this.defaults = defaults;

        ///////////////////////////////////////////////////////////////
        this.init = function() {
            that.$list = $('ul[data-name="' + that.post_name + '"]');
            that.$popup = $('#' + that.popup_id);
            that.$list.find('button[data-role="add_button"]').on('click.ncore',function() {
                that.edit_reset();
                that.in_edit = false;
            });

            for (var i in that.defaults) {
                that.add_entry(that.defaults[i]);
            }
        };
        ///////////////////////////////////////////////////////////////
        this.add_entry = function(data_obj) {
            var entry = that.data_get(data_obj.id);
            if (entry !== null) {
                return;
            }

            var line_preview = that.interpret_line_preview_content(data_obj);

            that.data.push(data_obj);

            var id = data_obj.id;

            var $li = $('<li style="margin-bottom: 20px;">' + line_preview + '</li>').attr('id',id);
            var $delete_button = $(that.delete_button_html).on('click.ncore',function() {
                that.data_delete(id);
                that.dom_delete(id);
            });

            var $edit_button = $(that.edit_button_html).on('click.ncore',function() {
                that.edit_start(id);
            });

            if (data_obj.error !== undefined) {
                $li.css('background-color','#EA595B');
            }

            // Hidden fields
            var hidden_fields = that.generate_hidden_fields(data_obj);

            $li.prepend($delete_button).prepend($edit_button).append(hidden_fields);

            that.$list.append($li);
        };
        ///////////////////////////////////////////////////////////////
        this.popup_submit_form = function() {
            var validated_fields = that.popup_validate_fields();

            if (validated_fields !== false) {
                if (that.in_edit) {
                    that.data_delete(that.edit_id);
                    that.dom_delete(that.edit_id);
                }

                var id = ncore.helpers.string.random(15);
                var fields = that.data_prepare_fields(validated_fields);
                var data_obj = {
                    id: id,
                    fields:fields
                };
                that.add_entry(data_obj);

                // Close Manually
                that.$popup.dialog('close');
            }
            else {
                return false;
            }
        };
        this.popup_validate_fields = function() {
            var return_obj = {};
            var validation_ok = true;

            for (var i in that.popup_fields) {
                var field_obj = that.popup_fields[i];

                var field_name = ncore.helpers.prefix + field_obj.name;
                var $field = that.$popup.find('[name="' + field_name + '"]');

                field_obj.value = ncore.helpers.form.field.getValue(field_obj);
                ncore.helpers.form.field.setDefault(field_obj);
                var validation_result = ncore.helpers.validateField(field_obj);
                if (validation_result === true) {
                    return_obj[field_obj.name] = field_obj;
                }
                else {
                    ncore.helpers.form.field.setError(field_obj,validation_result);
                    validation_ok = false;
                }
            }

            if (!validation_ok) {
                return false;
            }
            else {
                return return_obj;
            }
        };
        ///////////////////////////////////////////////////////////////
        this.generate_hidden_fields = function(data) {
            var id = data.id;
            var html = [];
            for (var key in data.fields) {
                var value = data.fields[key];

                html.push('<input type="hidden" data-for="' + id + '" name="' + that.post_name + '_posted[' + id + '][' + key + ']" value="' + ncore.helpers.string.htmlentities(value) + '">');
            }

            return html.join('');
        };
        ///////////////////////////////////////////////////////////////
        this.edit_reset = function() {
            for (var i in that.popup_fields) {
                var field_obj = that.popup_fields[i];

                ncore.helpers.form.field.reset(field_obj);
            }
            that.edit_trigger_dependson();
        };
        this.edit_trigger_dependson = function() {
            for (var i in that.popup_fields) {
                var field_obj = that.popup_fields[i];

                if (field_obj.type !== 'htmleditor') {
                    var $field = ncore.helpers.form.field.getDOM(field_obj);
                    $field.change().keyup();
                }
            }
        };
        this.edit_start = function(id) {
            var data_obj = that.data_get(id);
            if (data_obj !== null) {
                for (var name in data_obj.fields) {
                    ncore.helpers.form.field.setValue(name,data_obj.fields[name]);
                }

                that.in_edit = true;
                that.edit_id = id;

                that.edit_trigger_dependson();

                that.$popup.dialog('open');
            }
        };
        ///////////////////////////////////////////////////////////////
        this.data_get = function(id) {
            for (var i in that.data) {
                if (that.data[i].id === id) {
                    return that.data[i];
                }
            }

            return null;
        };
        this.data_delete = function(id) {
            for (var i in that.data) {
                if (that.data[i].id === id) {
                    that.data.splice(i,1);
                    return true;
                }
            }

            return false;
        };
        this.dom_delete = function(id) {
            $('#' + id).remove();
        };
        this.data_prepare_fields = function(validated_fields) {
            var fields = {};
            for (var name in validated_fields) {
                fields[name] = validated_fields[name].value;
            }

            return fields;
        };
        ///////////////////////////////////////////////////////////////
        this.interpret_line_preview_content = function(data_obj) {
            return ncore.helpers.interpretLineLanguage(that.line_preview_content,data_obj.fields);
        };
        ///////////////////////////////////////////////////////////////
        this.init();
    };
})(jQuery);

// PUT THIS INTO ANOTHER FILE / PLACE
ncore.helpers = {
    _: {},
    prefix: 'ncore_',
    form: {
        field: {
            reset: function(field) {
                var $field = ncore.helpers.form.field.getDOM(field);
                if ($field !== undefined) {
                    if (field.type === 'htmleditor' && typeof tinyMCE.editors[field.name] !== 'undefined') {
                        $field.setContent('');
                    }
                    else {
                        if ($field.is('[type="hidden"]')) {
                            $field = $field.parent().find('select,input[type!="hidden"],textarea');
                        }
                        $field.each(function() {
                            var $this = jQuery(this);
                            if ($this.is('select')) {
                                // The weirdest Bug....
                                window.setTimeout(function() {
                                    $this.val($this.find('option:first').val());
                                },1);

                            }
                            else {
                                if (!$this.is('input[type="checkbox"]')) {
                                    $this.val('');
                                }
                            }
                        });
                    }
                }
            },
            setValue: function(fieldname,value) {
                var $field = jQuery('[name="' + ncore.helpers.prefix + fieldname + '"]');
                if ($field.is('.wp-editor-area') && typeof tinyMCE.editors[fieldname] !== 'undefined') {
                    tinyMCE.editors[fieldname].setContent(value);
                }
                else {
                    $field.val(value);
                }
            },
            getDOM: function(field) {
                // Maybe add more later
                if (field.type === 'htmleditor' && typeof tinyMCE.editors[field.name] !== 'undefined') {
                    return tinyMCE.editors[field.name];
                }
                else {
                    return jQuery('[name="' + ncore.helpers.prefix + field.name + '"]');
                }
            },
            getValue: function(field) {
                // Maybe add more later
                if (field.type === 'htmleditor' && typeof tinyMCE.editors[field.name] !== 'undefined') {
                    var val = tinyMCE.editors[field.name].getContent();
                    if (val === '') {
                        return jQuery('[name="' + ncore.helpers.prefix + field.name + '"]').val();
                    }
                    return val;
                }
                else {
                    return jQuery('[name="' + ncore.helpers.prefix + field.name + '"]').val();
                }
            },
            setError: function(field_obj,validation_result) {
                var $field = ncore.helpers.form.field.getDOM(field_obj);
                if (field_obj.type === 'htmleditor' && typeof tinyMCE.editors[field_obj.name] !== 'undefined') {
                    $field = jQuery($field.iframeElement);
                }
                validation_result = (validation_result === false) ? ncore.helpers._.validation.general : validation_result;
                $field.parents('tr').addClass('formerror').parents('form').prepend('<div class="error ncore_error ncore-error-' + field_obj.name + '" style="height: 16px; padding: 2px; line-height: 8px;"><p style="padding: 0; margin: 4px; line-height: 8px;"><strong style="font-size: 8px; line-height: 8px;">' + validation_result.replace('[NAME]',field_obj.label) + '</strong></p></div>');
            },
            setDefault: function(field_obj) {
                var $field = ncore.helpers.form.field.getDOM(field_obj);
                if (field_obj.type === 'htmleditor' && typeof tinyMCE.editors[field_obj.name] !== 'undefined') {
                    $field = jQuery($field.iframeElement);
                }
                $field.parents('tr').removeClass('formerror').parents('form').find('.ncore-error-' + field_obj.name).remove();
            }
        }
    },
    validateField: function(field) {
        if (field.rules !== undefined) {
            if (field.rules === '') {
                return true;
            }
            if (field.depends_on instanceof Object) {
                if (!ncore.helpers.checkDependencies(field.depends_on)) {
                    return true;
                }
            }

            var rule_split = field.rules.split('|');

            for (var i in rule_split) {
                if (ncore.helpers.validationRules[rule_split[i]] !== undefined) {
                    if (ncore.helpers.validationRules[rule_split[i]](field.value) === false) {
                        if (ncore.helpers._.validation !== undefined) {
                            if (ncore.helpers._.validation[rule_split[i]] !== undefined) {
                                return ncore.helpers._.validation[rule_split[i]];
                            }
                            else {
                                return false;
                            }
                        }
                        else {
                            return false;
                        }
                    }
                }
            }

            return true;
        }
        else {
            return true;
        }
    },
    validationRules: {
        numeric: function(value) {
            return (jQuery.isNumeric(value)) ? value : false;
        },
        email: function(value) {
            // Rather simple method, might change in the future for reliable regex
            var atpos = value.indexOf('@');
            var dotpos = value.lastIndexOf('.');
            return (atpos < 1 || dotpos < atpos + 2 || dotpos + 2 >= value.length) ? false : value;
        },
        required: function(value) {
            return (value !== '') ? value : false;
        },
        time: function(value) {
            var ex = new RegExp(/([0-9]{1,2}):([0-9]{1,2})/);
            return ex.test(value);
        }
    },
    checkDependencies: function(dependencies) {
        if (dependencies instanceof Object) {
            var still_true = true;

            for (var key in dependencies) {
                var value_or_values = dependencies[key];
                var values = (value_or_values instanceof Object) ? value_or_values : [value_or_values];

                var value = jQuery('[name="' + ncore.helpers.prefix + key + '"]').val();
                if (value !== undefined) {
                    for (var i in values) {
                        var value_key = values[i];
                        var is_neg = false;
                        if (value_key.indexOf('!= ') > -1) {
                            value_key = value_key.replace('!= ','');
                            is_neg = true;
                        }
                        if ((!is_neg && value_key != value) || (is_neg && value_key == value)) {
                            still_true = false;
                        }
                    }
                }
                else {
                    still_true = false;
                }

            }

            return still_true;
        }
        else {
            return false;
        }
    },
    interpretLineLanguage: function(line,fields) {
        var that_interpret = this;

        this.analyse_conditions = function(line) {
            var new_line = line;

            var conditions = line.match(/\%\_\%(.*?)\%\_\%/g);

            if (conditions !== null) {
                for (var i in conditions) {
                    var condition_split = conditions[i].replace(/\%\_\%/g,'').split('%;%');
                    var _when = condition_split[0];
                    var _then = condition_split[1];
                    var _else = condition_split[2];

                    var when_split_value = '=';
                    if (_when.indexOf('!=') > -1) {
                        when_split_value = '!=';
                    }
                    var _when_split = _when.split(when_split_value);
                    var condition_result = null;

                    var condition_value = fields[_when_split[0]];
                    if (condition_value !== undefined) {
                        if (when_split_value === '=') {
                            condition_result = (condition_value == _when_split[1]);
                        }
                        else {
                            condition_result = (condition_value != _when_split[1]);
                        }
                    }
                    else {
                        condition_result = false;
                    }

                    new_line = new_line.replace(conditions[i],that_interpret.analyse_values((condition_result) ? _then : _else));
                }
            }

            return that_interpret.analyse_values(new_line);
        };

        this.analyse_values = function(line) {
            var new_line = line;

            var elements = line.match(/\{\{(.*?)\}\}/g);
            for (var i in elements) {
                var element = elements[i].replace('{{','').replace('}}','');

                // sections[0] = input name; sections[1] = format type; sections[2] = options for formatting; ...
                var sections = element.split('|');
                var replace_value = fields[sections[0]];
                if (sections[1] !== undefined) {
                    // Int
                    if (sections[1] === 'int') {
                        if (ncore.helpers.validationRules.numeric(sections[2])) {
                            replace_value = ncore.helpers.format.int(replace_value,sections[2]);
                        }
                    }
                    // String
                    if (sections[1] === 'string') {
                        if (sections[2] !== undefined) {
                            var replaces_split = sections[2].split(';');
                            var replaces = {};

                            for (var x in replaces_split) {
                                var equals = replaces_split[x].split('=');
                                replaces[equals[0]] = equals[1];
                            }
                            replace_value = ncore.helpers.format.string(replace_value,replaces);
                        }
                    }

                    // Time
                    if (sections[1] === 'time') {
                        var spl = replace_value.split(':');
                        if (spl.length === 2) {
                            var hours = spl[0];
                            var minutes = spl[1];

                            hours = ncore.helpers.format.int(hours,2);
                            minutes = ncore.helpers.format.int(minutes,2);

                            replace_value = hours + ':' + minutes;
                        }
                    }
                }

                new_line = new_line.replace(elements[i],replace_value);
            }

            return new_line;
        };

        return this.analyse_conditions(line);
    },
    logic: {
        isset: function() {
            var a = arguments,
            l = a.length,
            i = 0,
            undef;

            if (l === 0) {
                throw new Error('Empty isset');
            }

            while (i !== l) {
                if (a[i] === undef || a[i] === null) {
                    return false;
                }
                i++;
            }
            return true;
        }
    },
    string: {
        random: function (len, an){
            an = an && an.toLowerCase();
            var str = "";
            var min = an == "a" ? 10 : 0;
            var max = an == "n" ? 10 : 62;
            for (var i=0;i<len;i++) {
                var r = Math.random() * (max - min) + min << 0;
                str += String.fromCharCode(r += r > 9 ? r < 36 ? 55 : 61 : 48);
            }
            return str;
        },
        ltrim: function(str, charlist) {
            charlist = !charlist ? ' \\s\u00A0' : (charlist + '').replace(/([\[\]\(\)\.\?\/\*\{\}\+\$\^\:])/g, '$1');
            var re = new RegExp('^[' + charlist + ']+', 'g');
            return (str + '').replace(re, '');
        },
        htmlentities: function(string, quote_style, charset, double_encode) {
            // Source: http://phpjs.org/functions/htmlentities/
            var hash_map = ncore.helpers.string.get_html_translation_table('HTML_ENTITIES', quote_style), string = string == null ? '' : string + '';

            if (!hash_map) {
                return false;
            }

            if (quote_style && quote_style === 'ENT_QUOTES') {
                hash_map["'"] = '&#039;';
            }

            if ( !! double_encode || double_encode == null) {
                for (symbol in hash_map) {
                    if (hash_map.hasOwnProperty(symbol)) {
                        string = string.split(symbol).join(hash_map[symbol]);
                    }
                }
            }
            else {
                string = string.replace(/([\s\S]*?)(&(?:#\d+|#x[\da-f]+|[a-zA-Z][\da-z]*);|$)/g, function(ignore, text, entity) {
                    for (symbol in hash_map) {
                        if (hash_map.hasOwnProperty(symbol)) {
                            text = text.split(symbol).join(hash_map[symbol]);
                        }
                    }

                    return text + entity;
                });
            }

            return string;
        },
        get_html_translation_table: function(table, quote_style) {
            // Source: http://phpjs.org/functions/get_html_translation_table/
            var entities = {},hash_map = {},decimal;
            var constMappingTable = {},constMappingQuoteStyle = {};
            var useTable = {},useQuoteStyle = {};

            // Translate arguments
            constMappingTable[0] = 'HTML_SPECIALCHARS';
            constMappingTable[1] = 'HTML_ENTITIES';
            constMappingQuoteStyle[0] = 'ENT_NOQUOTES';
            constMappingQuoteStyle[2] = 'ENT_COMPAT';
            constMappingQuoteStyle[3] = 'ENT_QUOTES';

            useTable = !isNaN(table) ? constMappingTable[table] : table ? table.toUpperCase() : 'HTML_SPECIALCHARS';
            useQuoteStyle = !isNaN(quote_style) ? constMappingQuoteStyle[quote_style] : quote_style ? quote_style.toUpperCase() : 'ENT_COMPAT';

            if (useTable !== 'HTML_SPECIALCHARS' && useTable !== 'HTML_ENTITIES') {
                throw new Error('Table: ' + useTable + ' not supported');
                // return false;
            }

            entities['38'] = '&amp;';
            if (useTable === 'HTML_ENTITIES') {
                entities['160'] = '&nbsp;';
                entities['161'] = '&iexcl;';
                entities['162'] = '&cent;';
                entities['163'] = '&pound;';
                entities['164'] = '&curren;';
                entities['165'] = '&yen;';
                entities['166'] = '&brvbar;';
                entities['167'] = '&sect;';
                entities['168'] = '&uml;';
                entities['169'] = '&copy;';
                entities['170'] = '&ordf;';
                entities['171'] = '&laquo;';
                entities['172'] = '&not;';
                entities['173'] = '&shy;';
                entities['174'] = '&reg;';
                entities['175'] = '&macr;';
                entities['176'] = '&deg;';
                entities['177'] = '&plusmn;';
                entities['178'] = '&sup2;';
                entities['179'] = '&sup3;';
                entities['180'] = '&acute;';
                entities['181'] = '&micro;';
                entities['182'] = '&para;';
                entities['183'] = '&middot;';
                entities['184'] = '&cedil;';
                entities['185'] = '&sup1;';
                entities['186'] = '&ordm;';
                entities['187'] = '&raquo;';
                entities['188'] = '&frac14;';
                entities['189'] = '&frac12;';
                entities['190'] = '&frac34;';
                entities['191'] = '&iquest;';
                entities['192'] = '&Agrave;';
                entities['193'] = '&Aacute;';
                entities['194'] = '&Acirc;';
                entities['195'] = '&Atilde;';
                entities['196'] = '&Auml;';
                entities['197'] = '&Aring;';
                entities['198'] = '&AElig;';
                entities['199'] = '&Ccedil;';
                entities['200'] = '&Egrave;';
                entities['201'] = '&Eacute;';
                entities['202'] = '&Ecirc;';
                entities['203'] = '&Euml;';
                entities['204'] = '&Igrave;';
                entities['205'] = '&Iacute;';
                entities['206'] = '&Icirc;';
                entities['207'] = '&Iuml;';
                entities['208'] = '&ETH;';
                entities['209'] = '&Ntilde;';
                entities['210'] = '&Ograve;';
                entities['211'] = '&Oacute;';
                entities['212'] = '&Ocirc;';
                entities['213'] = '&Otilde;';
                entities['214'] = '&Ouml;';
                entities['215'] = '&times;';
                entities['216'] = '&Oslash;';
                entities['217'] = '&Ugrave;';
                entities['218'] = '&Uacute;';
                entities['219'] = '&Ucirc;';
                entities['220'] = '&Uuml;';
                entities['221'] = '&Yacute;';
                entities['222'] = '&THORN;';
                entities['223'] = '&szlig;';
                entities['224'] = '&agrave;';
                entities['225'] = '&aacute;';
                entities['226'] = '&acirc;';
                entities['227'] = '&atilde;';
                entities['228'] = '&auml;';
                entities['229'] = '&aring;';
                entities['230'] = '&aelig;';
                entities['231'] = '&ccedil;';
                entities['232'] = '&egrave;';
                entities['233'] = '&eacute;';
                entities['234'] = '&ecirc;';
                entities['235'] = '&euml;';
                entities['236'] = '&igrave;';
                entities['237'] = '&iacute;';
                entities['238'] = '&icirc;';
                entities['239'] = '&iuml;';
                entities['240'] = '&eth;';
                entities['241'] = '&ntilde;';
                entities['242'] = '&ograve;';
                entities['243'] = '&oacute;';
                entities['244'] = '&ocirc;';
                entities['245'] = '&otilde;';
                entities['246'] = '&ouml;';
                entities['247'] = '&divide;';
                entities['248'] = '&oslash;';
                entities['249'] = '&ugrave;';
                entities['250'] = '&uacute;';
                entities['251'] = '&ucirc;';
                entities['252'] = '&uuml;';
                entities['253'] = '&yacute;';
                entities['254'] = '&thorn;';
                entities['255'] = '&yuml;';
            }

            if (useQuoteStyle !== 'ENT_NOQUOTES') {
                entities['34'] = '&quot;';
            }
            if (useQuoteStyle === 'ENT_QUOTES') {
                entities['39'] = '&#39;';
            }
            entities['60'] = '&lt;';
            entities['62'] = '&gt;';

            // ascii decimals to real symbols
            for (decimal in entities) {
                if (entities.hasOwnProperty(decimal)) {
                    hash_map[String.fromCharCode(decimal)] = entities[decimal];
                }
            }

            return hash_map;
        }

    },
    format: {
        int: function(value,digits) {
            var zero_string = '';
            for (var i=0;i<digits-1;i++) {
                zero_string += '0';
            }
            if (value.length > digits) {
                digits = value.length;
            }
            return (zero_string + value).slice(digits * -1);
        },
        string: function (value,replaces) {
            for (var find in replaces) {
                value = value.replace(find,replaces[find]);
            }
            return value;
        }
    }
};

// Dialog TinyMCE Fix
(function($) {
    $(document).ready(function() {
        $(document).on('focusin', function(e) {
            var $e = $(e.target);
            if ($e.is('input')) {
                e.stopImmediatePropagation();
            }
        });
    });
    $(document).ready(function() {
        $.widget("ui.dialog", $.ui.dialog, {
            _allowInteraction: function(e) {
                return true;//!!$(event.target).closest(".wp-editor-container").length || this._super( event );
            }
        });
    });
})(jQuery);