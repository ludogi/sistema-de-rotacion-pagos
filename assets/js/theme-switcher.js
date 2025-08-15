// 🎨 Sistema de Cambio de Temas - Innovant Café

class ThemeSwitcher {
    constructor() {
        this.currentTheme = localStorage.getItem('theme') || 'light';
        // NO inicializar automáticamente, esperar a que el DOM esté listo
    }
    
    init() {
        console.log('🚀 Inicializando ThemeSwitcher...');
        
        // Aplicar tema guardado
        this.applyTheme(this.currentTheme);
        
        // Crear botón de cambio de tema si no existe
        this.createThemeToggle();
        
        // Agregar listener para cambios de tema del sistema
        this.watchSystemTheme();
        
        console.log('✅ ThemeSwitcher inicializado');
    }

    init() {
        console.log('🚀 Inicializando ThemeSwitcher...');
        
        // Aplicar tema guardado
        this.applyTheme(this.currentTheme);
        
        // Crear botón de cambio de tema si no existe
        this.createThemeToggle();
        
        // Agregar listener para cambios de tema del sistema
        this.watchSystemTheme();
        
        console.log('✅ ThemeSwitcher inicializado');
    }

    createThemeToggle() {
        // Buscar si ya existe el botón con ID themeToggle
        let themeToggle = document.querySelector('#themeToggle');
        
        if (themeToggle) {
            // El botón ya existe, solo agregar el evento click
            themeToggle.addEventListener('click', () => this.toggleTheme());
            
            // Actualizar el icono inicial
            themeToggle.innerHTML = this.getThemeIcon();
            
            console.log('✅ Botón de tema configurado correctamente');
        } else {
            console.log('❌ Botón de tema no encontrado');
        }
    }

    getThemeIcon() {
        return this.currentTheme === 'light' 
            ? '<i class="bi bi-moon-fill"></i>' 
            : '<i class="bi bi-sun-fill"></i>';
    }

    toggleTheme() {
        console.log('🔄 Cambiando tema...');
        const newTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        console.log(`Tema actual: ${this.currentTheme} -> Nuevo tema: ${newTheme}`);
        
        this.applyTheme(newTheme);
        
        // Actualizar icono del botón
        const themeToggle = document.querySelector('#themeToggle');
        if (themeToggle) {
            themeToggle.innerHTML = this.getThemeIcon();
            console.log('✅ Icono del botón actualizado');
        }
    }

    applyTheme(theme) {
        console.log(`🎨 Aplicando tema: ${theme}`);
        this.currentTheme = theme;
        
        // Aplicar tema al documento
        document.documentElement.setAttribute('data-theme', theme);
        console.log('✅ Atributo data-theme aplicado');
        
        // Guardar en localStorage
        localStorage.setItem('theme', theme);
        console.log('✅ Tema guardado en localStorage');
        
        // Aplicar tema a elementos específicos
        this.applyThemeToElements(theme);
        
        // Emitir evento personalizado
        document.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme } }));
        console.log('✅ Evento themeChanged emitido');
    }

    applyThemeToElements(theme) {
        // Aplicar tema a modales específicos
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (theme === 'dark') {
                modal.classList.add('dark-theme');
            } else {
                modal.classList.remove('dark-theme');
            }
        });

        // Aplicar tema a cards
        const cards = document.querySelectorAll('.card');
        cards.forEach(card => {
            if (theme === 'dark') {
                card.classList.add('dark-theme');
            } else {
                card.classList.remove('dark-theme');
            }
        });

        // Aplicar tema a tablas
        const tables = document.querySelectorAll('.table');
        tables.forEach(table => {
            if (theme === 'dark') {
                table.classList.add('dark-theme');
            } else {
                table.classList.remove('dark-theme');
            }
        });
    }

    watchSystemTheme() {
        // Verificar si el navegador soporta prefers-color-scheme
        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            
            // Listener para cambios automáticos del sistema
            mediaQuery.addEventListener('change', (e) => {
                if (!localStorage.getItem('theme')) {
                    // Solo cambiar automáticamente si el usuario no ha elegido un tema
                    const newTheme = e.matches ? 'dark' : 'light';
                    this.applyTheme(newTheme);
                }
            });
        }
    }

    // Método para obtener el tema actual
    getCurrentTheme() {
        return this.currentTheme;
    }

    // Método para verificar si es tema oscuro
    isDarkTheme() {
        return this.currentTheme === 'dark';
    }

    // Método para aplicar tema específico
    setTheme(theme) {
        if (['light', 'dark'].includes(theme)) {
            this.applyTheme(theme);
        }
    }
}

// 🚀 Inicializar sistema de temas cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.themeSwitcher = new ThemeSwitcher();
    
    // Agregar animaciones de entrada
    addEntryAnimations();
    
    // Agregar efectos de hover mejorados
    addHoverEffects();
    
    // Agregar funcionalidades responsivas
    addResponsiveFeatures();
});

// ✨ Animaciones de entrada
function addEntryAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in-up');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observar cards y elementos importantes
    document.querySelectorAll('.card, .alert, .modal-content').forEach(el => {
        observer.observe(el);
    });
}

// 🎯 Efectos de hover mejorados
function addHoverEffects() {
    // Efecto de elevación en cards
    document.querySelectorAll('.card').forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0) scale(1)';
        });
    });

    // Efecto de brillo en botones
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('mouseenter', () => {
            btn.style.filter = 'brightness(1.1)';
        });
        
        btn.addEventListener('mouseleave', () => {
            btn.style.filter = 'brightness(1)';
        });
    });
}

// 📱 Funcionalidades responsivas
function addResponsiveFeatures() {
    // Menú móvil mejorado
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    
    if (navbarToggler && navbarCollapse) {
        navbarToggler.addEventListener('click', () => {
            navbarCollapse.classList.toggle('show');
            
            // Agregar animación al menú móvil
            if (navbarCollapse.classList.contains('show')) {
                navbarCollapse.style.animation = 'slideInRight 0.3s ease-out';
            }
        });
    }

    // Cerrar menú móvil al hacer clic en un enlace
    document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 768) {
                navbarCollapse.classList.remove('show');
            }
        });
    });

    // Ajustar altura de modales en móviles
    window.addEventListener('resize', () => {
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(modal => {
            if (window.innerWidth < 768) {
                modal.style.height = '100vh';
                modal.style.margin = '0';
            } else {
                modal.style.height = '';
                modal.style.margin = '';
            }
        });
    });
}

// 🌙 Detectar tema del sistema al cargar
if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
    // Si no hay tema guardado, usar el del sistema
    if (!localStorage.getItem('theme')) {
        document.documentElement.setAttribute('data-theme', 'dark');
    }
}

// 📱 Detectar dispositivo móvil
function isMobile() {
    return window.innerWidth <= 768;
}

// 🎨 Aplicar estilos específicos para móviles
if (isMobile()) {
    document.body.classList.add('mobile-device');
}

// 🔄 Actualizar en tiempo real
window.addEventListener('resize', () => {
    if (isMobile()) {
        document.body.classList.add('mobile-device');
    } else {
        document.body.classList.remove('mobile-device');
    }
});

// Exportar para uso global
window.ThemeSwitcher = ThemeSwitcher;