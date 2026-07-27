// ============================================
// DASHBEAUTY - APP PRINCIPAL
// ============================================

// ============================================
// FUNÇÕES GLOBAIS
// ============================================

function showToast(msg, type = "success") {
  const toast = document.getElementById("toast");
  if (!toast) return;
  toast.textContent = msg;
  toast.className = `toast ${type}`;
  toast.style.display = "block";
  setTimeout(() => (toast.style.display = "none"), 3000);
}

function renderStars(rating) {
  let stars = "";
  for (let i = 0; i < Math.floor(rating); i++)
    stars += '<i class="fas fa-star"></i>';
  if (rating % 1 >= 0.5) stars += '<i class="fas fa-star-half-alt"></i>';
  for (let i = stars.length / 2; i < 5; i++)
    stars += '<i class="far fa-star"></i>';
  return stars;
}

function formatDate(dateStr) {
  const d = new Date(dateStr);
  return d.toLocaleDateString("pt-BR");
}

function getStatusText(status) {
  const map = {
    scheduled: "Agendado",
    completed: "Concluído",
    cancelled: "Cancelado",
    confirmed: "Confirmado",
  };
  return map[status] || status;
}

function logout() {
  localStorage.clear();
  window.location.href = "/TCC/Dashbeauty/public/index.html";
}

function checkAuth() {
  if (!localStorage.getItem("auth_token")) {
    window.location.href = "/TCC/Dashbeauty/public/login.html";
  }
}

// ============================================
// NAVEGAÇÃO ENTRE ABAS
// ============================================

function setupNavigation() {
  document.querySelectorAll(".nav-link").forEach((btn) => {
    btn.addEventListener("click", function () {
      document
        .querySelectorAll(".nav-link")
        .forEach((b) => b.classList.remove("active"));
      this.classList.add("active");
      document
        .querySelectorAll(".page")
        .forEach((p) => p.classList.remove("active"));
      const pageId = "page-" + this.dataset.page;
      const page = document.getElementById(pageId);
      if (page) page.classList.add("active");
    });
  });
}

// ============================================
// INICIALIZAÇÃO
// ============================================

document.addEventListener("DOMContentLoaded", function () {
  // Verificar autenticação
  if (
    window.location.pathname.includes("client.html") ||
    window.location.pathname.includes("business.html")
  ) {
    checkAuth();
  }

  // Configurar navegação
  setupNavigation();
});
