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
- **Practical 13 (JSON API + AJAX CRUD):** Build a PHP `api/students.php` endpoint, connect it to `students.php` using `fetch()`, and implement add/edit/delete/search operations without reloading the page.
- **P14:** Admin dashboard at `admin.php`, with recent audit log.
- **P15:** Test all pages and complete the checklist below.

## Practical 13: PHP JSON API and AJAX CRUD (Student Management)

### Objective
Use PHP and MySQL to create a JSON API for student records and connect it to a front-end page using the JavaScript Fetch API. Students can be added, searched, edited, and deleted without reloading the page.

### Theory
A JSON API is a server endpoint that returns data in JSON format. JavaScript can call that API asynchronously using `fetch()`. This is used in modern web applications because it gives faster user experience and cleaner page updates. In this practical, the browser sends requests to `api/students.php`, and the PHP file reads JSON, validates data, executes prepared SQL statements, and returns JSON success or error messages.

### Tools and technologies
- PHP 8+
- MySQL / MariaDB
- XAMPP Apache + MySQL
- HTML, CSS, JavaScript
- PDO database connection
- Fetch API and JSON

### File descriptions
- `api/students.php` — JSON endpoint for GET, POST, PUT, and DELETE requests.
- `students.php` — admin page with the student form, search box, and table.
- `assets/js/app.js` — JavaScript logic for Fetch calls, validation, and UI updates.
- `config/db.php` — MySQL connection file.
- `database/studenthub.sql` — project database with the `students` table.

### Setup instructions for XAMPP
1. Start **Apache** and **MySQL** in XAMPP.
2. Import `database/studenthub.sql` in phpMyAdmin.
3. Make sure your database is `studenthub` and the user is `root` with empty password.
4. Open the project in the XAMPP `htdocs` folder.
5. Log in as the admin account and open the student management page.

### How to run and test the practical
1. Open `http://localhost/StudentHub/students.php` in the browser.
2. Log in as admin (`admin@studenthub.test` / `Admin@123`).
3. The page loads students using a GET request to the API.
4. Add a new student using the form.
5. Edit a row and save the changes.
6. Use the search box to filter records.
7. Delete a record after confirmation.
8. Open browser DevTools → Network to inspect the JSON requests.

### API methods and sample JSON responses

#### GET /api/students.php?q=rohan
```json
{
  "success": true,
  "students": [
    {
      "id": 5,
      "full_name": "Rohan Desai",
      "email": "rohan@studenthub.test",
      "course": "CE",
      "semester": 3,
      "status": "active"
    }
  ]
}
```

#### POST /api/students.php
Request body:
```json
{
  "csrf_token": "abc123",
  "full_name": "Aisha Mehta",
  "email": "aisha@studenthub.test",
  "course": "AIML",
  "semester": 2
}
```

Response:
```json
{
  "success": true,
  "message": "Student added successfully."
}
```

#### PUT /api/students.php
```json
{
  "csrf_token": "abc123",
  "id": 9,
  "full_name": "Aisha Sharma",
  "email": "aisha.sharma@studenthub.test",
  "course": "CSE",
  "semester": 3
}
```

#### DELETE /api/students.php
```json
{
  "csrf_token": "abc123",
  "id": 9
}
```

### Expected output
The student table updates instantly after add, edit, delete, and search actions. There are no page reloads, and JSON responses can be seen in the browser’s Network tab.

### Learning outcomes
- Understand the PHP JSON API structure.
- Use `fetch()` to call server-side APIs.
- Validate input on the server.
- Use `PDO` prepared statements.
- Apply CSRF protection on state-changing actions.
- Build a beginner-friendly AJAX CRUD system.

### Viva questions with simple answers
1. What is JSON?  
   JSON is a lightweight format used to send data between browser and server.

2. What does `fetch()` do?  
   It sends HTTP requests to a server and returns a response.

3. Why do we use prepared statements?  
   To protect against SQL injection and handle data safely.

4. Why do we validate input on the server?  
   Because browser validation can be bypassed and server-side validation is required.

5. Why is CSRF protection important?  
   It prevents attackers from sending fake requests using a logged-in user session.

6. What is AJAX?  
   AJAX means asynchronous JavaScript and XML. In modern projects, it usually means sending requests without reloading the page.

7. What is the purpose of HTTP status codes like 201, 404, and 500?  
   They explain whether the request succeeded, failed, or had a server issue.

8. Why do we use `Content-Type: application/json`?  
   So the browser and server both understand that the response is JSON data.

9. What is the difference between GET and POST requests?  
   GET is used to read data; POST is used to create data.

10. Why is search debounced?  
   Debouncing reduces unnecessary requests while the user is typing.

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


## Password recovery (local XAMPP)

- The forgot-password and reset-password pages create `password_resets` if it is missing. The SQL dump also includes this table.
- Reset links are built from the current script folder, so the project works from either `/StudentHub/` or `/Other/StudentHub/`.
- This project stores password hashes in `users.password_hash`; `reset_password.php` updates that column using `password_hash()`.
- For local testing, the reset link is displayed on screen. Do not display reset tokens this way in a live deployment; send them through a verified email workflow instead.
- Reset tokens expire after 15 minutes and are single-use.
