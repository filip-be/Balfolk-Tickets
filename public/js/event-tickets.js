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
	
	$(".prod-price.bft-open-price input").change(function() {
		$total = $(this)
			.closest('form.cart')
			.find('.prod-total p.bft-total');
		$quantity = $(this)
			.closest('form.cart')
			.find('.prod-qty .quantity input');
		
		$total.data("price", $(this).val())
		
		$total.text(
				($quantity.val() * $total.data("price")) 
				+ " " + $total.data("symbol")
		);
	});
});