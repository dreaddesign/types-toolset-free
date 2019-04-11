var Toolset = Toolset || {};

Toolset.Gui = Toolset.Gui || {};

/**
 * Generic listing page controller.
 *
 * See the AbstractPage controller for (much) more information.
 *
 * @constructor
 * @since 2.2
 */
Toolset.Gui.ListingPage = function() {

    var self = this;

    Toolset.Gui.AbstractPage.call(self);

    // Preserve the implementation in AbstractPage.
    var parentInitMainViewModel = self.initMainViewModel;

    self.initMainViewModel = function() {

        parentInitMainViewModel();

        // Focus the search field so that the user can start typing immediately.
        self.getPageContent().find('.toolset-field-search').focus();

        // Immediately apply the item per page setting without reloading the page.
        jQuery('#toolset_fields_per_page').change(function() {
            $this = jQuery(this);
            var value = $this.val();
            if (value < 1) {
                value = 1;
                $this.val(value);
            }
            self.viewModel.itemsPerPage(value);
        });

    }

};
