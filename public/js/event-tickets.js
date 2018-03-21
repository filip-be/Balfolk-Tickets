jQuery(document).ready(function($){
	$(".prod-qty input").change(function() {
		$total = $(this)
			.closest('form.cart')
			.find('.prod-total p.bft-total');
		
		$total.text(
				($(this).val() * $total.data("price")) 
				+ " " + $total.data("symbol")
		);
	});
});