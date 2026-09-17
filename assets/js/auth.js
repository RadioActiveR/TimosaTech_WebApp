/* Timosa Tech — Auth modal (open/close + Login/Sign Up tab switching) */

/* INFO: Linked Files:

    NONE

*/

/* INFO: Is used by:

    shop.php
    homepage.php

*/

document.addEventListener("DOMContentLoaded", function () {
  const overlay   = document.getElementById("authOverlay");
  const openBtns  = document.querySelectorAll("[data-open-auth]");
  const closeBtn  = document.getElementById("authClose");
  const tabs      = document.querySelectorAll(".auth-tab");
  const forms     = document.querySelectorAll(".auth-form");
  const switchBtns = document.querySelectorAll(".auth-switch [data-target]");
  const redirectInputs = document.querySelectorAll(".auth-redirect-to");

  if (!overlay) return;

  function setRedirectTarget(redirectTo) {
    const target = redirectTo || (window.location.pathname + window.location.search);
    redirectInputs.forEach(input => { input.value = target; });
  }

  function openModal(target, redirectTo) {
    setRedirectTarget(redirectTo);
    overlay.classList.add("active");
    document.body.style.overflow = "hidden";
    if (target) showTab(target);
  }

  window.openAuthModal = openModal;

  function closeModal() {
    overlay.classList.remove("active");
    document.body.style.overflow = "";
  }

  function showTab(target) {
    tabs.forEach(t => t.classList.toggle("active", t.dataset.target === target));
    forms.forEach(f => f.classList.toggle("active", f.dataset.form === target));
  }

  openBtns.forEach(btn => {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      openModal(btn.dataset.openAuth || "login", btn.dataset.redirectTo);
    });
  });

  closeBtn.addEventListener("click", closeModal);

  overlay.addEventListener("click", function (e) {
    if (e.target === overlay) closeModal();
  });

  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && overlay.classList.contains("active")) closeModal();
  });

  tabs.forEach(tab => {
    tab.addEventListener("click", () => showTab(tab.dataset.target));
  });

  switchBtns.forEach(btn => {
    btn.addEventListener("click", () => showTab(btn.dataset.target));
  });

  const togglePassBtns = document.querySelectorAll(".toggle-password");
  togglePassBtns.forEach(btn => {
    btn.addEventListener("click", function () {
      const wrapper = this.closest(".password-wrapper");
      const input = wrapper.querySelector("input");
      const isPassword = input.getAttribute("type") === "password";

      input.setAttribute("type", isPassword ? "text" : "password");
      this.classList.toggle("visible", isPassword);
    });
  });

  const signupForm = document.getElementById("signupForm");
  if (signupForm) {
    signupForm.addEventListener("submit", function (e) {
      const password = document.getElementById("signupPassword").value;
      const confirm = document.getElementById("signupConfirm").value;

      if (password !== confirm) {
        e.preventDefault();
        
        let errorContainer = signupForm.querySelector(".auth-error");
        if (!errorContainer) {
          errorContainer = document.createElement("p");
          errorContainer.className = "auth-error";
          
          const subtext = signupForm.querySelector(".auth-subtext");
          if (subtext) {
            subtext.after(errorContainer);
          } else {
            signupForm.prepend(errorContainer);
          }
        }
        errorContainer.textContent = "Passwords do not match.";
      }
    });
  }
});