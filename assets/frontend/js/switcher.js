/**
 * WPCalibrate Multi-Currency Frontend Switcher Script
 */
(function() {
	'use strict';

	function getCookie(name) {
		const value = '; ' + document.cookie;
		const parts = value.split('; ' + name + '=');
		if (parts.length === 2) {
			return parts.pop().split(';').shift();
		}
		return null;
	}

	function initSwitcher() {
		// Cache-safe cookie sync: update dropdown selected value to reflect actual visitor cookie
		const cookieCurrency = getCookie('wpc_currency');
		if (cookieCurrency) {
			const selects = document.querySelectorAll('.wpcalibrate-currency-select');
			selects.forEach(function(select) {
				if (select.value !== cookieCurrency) {
					const matchingOption = select.querySelector('option[value="' + cookieCurrency + '"]');
					if (matchingOption) {
						select.value = cookieCurrency;
					}
				}
			});
		}

		// Handle button clicks to prevent full reload delays when REST switch is enabled
		const buttons = document.querySelectorAll('.wpcalibrate-currency-btn');
		buttons.forEach(function(btn) {
			btn.addEventListener('click', function(e) {
				const currency = this.getAttribute('data-currency');
				if (currency) {
					// Visual active state immediately
					buttons.forEach(function(b) {
						b.classList.remove('is-active');
						b.removeAttribute('aria-current');
					});
					this.classList.add('is-active');
					this.setAttribute('aria-current', 'true');
				}
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initSwitcher);
	} else {
		initSwitcher();
	}
})();
