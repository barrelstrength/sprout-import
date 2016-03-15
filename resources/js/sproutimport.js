$(document).ready( function() {
	SproutImport.init();
});

var SproutImport = {

	init: function()
	{
		$('#elementType').change(function(){
			SproutImport.selectElementTypeEvent();
		});

	},
	selectElementTypeEvent: function()
	{
		$('.element-options').hide();
		$('.' + $('#elementType').val()).show();
	}

}