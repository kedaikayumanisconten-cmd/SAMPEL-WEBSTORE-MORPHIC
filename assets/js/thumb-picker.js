/* ==========================================================================
   M.O.R.P.H.I.C - admin product thumbnail picker
   Handles the three image sources: upload, direct URL and Unsplash.
   ========================================================================== */
(function () {
	'use strict';

	var cfg = window.MORPHIC_THUMB || null;

	function ready(fn) {
		if (document.readyState !== 'loading') {
			fn();
		} else {
			document.addEventListener('DOMContentLoaded', fn);
		}
	}

	ready(function () {
		var root = document.getElementById('thumb-picker');

		if (!root || !cfg) {
			return;
		}

		var radios = root.querySelectorAll('input[name="thumbnail_source"]');
		var panels = root.querySelectorAll('[data-panel]');
		var urlInput = document.getElementById('thumbnail_url');
		var creditInput = document.getElementById('thumbnail_credit');
		var preview = document.getElementById('thumb-preview');
		var previewEmpty = document.getElementById('thumb-preview-empty');
		var keyword = document.getElementById('unsplash-keyword');
		var searchButton = document.getElementById('unsplash-search');
		var status = document.getElementById('unsplash-status');
		var results = document.getElementById('unsplash-results');
		var openLink = document.getElementById('unsplash-open');
		var uploadInput = document.getElementById('thumbnail_file');
		var fileLabel = document.getElementById('thumbnail_file_name');

		/* ---------------------------------------------------------------
		   Panel switching
		   --------------------------------------------------------------- */
		function currentSource() {
			for (var i = 0; i < radios.length; i++) {
				if (radios[i].checked) {
					return radios[i].value;
				}
			}

			return 'none';
		}

		function syncPanels() {
			var source = currentSource();

			for (var i = 0; i < panels.length; i++) {
				panels[i].style.display = panels[i].getAttribute('data-panel') === source ? '' : 'none';
			}

			// Keep the chip highlight in sync, including when the picker
			// switches the source programmatically.
			for (var j = 0; j < radios.length; j++) {
				var chip = radios[j].closest('.chip');

				if (chip) {
					chip.classList.toggle('is-active', radios[j].checked);
				}
			}
		}

		for (var i = 0; i < radios.length; i++) {
			radios[i].addEventListener('change', syncPanels);
		}

		syncPanels();

		/* ---------------------------------------------------------------
		   Preview
		   --------------------------------------------------------------- */
		function renderPreview(url) {
			if (!preview || !previewEmpty) {
				return;
			}

			if (url) {
				preview.src = url;
				preview.style.display = '';
				previewEmpty.style.display = 'none';
			} else {
				preview.removeAttribute('src');
				preview.style.display = 'none';
				previewEmpty.style.display = '';
			}
		}

		if (urlInput) {
			urlInput.addEventListener('input', function () {
				renderPreview(urlInput.value.trim());
			});
		}

		if (uploadInput && fileLabel) {
			uploadInput.addEventListener('change', function () {
				if (!uploadInput.files || !uploadInput.files.length) {
					fileLabel.textContent = '';
					return;
				}

				fileLabel.textContent = uploadInput.files[0].name;
				renderPreview(URL.createObjectURL(uploadInput.files[0]));
			});
		}

		/* ---------------------------------------------------------------
		   Selection helper shared by Unsplash results and quick picks
		   --------------------------------------------------------------- */
		function selectImage(url, credit, source) {
			if (urlInput) {
				urlInput.value = url;
			}

			if (creditInput) {
				creditInput.value = credit || 'Photo from Unsplash';
			}

			// Keep the tab the photo was picked from selected, so the panel
			// does not jump around under the cursor. The value itself always
			// travels through the URL field.
			var target = source || 'url';

			for (var i = 0; i < radios.length; i++) {
				if (radios[i].value === target) {
					radios[i].checked = true;
					break;
				}
			}

			syncPanels();
			renderPreview(url);

			if (status) {
				status.textContent = 'Gambar dipilih. Jangan lupa simpan produk.';
			}
		}

		root.addEventListener('click', function (event) {
			var trigger = event.target.closest('[data-pick-url]');

			if (!trigger) {
				return;
			}

			event.preventDefault();

			var panel = trigger.closest('[data-panel]');

			selectImage(
				trigger.getAttribute('data-pick-url'),
				trigger.getAttribute('data-pick-credit') || '',
				panel ? panel.getAttribute('data-panel') : 'url'
			);
		});

		/* ---------------------------------------------------------------
		   Unsplash search
		   --------------------------------------------------------------- */
		function updateCsrf(token) {
			if (!token) {
				return;
			}

			var fields = document.querySelectorAll('input[name="' + cfg.csrfName + '"]');

			for (var i = 0; i < fields.length; i++) {
				fields[i].value = token;
			}
		}

		function renderPhotos(photos) {
			if (!results) {
				return;
			}

			results.innerHTML = '';

			if (!photos.length) {
				return;
			}

			var grid = document.createElement('div');
			grid.className = 'thumb-result-grid';

			photos.forEach(function (photo) {
				var button = document.createElement('button');
				button.type = 'button';
				button.className = 'thumb-result';
				button.setAttribute('data-pick-url', photo.regular);
				button.setAttribute('data-pick-credit', photo.credit || '');

				var img = document.createElement('img');
				img.src = photo.thumb;
				img.alt = photo.alt || '';
				img.loading = 'lazy';

				var caption = document.createElement('span');
				caption.textContent = photo.photographer || '';

				button.appendChild(img);
				button.appendChild(caption);
				grid.appendChild(button);
			});

			results.appendChild(grid);
		}

		function syncOpenLink() {
			if (!openLink || !keyword) {
				return;
			}

			var term = keyword.value.trim() || 'digital audio workstation';
			openLink.href = 'https://unsplash.com/s/photos/' + encodeURIComponent(term);
		}

		if (keyword) {
			keyword.addEventListener('input', syncOpenLink);
			syncOpenLink();
		}

		if (searchButton) {
			searchButton.addEventListener('click', function () {
				var term = keyword ? keyword.value.trim() : '';

				if (!term) {
					if (status) {
						status.textContent = 'Isi kata kunci terlebih dahulu.';
					}

					return;
				}

				searchButton.disabled = true;

				if (status) {
					status.textContent = 'Mencari foto di Unsplash…';
				}

				var body = new URLSearchParams();
				body.append('q', term);
				body.append('page', '1');
				body.append(cfg.csrfName, cfg.csrfHash);

				fetch(cfg.searchUrl, {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'X-Requested-With': 'XMLHttpRequest' },
					body: body
				})
					.then(function (response) { return response.json(); })
					.then(function (data) {
						searchButton.disabled = false;
						updateCsrf(data.csrf);
						cfg.csrfHash = data.csrf || cfg.csrfHash;

						if (!data.configured) {
							if (status) {
								status.textContent = 'Access Key Unsplash belum diisi. Gunakan tombol "Buka pencarian Unsplash", salin alamat gambar, lalu tempel pada kolom URL gambar di tab "Dari URL".';
							}

							return;
						}

						if (data.error) {
							if (status) {
								status.textContent = data.error;
							}

							return;
						}

						if (status) {
							status.textContent = data.photos.length
								? data.photos.length + ' foto ditemukan untuk “' + term + '”. Klik salah satu untuk memakai.'
								: 'Tidak ada foto yang cocok. Coba kata kunci lain.';
						}

						renderPhotos(data.photos);
					})
					.catch(function () {
						searchButton.disabled = false;

						if (status) {
							status.textContent = 'Gagal menghubungi server. Coba lagi.';
						}
					});
			});
		}
	});
})();
