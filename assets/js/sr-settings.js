// assets/js/sr-settings.js
// JS dla zakładki "Ustawienia" w panelu /panel-reklamy
// - toasty (saved / deleted / error)
// - modale Cennik RADIO (Dodaj / Edytuj / Usuń)

(function () {
    'use strict';

    /**
     * Inicjalizacja toastów (komunikaty "zapisano", "usunięto", "błąd").
     */
    function initToasts() {
        var toasts = document.querySelectorAll('.sr-toast');
        if (!toasts.length) return;

        toasts.forEach(function (t) {
            // już są domyślnie widoczne (klasa sr-toast--visible),
            // po ~3.5s chowamy
            setTimeout(function () {
                t.classList.remove('sr-toast--visible');
            }, 3500);
        });
    }

    /**
     * Inicjalizacja modala dla Cennika RADIO.
     *
     * HTML generuje klasa SR_Frontend_Settings:
     * - overlay: #sr-modal-overlay
     * - modal:   #sr-modal-cennik-radio
     * - formularz zapis: #sr-modal-cr-form
     * - formularz delete: #sr-modal-cr-del-form
     * - przyciski sterujące:
     *   - .sr-btn-add-cennik-radio
     *   - .sr-btn-edit-cennik-radio
     *   - .sr-btn-del-cennik-radio
     */
    function initCennikRadioModal() {
        var overlay = document.getElementById('sr-modal-overlay');
        var modal   = document.getElementById('sr-modal-cennik-radio');

        if (!overlay || !modal) {
            // nie jesteśmy na widoku Cennika RADIO
            return;
        }

        var form      = document.getElementById('sr-modal-cr-form');
        var delForm   = document.getElementById('sr-modal-cr-del-form');
        var titleEl   = document.getElementById('sr-modal-cr-title');
        var idInput   = document.getElementById('sr-modal-cr-id');
        var godzInput = document.getElementById('sr-modal-cr-godzina');
        var cenaInput = document.getElementById('sr-modal-cr-cena');
        var cenaWInput = document.getElementById('sr-modal-cr-cena-weekend');
        var aktywnaCb  = document.getElementById('sr-modal-cr-aktywna');
        var delIdInput = document.getElementById('sr-modal-cr-del-id');

        var btnAdd = document.querySelector('.sr-btn-add-cennik-radio');

        function openModal(mode, row) {
            overlay.style.display = 'block';
            modal.style.display   = 'block';

            if (mode === 'add') {
                titleEl.textContent = 'Dodaj godzinę RADIO';
                if (idInput) idInput.value = '0';
                if (godzInput) godzInput.value = '';
                if (cenaInput) cenaInput.value = '';
                if (cenaWInput) cenaWInput.value = '';
                if (aktywnaCb) aktywnaCb.checked = true;

                if (form) {
                    var radios = form.querySelectorAll('input[name="start_reklamy"]');
                    radios.forEach(function (r) {
                        r.checked = (r.value === '>>');
                    });
                    form.style.display = 'block';
                }
                if (delForm) {
                    delForm.style.display = 'none';
                }
                return;
            }

            if (mode === 'edit' && row) {
                titleEl.textContent = 'Edytuj godzinę RADIO';

                if (idInput) idInput.value = row.dataset.id || '0';
                if (godzInput) godzInput.value = row.dataset.godzina || '';
                if (cenaInput) cenaInput.value = row.dataset.cena || '';
                if (cenaWInput) cenaWInput.value = row.dataset.cenaWeekend || '';

                var start = row.dataset.start || '>>';
                if (form) {
                    var radiosEdit = form.querySelectorAll('input[name="start_reklamy"]');
                    radiosEdit.forEach(function (r) {
                        r.checked = (r.value === start);
                    });
                }
                if (aktywnaCb) {
                    aktywnaCb.checked = (row.dataset.aktywna === '1');
                }

                if (form) form.style.display = 'block';
                if (delForm) delForm.style.display = 'none';
                return;
            }

            if (mode === 'delete' && row) {
                if (delIdInput) delIdInput.value = row.dataset.id || '0';
                if (form) form.style.display = 'none';
                if (delForm) delForm.style.display = 'block';
            }
        }

        function closeModal() {
            modal.style.display   = 'none';
            overlay.style.display = 'none';
        }

        if (btnAdd) {
            btnAdd.addEventListener('click', function () {
                openModal('add');
            });
        }

        var editButtons = document.querySelectorAll('.sr-btn-edit-cennik-radio');
        editButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var row = btn.closest('tr');
                if (!row) return;
                openModal('edit', row);
            });
        });

        var deleteButtons = document.querySelectorAll('.sr-btn-del-cennik-radio');
        deleteButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var row = btn.closest('tr');
                if (!row) return;
                openModal('delete', row);
            });
        });

        var cancelBtn = document.getElementById('sr-modal-cr-cancel');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeModal);
        }

        var delCancelBtn = document.getElementById('sr-modal-cr-del-cancel');
        if (delCancelBtn) {
            delCancelBtn.addEventListener('click', closeModal);
        }

        overlay.addEventListener('click', closeModal);
    }

    /**
     * Init całości dla zakładki Ustawienia.
     */
    function initSettings() {
        initToasts();
        initCennikRadioModal();
    }

    // Podpinamy się zarówno pod DOMContentLoaded jak i load (na wszelki wypadek).
    document.addEventListener('DOMContentLoaded', initSettings);
    window.addEventListener('load', initSettings);

})();