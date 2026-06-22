document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".togglePassword").forEach(button => {
    button.addEventListener("click", () => {
      const input = document.getElementById(button.dataset.target || "");
      if (!input) return;

      const visible = input.type === "text";
      input.type = visible ? "password" : "text";
      button.querySelector("i")?.classList.toggle("bi-eye", visible);
      button.querySelector("i")?.classList.toggle("bi-eye-slash", !visible);
    });
  });

  const nueva = document.getElementById("passwordNueva");
  const confirmacion = document.getElementById("passwordConfirmacion");

  function validarConfirmacion() {
    if (!confirmacion) return;
    confirmacion.setCustomValidity(
      confirmacion.value && confirmacion.value !== nueva?.value
        ? "Las contraseñas no coinciden."
        : ""
    );
  }

  nueva?.addEventListener("input", validarConfirmacion);
  confirmacion?.addEventListener("input", validarConfirmacion);

  const notificacion = document.getElementById("perfilNotificacion");
  if (notificacion) {
    setTimeout(() => {
      notificacion.style.opacity = "0";
      setTimeout(() => notificacion.remove(), 350);
    }, 3500);
  }
});
