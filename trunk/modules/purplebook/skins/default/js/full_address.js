jQuery(function($) {
	$(document).ready(function()
	{
		$('input, a, img, button','.full_header').filter(function(index) { return !$(this).hasClass('help'); }).tipsy(); // tipsy 다시호출
	});
});


/*
 * 전체보기 추가 폼, 히스토리 감추기/보이기
 */ 
var full_overlap_menu = '';
function showFullAddressMenu(id)
{
	jQuery("#full_add_address_excel").css('display','none');
	jQuery("#full_add_address_direct").css('display','none');
	jQuery("#full_history").css('display','none');

	//jQuery(id).css('display','block');
	
	if(full_overlap_menu == id)
	{
		jQuery(id).css('display','none');;
		full_overlap_menu = '';
	}
	else 
	{
		jQuery(id).slideDown("slow");
		full_overlap_menu = id;
	}
}

function closeFullMenu(id)
{
	jQuery(id).css('display','none');
	full_overlap_menu = '';
}

/*
 * END
 */
