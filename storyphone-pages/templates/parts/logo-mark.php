<?php
/**
 * Part: animated Story Phone logo mark (SVG + CSS).
 *
 * Source: story-phone-logo-animated-v2.html
 *
 * Critical: this SVG must stay direction=ltr. On the RTL storefront,
 * inherited direction flips text-anchor start/end so ST/RY/PH/NE all
 * collapse into the phone and look "intersected". Hebrew taglines keep
 * their own direction="rtl".
 *
 * @package StoryPhone_Pages
 */

defined( 'ABSPATH' ) || exit;
?>
<span class="sp-logoMark" aria-hidden="true" dir="ltr">
	<svg
		class="sp-logoMark__svg"
		viewBox="200 48 280 185"
		xmlns="http://www.w3.org/2000/svg"
		focusable="false"
		direction="ltr"
	>
		<g class="sp-logoMark__story">
			<text x="296" y="105" text-anchor="end" direction="ltr" font-size="68" font-weight="700" letter-spacing="0.5" fill="#B6FD59">ST</text>
			<text x="384" y="105" text-anchor="start" direction="ltr" font-size="68" font-weight="700" letter-spacing="0.5" fill="#FFFFFF">RY</text>
		</g>

		<g class="sp-logoMark__phone">
			<text x="296" y="160" text-anchor="end" direction="ltr" font-size="68" font-weight="700" letter-spacing="0.5" fill="#B6FD59">PH</text>
			<text x="384" y="160" text-anchor="start" direction="ltr" font-size="68" font-weight="700" letter-spacing="0.5" fill="#FFFFFF">NE</text>
		</g>

		<g class="sp-logoMark__icon">
			<rect x="310" y="56" width="60" height="104" rx="16" fill="#051918" stroke="#FFFFFF" stroke-width="6"/>
			<path d="M354 56 L370 56 L370 72 Z" fill="#B6FD59"/>
			<line x1="354" y1="56" x2="370" y2="72" stroke="#6D9835" stroke-width="1.2"/>
			<rect x="326" y="64" width="18" height="4" rx="2" fill="#FFFFFF" opacity="0.85"/>
			<rect x="304" y="68" width="6" height="14" rx="2.5" fill="#FFFFFF"/>
			<rect x="304" y="86" width="6" height="14" rx="2.5" fill="#FFFFFF"/>
			<rect x="370" y="76" width="6" height="20" rx="2.5" fill="#FFFFFF"/>
			<path d="M310 88 C310 110 370 110 370 88 L370 128 C370 106 310 106 310 128 Z" fill="#B6FD59" stroke="#6D9835" stroke-width="1" stroke-linejoin="round"/>
		</g>

		<line x1="310" y1="185" x2="370" y2="185" stroke="#B6FD59" stroke-width="3" stroke-linecap="round"/>

		<text class="sp-logoMark__tag" x="430" y="208" text-anchor="middle" direction="rtl" lang="he" font-size="13" fill="#C7D9CF">מכשירים</text>
		<text class="sp-logoMark__tag" x="382" y="208" text-anchor="middle" font-size="13" fill="#C7D9CF">·</text>
		<text class="sp-logoMark__tag" x="340" y="208" text-anchor="middle" direction="rtl" lang="he" font-size="13" fill="#C7D9CF">אביזרים</text>
		<text class="sp-logoMark__tag" x="298" y="208" text-anchor="middle" font-size="13" fill="#C7D9CF">·</text>
		<text class="sp-logoMark__tag" x="264" y="208" text-anchor="middle" direction="rtl" lang="he" font-size="13" fill="#C7D9CF">מעבדה</text>

		<text class="sp-logoMark__city" x="340" y="224" text-anchor="middle" direction="ltr" font-size="11" letter-spacing="3" fill="#7B8F86">TEL AVIV</text>
	</svg>
</span>
