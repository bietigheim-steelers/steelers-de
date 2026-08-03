/**
 * Einblend-Animation für die Partnerliste.
 *
 * Die generische Animation aus animation.js ([data-sttr-wrapper]) haengt alle
 * Karten in EINE Timeline (0.6s je Karte, versetzt um -0.4s). Bei 150+ Partnern
 * ergibt das eine Timeline von über 30 Sekunden, die unabhaengig von der
 * Scrollposition durchlaeuft – wer schnell nach unten scrollt, sieht minutenlang
 * leere Kacheln.
 *
 * Hier wird stattdessen pro Karte beim Eintritt in den Viewport animiert
 * (IntersectionObserver). Sichtbare Karten sind sofort da, egal wie schnell
 * gescrollt wird. Die Optik (Dauer, Easing, Versatz) entspricht der des Themes.
 *
 * Greift ausschliesslich auf [data-partner-grid] – alle anderen Animationen
 * bleiben unberuehrt.
 */
(function () {
    'use strict';

    var GRID_SELECTOR = '[data-partner-grid]';
    var CARD_SELECTOR = '[data-partner-card]';

    function revealInstantly(cards) {
        for (var i = 0; i < cards.length; i++) {
            cards[i].style.opacity = '';
            cards[i].style.transform = '';
            cards[i].style.filter = '';
        }
    }

    function initGrid(grid) {
        var cards = grid.querySelectorAll(CARD_SELECTOR);

        if (!cards.length) {
            return;
        }

        var hasGsap = 'undefined' !== typeof window.gsap;

        // Ohne IntersectionObserver bleibt alles sichtbar – lieber keine
        // Animation als dauerhaft leere Kacheln.
        if ('undefined' === typeof window.IntersectionObserver) {
            return;
        }

        if (hasGsap) {
            window.gsap.set(cards, {
                y: 40,
                opacity: 0,
                filter: 'blur(8px)',
                force3D: true,
                willChange: 'transform, opacity, filter'
            });
        } else {
            for (var i = 0; i < cards.length; i++) {
                cards[i].style.opacity = '0';
            }
        }

        // Karten, die gemeinsam in den Viewport kommen, werden leicht versetzt
        // eingeblendet. Der Versatz wird pro Sichtbarkeitsschub zurueckgesetzt,
        // damit er sich ueber die Liste hinweg nicht aufsummiert.
        var pending = [];
        var frame = null;

        function flush() {
            frame = null;

            var batch = pending;
            pending = [];

            if (!batch.length) {
                return;
            }

            if (!hasGsap) {
                revealInstantly(batch);

                return;
            }

            window.gsap.to(batch, {
                y: 0,
                opacity: 1,
                filter: 'blur(0px)',
                duration: 0.5,
                ease: 'power3.out',
                stagger: 0.06,
                overwrite: 'auto',
                force3D: true,
                clearProps: 'filter,willChange'
            });
        }

        var observer = new window.IntersectionObserver(function (entries) {
            for (var i = 0; i < entries.length; i++) {
                if (!entries[i].isIntersecting) {
                    continue;
                }

                pending.push(entries[i].target);
                observer.unobserve(entries[i].target);
            }

            if (pending.length && null === frame) {
                frame = window.requestAnimationFrame(flush);
            }
        }, {
            // Etwas vor dem Viewport starten, damit die Karte beim Erscheinen
            // bereits im Einblenden ist.
            rootMargin: '0px 0px 10% 0px',
            threshold: 0
        });

        for (var j = 0; j < cards.length; j++) {
            observer.observe(cards[j]);
        }
    }

    function init() {
        var grids = document.querySelectorAll(GRID_SELECTOR);

        for (var i = 0; i < grids.length; i++) {
            initGrid(grids[i]);
        }
    }

    if ('loading' === document.readyState) {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
