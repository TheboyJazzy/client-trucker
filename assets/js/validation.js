// Lightweight required-field validation for forms marked novalidate.
// Server-side validation is still the source of truth; this just gives
// faster feedback before the page round-trips.
document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form.matches('form[novalidate]')) {
        return;
    }

    var valid = true;
    var fields = form.querySelectorAll('[required]');

    fields.forEach(function (field) {
        var group = field.closest('.form-group');
        var existingError = group ? group.querySelector('.js-field-error') : null;

        if (field.value.trim() === '') {
            valid = false;
            if (group && !existingError) {
                var message = document.createElement('div');
                message.className = 'field-error js-field-error';
                message.textContent = 'This field is required.';
                group.appendChild(message);
            }
            field.classList.add('input-invalid');
        } else {
            if (existingError) {
                existingError.remove();
            }
            field.classList.remove('input-invalid');
        }
    });

    if (!valid) {
        event.preventDefault();
    }
});
