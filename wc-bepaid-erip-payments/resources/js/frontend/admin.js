jQuery(document).ready(function ($) {
	$(document).on('click', '#begateway_cancel', function (e) {
		e.preventDefault();
		var nonce = $(this).data('nonce');
		var order_id = $(this).data('order-id');
		var self = $(this);

		$.ajax({
			url       : Begateway_Erip_Admin.ajax_url,
			type      : 'POST',
			data      : {
				action        : 'begateway_erip_cancel_bill',
				nonce         : nonce,
				order_id      : order_id
			},
			beforeSend: function () {
				self.data('text', self.html());
				self.html(Begateway_Erip_Admin.text_wait);
				self.prop('disabled', true);
			},
			success   : function (response) {
				self.html(self.data('text'));
				self.prop('disabled', false);
				if (!response.success) {
  				alert(response.data);
					return false;
				}

        url = new URL(window.location.href);
        url.searchParams.set('plugin', Begateway_Erip_Admin.module_id);
        url.searchParams.set('plugin_message', 'cancel');

        window.location.href = url.toString();
			}
		});
	});

	$(document).on('click', '#begateway_create', function (e) {
		e.preventDefault();

		var nonce = $(this).data('nonce');
		var order_id = $(this).data('order-id');
		var self = $(this);
		$.ajax({
			url       : Begateway_Erip_Admin.ajax_url,
			type      : 'POST',
			data      : {
				action        : 'begateway_erip_create_bill',
				nonce         : nonce,
				order_id      : order_id
			},
			beforeSend: function () {
				self.data('text', self.html());
				self.html(Begateway_Erip_Admin.text_wait);
				self.prop('disabled', true);
			},
			success   : function (response) {
				self.html(self.data('text'));
				self.prop('disabled', false);
				if (!response.success) {
				  alert(response.data);
					return false;
				}

        url = new URL(window.location.href);
        url.searchParams.set('plugin', Begateway_Erip_Admin.module_id);
        url.searchParams.set('plugin_message', 'create');

				window.location.href = url.toString();
			}
		});
	});
});
