document.addEventListener("DOMContentLoaded", () => {
    
    // 1. Lógica do Modo Escuro / Claro
    const themeToggleBtn = document.getElementById("themeToggle");
    const themeIcon = document.getElementById("themeIcon");
    
    // Verifica a preferência salva no LocalStorage ou do sistema
    const savedTheme = localStorage.getItem("theme");
    if (savedTheme) {
        document.documentElement.setAttribute("data-theme", savedTheme);
        themeIcon.textContent = savedTheme === "light" ? "☀️" : "🌙";
    }

    themeToggleBtn.addEventListener("click", () => {
        const currentTheme = document.documentElement.getAttribute("data-theme");
        let newTheme = "dark";

        if (currentTheme !== "light") {
            newTheme = "light";
            themeIcon.textContent = "☀️";
        } else {
            themeIcon.textContent = "🌙";
        }

        document.documentElement.setAttribute("data-theme", newTheme);
        localStorage.setItem("theme", newTheme);
    });

    // 2. Animação de Scroll (Intersection Observer)
    const reveals = document.querySelectorAll(".reveal");

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add("active");
            }
        });
    }, { threshold: 0.15 });

    reveals.forEach(el => observer.observe(el));

    // 3. Ação do Botão
    const btnAction = document.getElementById("btnAction");
    if (btnAction) {
        btnAction.addEventListener("click", () => {
            alert("🚀 Tudo pronto e funcionando!");
        });
    }
});
