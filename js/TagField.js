/**
 * Register TagField functions with fields.
 */
(function ($) {

	/*
	 * The multiple-select plugin (Chosen) applies itself to everything!
	 * We have to remove it before selectively applying select2, or
	 * we'll see an extra field where there should be only one.
	 */

	$.fn.chosenDestroy = function () {
		$(this).show().removeClass('chzn-done');
		$(this).next().remove();

		return $(this);
	};

	$(function () {
		setTimeout(function() {
			$('.silverstripe-tag-field')
				.chosenDestroy()
				.select2({
					'tags': true,
					'tokenSeparators': [',', ' ']
				});
		}, 0);
	});

})(jQuery);
