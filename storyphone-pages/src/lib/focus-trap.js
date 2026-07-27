/**
 * Keep Tab inside an open dialog.
 *
 * Both overlays declare aria-modal="true", which promises assistive tech that
 * the rest of the page is inert. Without trapping focus that promise is a lie
 * and Tab walks off into the page behind the scrim.
 */

const FOCUSABLE = [
	'a[href]',
	'button:not([disabled])',
	'input:not([disabled]):not([type="hidden"])',
	'select:not([disabled])',
	'textarea:not([disabled])',
	'[tabindex]:not([tabindex="-1"])',
].join(',');

/**
 * Install a focus trap on a container.
 *
 * @param {HTMLElement} container Dialog root.
 * @return {Function} Detach function.
 */
export function trapFocus(container) {
	if (!container) {
		return () => {};
	}

	const onKeydown = (event) => {
		if (event.key !== 'Tab' || container.hidden) {
			return;
		}

		const items = Array.from(container.querySelectorAll(FOCUSABLE)).filter(
			(node) => node.offsetParent !== null || node === document.activeElement
		);

		if (items.length === 0) {
			return;
		}

		const first = items[0];
		const last = items[items.length - 1];

		if (event.shiftKey && document.activeElement === first) {
			event.preventDefault();
			last.focus();
		} else if (!event.shiftKey && document.activeElement === last) {
			event.preventDefault();
			first.focus();
		} else if (!container.contains(document.activeElement)) {
			event.preventDefault();
			first.focus();
		}
	};

	document.addEventListener('keydown', onKeydown);

	return () => document.removeEventListener('keydown', onKeydown);
}
