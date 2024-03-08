all:
	if [[ -e wc-bepaid-erip-payments.zip ]]; then rm wc-bepaid-erip-payments.zip; fi
	zip -r wc-bepaid-erip-payments.zip wc-bepaid-erip-payments -x */test/* -x */examples/* -x *.DS_Store* -x *node_modules*
