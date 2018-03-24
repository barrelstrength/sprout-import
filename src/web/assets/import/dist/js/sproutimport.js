if (typeof Craft.SproutImport === typeof undefined) {
    Craft.SproutImport = {};
}

Craft.SproutImport.Seed = {

    init: function() {
        'use strict';

        $('#elementType').change(function() {
            Craft.SproutImport.Seed.selectElementTypeEvent();
        });

        $('#sectionType').change(function() {
            Craft.SproutImport.Seed.selectSectionEvent();
        });
    },

    selectElementTypeEvent: function() {
        'use strict';

        $('.element-options').hide();
        var val = $('#elementType').val().replace(/\\/g, '-');

        $('.' + val).show();
    },

    selectSectionEvent: function() {
        'use strict';

        $('.section-options').hide();

        var val = $('#sectionType').val().replace(/\\/g, '-');

        $('.' + val).show();
    }
};