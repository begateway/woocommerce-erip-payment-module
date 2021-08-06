jQuery(document).ready(function ($) {
	$(document).on('click', '#begateway_cancel', function (e) {
		e.preventDefault();
		var nonce = $(this).data('nonce');
		var order_id = $(this).data('order-id');
		var self = $(this);

		$.ajax({
			url       : BeGateway_Admin.ajax_url,
			type      : 'POST',
			data      : {
				action        : 'begateway_cancel',
				nonce         : nonce,
				order_id      : order_id
			},
			beforeSend: function () {
				self.data('text', self.html());
				self.html(BeGateway_Admin.text_wait);
				self.prop('disabled', true);
			},
			success   : function (response) {
				self.html(self.data('text'));
				self.prop('disabled', false);
				if (!response.success) {
					alert(response.data);
					return false;
				}

				window.location.href = location.href;
			}
		});
	});

	$(document).on('click', '#begateway_create', function (e) {
		e.preventDefault();

		var nonce = $(this).data('nonce');
		var order_id = $(this).data('order-id');
		var self = $(this);
		$.ajax({
			url       : BeGateway_Admin.ajax_url,
			type      : 'POST',
			data      : {
				action        : 'begateway_create',
				nonce         : nonce,
				order_id      : order_id
			},
			beforeSend: function () {
				self.data('text', self.html());
				self.html(BeGateway_Admin.text_wait);
				self.prop('disabled', true);
			},
			success   : function (response) {
				self.html(self.data('text'));
				self.prop('disabled', false);
				if (!response.success) {
					alert(response.data);
					return false;
				}

				window.location.href = location.href;
			}
		});
	});
});
