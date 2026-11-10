(function( $ ) {
	$.fn.defaultButton = function(selector) {
		if (selector == null) {
			selector = 'input[type=submit]:visible:first';
		}
		$(this).live('keypress', function(e) {
			if (!((e.which && e.which === 13) || (e.keyCode && e.keyCode === 13))) {
				return this;
			}
//			({
				firstSubmit = $(this).closest('form').find(selector);
//			});
			if (firstSubmit) {
				firstSubmit.click();
				return false;
			} else {
				return true;
			}
		});
		return this;
	};
})( jQuery );