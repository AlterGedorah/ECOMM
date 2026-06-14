<?php
/**
 * Cookie Consent Banner
 *
 * Reads the user's stored preference (if any) from the cookie_consent cookie
 * and only renders the banner when the user has not yet made a choice.
 *
 * Accepted values for the cookie_consent cookie:
 *   - "accepted"  -> user accepted cookies
 *   - "rejected"  -> user rejected non-essential cookies
 */
$cookie_consent = $_COOKIE['cookie_consent'] ?? null;
$show_banner    = ($cookie_consent !== 'accepted' && $cookie_consent !== 'rejected');
?>
<!-- Cookie Consent Banner -->
<div id="cookieConsentBanner"
     class="cookie-consent-banner<?= $show_banner ? '' : ' d-none' ?>"
     role="dialog"
     aria-live="polite"
     aria-label="Cookie consent">
  <div class="cookie-consent-card shadow-lg">
    <div class="cookie-consent-icon" aria-hidden="true">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10c0-.46-.04-.92-.1-1.36-.5.21-1.05.36-1.65.36-1.38 0-2.5-1.12-2.5-2.5 0-.6.15-1.15.36-1.65C17.92 6.04 17.46 6 17 6c-1.66 0-3-1.34-3-3 0-.46.04-.92.1-1.36C13.58 2.04 12.8 2 12 2z"
              fill="#1E3028"/>
        <circle cx="9" cy="10" r="1.2" fill="#FFFDD0"/>
        <circle cx="14" cy="14" r="1" fill="#FFFDD0"/>
        <circle cx="10" cy="15.5" r="0.9" fill="#FFFDD0"/>
        <circle cx="15" cy="9" r="0.8" fill="#FFFDD0"/>
      </svg>
    </div>

    <div class="cookie-consent-content">
      <h2 class="cookie-consent-title">We value your privacy</h2>
      <p class="cookie-consent-text">
        We use cookies to enhance your browsing experience, serve personalized
        content, and analyze our traffic. By clicking
        <strong>“Accept All”</strong> you consent to our use of cookies.
        You can also choose <strong>“Reject Non-Essential”</strong> to keep
        only the cookies required for the site to function.
        <a href="#" class="cookie-consent-link">Read our cookie policy</a>.
      </p>
    </div>

    <div class="cookie-consent-actions">
      <button type="button"
              id="cookieRejectBtn"
              class="btn btn-outline-secondary cookie-btn cookie-btn--reject">
        Reject Non-Essential
      </button>
      <button type="button"
              id="cookieAcceptBtn"
              class="btn cookie-btn cookie-btn--accept">
        Accept All
      </button>
    </div>
  </div>
</div>
