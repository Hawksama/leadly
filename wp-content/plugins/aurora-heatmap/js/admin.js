/**
 * Aurora Heatmap Admin helper
 *
 * @package aurora-heatmap
 * @copyright 2019-2020 R3098 <info@seous.info>
 * @version 1.4.11
 */

/**
 * Anonymous function for scope
 */
(function($) {
	"use strict";

	/**
	 * Main object
	 */
	var self = {
		view: function() {
			var args   = aurora_heatmap_admin;
			var desc   = document.getElementById( 'ahm-description' );
			var legend = document.getElementById( 'ahm-legend' );
			var table  = document.getElementsByClassName( 'wp-list-table' )[0];
			var prev;
			var events = [
				{ column: 'click', legend: args.click_heatmap, },
				{ column: 'breakaway', legend: args.breakaway_heatmap, },
				{ column: 'attention', legend: args.attention_heatmap, },
			];

			if ( ! desc || ! table ) {
				return;
			}

			table.addEventListener(
				'mousemove',
				function( ev ) {
					var e = document.elementFromPoint( ev.clientX, ev.clientY );

					if ( prev && prev === e ) {
						return;
					}

					prev = e;
					while ( 'TD' !== e.tagName && 'TH' !== e.tagName ) {
						if ( 'TABLE' === e.tagName ) {
							return;
						}
						e = e.parentElement;
						if ( ! e ) {
							return;
						}
					}

					events.some(
						function(t) {
							var column_pc     = 'column-' + t.column + '_pc';
							var column_mobile = 'column-' + t.column + '_mobile';
							var desc_class    = t.column + '-heatmap';
							if ( ( e.classList.contains( column_pc ) || e.classList.contains( column_mobile ) ) && desc_class !== desc.className ) {
								desc.className   = desc_class;
								legend.innerText = t.legend;
								return true;
							}
						}
					);
				}
			);

			var w;
			Array.prototype.forEach.call(
				document.getElementsByClassName( 'ahm-view' ),
				function( e ) {
					e.addEventListener(
						'click',
						function( ev ) {
							if ( w && w.outerWidth !== parseInt( e.dataset.width ) ) {
								w.close();
							}
							w = window.open( e.dataset.url, 'Aurora Heatmap Viewer', 'scrollbars=yes, resizable=no, location=yes, width=' + e.dataset.width + ', height=600' );
							ev.preventDefault();
						},
						{ passive: false }
					);
				}
			);
		},

		settings: function() {
			function radio_group_disable( e ) {
				return function( ev ) {
					e.form[e.name].forEach(
						function( r ) {
							var n           = r.nextElementSibling.children[1];
							var is_disabled = n.classList.contains( 'disabled' ) || r.disabled || ! r.checked;

							Array.prototype.forEach.call(
								n.querySelectorAll( '.inner-label' ),
								function( i ) {
									i.style.opacity = is_disabled ? '.6' : '1';
								}
							);

							Array.prototype.forEach.call(
								n.querySelectorAll( 'input[type="text"]' ),
								function( i ) {
									i.disabled = is_disabled;
								}
							);
						}
					);
				};
			}

			var rg = document.querySelectorAll( '.ahm-radio-group' );

			Array.prototype.forEach.call(
				rg,
				function( e ) {
					var el = e.parentElement.querySelectorAll( 'input[type="text"]' );

					if ( ! el || ! el.length ) {
						return;
					}

					var f = radio_group_disable( e );
					f();
					e.addEventListener( 'input', f );
				}
			);

			document.getElementById( 'ahm-options-form' ).addEventListener(
				'keydown',
				function( event ) {
					if ( 13 === event.which ) {
						document.getElementById( 'ahm-options-save' ).click();
						event.preventDefault();
						return false;
					}
				}
			);
		},
	};

	function init() {
		self[ aurora_heatmap_admin.active_tab ]();
	}

	if (
		'object' !== typeof aurora_heatmap_admin ||
		! ( 'active_tab' in aurora_heatmap_admin ) ||
		! ( aurora_heatmap_admin.active_tab in self )
	) {
		return;
	} else if ( 'loading' !== document.readyState ) {
		init();
	} else {
		document.addEventListener( 'DOMContentLoaded', init );
	}
})( jQuery );

/* vim: set ts=4 sw=4 sts=4 noet: */
