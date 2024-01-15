

 (function() {

     tinymce.create('tinymce.plugins.digimember', {

            init : function(ed, url) {

                ed.addButton('digimember', {

                    title : (typeof __ncore_tinymce_helper == 'function'
                            ? __ncore_tinymce_helper( 'tinymce_button_tooltip' )
                            : ''),

                    image : url+'/../image/icon/tinymce.png',

                    onclick : function() {
                        digimember_tinymce_handleShortcode( ed )
                    }
                });
            },

            createControl : function(n, cm) {

                return null;

            },

        });

        tinymce.PluginManager.add('digimember', tinymce.plugins.digimember);

    })();