// Confirms before following any delete link.
document.addEventListener('click', function (event) {
    var link = event.target.closest('.js-confirm-delete');
    if (!link) {
        return;
    }

    var message = link.getAttribute('data-message') || 'Are you sure you want to delete this?';
    if (!window.confirm(message)) {
        event.preventDefault();
    }
});
