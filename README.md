# Client Project Tracker

Internal tool for tracking clients, projects, milestones, invoices, and payments.
Plain PHP (procedural, mysqli + prepared statements), MySQL, and vanilla JS. No frameworks.

## Setup (WAMP/XAMPP)

1. Copy this `client-tracker` folder into your server root:
   - WAMP: `C:\wamp64\www\client-tracker`
   - XAMPP: `C:\xampp\htdocs\client-tracker`
2. Import the schema: open phpMyAdmin (or run `mysql -u root < sql/schema.sql`). This creates the
   `client_tracker` database, all tables, and one default admin user.
3. Check `config/database.php` — defaults are `localhost` / `root` / no password / `client_tracker`,
   which matches a stock WAMP/XAMPP install. Update if your MySQL setup differs.
4. Visit `http://localhost/client-tracker/`. You'll be redirected to the login page.

**Default login:**
- Email: `admin@example.com`
- Password: `admin123`

Change this password (or add a proper "change password" flow) before using this for real client data.

## Notes

- `includes/functions.php` → `base_url()` assumes the app lives at `/client-tracker/`. If you rename
  the folder, update that one function.
- Invoice status (`unpaid` / `partially_paid` / `paid`) is recalculated automatically whenever a
  payment is added or removed — see `recalculate_invoice_status()` in `includes/functions.php`.
- "Overdue" isn't a status you set manually — any unpaid/partially paid invoice past its due date
  displays as overdue automatically (`invoice_display_status()`), without changing the stored status.
- Deleting a client with existing projects or invoices is blocked at the database level (foreign key
  `RESTRICT`) — remove those first.
