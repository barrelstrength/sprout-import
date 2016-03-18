$(document).ready( function() {
	SproutImport.init();
});

var SproutImport = {

	init: function()
	{
		$('#elementType').change(function() {
			SproutImport.selectElementTypeEvent();
		});

		$('#sectionType').change(function() {
			SproutImport.selectSectionEvent();
		});
	},
	selectElementTypeEvent: function()
	{
		$('.element-options').hide();
		$('.' + $('#elementType').val()).show();
	},
	selectSectionEvent: function()
	{
		$('.section-options').hide();
		$('.' + $('#sectionType').val()).show();
	},

}