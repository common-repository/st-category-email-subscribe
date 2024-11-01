var $ = jQuery.noConflict();



jQuery(document).ready(function(){



	jQuery('#edit_subscriber').hide();

	

	jQuery( ".edit_this_subscriber" ).click(function() {

		jQuery('#edit_subscriber').show();

		

		var st_id = jQuery(this).attr('st_id');

		jQuery('#edit_st_id').val(st_id);

		

		var st_name = jQuery(this).attr('st_name');

		jQuery('#edit_st_name').val(st_name);

		

		var st_email = jQuery(this).attr('st_email');

		jQuery('#edit_st_email').val(st_email);

		

		var st_category = jQuery(this).attr('st_category');//comma separated ids

		jQuery('#edit_st_category').val('');

		if(st_category != '') {
			var category_array = jQuery.csv.toArray(st_category);
			jQuery.each(category_array, function( index, value ) {

				jQuery('#edit_st_category option[value=' + value + ']').attr('selected', true);

			});
		}		
	});
});