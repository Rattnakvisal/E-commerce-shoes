## Quickstart

1. Clone the repo:
   git clone https://github.com/yourname/E-commerce-shoes.git
2. Import DB schema:
   - Use `database/table.sql` to create tables and sample data.
3. Configure:
   - Copy `config/google.local.php` from `config/google.php` and set credentials if using OAuth.
   - Edit `config/conn.php` with your MySQL credentials.
4. Serve:
   - Place project in your local webroot (XAMPP: `c:\xampp\htdocs\E-commerce-shoes`) and visit `http://localhost/E-commerce-shoes/`.

## Requirements

- PHP 8.0+
- PDO extension (pdo_mysql)
- MySQL 5.7+ / MariaDB
- Composer (optional for dev tooling)
- Node.js + npm (optional for building assets)
- Tailwind CLI (if customizing CSS)

## Environment & Configuration

- config/conn.php — database connection (PDO). Enable exceptions and set proper charset (utf8mb4).
- config/google.php / config/google.local.php — Google OAuth configuration (optional).
- For production:
  - Disable PHP error display.
  - Set cookie options to secure and use HTTPS.
  - Store secrets outside webroot or use environment variables.

## Installation (detailed)

1. Ensure MySQL is running.
2. Import schema:
   mysql -u root -p your_db_name < database/table.sql
3. Update `config/conn.php`:
   - DSN, username, password, options (ERRMODE_EXCEPTION).
4. Seed admin user (if not included in SQL) via INSERT into `admins` table or use included seed script.

## Running Locally (Windows / XAMPP)

- Copy project to `c:\xampp\htdocs\E-commerce-shoes`.
- Start Apache & MySQL via XAMPP Control Panel.
- Open browser: `http://localhost/E-commerce-shoes/`.
- Admin panel: `http://localhost/E-commerce-shoes/admin/` (login route depends on project routes).

## Default Admin Credentials (example)

If sample SQL includes a default admin, update these after first login:

- Email: admin@example.com
- Password: password123

(Always change default passwords.)

## Project Structure (expanded)

- admin/ — admin dashboard, auth, process handlers
- admin/process/* — controllers for analytics, orders, products
- config/ — database and external service configs
- database/table.sql — schema + seed data
- public/ — assets served to clients (css, js, images)
- helpers/ — auth, csrf, response utilities

## Database Notes

- payments table drives revenue calculations.
- orders table stores order lifecycle states: pending, paid, shipped, completed.
- payment_methods table holds gateway config and activation flags.

## Testing & Development

- Enable error reporting during development:
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
- Create unit/integration tests using PHPUnit (not included). Scaffold tests under `tests/`.
- Use sample data in `database/table.sql` to validate analytics and dashboard charts.

## Deployment Checklist

- Disable error display and set proper log handling.
- Use HTTPS and secure cookies (secure => true).
- Use environment variables or a secrets manager for DB and OAuth credentials.
- Regularly back up DB.
- Run DB migrations and seeders in CI/CD pipeline.

## API / Endpoints

- admin/process/analytics/analyties_api.php?gateway={code}&limit={n}
  - Returns JSON of transactions filtered by gateway and limit.
- Review admin/process/* for other API-like endpoints (orders, products).

## Security Considerations

- All DB access uses prepared statements (PDO) — keep this across new code.
- CSRF tokens required for POST requests via helpers/csrf.php.
- Regenerate session ID on login and use HttpOnly cookies.

## Troubleshooting

- Blank page: enable PHP errors and check Apache/PHP logs.
- DB connection errors: verify `config/conn.php` DSN and credentials.
- Chart.js not loading: ensure public/js assets are included and browser console has no errors.

## Contributing

- Fork -> feature branch -> PR
- Follow PSR-12 and include tests where applicable
- Keep commits atomic and documented

## License

This project is recommended to use the MIT License. Add `LICENSE` file with MIT contents.

## Credits

- Built with plain PHP (PDO), Tailwind CSS, Chart.js
- Inspired by common e-commerce patterns and learning resources
