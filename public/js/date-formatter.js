/**
 * US date inputs: visible MM-DD-YYYY, values submitted as YYYY-MM-DD (hidden field).
 */
(function () {
    function pad2(n) {
        return String(n).padStart(2, '0');
    }

    window.dateFormatter = {
        format: function (dateValue, separator) {
            separator = separator || '-';
            if (!dateValue) return '';

            let date;
            if (typeof dateValue === 'string') {
                if (/^\d{4}-\d{2}-\d{2}$/.test(dateValue)) {
                    date = new Date(dateValue + 'T00:00:00');
                } else if (/^(\d{1,2})[/-](\d{1,2})[/-](\d{4})$/.test(dateValue)) {
                    const m = dateValue.match(/^(\d{1,2})[/-](\d{1,2})[/-](\d{4})$/);
                    date = new Date(parseInt(m[3], 10), parseInt(m[1], 10) - 1, parseInt(m[2], 10));
                } else {
                    date = new Date(dateValue);
                }
            } else if (dateValue instanceof Date) {
                date = dateValue;
            } else {
                date = new Date(dateValue);
            }

            if (isNaN(date.getTime())) return '';

            return pad2(date.getMonth() + 1) + separator + pad2(date.getDate()) + separator + date.getFullYear();
        },

        toISO: function (dateValue) {
            if (!dateValue) return '';

            if (/^\d{4}-\d{2}-\d{2}$/.test(dateValue)) return dateValue;

            const m = String(dateValue).match(/^(\d{1,2})[/-](\d{1,2})[/-](\d{4})$/);
            if (m) {
                const month = pad2(parseInt(m[1], 10));
                const day = pad2(parseInt(m[2], 10));
                const year = m[3];
                const iso = year + '-' + month + '-' + day;
                const d = new Date(iso + 'T00:00:00');
                return isNaN(d.getTime()) ? '' : iso;
            }

            const d = new Date(dateValue);
            if (isNaN(d.getTime())) return '';
            return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
        },

        today: function () {
            return this.format(new Date());
        },

        validate: function (dateValue) {
            if (!dateValue) return false;
            const iso = this.toISO(dateValue);
            if (!iso) return false;
            const d = new Date(iso + 'T00:00:00');
            return !isNaN(d.getTime()) && iso === (d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate()));
        },

        displayFormat: function (dateValue) {
            return this.format(dateValue, '-');
        },
    };

    function showDateError(input, message) {
        var existingError = input.parentNode.querySelector('.date-error');
        if (existingError) existingError.remove();

        var errorDiv = document.createElement('div');
        errorDiv.className = 'date-error text-danger small mt-1';
        errorDiv.textContent = message;
        input.parentNode.insertBefore(errorDiv, input.nextSibling);
    }

    function applyMinMax(hiddenInput, visibleInput) {
        var minIso = visibleInput.getAttribute('data-date-min');
        var maxIso = visibleInput.getAttribute('data-date-max');
        var iso = hiddenInput.value;
        if (!iso) return;
        if (minIso && iso < minIso) {
            hiddenInput.value = minIso;
            visibleInput.value = window.dateFormatter.displayFormat(minIso);
        }
        if (maxIso && iso > maxIso) {
            hiddenInput.value = maxIso;
            visibleInput.value = window.dateFormatter.displayFormat(maxIso);
        }
    }

    function syncHiddenFromVisible(visibleInput, hiddenInput) {
        var raw = visibleInput.value.trim();
        if (!raw) {
            hiddenInput.value = '';
            return;
        }
        var iso = window.dateFormatter.toISO(raw);
        hiddenInput.value = iso || '';
        applyMinMax(hiddenInput, visibleInput);
    }

    /** Typing mask for MM-DD-YYYY */
    function attachUsDateMask(visibleInput) {
        visibleInput.addEventListener('input', function (e) {
            var digits = e.target.value.replace(/\D/g, '').slice(0, 8);
            var formatted = digits;
            if (digits.length >= 2) formatted = digits.slice(0, 2) + '-' + digits.slice(2);
            if (digits.length >= 4) formatted = digits.slice(0, 2) + '-' + digits.slice(2, 4) + '-' + digits.slice(4);
            e.target.value = formatted;
        });
    }

    function attachVisibleHandlers(visibleInput, hiddenInput) {
        attachUsDateMask(visibleInput);

        visibleInput.addEventListener('blur', function () {
            syncHiddenFromVisible(visibleInput, hiddenInput);
            if (visibleInput.value && !window.dateFormatter.validate(visibleInput.value)) {
                visibleInput.classList.add('date-invalid');
                showDateError(visibleInput, 'Please enter a valid date in MM-DD-YYYY format');
            } else {
                visibleInput.classList.remove('date-invalid');
                var err = visibleInput.parentNode.querySelector('.date-error');
                if (err) err.remove();
            }
        });

        visibleInput.addEventListener('focus', function (e) {
            e.target.classList.remove('date-invalid');
            var err = e.target.parentNode.querySelector('.date-error');
            if (err) err.remove();
        });

        visibleInput.addEventListener('change', function () {
            syncHiddenFromVisible(visibleInput, hiddenInput);
        });
    }

    function upgradeHtml5DateInput(input) {
        if (input.classList.contains('no-us-date')) return;
        if (input.getAttribute('data-us-date-upgraded') === '1') return;

        var name = input.getAttribute('name');
        var min = input.getAttribute('min');
        var max = input.getAttribute('max');
        var required = input.required;
        var initial = input.value || '';

        input.setAttribute('data-us-date-upgraded', '1');

        if (name) {
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = name;
            hidden.value = initial && /^\d{4}-\d{2}-\d{2}$/.test(initial) ? initial : (initial ? window.dateFormatter.toISO(initial) : '');
            hidden.setAttribute('tabindex', '-1');
            input.parentNode.insertBefore(hidden, input);

            input.removeAttribute('name');
            input.type = 'text';
            input.classList.add('us-date-visible');
            input.setAttribute('placeholder', 'MM-DD-YYYY');
            input.setAttribute('maxlength', '10');
            input.setAttribute('autocomplete', 'off');
            if (min) {
                input.setAttribute('data-date-min', min);
                input.removeAttribute('min');
            }
            if (max) {
                input.setAttribute('data-date-max', max);
                input.removeAttribute('max');
            }
            input.required = required;

            input.value = hidden.value ? window.dateFormatter.displayFormat(hidden.value) : '';

            attachVisibleHandlers(input, hidden);

            input.addEventListener('invalid', function (e) {
                e.preventDefault();
            });
        } else {
            input.type = 'text';
            input.classList.add('us-date-visible');
            input.setAttribute('placeholder', 'MM-DD-YYYY');
            input.setAttribute('maxlength', '10');
            input.setAttribute('autocomplete', 'off');
            if (min) {
                input.setAttribute('data-date-min', min);
                input.removeAttribute('min');
            }
            if (max) {
                input.setAttribute('data-date-max', max);
                input.removeAttribute('max');
            }
            input.value = initial ? window.dateFormatter.displayFormat(initial) : '';
            attachUsDateMask(input);
            input.addEventListener('blur', function () {
                if (input.value && !window.dateFormatter.validate(input.value)) {
                    input.classList.add('date-invalid');
                    showDateError(input, 'Please enter a valid date in MM-DD-YYYY format');
                } else {
                    input.classList.remove('date-invalid');
                    var err = input.parentNode.querySelector('.date-error');
                    if (err) err.remove();
                }
                var minIso = input.getAttribute('data-date-min');
                var maxIso = input.getAttribute('data-date-max');
                var iso = window.dateFormatter.toISO(input.value);
                if (iso && minIso && iso < minIso) input.value = window.dateFormatter.displayFormat(minIso);
                if (iso && maxIso && iso > maxIso) input.value = window.dateFormatter.displayFormat(maxIso);
            });
        }
    }

    function initPlainDateInputs() {
        document.querySelectorAll('input.date-input:not([data-date-formatted])').forEach(function (input) {
            if (input.type === 'hidden' || input.closest('.no-us-date-wrap')) return;
            input.setAttribute('data-date-formatted', 'true');
            input.setAttribute('placeholder', 'MM-DD-YYYY');
            input.setAttribute('maxlength', '10');
            attachUsDateMask(input);

            if (input.value) {
                var iso = window.dateFormatter.toISO(input.value);
                if (iso) input.value = window.dateFormatter.displayFormat(iso);
            }

            input.addEventListener('blur', function () {
                if (input.value && !window.dateFormatter.validate(input.value)) {
                    input.classList.add('date-invalid');
                    showDateError(input, 'Please enter a valid date in MM-DD-YYYY format');
                } else {
                    input.classList.remove('date-invalid');
                    var err = input.parentNode.querySelector('.date-error');
                    if (err) err.remove();
                }
            });
        });
    }

    function initDateFormatting() {
        document.querySelectorAll('input[type="date"]').forEach(function (el) {
            upgradeHtml5DateInput(el);
        });
        initPlainDateInputs();
        document.dispatchEvent(new CustomEvent('usDateInputsReady'));
    }

    function formatDateDisplays() {
        ['.date-display', '.created-at', '.updated-at', '.report-date'].forEach(function (selector) {
            document.querySelectorAll(selector).forEach(function (element) {
                var dateValue = element.textContent || element.getAttribute('data-date');
                if (dateValue) {
                    var formatted = window.dateFormatter.displayFormat(dateValue);
                    if (formatted) element.textContent = formatted;
                }
            });
        });
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || !form.querySelectorAll) return;
        form.querySelectorAll('.us-date-visible').forEach(function (vis) {
            var hid = vis.previousElementSibling;
            if (hid && hid.tagName === 'INPUT' && hid.type === 'hidden' && hid.name) {
                syncHiddenFromVisible(vis, hid);
            }
        });
    }, true);

    document.addEventListener('DOMContentLoaded', function () {
        initDateFormatting();
        formatDateDisplays();

        var moTimer;
        var observer = new MutationObserver(function () {
            clearTimeout(moTimer);
            moTimer = setTimeout(function () {
                initDateFormatting();
                formatDateDisplays();
            }, 50);
        });
        observer.observe(document.body, { childList: true, subtree: true });
    });

    window.formatAllDates = function () {
        initDateFormatting();
        formatDateDisplays();
    };
})();
