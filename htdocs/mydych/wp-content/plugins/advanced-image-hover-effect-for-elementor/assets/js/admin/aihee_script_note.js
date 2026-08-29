jQuery(document).ready(function(){
    $ = jQuery.noConflict();    
	$(document).on('click', '.aihee-notice .notice-dismiss, .aihee-notice .aihee-done', function() {
		var $aihee_click = $(this).closest('.aihee-notice');		
		$aihee_click.slideUp();
		$.ajax({
			url: ajaxurl,
			data: {
				action: 'aihee_top_notice'
			}
		})
	});
});