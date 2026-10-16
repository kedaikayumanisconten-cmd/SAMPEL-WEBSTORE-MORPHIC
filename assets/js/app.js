/* ==========================================================================
   M.O.R.P.H.I.C - front-end behaviour (vanilla JS, no dependencies)
   ========================================================================== */
(function () {
	'use strict';

	/**
	 * Mobile navigation toggle for the storefront header.
	 */
	function initNavToggle() {
		var toggle = document.querySelector('[data-nav-toggle]');
		var nav = document.getElementById('main-nav');

		if (!toggle || !nav) {
			return;
		}

		toggle.addEventListener('click', function () {
			nav.classList.toggle('is-open');
			toggle.setAttribute('aria-expanded', nav.classList.contains('is-open') ? 'true' : 'false');
		});
	}

	/**
	 * Collapsible sidebar used by the admin panel on small screens.
	 */
	function initSidebarToggle() {
		var toggle = document.querySelector('[data-sidebar-toggle]');
		var sidebar = document.getElementById('admin-sidebar');

		if (!toggle || !sidebar) {
			return;
		}

		toggle.addEventListener('click', function (event) {
			event.stopPropagation();
			sidebar.classList.toggle('is-open');
		});

		document.addEventListener('click', function (event) {
			if (sidebar.classList.contains('is-open') && !sidebar.contains(event.target) && event.target !== toggle) {
				sidebar.classList.remove('is-open');
			}
		});
	}

	/**
	 * Copy-to-clipboard buttons (license keys, bank account numbers queued by
	 * the Click2Copy hero in the template).
	 */
	function initCopyButtons() {
		document.querySelectorAll('[data-copy]').forEach(function (button) {
			button.addEventListener('click', function () {
				var value = button.getAttribute('data-copy');
				var label = button.getAttribute('data-copy-label') || button.textContent;

				if (!value) {
					return;
				}

				var done = function () {
					var original = button.textContent;
					button.textContent = 'Tersalin!';

					window.setTimeout(function () {
						button.textContent = original || label;
					}, 1400);
				};

				if (navigator.clipboard && window.isSecureContext) {
					navigator.clipboard.writeText(value).then(done);
					return;
				}

				// Fallback for plain http:// (local XAMPP).
				var helper = document.createElement('textarea');
				helper.value = value;
				helper.setAttribute('readonly', 'readonly');
				helper.style.position = 'absolute';
				helper.style.left = '-9999px';
				document.body.appendChild(helper);
				helper.select();

				try {
					document.execCommand('copy');
					done();
				} catch (error) {
					window.prompt('Salin manual:', value);
				}

				document.body.removeChild(helper);
			});
		});
	}

	/**
	 * Confirmation guard for destructive actions.
	 */
	function initConfirmLinks() {
		document.querySelectorAll('[data-confirm]').forEach(function (element) {
			element.addEventListener('click', function (event) {
				if (!window.confirm(element.getAttribute('data-confirm'))) {
					event.preventDefault();
				}
			});
		});
	}

	/**
	 * Auto-dismiss the flash alerts.
	 */
	function initFlashFade() {
		document.querySelectorAll('[data-autohide]').forEach(function (alert) {
			window.setTimeout(function () {
				alert.style.transition = 'opacity .5s ease';
				alert.style.opacity = '0';
				window.setTimeout(function () {
					alert.style.display = 'none';
				}, 500);
			}, 6000);
		});
	}

	/**
	 * Show the file name of a chosen upload next to its input.
	 */
	function initFilePreviews() {
		document.querySelectorAll('input[type="file"][data-filename-target]').forEach(function (input) {
			var target = document.querySelector(input.getAttribute('data-filename-target'));

			if (!target) {
				return;
			}

			input.addEventListener('change', function () {
				target.textContent = input.files && input.files.length ? input.files[0].name : '';
			});
		});
	}

	/**
	 * Horizontal image slider used by the home page product highlight.
	 */
	function initSliders() {
		document.querySelectorAll('[data-slider]').forEach(function (slider) {
			var track = slider.querySelector('[data-slider-track]');
			var prev = slider.querySelector('[data-slider-prev]');
			var next = slider.querySelector('[data-slider-next]');
			var progress = slider.querySelector('[data-slider-progress]');
			var progressBar = progress ? progress.parentElement : null;

			if (!track) {
				return;
			}

			function step() {
				var slide = track.querySelector('.slider-slide');

				if (!slide) {
					return track.clientWidth;
				}

				var styles = window.getComputedStyle(track);
				var gap = parseFloat(styles.columnGap || styles.gap) || 0;

				return slide.getBoundingClientRect().width + gap;
			}

			function updateState() {
				var max = track.scrollWidth - track.clientWidth;
				var scrollable = max > 1;

				if (prev) {
					prev.disabled = !scrollable || track.scrollLeft <= 1;
				}
				if (next) {
					next.disabled = !scrollable || track.scrollLeft >= max - 1;
				}

				if (progressBar) {
					progressBar.style.display = scrollable ? '' : 'none';
				}

				if (progress && scrollable) {
					var visible = (track.scrollLeft + track.clientWidth) / track.scrollWidth;
					progress.style.width = Math.min(100, Math.max(0, visible * 100)) + '%';
				}
			}

			function scrollByStep(direction) {
				track.scrollBy({ left: direction * step(), behavior: 'smooth' });
			}

			if (prev) {
				prev.addEventListener('click', function () {
					scrollByStep(-1);
				});
			}

			if (next) {
				next.addEventListener('click', function () {
					scrollByStep(1);
				});
			}

			track.addEventListener('scroll', function () {
				window.requestAnimationFrame(updateState);
			});

			track.addEventListener('keydown', function (event) {
				if (event.key === 'ArrowRight') {
					event.preventDefault();
					scrollByStep(1);
				} else if (event.key === 'ArrowLeft') {
					event.preventDefault();
					scrollByStep(-1);
				}
			});

			window.addEventListener('resize', function () {
				window.requestAnimationFrame(updateState);
			});

			updateState();
		});
	}

	/**
	 * Derive the slug field from the title field while the admin types.
	 */
	function initSlugField() {
		var title = document.querySelector('[data-slug-source]');
		var slug = document.querySelector('[data-slug-target]');

		if (!title || !slug) {
			return;
		}

		var locked = slug.value.trim() !== '';

		slug.addEventListener('input', function () {
			locked = true;
		});

		title.addEventListener('input', function () {
			if (locked) {
				return;
			}

			slug.value = title.value
				.toLowerCase()
				.replace(/[^a-z0-9\s-]/g, '')
				.trim()
				.replace(/\s+/g, '-')
				.replace(/-+/g, '-');
		});
	}

	/**
	 * Theme picker: persists the chosen palette and reflects it on demand.
	 */
	function initThemePicker() {
		var pickers = document.querySelectorAll('[data-theme-picker]');

		if (!pickers.length) {
			return;
		}

		var root = document.documentElement;
		var THEMES = ['morphic', 'midnight', 'emerald', 'royal', 'rose', 'ocean'];

		function currentTheme() {
			var active = root.getAttribute('data-theme');

			return THEMES.indexOf(active) !== -1 ? active : 'morphic';
		}

		function applyTheme(theme) {
			if (theme === 'morphic') {
				root.removeAttribute('data-theme');
			} else {
				root.setAttribute('data-theme', theme);
			}

			try {
				if (theme === 'morphic') {
					localStorage.removeItem('morphic-theme');
				} else {
					localStorage.setItem('morphic-theme', theme);
				}
			} catch (error) {
				// Storage unavailable (private mode) - theme still applies for the session.
			}
		}

		function syncActive() {
			var theme = currentTheme();

			document.querySelectorAll('.theme-option').forEach(function (option) {
				var isActive = option.getAttribute('data-theme-value') === theme;
				option.classList.toggle('is-active', isActive);
				option.setAttribute('aria-checked', isActive ? 'true' : 'false');
			});
		}

		pickers.forEach(function (picker) {
			var toggle = picker.querySelector('[data-theme-toggle]');
			var menu = picker.querySelector('[data-theme-menu]');

			if (!toggle || !menu) {
				return;
			}

			function close() {
				menu.classList.remove('is-open');
				toggle.setAttribute('aria-expanded', 'false');
			}

			toggle.addEventListener('click', function (event) {
				event.stopPropagation();
				var open = menu.classList.toggle('is-open');
				toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
			});

			menu.querySelectorAll('.theme-option').forEach(function (option) {
				option.addEventListener('click', function () {
					applyTheme(option.getAttribute('data-theme-value'));
					syncActive();
					close();
				});
			});

			document.addEventListener('click', function (event) {
				if (menu.classList.contains('is-open') && !picker.contains(event.target)) {
					close();
				}
			});

			document.addEventListener('keydown', function (event) {
				if (event.key === 'Escape' && menu.classList.contains('is-open')) {
					close();
					toggle.focus();
				}
			});
		});

		syncActive();
	}

	/**
	 * Reveal/hide the value of password fields (login & registration).
	 */
	function initPasswordToggles() {
		document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
			var field = document.getElementById(button.getAttribute('data-password-toggle'));

			if (!field) {
				return;
			}

			button.addEventListener('click', function () {
				var reveal = field.type === 'password';
				field.type = reveal ? 'text' : 'password';
				button.classList.toggle('is-visible', reveal);
				button.setAttribute('aria-pressed', reveal ? 'true' : 'false');
				button.setAttribute('aria-label', reveal ? 'Sembunyikan password' : 'Tampilkan password');
			});
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		initNavToggle();
		initSidebarToggle();
		initCopyButtons();
		initConfirmLinks();
		initFlashFade();
		initFilePreviews();
		initSlugField();
		initSliders();
		initThemePicker();
		initPasswordToggles();
	});
})();
