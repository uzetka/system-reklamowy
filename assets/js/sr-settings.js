// assets/js/sr-settings.js
// JS dla zakładki "Ustawienia" w panelu /panel-reklamy
// - toasty (saved / deleted / error)
// - modale Cennik RADIO (Dodaj / Edytuj / Usuń)
// - modale Przelicznik czasu (Dodaj / Edytuj / Usuń)
// - modale Przedmiot działalności (Dodaj / Edytuj / Usuń)

(function () {
    'use strict';

    function initToasts() {
        var toasts = document.querySelectorAll('.sr-toast');
        if (!toasts.length) return;

        toasts.forEach(function (t) {
            setTimeout(function () {
                t.classList.remove('sr-toast--visible');
            }, 3500);
        });
    }

    function initCennikRadioModal() {
        var overlay = document.getElementById('sr-modal-overlay');
        var modal   = document.getElementById('sr-modal-cennik-radio');

        if (!overlay || !modal) return;

        var form     = document.getElementById('sr-modal-cr-form');
        var delForm  = document.getElementById('sr-modal-cr-del-form');
        var titleEl  = document.getElementById('sr-modal-cr-title');
        var idInput  = document.getElementById('sr-modal-cr-id');
        var godzInput= document.getElementById('sr-modal-cr-godzina');
        var cenaInput= document.getElementById('sr-modal-cr-cena');
        var cenaWInput = document.getElementById('sr-modal-cr-cena-weekend');
        var aktywnaCb  = document.getElementById('sr-modal-cr-aktywna');
        var delIdInput = document.getElementById('sr-modal-cr-del-id');

        var btnAdd   = document.querySelector('.sr-btn-add-cennik-radio');

        function openModal(mode, row) {
            overlay.style.display = 'block';
            modal.style.display   = 'block';

            if (mode === 'add') {
                titleEl.textContent = 'Dodaj godzinę RADIO';
                if (idInput)   idInput.value   = '0';
                if (godzInput) godzInput.value = '';
                if (cenaInput) cenaInput.value = '';
                if (cenaWInput) cenaWInput.value = '';
                if (aktywnaCb) aktywnaCb.checked = true;

                if (form) {
                    var radios = form.querySelectorAll('input[name="start_reklamy"]');
                    radios.forEach(function (r) {
                        r.checked = (r.value === 'BackwardFloating');
                    });
                    form.style.display = 'block';
                }
                if (delForm) delForm.style.display = 'none';
                return;
            }

            if (mode === 'edit' && row) {
                titleEl.textContent = 'Edytuj godzinę RADIO';

                if (idInput)   idInput.value   = row.dataset.id || '0';
                if (godzInput) godzInput.value = row.dataset.godzina || '';
                if (cenaInput) cenaInput.value = row.dataset.cena || '';
                if (cenaWInput) cenaWInput.value = row.dataset.cenaWeekend || row.dataset.cena_weekend || '';

                var start = row.dataset.start || 'BackwardFloating';
                if (form) {
                    var radiosEdit = form.querySelectorAll('input[name="start_reklamy"]');
                    radiosEdit.forEach(function (r) {
                        r.checked = (r.value === start);
                    });
                }
                if (aktywnaCb) {
                    aktywnaCb.checked = (row.dataset.aktywna === '1');
                }

                if (form)    form.style.display    = 'block';
                if (delForm) delForm.style.display = 'none';
                return;
            }

            if (mode === 'delete' && row) {
                if (delIdInput) delIdInput.value = row.dataset.id || '0';
                if (form)    form.style.display    = 'none';
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
        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

        var delCancelBtn = document.getElementById('sr-modal-cr-del-cancel');
        if (delCancelBtn) delCancelBtn.addEventListener('click', closeModal);

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal();
        });
    }

    function initPrzelicznikCzasuModal() {
        var overlay = document.getElementById('sr-modal-overlay-przelicznik');
        var modal   = document.getElementById('sr-modal-przelicznik');

        if (!overlay || !modal) return;

        var form     = document.getElementById('sr-modal-przel-form');
        var delForm  = document.getElementById('sr-modal-przel-del-form');
        var titleEl  = document.getElementById('sr-modal-przel-title');
        var idInput  = document.getElementById('sr-modal-przel-id');
        var dlugoscInput = document.getElementById('sr-modal-przel-dlugosc');
        var mnoznikInput = document.getElementById('sr-modal-przel-mnoznik');
        var delIdInput   = document.getElementById('sr-modal-przel-del-id');

        var btnAdd   = document.querySelector('.sr-btn-add-przelicznik');

        function openModal(mode, row) {
            overlay.style.display = 'block';
            modal.style.display   = 'block';

            if (mode === 'add') {
                titleEl.textContent = 'Dodaj przelicznik';
                if (idInput)      idInput.value      = '0';
                if (dlugoscInput) dlugoscInput.value = '';
                if (mnoznikInput) mnoznikInput.value = '';

                if (form) form.style.display = 'block';
                if (delForm) delForm.style.display = 'none';
                return;
            }

            if (mode === 'edit' && row) {
                titleEl.textContent = 'Edytuj przelicznik';

                if (idInput)      idInput.value      = row.dataset.id || '0';
                if (dlugoscInput) dlugoscInput.value = row.dataset.dlugosc || '';
                if (mnoznikInput) mnoznikInput.value = row.dataset.mnoznik || '';

                if (form)    form.style.display    = 'block';
                if (delForm) delForm.style.display = 'none';
                return;
            }

            if (mode === 'delete' && row) {
                if (delIdInput) delIdInput.value = row.dataset.id || '0';
                if (form)    form.style.display    = 'none';
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

        var editButtons = document.querySelectorAll('.sr-btn-edit-przelicznik');
        editButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var row = btn.closest('tr');
                if (!row) return;
                openModal('edit', row);
            });
        });

        var deleteButtons = document.querySelectorAll('.sr-btn-del-przelicznik');
        deleteButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var row = btn.closest('tr');
                if (!row) return;
                openModal('delete', row);
            });
        });

        var cancelBtn = document.getElementById('sr-modal-przel-cancel');
        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

        var delCancelBtn = document.getElementById('sr-modal-przel-del-cancel');
        if (delCancelBtn) delCancelBtn.addEventListener('click', closeModal);

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal();
        });
    }

    function initPrzedmiotModal() {
        var overlay = document.getElementById('sr-modal-overlay-przedmiot');
        var modal   = document.getElementById('sr-modal-przedmiot');

        if (!overlay || !modal) return;

        var form     = document.getElementById('sr-modal-przedmiot-form');
        var delForm  = document.getElementById('sr-modal-przedmiot-del-form');
        var titleEl  = document.getElementById('sr-modal-przedmiot-title');
        var idInput  = document.getElementById('sr-modal-przedmiot-id');
        var nazwaInput = document.getElementById('sr-modal-przedmiot-nazwa');
        var aktywnaCb  = document.getElementById('sr-modal-przedmiot-aktywna');
        var delIdInput = document.getElementById('sr-modal-przedmiot-del-id');

        var btnAdd   = document.querySelector('.sr-btn-add-przedmiot');

        function openModal(mode, row) {
            overlay.style.display = 'block';
            modal.style.display   = 'block';

            if (mode === 'add') {
                titleEl.textContent = 'Dodaj przedmiot działalności';
                if (idInput)      idInput.value      = '0';
                if (nazwaInput)   nazwaInput.value   = '';
                if (aktywnaCb)    aktywnaCb.checked  = true;

                if (form)    form.style.display    = 'block';
                if (delForm) delForm.style.display = 'none';
                return;
            }

            if (mode === 'edit' && row) {
                titleEl.textContent = 'Edytuj przedmiot działalności';

                if (idInput)      idInput.value      = row.dataset.id || '0';
                if (nazwaInput)   nazwaInput.value   = row.dataset.nazwa || '';
                if (aktywnaCb)    aktywnaCb.checked  = (row.dataset.aktywna === '1');

                if (form)    form.style.display    = 'block';
                if (delForm) delForm.style.display = 'none';
                return;
            }

            if (mode === 'delete' && row) {
                if (delIdInput) delIdInput.value = row.dataset.id || '0';
                if (form)    form.style.display    = 'none';
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

        var editButtons = document.querySelectorAll('.sr-btn-edit-przedmiot');
        editButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var row = btn.closest('tr');
                if (!row) return;
                openModal('edit', row);
            });
        });

        var deleteButtons = document.querySelectorAll('.sr-btn-del-przedmiot');
        deleteButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var row = btn.closest('tr');
                if (!row) return;
                openModal('delete', row);
            });
        });

        var cancelBtn = document.getElementById('sr-modal-przedmiot-cancel');
        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

        var delCancelBtn = document.getElementById('sr-modal-przedmiot-del-cancel');
        if (delCancelBtn) delCancelBtn.addEventListener('click', closeModal);

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal();
        });
    }

    function initSettings() {
        initToasts();
        initCennikRadioModal();
        initPrzelicznikCzasuModal();
        initPrzedmiotModal();
    }

    document.addEventListener('DOMContentLoaded', initSettings);
    window.addEventListener('load', initSettings);
})();