// assets/js/sr-kontrahenci.js
// JS dla widoku "Kontrahenci" w panelu /panel-reklamy
// - walidacja formularza kontrahenta
// - maski NIP / kod pocztowy
// - backendowe błędy (scroll do boxa)
// - toast "zapisano"
// - lookup NIP → GUS (REST /wp-json/sr/v1/nip-lookup)

(function () {
    'use strict';

    function initKontrahentForm() {
        // Szukamy formularza KONTRAHENTA (sr_action=save_kontrahent)
        var actionInput = document.querySelector(
            'form[method="post"] input[name="sr_action"][value="save_kontrahent"]'
        );
        var form = actionInput ? actionInput.form : null;

        // Auto-scroll do błędów backendowych
        var backendErrorsBox = document.getElementById('sr-errors-box');
        if (backendErrorsBox) {
            window.scrollTo({ top: backendErrorsBox.offsetTop - 80, behavior: 'smooth' });
        }

        // Toast „zapisano”
        var toast = document.getElementById('sr-toast');
        if (toast) {
            setTimeout(function () {
                toast.classList.add('sr-toast--visible');
            }, 100);

            setTimeout(function () {
                toast.classList.remove('sr-toast--visible');
                setTimeout(function () {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 400);
            }, 4100);
        }

        // Jeżeli nie jesteśmy na widoku formularza kontrahenta – nic dalej nie robimy
        if (!form) {
            return;
        }

        var nazwaInput    = form.querySelector('input[name="nazwa"]');
        var nipInput      = form.querySelector('input[name="nip"]');
        var kodInput      = form.querySelector('input[name="kod"]');
        var miastoInput   = form.querySelector('input[name="miasto"]');
        var przedmiotInput = form.querySelector('select[name="przedmiot_dzialalnosci"]');
        var adresTextarea = form.querySelector('textarea[name="adres"]');

        // =====================
        // WALIDACJA POL (JS)
        // =====================
        function validateField(fieldName, value) {
            value = value.trim();

            switch (fieldName) {
                case 'nazwa':
                    if (value === '') return 'Nazwa firmy jest wymagana.';
                    return null;

                case 'nip':
                    if (value === '') return null;
                    var digits = value.replace(/\D/g, '');
                    if (!/^[0-9]{10}$/.test(digits)) return 'NIP musi mieć 10 cyfr.';
                    return null;

                case 'kod':
                    if (value === '') return null;
                    if (!/^[0-9]{2}-[0-9]{3}$/.test(value)) return 'Kod pocztowy musi być w formacie 00-000.';
                    return null;

                case 'miasto':
                    if (value === '') return null;
                    if (!/^[\p{L}\s\-]+$/u.test(value)) return 'Miasto zawiera niedozwolone znaki.';
                    return null;

                default:
                    return null;
            }
        }

        function markField(input, errorText) {
            if (!input) return;
            input.classList.remove('sr-input-error', 'sr-input-ok');
            if (errorText) {
                input.classList.add('sr-input-error');
            } else if (input.value.trim() !== '') {
                input.classList.add('sr-input-ok');
            }
        }

        // Maski: NIP (same cyfry, max 10)
        if (nipInput) {
            nipInput.addEventListener('input', function (e) {
                var v = e.target.value.replace(/\D/g, '');
                if (v.length > 10) v = v.slice(0, 10);
                e.target.value = v;
            });
        }

        // Maski: kod pocztowy 00-000
        if (kodInput) {
            kodInput.addEventListener('input', function (e) {
                var v = e.target.value.replace(/\D/g, '');
                if (v.length > 5) v = v.slice(0, 5);
                if (v.length >= 3) {
                    v = v.slice(0, 2) + '-' + v.slice(2);
                }
                e.target.value = v;
            });
        }

        if (nazwaInput) {
            nazwaInput.addEventListener('blur', function (e) {
                var err = validateField('nazwa', e.target.value);
                markField(e.target, err);
            });
        }

        if (miastoInput) {
            miastoInput.addEventListener('blur', function (e) {
                var err = validateField('miasto', e.target.value);
                markField(e.target, err);
            });
        }

        if (kodInput) {
            kodInput.addEventListener('blur', function (e) {
                var err = validateField('kod', e.target.value);
                markField(e.target, err);
            });
        }

        // Walidacja całości przy submit
        form.addEventListener('submit', function (e) {
            var errors = [];

            var nazwa  = nazwaInput ? nazwaInput.value : '';
            var nip    = nipInput ? nipInput.value : '';
            var kod    = kodInput ? kodInput.value : '';
            var miasto = miastoInput ? miastoInput.value : '';

            var nazwaErr  = validateField('nazwa', nazwa);
            var nipErr    = validateField('nip', nip);
            var kodErr    = validateField('kod', kod);
            var miastoErr = validateField('miasto', miasto);

            // Zaznaczamy pola
            markField(nazwaInput, nazwaErr);
            markField(nipInput, nipErr);
            markField(kodInput, kodErr);
            markField(miastoInput, miastoErr);

            [nazwaErr, nipErr, kodErr, miastoErr].forEach(function (err) {
                if (err) errors.push(err);
            });

            if (errors.length > 0) {
                e.preventDefault();
                var box = document.getElementById('sr-errors-box');
                if (!box) {
                    box = document.createElement('div');
                    box.id = 'sr-errors-box';
                    box.style.cssText =
                        'margin-top:12px;padding:10px 12px;border-radius:8px;' +
                        'background:#FEF2F2;color:#B91C1C;font-size:13px;';
                    form.parentNode.insertBefore(box, form);
                }
                box.innerHTML = errors.map(function (msg) {
                    return '• ' + msg;
                }).join('<br>');
                window.scrollTo({ top: box.offsetTop - 80, behavior: 'smooth' });
            }
        });

        // =====================
        // NIP LOOKUP – GUS (GusApi)
        // =====================
        function srLookupNip(nipDigits) {
            var url = '/wp-json/sr/v1/nip-lookup?nip=' + encodeURIComponent(nipDigits);

            fetch(url, { credentials: 'same-origin' })
                .then(function (resp) {
                    return resp.json().catch(function () {
                        return {
                            success: false,
                            message: 'Błąd połączenia z GUS (HTTP ' + resp.status + ').'
                        };
                    });
                })
                .then(function (data) {
                    if (!data) return;

                    // Błąd → czerwony toast
                    if (!data.success) {
                        var t = document.createElement('div');
                        t.className = 'sr-toast sr-toast--success sr-toast--visible';
                        t.style.background = '#DC2626';
                        t.style.color      = '#F9FAFB';
                        t.textContent      = data.message || 'Nie udało się pobrać danych z GUS.';
                        document.body.appendChild(t);
                        setTimeout(function () {
                            t.classList.remove('sr-toast--visible');
                            setTimeout(function () { t.remove(); }, 400);
                        }, 4000);
                        return;
                    }

                    // Sukces – uzupełniamy TYLKO puste pola
                    if (data.nazwa && nazwaInput && nazwaInput.value.trim() === '') {
                        nazwaInput.value = data.nazwa;
                    }
                    if (data.adres && adresTextarea && adresTextarea.value.trim() === '') {
                        adresTextarea.value = data.adres;
                    }
                    if (data.kod && kodInput && kodInput.value.trim() === '') {
                        kodInput.value = data.kod;
                    }
                    if (data.miasto && miastoInput && miastoInput.value.trim() === '') {
                        miastoInput.value = data.miasto;
                    }
                    if (data.przedmiot_dzialalnosci && przedmiotInput && przedmiotInput.value === '') {
                        przedmiotInput.value = data.przedmiot_dzialalnosci;
                    }

                    var t2 = document.createElement('div');
                    t2.className = 'sr-toast sr-toast--success sr-toast--visible';
                    t2.textContent = 'Dane kontrahenta zostały pobrane z GUS.';
                    document.body.appendChild(t2);
                    setTimeout(function () {
                        t2.classList.remove('sr-toast--visible');
                        setTimeout(function () { t2.remove(); }, 400);
                    }, 4000);
                })
                .catch(function () {
                    // przy błędzie sieciowym – user uzupełni ręcznie
                });
        }

        // NIP – walidacja + lookup z GUS (input + blur)
        if (nipInput) {
            var lastLookupNip = '';

            // Reakcja na wpisywanie / wklejanie
            nipInput.addEventListener('input', function (e) {
                var v = e.target.value.replace(/\D/g, '');
                if (v.length > 10) v = v.slice(0, 10);
                e.target.value = v;

                if (v.length === 10) {
                    var err = validateField('nip', e.target.value);
                    markField(e.target, err);

                    if (!err && v !== lastLookupNip) {
                        lastLookupNip = v;
                        srLookupNip(v);
                    }
                }
            });

            // „Bezpieczny” blur – gdyby ktoś dopisał cyfrę i opuścił pole
            nipInput.addEventListener('blur', function (e) {
                var v = e.target.value;
                var err = validateField('nip', v);
                markField(e.target, err);

                var digits = v.replace(/\D/g, '');
                if (!err && digits.length === 10 && digits !== lastLookupNip) {
                    lastLookupNip = digits;
                    srLookupNip(digits);
                }
            });
        }
    }

    function initKontrahenci() {
        initKontrahentForm();
    }

    document.addEventListener('DOMContentLoaded', initKontrahenci);
    window.addEventListener('load', initKontrahenci);

})();