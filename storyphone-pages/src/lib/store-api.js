/**
 * Thin client for the WooCommerce Store API.
 *
 * This is the only place the front end talks to commerce, and it never
 * calculates anything: prices, stock and totals all come back from
 * WooCommerce. Requests are same-origin with cookies, so the cart we mutate
 * here is the exact same session cart that WooCommerce's own cart and
 * checkout pages read.
 */

const config = window.storyphonePages || {};

// WooCommerce 8.x validates this against the `wc_store_api` action. Newer
// versions ignore it in favour of cart tokens, and responses hand back a fresh
// value we swap in.
let nonce = config.nonce || '';

/**
 * Whether cart operations can run at all.
 *
 * @return {boolean} True when WooCommerce and the Store API are available.
 */
export function isAvailable() {
	return Boolean(config.hasWoo && config.storeApi);
}

/**
 * Build an absolute Store API URL.
 *
 * The site sits behind LiteSpeed Cache, which will happily serve a cached
 * `GET /cart` — that would show a returning customer an empty cart. A unique
 * query parameter on reads keeps them off the cache. Writes are POSTs and are
 * never cached.
 *
 * @param {string}  path        Route relative to the Store API root.
 * @param {boolean} [bustCache] Append a cache-busting parameter.
 * @return {string} Absolute URL.
 */
function endpoint(path, bustCache = false) {
	const base = String(config.storeApi || '').replace(/\/+$/, '');
	const url = new URL(`${base}/${String(path).replace(/^\/+/, '')}`, window.location.origin);

	if (bustCache) {
		url.searchParams.set('_', String(Date.now()));
	}

	return url.toString();
}

/**
 * Perform a Store API request.
 *
 * @param {string} path                Route relative to the Store API root.
 * @param {Object} [options]           Request options.
 * @param {string} [options.method]    HTTP method.
 * @param {Object} [options.body]      JSON body.
 * @return {Promise<Object>} Parsed response payload.
 */
async function request(path, { method = 'GET', body } = {}) {
	const headers = { Accept: 'application/json' };

	if (body !== undefined) {
		headers['Content-Type'] = 'application/json';
	}
	if (nonce) {
		headers.Nonce = nonce;
	}

	const response = await fetch(endpoint(path, method === 'GET'), {
		method,
		credentials: 'same-origin',
		cache: 'no-store',
		headers,
		body: body === undefined ? undefined : JSON.stringify(body),
	});

	const refreshed = response.headers.get('Nonce');
	if (refreshed) {
		nonce = refreshed;
	}

	let payload = null;
	try {
		payload = await response.json();
	} catch (error) {
		payload = null;
	}

	if (!response.ok) {
		const failure = new Error(
			(payload && payload.message) || `Store API request failed (${response.status})`
		);
		failure.code = payload && payload.code;
		failure.status = response.status;
		throw failure;
	}

	return payload;
}

export const getCart = () => request('cart');

export const addItem = (id, quantity = 1) =>
	request('cart/add-item', {
		method: 'POST',
		body: { id: Number(id), quantity: Number(quantity) },
	});

export const removeItem = (key) =>
	request('cart/remove-item', { method: 'POST', body: { key } });

export const updateItem = (key, quantity) =>
	request('cart/update-item', {
		method: 'POST',
		body: { key, quantity: Number(quantity) },
	});

/**
 * Live product search.
 *
 * Runs against the public products route, so WooCommerce's own catalog
 * visibility rules decide what a shopper is allowed to see — the front end
 * never gets to widen that. `_fields` keeps the payload small enough to feel
 * instant while typing.
 *
 * @param {string}      term              Raw search term.
 * @param {Object}      [options]         Options.
 * @param {AbortSignal} [options.signal]  Abort signal for superseded requests.
 * @param {number}      [options.limit]   Maximum results.
 * @return {Promise<Array>} Matching products.
 */
export async function searchProducts(term, { signal, limit = 6 } = {}) {
	const base = String(config.storeApi || '').replace(/\/+$/, '');
	const url = new URL(`${base}/products`, window.location.origin);

	url.searchParams.set('search', term);
	url.searchParams.set('per_page', String(limit));
	url.searchParams.set('catalog_visibility', 'catalog');
	url.searchParams.set('_fields', 'id,name,permalink,prices,images,is_in_stock,is_purchasable,type');
	url.searchParams.set('_', String(Date.now()));

	const response = await fetch(url.toString(), {
		method: 'GET',
		credentials: 'same-origin',
		cache: 'no-store',
		headers: { Accept: 'application/json' },
		signal,
	});

	if (!response.ok) {
		throw new Error(`Store API search failed (${response.status})`);
	}

	const payload = await response.json();

	return Array.isArray(payload) ? payload : [];
}

/**
 * Format a Store API minor-unit amount using the currency data WooCommerce
 * returned alongside it. We never hardcode the symbol or separators.
 *
 * @param {string|number} amount   Amount in minor units.
 * @param {Object}        currency Object carrying the Store API `currency_*` fields.
 * @return {string} Display string, e.g. "₪1,299.00".
 */
export function formatMoney(amount, currency = {}) {
	const minorUnit = Number.isFinite(currency.currency_minor_unit)
		? currency.currency_minor_unit
		: 2;
	const thousandSeparator = currency.currency_thousand_separator ?? ',';
	const decimalSeparator = currency.currency_decimal_separator ?? '.';
	const prefix = currency.currency_prefix ?? '';
	const suffix = currency.currency_suffix ?? '';

	const numeric = Number(amount);
	if (!Number.isFinite(numeric)) {
		return '';
	}

	const value = numeric / 10 ** minorUnit;
	const [whole, fraction = ''] = Math.abs(value).toFixed(minorUnit).split('.');
	const grouped = whole.replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator);
	const body = minorUnit > 0 ? `${grouped}${decimalSeparator}${fraction}` : grouped;
	const sign = value < 0 ? '-' : '';

	return `${sign}${prefix}${body}${suffix}`;
}
