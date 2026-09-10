/* HI TECH SAVVY'S — vanilla interactions (no frameworks, no TS) */
(function () {
  "use strict";

  // Sticky header state
  var header = document.getElementById("siteHeader");
  function onScroll() {
    if (header) header.classList.toggle("scrolled", window.scrollY > 40);
  }
  window.addEventListener("scroll", onScroll, { passive: true });
  onScroll();

  // Mobile nav toggle
  var toggle = document.getElementById("navToggle");
  if (toggle && header) {
    toggle.addEventListener("click", function () {
      header.classList.toggle("open");
    });
    header.querySelectorAll(".nav a").forEach(function (a) {
      a.addEventListener("click", function () {
        header.classList.remove("open");
      });
    });
  }

  // Scroll reveal via IntersectionObserver
  var reveals = document.querySelectorAll(".reveal");
  if ("IntersectionObserver" in window) {
    // stagger index inside grids
    document
      .querySelectorAll(".svc-grid, .project-grid, .showcase-row")
      .forEach(function (grid) {
        grid.querySelectorAll(".reveal").forEach(function (el, i) {
          el.style.setProperty("--i", i % 4);
        });
      });

    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add("in");
            io.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.12, rootMargin: "0px 0px -8% 0px" }
    );
    reveals.forEach(function (el) {
      io.observe(el);
    });
  } else {
    reveals.forEach(function (el) {
      el.classList.add("in");
    });
  }

  // Animated stat counters
  function animateCount(el) {
    var target = parseInt(el.getAttribute("data-count"), 10) || 0;
    var dur = 1400,
      start = null;
    function step(ts) {
      if (!start) start = ts;
      var p = Math.min((ts - start) / dur, 1);
      var eased = 1 - Math.pow(1 - p, 3);
      el.textContent = Math.floor(eased * target);
      if (p < 1) requestAnimationFrame(step);
      else el.textContent = target;
    }
    requestAnimationFrame(step);
  }
  var counters = document.querySelectorAll("[data-count]");
  if ("IntersectionObserver" in window && counters.length) {
    var co = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (e) {
          if (e.isIntersecting) {
            animateCount(e.target);
            co.unobserve(e.target);
          }
        });
      },
      { threshold: 0.5 }
    );
    counters.forEach(function (c) {
      co.observe(c);
    });
  }

  // Contact form — send the submission to the same-origin PHP endpoint.
  var form = document.getElementById("contactForm");
  var note = document.getElementById("formNote");
  if (form && note) {
    form.addEventListener("submit", async function (e) {
      e.preventDefault();
      var name = form.querySelector("#cf-name");
      var email = form.querySelector("#cf-email");
      var phone = form.querySelector("#cf-phone");
      var message = form.querySelector("#cf-msg");
      var submitButton = form.querySelector("[type=submit]");
      var fields = [name, email, phone, message];
      var hasInvalidField = false;
      fields.forEach(function (field) {
        var error = form.querySelector("#" + field.id + "-error");
        var invalid = !field.value.trim() || (field === email && !field.validity.valid);
        field.classList.toggle("invalid", invalid);
        error.classList.toggle("show", invalid);
        hasInvalidField = hasInvalidField || invalid;
      });
      if (hasInvalidField) {
        note.textContent = "Please fill in all the information before sending your request.";
        note.style.color = "#ff8f8f";
        note.classList.add("show");
        return;
      }

      submitButton.disabled = true;
      submitButton.setAttribute("aria-busy", "true");
      note.textContent = "Sending your request...";
      note.style.color = "";
      note.classList.add("show");

      try {
        var response = await fetch("contact.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            name: name.value.trim(),
            email: email.value.trim(),
            phone: phone.value.trim(),
            message: message.value.trim(),
          }),
        });
        var result = await response.json();
        if (!response.ok || !result.success) {
          throw new Error(result.message || "We could not send your request.");
        }
        note.textContent = result.message;
        form.reset();
      } catch (error) {
        note.textContent = error.message || "We could not send your request. Please try again.";
        note.style.color = "#ff8f8f";
      } finally {
        submitButton.disabled = false;
        submitButton.removeAttribute("aria-busy");
      }
    });
    form.querySelectorAll("input, textarea").forEach(function (field) {
      field.addEventListener("input", function () {
        var error = form.querySelector("#" + field.id + "-error");
        if (field.value.trim() && (field.type !== "email" || field.validity.valid)) {
          field.classList.remove("invalid");
          error.classList.remove("show");
        }
      });
    });
  }
})

const form = document.getElementById('form');
const submitBtn = form.querySelector('button[type="submit"]');

form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData(form);
    formData.append("access_key", "28585c47-5aca-44e7-a37f-e1919f518dda");

    const originalText = submitBtn.textContent;

    submitBtn.textContent = "Sending...";
    submitBtn.disabled = true;

    try {
        const response = await fetch("https://api.web3forms.com/submit", {
            method: "POST",
            body: formData
        });

        const data = await response.json();

        if (response.ok) {
            alert("Success! Your message has been sent.");
            form.reset();
        } else {
            alert("Error: " + data.message);
        }

    } catch (error) {
        alert("Something went wrong. Please try again.");
    } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
});



