(function () {
    'use strict';

    const $ = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));

    // ---------------------------------------------------------------------
    // Menú móvil
    // ---------------------------------------------------------------------
    const menuToggle = $('[data-menu-toggle]');
    if (menuToggle) {
        menuToggle.addEventListener('click', () => {
            const open = document.body.classList.toggle('nav-open');
            menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    // ---------------------------------------------------------------------
    // Menú del panel editorial
    // ---------------------------------------------------------------------
    const adminToggle = $('[data-admin-toggle]');
    const adminOverlay = $('[data-admin-overlay]');
    if (adminToggle) {
        const closeAdmin = () => {
            document.body.classList.remove('admin-nav-open');
            adminToggle.setAttribute('aria-expanded', 'false');
            if (adminOverlay) adminOverlay.hidden = true;
        };
        adminToggle.addEventListener('click', () => {
            const open = document.body.classList.toggle('admin-nav-open');
            adminToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (adminOverlay) adminOverlay.hidden = !open;
        });
        if (adminOverlay) adminOverlay.addEventListener('click', closeAdmin);
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeAdmin();
        });
    }

    // ---------------------------------------------------------------------
    // Cerrar menús de usuario al hacer clic fuera
    // ---------------------------------------------------------------------
    document.addEventListener('click', (event) => {
        $$('details.user-menu[open]').forEach((details) => {
            if (!details.contains(event.target)) details.removeAttribute('open');
        });
    });

    // ---------------------------------------------------------------------
    // Mensajes flash
    // ---------------------------------------------------------------------
    $$('[data-dismiss]').forEach((button) => {
        button.addEventListener('click', () => {
            const flash = button.closest('.flash');
            if (flash) flash.remove();
        });
    });

    // ---------------------------------------------------------------------
    // Copiar al portapapeles
    // ---------------------------------------------------------------------
    $$('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const text = button.getAttribute('data-copy') || '';
            const original = button.innerHTML;
            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(text);
                } else {
                    const area = document.createElement('textarea');
                    area.value = text;
                    area.style.position = 'fixed';
                    area.style.opacity = '0';
                    document.body.appendChild(area);
                    area.select();
                    document.execCommand('copy');
                    area.remove();
                }
                button.innerHTML = 'Copiado';
                window.setTimeout(() => { button.innerHTML = original; }, 1800);
            } catch (error) {
                window.prompt('Copia el texto:', text);
            }
        });
    });

    // ---------------------------------------------------------------------
    // Contadores de caracteres
    // ---------------------------------------------------------------------
    const updateCounter = (field) => {
        const target = document.querySelector('[data-counter-for="' + field.id + '"]');
        if (!target) return;
        const max = field.getAttribute('maxlength');
        const current = field.value.length;
        target.textContent = max
            ? current.toLocaleString('es-MX') + ' / ' + Number(max).toLocaleString('es-MX') + ' caracteres'
            : current.toLocaleString('es-MX') + ' caracteres';
    };
    $$('[data-counter]').forEach((field) => {
        updateCounter(field);
        field.addEventListener('input', () => updateCounter(field));
    });

    // ---------------------------------------------------------------------
    // Especie → razas
    // ---------------------------------------------------------------------
    const speciesSelect = $('[data-species-select]');
    const breedSelect = $('[data-breed-select]');
    if (speciesSelect && breedSelect) {
        const filterBreeds = () => {
            const speciesId = speciesSelect.value;
            let hasSelected = false;
            $$('option', breedSelect).forEach((option) => {
                const optionSpecies = option.getAttribute('data-species-id');
                if (!optionSpecies) return;
                const visible = !speciesId || optionSpecies === speciesId;
                option.hidden = !visible;
                option.disabled = !visible;
                if (visible && option.selected) hasSelected = true;
            });
            if (!hasSelected) {
                const first = $('option[value=""]', breedSelect);
                if (first) first.selected = true;
            }
        };
        speciesSelect.addEventListener('change', filterBreeds);
        filterBreeds();
    }

    // ---------------------------------------------------------------------
    // Previsualización de imágenes
    // ---------------------------------------------------------------------
    const imageInput = $('[data-image-input]');
    const previews = $('[data-image-previews]');
    const maxImages = 6;

    const renderPreviews = (files) => {
        if (!previews) return;
        previews.innerHTML = '';
        Array.from(files).slice(0, maxImages).forEach((file) => {
            if (!file.type.startsWith('image/')) return;
            const figure = document.createElement('figure');
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.alt = '';
            const caption = document.createElement('figcaption');
            caption.textContent = file.name;
            figure.appendChild(img);
            figure.appendChild(caption);
            previews.appendChild(figure);
        });
    };

    if (imageInput) {
        imageInput.addEventListener('change', () => {
            if (imageInput.files.length > maxImages) {
                const transfer = new DataTransfer();
                Array.from(imageInput.files).slice(0, maxImages).forEach((file) => transfer.items.add(file));
                imageInput.files = transfer.files;
                window.alert('Puedes subir hasta ' + maxImages + ' imágenes por caso.');
            }
            renderPreviews(imageInput.files);
        });
    }

    $$('[data-file-drop]').forEach((drop) => {
        const input = $('[data-image-input]', drop);
        if (!input) return;
        ['dragenter', 'dragover'].forEach((eventName) => {
            drop.addEventListener(eventName, (event) => {
                event.preventDefault();
                drop.classList.add('is-dragging');
            });
        });
        ['dragleave', 'drop'].forEach((eventName) => {
            drop.addEventListener(eventName, (event) => {
                event.preventDefault();
                drop.classList.remove('is-dragging');
            });
        });
        drop.addEventListener('drop', (event) => {
            if (!event.dataTransfer || !event.dataTransfer.files.length) return;
            const transfer = new DataTransfer();
            Array.from(event.dataTransfer.files).slice(0, maxImages).forEach((file) => {
                if (file.type.startsWith('image/')) transfer.items.add(file);
            });
            input.files = transfer.files;
            renderPreviews(input.files);
        });
    });

    // ---------------------------------------------------------------------
    // Reacciones «Me resultó útil» sin recargar
    // ---------------------------------------------------------------------
    $$('[data-reaction-form]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = $('[data-reaction-button]', form);
            const label = $('[data-reaction-label]', form);
            const count = $('[data-reaction-count]', form);
            if (!button) return;
            button.disabled = true;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });
                if (!response.ok) throw new Error('No se pudo registrar');
                const data = await response.json();
                if (count) count.textContent = String(data.count);
                if (label) label.textContent = data.active ? 'Marcado como útil' : 'Me resultó útil';
                button.classList.toggle('btn-primary', !!data.active);
                button.classList.toggle('btn-outline', !data.active);
            } catch (error) {
                form.submit();
            } finally {
                button.disabled = false;
            }
        });
    });

    // ---------------------------------------------------------------------
    // Apoyo diagnóstico
    // ---------------------------------------------------------------------
    const diagnosticForm = $('[data-diagnostic-form]');
    if (diagnosticForm) {
        const maxSigns = 20;
        const countEl = $('[data-selected-count]', diagnosticForm);
        const chipsEl = $('[data-selected-chips]', diagnosticForm);
        const checkboxes = $$('[data-sign]', diagnosticForm);

        const selected = () => checkboxes.filter((checkbox) => checkbox.checked);

        const renderChips = () => {
            if (!chipsEl) return;
            const checked = selected();
            chipsEl.innerHTML = '';
            if (!checked.length) {
                const empty = document.createElement('span');
                empty.className = 'muted';
                empty.textContent = 'Aún no has seleccionado signos.';
                chipsEl.appendChild(empty);
                return;
            }
            checked.forEach((checkbox) => {
                const label = checkbox.closest('.sign-option');
                const name = label ? $('span', label)?.textContent?.trim() : 'Signo';
                const chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'tag';
                chip.textContent = name + ' ×';
                chip.setAttribute('aria-label', 'Quitar ' + name);
                chip.addEventListener('click', () => {
                    checkbox.checked = false;
                    update();
                });
                chipsEl.appendChild(chip);
            });
        };

        const update = () => {
            const checked = selected();
            if (countEl) countEl.textContent = String(checked.length);
            if (checked.length > maxSigns) {
                const last = checked[checked.length - 1];
                last.checked = false;
                window.alert('Puedes seleccionar hasta ' + maxSigns + ' signos. Quita alguno para agregar otro.');
            }
            renderChips();
        };

        checkboxes.forEach((checkbox) => checkbox.addEventListener('change', update));
        update();

        const filterInput = $('[data-sign-filter]', diagnosticForm);
        if (filterInput) {
            const normalize = (value) => value.toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            filterInput.addEventListener('input', () => {
                const term = normalize(filterInput.value.trim());
                $$('[data-sign-group]', diagnosticForm).forEach((group) => {
                    let visible = 0;
                    $$('[data-sign-option]', group).forEach((option) => {
                        const match = !term || normalize(option.getAttribute('data-sign-label') || '').includes(term);
                        option.hidden = !match;
                        if (match) visible++;
                    });
                    group.hidden = visible === 0;
                });
            });
        }

        diagnosticForm.addEventListener('submit', (event) => {
            if (selected().length === 0) {
                event.preventDefault();
                window.alert('Selecciona al menos un signo clínico para calcular los diferenciales.');
            }
        });
    }

    // ---------------------------------------------------------------------
    // Confirmación de formularios marcados
    // ---------------------------------------------------------------------
    $$('[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.getAttribute('data-confirm') || '¿Continuar?')) {
                event.preventDefault();
            }
        });
    });

    // ---------------------------------------------------------------------
    // Sombra del header al hacer scroll
    // ---------------------------------------------------------------------
    const header = $('[data-header]');
    if (header) {
        const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 12);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }
})();
