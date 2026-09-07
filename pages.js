(function () {
  "use strict";

  document.querySelectorAll(".page-reveal").forEach(function (element, index) {
    element.style.transitionDelay = index * 70 + "ms";
  });

  if (!("IntersectionObserver" in window)) {
    document.querySelectorAll(".page-reveal").forEach(function (element) {
      element.classList.add("is-visible");
    });
    return;
  }

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add("is-visible");
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });

  document.querySelectorAll(".page-reveal").forEach(function (element) {
    observer.observe(element);
  });
})();
