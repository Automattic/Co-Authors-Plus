jQuery(document).ready(function($){
	$('.reassign-option').on('click',function(){
		$('#wpbody-content input#submit').addClass('button-primary').removeAttr('disabled');
	});
});