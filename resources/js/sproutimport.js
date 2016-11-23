if (typeof Craft.SproutImport === typeof undefined) {
	Craft.SproutImport = {};
}

Craft.SproutImport.Seed = {

	init: function() {
		$('#elementType').change(function() {
			Craft.SproutImport.Seed.selectElementTypeEvent();
		});

		$('#sectionType').change(function() {
			Craft.SproutImport.Seed.selectSectionEvent();
		});
	},

	selectElementTypeEvent: function() {
		$('.element-options').hide();
		$('.' + $('#elementType').val()).show();
	},

	selectSectionEvent: function() {
		$('.section-options').hide();
		$('.' + $('#sectionType').val()).show();
	}

}

$(document).ready(function() {
	Craft.SproutImport.Seed.init();

	$('#elementType').val('Entry');
	$('.element-options.Entry').show();
});