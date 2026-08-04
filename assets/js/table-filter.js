// Instant client-side filtering for tables that don't need a server round trip.
// Usage: <input data-table-filter="#some-table"> filters the rows of #some-table
// by matching typed text against each row's visible text.
document.addEventListener('input', function (event) {
    var input = event.target;
    if (!input.matches('[data-table-filter]')) {
        return;
    }

    var table = document.querySelector(input.getAttribute('data-table-filter'));
    if (!table) {
        return;
    }

    var query = input.value.trim().toLowerCase();
    var rows = table.querySelectorAll('tbody tr');

    rows.forEach(function (row) {
        var text = row.textContent.toLowerCase();
        row.style.display = text.indexOf(query) === -1 ? 'none' : '';
    });
});
