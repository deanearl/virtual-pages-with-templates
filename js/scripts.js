jQuery(document).ready(function () {
	Vpt.toggleUrlSettings(jQuery('#use_custom_permalink_structure').is(':checked'));
	jQuery('#use_custom_permalink_structure').change(function () {
		Vpt.toggleUrlSettings(jQuery(this).is(':checked'));
	});

	jQuery( "#vpt_form" ).submit(function( event ) {
		if (Vpt.validate_form())
			return
		else
			event.preventDefault();
	});
});


var Vpt = {
	toggleUrlSettings : function ( use_custom_permalink ){
		if (use_custom_permalink == true){
			jQuery('#use_custom_pageurl').show();
			jQuery('#use_permalink_label').hide();
		}else{
			jQuery('#use_custom_pageurl').hide();
			jQuery('#use_permalink_label').show();
		}
	},
	validate_form : function (){
		var passed = true;
		if (jQuery('#use_custom_permalink_structure').is(':checked') && jQuery('#virtualpageurl').val() == ''){
			jQuery('#message').remove();
			jQuery('.no-url').show();
			passed = false;
		}else{
			jQuery('.no-url ').hide();	
		}

		if (!jQuery('#page_template').val()){
			jQuery('#message').remove();
			jQuery('.no-template-message ').show();
			passed = false;
		}else{
			jQuery('.no-template-message ').hide();	

		var post_type = jQuery('#page_template :selected').parent().attr('label').toLowerCase();
		if (jQuery('#use_custom_permalink_structure').is(':checked') && post_type == 'pages'){
			has_category = jQuery('#virtualpageurl').val().match(/%category%/);
			if (has_category)
			{
				jQuery('#message').remove();
				jQuery('.has-category-error').show();
				passed = false;
			}
		}else
			jQuery('.has-category-error').hide();

	}
		return passed;
}


}