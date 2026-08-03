/**
 * SubcategorySelector — chip row with keyboard support.
 *
 * Active chip gets fill + glow via `.is-active`. Rapid clicks are fine; the
 * parent CategoryPage aborts stale fetches.
 */

/**
 * @param {HTMLElement} root [data-sp-cat-subs] or chips track
 * @param {(payload: { id: number, name: string }) => void} onSelect
 */
export function SubcategorySelector(root, onSelect) {
	const track = root.matches('[data-sp-cat-chips]')
		? root
		: root.querySelector('[data-sp-cat-chips]');
	if (!track) {
		return { setActive() {} };
	}

	const chips = () => Array.from(track.querySelectorAll('[data-sp-cat-chip]'));

	const setActive = (id) => {
		chips().forEach((chip) => {
			const active = Number(chip.dataset.termId) === Number(id);
			chip.classList.toggle('is-active', active);
			chip.setAttribute('aria-selected', active ? 'true' : 'false');
			chip.tabIndex = active ? 0 : -1;
		});
	};

	chips().forEach((chip, index) => {
		chip.tabIndex = chip.classList.contains('is-active') ? 0 : -1;

		chip.addEventListener('click', () => {
			const id = Number(chip.dataset.termId);
			const name = chip.dataset.termName || chip.textContent.trim();
			setActive(id);
			onSelect({ id, name });
		});

		chip.addEventListener('keydown', (event) => {
			const list = chips();
			let next = index;
			if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
				event.preventDefault();
				const dir = event.key === 'ArrowRight' ? -1 : 1; // RTL: Right moves earlier
				const rtl = document.documentElement.dir === 'rtl';
				next = (index + (rtl ? dir : -dir) + list.length) % list.length;
			} else if (event.key === 'Home') {
				event.preventDefault();
				next = 0;
			} else if (event.key === 'End') {
				event.preventDefault();
				next = list.length - 1;
			} else {
				return;
			}
			list[next]?.focus();
		});
	});

	return { setActive };
}
