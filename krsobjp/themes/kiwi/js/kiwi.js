/*
 * kiwi theme: sidebar sections that open and close (as in the admin), the
 * current menu item and the sidebar as a drawer on small screens.
 *
 * The menu arrives via AJAX (menu.tpl calls kiwiNav.init() when it's in the
 * page). Without JavaScript every section stays open: the closing rules
 * only apply under .kw-collapsible.
 */
(function (window, document) {
	var STORAGE_KEY = 'kiwi.site.nav.open';

	// sections opened or closed by hand: {id: true|false}
	function saved() {
		try {
			return JSON.parse(window.localStorage.getItem(STORAGE_KEY)) || {};
		} catch (e) {
			return {};
		}
	}

	function remember(group, open) {
		var state = saved();

		state[group.getAttribute('data-group')] = open;

		try {
			window.localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
		} catch (e) {
			// private mode or storage off: the state just isn't kept
		}
	}

	function setOpen(group, open) {
		group.classList.toggle('open', open);
		group.querySelector('.kw-nav-toggle').setAttribute('aria-expanded', open ? 'true' : 'false');
	}

	function markActive(nav, item) {
		var groups = nav.querySelectorAll('.kw-nav-group');
		var i;

		Array.prototype.forEach.call(nav.querySelectorAll('.kw-nav-item.active'), function (current) {
			current.classList.remove('active');
		});

		for (i = 0; i < groups.length; i++) {
			groups[i].classList.remove('has-active');
		}

		if (!item) {
			return;
		}

		item.classList.add('active');

		for (var parent = item.parentNode; parent && parent !== nav; parent = parent.parentNode) {
			if (parent.classList && parent.classList.contains('kw-nav-group')) {
				parent.classList.add('has-active');
			}
		}
	}

	function setDrawer(open) {
		var toggle = document.querySelector('.kw-menu-toggle');

		document.body.classList.toggle('kw-drawer-open', open);

		if (toggle) {
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		}
	}

	var kiwiNav = {
		init: function () {
			var nav = document.getElementById('menudinamico');

			if (!nav) {
				return;
			}

			var state = saved();

			Array.prototype.forEach.call(nav.querySelectorAll('.kw-nav-group'), function (group) {
				var id = group.getAttribute('data-group');

				// closed unless opened by hand earlier
				setOpen(group, state[id] === true);

				group.querySelector('.kw-nav-toggle').addEventListener('click', function () {
					var open = !group.classList.contains('open');

					setOpen(group, open);
					remember(group, open);
				});
			});

			// the container stays when the menu is loaded again: one listener only
			if (!nav.getAttribute('data-kw-bound')) {
				nav.setAttribute('data-kw-bound', '1');

				nav.addEventListener('click', function (event) {
					var item = event.target.closest ? event.target.closest('.kw-nav-item') : null;

					if (item && nav.contains(item)) {
						markActive(nav, item);
						setDrawer(false);
					}
				});
			}

			nav.classList.add('kw-collapsible');

			// transitions only after the first paint, so nothing slides on load
			window.setTimeout(function () {
				nav.classList.add('kw-animated');
			}, 0);
		}
	};

	document.addEventListener('click', function (event) {
		if (event.target.closest && event.target.closest('.kw-menu-toggle')) {
			setDrawer(!document.body.classList.contains('kw-drawer-open'));
		} else if (event.target.classList && event.target.classList.contains('kw-backdrop')) {
			setDrawer(false);
		}
	});

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape' && document.body.classList.contains('kw-drawer-open')) {
			setDrawer(false);
		}
	});

	window.kiwiNav = kiwiNav;
})(window, document);
