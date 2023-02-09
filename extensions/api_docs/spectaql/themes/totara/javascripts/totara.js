function totara(container) {
  var anchor;
  var id;
  var linkTarget;
  var prefersReducedMotionNoPreference = () =>
    window.matchMedia('(prefers-reduced-motion: no-preference)').matches;
  if (window.location.hash) {
    id = window.location.hash.substring(1);
    linkTarget = container.getElementById(id);
    if (prefersReducedMotionNoPreference()) {
      linkTarget.scrollIntoView({ behavior: 'smooth' });
    } else {
      linkTarget.scrollIntoView();
    }
  }

  container.addEventListener('click', e => {
    anchor = e.target.closest('a');
    if (!anchor || !anchor.hash) {
      return;
    }
    e.preventDefault();
    window.location.hash = anchor.hash;
    id = anchor.hash.substring(1);
    linkTarget = container.getElementById(id);
    linkTarget.focus({ preventScroll: true });
    if (document.activeElement !== linkTarget) {
      linkTarget.tabIndex = '-1';
      linkTarget.focus({ preventScroll: true });
      linkTarget.removeAttribute('tabindex');
    }
    if (prefersReducedMotionNoPreference()) {
      linkTarget.scrollIntoView({ behavior: 'smooth' });
    } else {
      linkTarget.scrollIntoView();
    }
  });
}
