function CheckForm(action) {
	if (action == 'compare') {
		return compareProducts(config.CompareLink);
	}

	var data = {};
	var i = 0;

	// iterate over each product on the page and add to cart if qty > 0
	$(".quantityInput").each(function() {
		if(isNaN($(this).val())) {
			alert(lang.InvalidQuantity);
			this.focus();
			this.select();
			valid = false;
			return false;
		}

		if ($(this).val() > 0) {
			// get the id of the product
			// qty[43]
			var len = this.name.length;
			var id = this.name.substr(4, len - 5);
			data[id] = $(this).val();

			i++;
		}
	});

	if (i > 0) {
		// ajax request to add products
		$.ajax({
			url: config.ShopPath + '/remote.php?w=addproducts',
			dataType: 'json',
			data: {products: $.param(data)},
			success: function(data) {
				if(data.error != undefined) {
					alert(data.error);
				}
				else {
					window.location = config.ShopPath + "/cart.php";
				}
			}
		});
	}
	else {
		alert(lang.PleaseSelectAProduct);
	}

	return false;
}