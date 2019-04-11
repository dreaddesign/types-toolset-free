jQuery(document).ready(function () {
    var wptoolset_taxonomy_settings_instances = wptoolset_taxonomy_settings['instances'];
    for(var taxonomy_settings_instance_index in wptoolset_taxonomy_settings_instances){
        var currentTaxonomySettings = wptoolset_taxonomy_settings_instances[taxonomy_settings_instance_index];
        initTaxonomies(currentTaxonomySettings.values, currentTaxonomySettings.name, currentTaxonomySettings.form, currentTaxonomySettings.field);
    }
    jQuery('head').append('<style>.wpt-suggest-taxonomy-term{position:absolute;display:none;min-width:100px;outline:#ccc solid 1px;padding:0;background-color:Window;overflow:hidden}.wpt-suggest-taxonomy-term li{margin:0;padding:2px 5px;cursor:pointer;display:block;width:100%;font:menu;font-size:12px;overflow:hidden}.wpt-suggest-taxonomy-term-select{background-color:Highlight;color:HighlightText}</style>');
});