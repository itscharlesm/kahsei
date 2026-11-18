(function () {
    'use strict';

    // Lets CSS hide .reveal items only when JS is running
    document.documentElement.classList.add('js');

    document.addEventListener('DOMContentLoaded', function () {

        /* ---------- Sticky nav shadow + back-to-top ---------- */
        var nav = document.querySelector('.site-nav');
        var topBtn = document.getElementById('backToTop');

        function onScroll() {
            var y = window.scrollY || document.documentElement.scrollTop;
            if (nav) nav.classList.toggle('is-stuck', y > 160);
            if (topBtn) topBtn.classList.toggle('show', y > 400);
        }
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();

        if (topBtn) {
            topBtn.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }

        /* ---------- Reveal on scroll ---------- */
        var items = document.querySelectorAll('.reveal');
        if ('IntersectionObserver' in window) {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('in');
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12 });
            items.forEach(function (el) { io.observe(el); });
        } else {
            items.forEach(function (el) { el.classList.add('in'); });
        }

        /* ---------- Show / hide password ---------- */
        document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = document.querySelector(btn.getAttribute('data-toggle-password'));
                if (!input) return;
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                btn.querySelector('i').className = 'bi ' + (show ? 'bi-eye-slash' : 'bi-eye');
            });
        });

        /* ---------- Modal autofocus ---------- */
        var loginModal = document.getElementById('loginModal');
        if (loginModal) {
            loginModal.addEventListener('shown.bs.modal', function () {
                var email = document.getElementById('login_email');
                if (email) email.focus();
            });
        }
        var forgotModal = document.getElementById('forgotPasswordModal');
        if (forgotModal) {
            forgotModal.addEventListener('shown.bs.modal', function () {
                var id = document.getElementById('identifier');
                if (id) id.focus();
            });
        }

        /* ---------- Answered questions: live search ---------- */
        var search = document.getElementById('qaSearch');
        if (search) {
            var cards = document.querySelectorAll('[data-qa-item]');
            var shown = document.getElementById('qaShown');
            var empty = document.getElementById('qaEmpty');

            search.addEventListener('input', function () {
                var term = this.value.toLowerCase().trim();
                var visible = 0;

                cards.forEach(function (card) {
                    var match = card.getAttribute('data-search').indexOf(term) !== -1;
                    card.classList.toggle('d-none', !match);
                    if (match) {
                        visible++;
                        card.classList.add('in'); // make sure revealed items appear
                    }
                });

                if (shown) shown.textContent = visible;
                if (empty) empty.classList.toggle('d-none', visible !== 0);
            });
        }

        /* ---------- Ask a question (no function yet) ---------- */
        var askForm = document.getElementById('askForm');
        if (askForm) {
            var box = document.getElementById('ask_question');
            var count = document.getElementById('askCount');
            var notice = document.getElementById('askNotice');

            box.addEventListener('input', function () {
                count.textContent = box.value.length;
            });

            askForm.addEventListener('submit', function (e) {
                e.preventDefault();
                if (box.value.trim() === '') {
                    box.focus();
                    notice.innerHTML = '<div class="alert alert-warning py-2" role="alert">' +
                        'Please type your question first.</div>';
                    return;
                }
                notice.innerHTML = '<div class="alert alert-info py-2" role="alert">' +
                    'Question submission is not available yet. Please check back soon.</div>';
            });
        }
    });
})();
