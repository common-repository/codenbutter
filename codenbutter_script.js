if (codenbutter_options.script_url) {
  (function (co, de, n, but, t, e, r) {
    !n[co] &&
      (n[co] = function () {
        (n[co].q = n[co].q || []).push(arguments);
      });
    e = t.createElement(but);
    e.async = true;
    e.src = de;
    r = t.getElementsByTagName(but)[0];
    r.parentNode.insertBefore(e, r);
  })("CodenButter", codenbutter_options.script_url, window, "script", document);
}

if (codenbutter_options.codenbutter_site_id) {
  window.CodenButter("boot", {
    siteId: codenbutter_options.codenbutter_site_id,
    auto: true,
  });
}
