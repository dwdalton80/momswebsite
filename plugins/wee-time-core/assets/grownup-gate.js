/* Grown-up check: any link marked data-wt-grownup asks a small sum first, so a
   child tapping around Kids Corner doesn't land on a plans or checkout page.
   It's a speed bump, not security: what's members-only is checked on the server. */
(function () {
	var words = ['zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine'];
	function ask(go) {
		var a = 2 + Math.floor(Math.random() * 7), b = 2 + Math.floor(Math.random() * 7);
		var d = document.createElement('div');
		d.className = 'gate';
		d.setAttribute('role', 'dialog');
		d.setAttribute('aria-modal', 'true');
		d.setAttribute('aria-labelledby', 'wt-gate-h');
		d.innerHTML = '<form class="gate-box"><h3 id="wt-gate-h">Grown-ups only</h3>' +
			'<p>What is ' + words[a] + ' plus ' + words[b] + '?</p>' +
			'<input inputmode="numeric" autocomplete="off" aria-label="Answer" required>' +
			'<p class="gate-err" hidden>Not quite. Try again.</p>' +
			'<div class="chips"><button class="btn kid" type="submit">Continue</button>' +
			'<button class="btn ghost" type="button" data-close>Cancel</button></div></form>';
		document.body.appendChild(d);
		var input = d.querySelector('input');
		input.focus();
		function close() { d.remove(); }
		d.querySelector('[data-close]').onclick = close;
		d.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
		d.querySelector('form').onsubmit = function (e) {
			e.preventDefault();
			if (parseInt(input.value, 10) === a + b) { close(); go(); }
			else { d.querySelector('.gate-err').hidden = false; input.value = ''; input.focus(); }
		};
	}
	document.addEventListener('click', function (e) {
		var link = e.target.closest && e.target.closest('a[data-wt-grownup]');
		if (!link || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey) return;
		e.preventDefault();
		ask(function () { window.location.href = link.href; });
	});
})();
