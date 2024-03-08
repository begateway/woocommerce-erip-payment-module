
import { sprintf, __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting, WC_ASSET_URL } from '@woocommerce/settings';

const settings = getSetting( 'begateway_erip_data', {} );

const defaultLabel = __(
	'beGateway ERIP Payments',
	'woo-gutenberg-products-block'
);

const label = decodeEntities( settings.title ) || defaultLabel;
/**
 * Content component
 */
const Content = () => {
	return decodeEntities( settings.description || '' );
};

/**
 * Dummy payment method config object.
 */
const BeGatewayErip = {
	name: "begateway_erip",
	label: (
        <img
			src={ decodeEntities( settings.logo_url ) }
            alt={ label }
        />
    ),
	content: <Content />,
	edit: <Content />,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: settings.supports,
	}
};

registerPaymentMethod( BeGatewayErip );
