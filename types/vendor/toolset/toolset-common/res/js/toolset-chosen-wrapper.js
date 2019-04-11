/**
 * Wrapper class for Chosen library
 */

var ToolsetCommon = ToolsetCommon || {};

ToolsetCommon.ToolsetChosenSelector = function( ) {


    jQuery.fn.extend({
        toolset_chosen_multiple: function( params, options, selected_classes ) {

            var chosen_element = jQuery( this ).length === 1 ? jQuery( this ) : jQuery( this.selector );
            var item_selected = '';

            chosen_element.chosen("destroy");

            chosen_element.chosen(params);
            chosen_element.empty().trigger('chosen:updated');


            if(options){
                jQuery.each(options, function( index, value ) {
                    if( jQuery.inArray( value, selected_classes ) > -1 ){
                        item_selected = 'selected';
                    }
                    chosen_element.append( '<option '+item_selected+' value="'+value+'">'+value+'</option>' );
                    item_selected = '';
                });
            }

            jQuery('.chosen-choices').find('input').on('keyup', function(e){

                // if we hit Enter or space and the results list is empty (no matches) add the option
                if (( e.which == 13 || e.which == 32) && jQuery('.chosen-drop').find('li.no-results').length > 0)
                {
                    var option = jQuery("<option>").val(this.value).text(this.value);
                    chosen_element.prepend(option);
                    chosen_element.find(option).prop('selected', true);
                    chosen_element.trigger("chosen:updated");
                }
            });

            _.defer(function(){
                chosen_element.trigger("chosen:updated");
            });


            return this;
        },

    });

    jQuery.fn.extend({
        toolset_chosen_multiple_css_classes: function( params, options, selected_classes ) {

            var chosen_element = jQuery( this ).length === 1 ? jQuery( this ) : jQuery( this.selector );
            var item_selected = '';

            chosen_element.chosen("destroy");

            chosen_element.chosen(params);
            chosen_element.empty().trigger('chosen:updated');


            if(options){
                jQuery.each(options, function( index, value ) {
                    if( jQuery.inArray( value, selected_classes ) > -1 ){
                        item_selected = 'selected';
                    }
                    chosen_element.append( '<option '+item_selected+' value="'+value+'">'+value+'</option>' );
                    item_selected = '';
                });
            }

            jQuery('.chosen-choices').find('input').on('keyup', function(e){

                // if we hit Enter, space or comma
                if ( e.which == 13 || e.which == 32 || e.which == 188 )
                {

                    var multiple_classes = this.value.split(/(?:,| )+/); // split by space or comma

                    if( multiple_classes.length > 0 ) {

                        // remove duplicates and empty strings
                        multiple_classes = _.compact(_.uniq( multiple_classes ) );

                        _.each( multiple_classes, function(single_class) {
                            var option = jQuery("<option>").val(single_class).text(single_class);
                            chosen_element.prepend(option);
                            chosen_element.find(option).prop('selected', true);
                            chosen_element.trigger("chosen:updated");

                        });

                    } else {
                        var option = jQuery("<option>").val(this.value).text(this.value);
                        chosen_element.prepend(option);
                        chosen_element.find(option).prop('selected', true);
                        chosen_element.trigger("chosen:updated");
                    }
                }
            });

            _.defer(function(){
                chosen_element.trigger("chosen:updated");
            });


            return this;
        },

    });

};

jQuery(document).ready(function($){ ToolsetCommon.ToolsetChosenSelector(); });