all:
	if [[ -e woocommerce-erip-payments.zip ]]; then rm woocommerce-erip-payments.zip; fi
	zip -r woocommerce-erip-payments.zip woocommerce-erip-payments