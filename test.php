<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bootstrap demo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <link rel="stylesheet" href="styles/style.css">
</head>

<body>
  <div class="navbar"></div>
  <div id="cookie-consent">
    <p>We use cookies to enhance your experience. By continuing to use our website, you agree to our cookie policy.</p>
    <button onclick='acceptCookie()'>Accept</button>
    <button onclick='rejectCookie()'>Reject</button>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous">
      function acceptCookie() {
        document.cookie = "cookieConsent=accepted; expires=Fri, 31 Dec 9999 23:59:59 GMT; path=/";
        document.getElementById('cookie-consent').style.display = 'none';
      }

      function rejectCookie() { document.cookie = "cookieConsent=rejected; expires=Fri, 31 Dec 9999 23:59:59 GMT; path=/"; document.getElementById('cookie-consent').style.display = 'none'; }
      (document.cookie.indexOf("cookieConsent=") == -1) { document.getElementById('cookie-consent').style.display = 'block'; }
    </script>
</body>

</html>