# StudentHub — Web Technology Practicals 2–15

A beginner-friendly student portal project built with HTML5, CSS3, JavaScript, PHP, and MySQL.

## Features
- Semantic static pages: Home, About, Register, Login, Dashboard, Events, Profile, Contact, Admin, FAQ, Feedback
- Responsive layout with CSS Grid/Flexbox and Bootstrap-like custom styling (no external framework required)
- JavaScript FAQ accordion, mobile navigation, theme switcher, search/filter, and Fetch API demo
- Client-side registration validation
- PHP server-side validation and JSON API endpoints
- MySQL schema with students, users, events, registrations, and audit_logs
- Secure password hashing, prepared statements, sessions, role-based access, session timeout
- Student CRUD and event CRUD (including basic validated poster upload)
- Admin dashboard and audit log
- CSV export and JSON event data

## Requirements
- XAMPP with PHP 8+ and MySQL/MariaDB
- A modern browser
- Optional: VS Code and Git

## Install with XAMPP
1. Copy the `StudentHub` folder into `C:\xampp\htdocs\` (Windows) or your XAMPP `htdocs` directory.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin`.
4. Create/import the database:
   - Select **Import**, choose `database/studenthub.sql`, then click **Import**.
   - Alternatively, paste the SQL file contents into the SQL tab.
5. Check `config/db.php`. Default local credentials are database `studenthub`, username `root`, empty password. Change them if your setup differs.
6. Visit `http://localhost/StudentHub/`.

## Demo login accounts
The SQL seed uses a known development password for both demo accounts:
- Admin: `admin@studenthub.test` / `Admin@123`
- Student: `student@studenthub.test` / `Student@123`
- My: `25AIML012@charusat.edu.in` / `Romil@2512`


If the seeded hashes fail on your PHP version, register a new account using the Register page and manually set its `role` to `admin` in phpMyAdmin for admin testing. Do not use these demo credentials on a public server.

## Practical walkthrough
- **P2:** Open the 11 static pages and inspect semantic landmarks, labels, and navigation.
- **P3:** Resize browser / use DevTools responsive mode.
- **P4:** Test menu, theme toggle, FAQ accordion, notification.
- **P5:** Submit invalid and valid registration data.
- **P6:** Open `events.html` for JSON search/filter; `api/events.php` returns JSON.
- **P7:** `contact.php` processes a contact form and appends to `storage/contacts.csv`.
- **P8:** Review `database/studenthub.sql` and `config/db.php`.
- **P9:** Register a new user; password is hashed before storage and duplicate email is rejected.
- **P10:** Login/logout, dashboard access checks, role redirects, and idle timeout.
- **P11:** Admin student CRUD at `students.php`.
- **P12:** Admin event CRUD at `admin_events.php`, including image upload.
- **P13:** JSON API at `api/events.php`; test `api-demo.html`.
- **P14:** Admin dashboard at `admin.php`, with recent audit log.
- **P15:** Test all pages and complete the checklist below.

## Important security notes
This is a teaching project, not a production-ready portal. Before public deployment, add HTTPS, CSRF protection to every state-changing form, rate limiting, robust logging, stricter upload isolation, and a production secret/configuration strategy. The demo accounts and default local database settings are for local learning only.

## Accessibility checklist
- [ ] Every page has a unique descriptive title.
- [ ] Skip-to-content link works.
- [ ] Heading levels follow a sensible hierarchy.
- [ ] Forms have visible labels and useful errors.
- [ ] Images have meaningful `alt` text (or empty alt for decoration).
- [ ] Navigation and controls can be used with keyboard.
- [ ] Text contrast and responsive layouts are checked.
- [ ] Test with browser DevTools and keyboard only.

## Suggested submission contents
Submit screenshots of Home, Register errors/success, Events search, Dashboard, Admin, CRUD, mobile/tablet/desktop views, plus this source folder, SQL dump, and a short test report.
