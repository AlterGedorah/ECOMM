/**
 * Cookie Consent Logic
 * - Persists the user's choice in a first-party cookie (cookie_consent)
 * - Value "accepted"  -> enable analytics/marketing cookies (stubs provided)
 * - Value "rejected"  -> only keep strictly essential cookies
 *
 * The cookie is set with SameSite=Lax and a 180-day expiry so the banner
 * stays dismissed for returning visitors.
 */
(function () {
    'use strict';

    var COOKIE_NAME = 'cookie_consent';
    var COOKIE_DAYS = 180;

    function setCookie(name, value, days) {
        var expires = '';
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }
        // SameSite=Lax; path=/ so it's available across the site.
        document.cookie = name + '=' + encodeURIComponent(value) + expires +
                          '; path=/; SameSite=Lax';
    }

    function getCookie(name) {
        var nameEQ = name + '=';
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i].trim();
            if (c.indexOf(nameEQ) === 0) {
                return decodeURIComponent(c.substring(nameEQ.length));
            }
        }
        return null;
    }

    /**
     * Mirror the consent choice to the server so PHP can react on the next
     * request (for example, to convert a session-only login into a
     * cookie-backed one when the visitor accepts).
     */
    function syncConsentToServer(choice) {
        try {
            var body = 'consent=' + encodeURIComponent(choice);
            var url = (window.CookieConsent && window.CookieConsent.endpoint)
                ? window.CookieConsent.endpoint
                : '/Fresh Harvest/cookie_consent_handler.php';

            if (navigator.sendBeacon) {
                var blob = new Blob([body], { type: 'application/x-www-form-urlencoded' });
                navigator.sendBeacon(url, blob);
                return;
            }

            fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            }).catch(function () { /* best effort */ });
        } catch (e) { /* ignore */ }
    }

    function hideBanner() {
        var banner = document.getElementById('cookieConsentBanner');
        if (banner) {
            banner.classList.add('d-none');
        }
    }

    /**
     * Hooks that can be triggered when the user makes a choice.
     * Add your analytics, marketing, or personalization scripts here.
     */
    function onAccepted() {
        // Example: enable analytics. Replace with your real loader.
        // loadGoogleAnalytics();
        // loadMetaPixel();
        console.info('[cookies] User accepted all cookies.');
    }

    function onRejected() {
        // Make sure any non-essential scripts are turned off.
        // window['ga-disable-UA-XXXXXX-Y'] = true;
        console.info('[cookies] User rejected non-essential cookies.');
    }

    function handleChoice(choice) {
        if (choice !== 'accepted' && choice !== 'rejected') return;
        setCookie(COOKIE_NAME, choice, COOKIE_DAYS);
        // Tell the server about the new consent value so the next request
        // (e.g. when the user logs in or reloads the page) can react to it.
        syncConsentToServer(choice);
        hideBanner();
        if (choice === 'accepted') {
            onAccepted();
        } else {
            onRejected();
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var acceptBtn = document.getElementById('cookieAcceptBtn');
        var rejectBtn = document.getElementById('cookieRejectBtn');

        if (acceptBtn) {
            acceptBtn.addEventListener('click', function () {
                handleChoice('accepted');
            });
        }

        if (rejectBtn) {
            rejectBtn.addEventListener('click', function () {
                handleChoice('rejected');
            });
        }

        // Expose a small API so the rest of the app can read/update the
        // consent state without having to touch the cookie directly.
        window.CookieConsent = {
            get: function () { return getCookie(COOKIE_NAME); },
            accept: function () { handleChoice('accepted'); },
            reject: function () { handleChoice('rejected'); }
        };
    });
})();
