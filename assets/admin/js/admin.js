/**
 * WPCalibrate Multi-Currency Admin Dashboard Script
 */
(function($) {
	'use strict';

	$(document).ready(function() {
		// Confirm deletion of a currency
		$('.wpcalibrate-delete-currency-btn').on('click', function(e) {
			const msg = (window.wpcalibrateAdminParams && window.wpcalibrateAdminParams.confirmDelete)
				? window.wpcalibrateAdminParams.confirmDelete
				: 'Are you sure you want to delete this currency?';
			if (!confirm(msg)) {
				e.preventDefault();
				return false;
			}
		});

		// Dynamic toggle for OpenExchangeRates provider credentials field
		const $providerSelect = $('#rate_provider');
		const $oerGroup = $('#openexchangerates_credentials_group');

		function toggleOerFields() {
			if ($providerSelect.val() === 'openexchangerates') {
				$oerGroup.show();
			} else {
				$oerGroup.hide();
			}
		}

		if ($providerSelect.length && $oerGroup.length) {
			toggleOerFields();
			$providerSelect.on('change', toggleOerFields);
		}
	});
})(jQuery);
