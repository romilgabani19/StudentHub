(() => {
  const menuButton = document.querySelector('.menu-toggle');
  const nav = document.querySelector('#primary-nav');
  if (menuButton && nav) {
    menuButton.addEventListener('click', () => {
      const open = nav.classList.toggle('open');
      menuButton.setAttribute('aria-expanded', String(open));
    });
  }

  const themeButton = document.querySelector('#theme-toggle');
  const savedTheme = localStorage.getItem('studenthub-theme');
  if (savedTheme === 'dark') document.body.classList.add('dark');
  if (themeButton) {
    themeButton.addEventListener('click', () => {
      document.body.classList.toggle('dark');
      localStorage.setItem('studenthub-theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    });
  }

  document.querySelectorAll('[data-faq-toggle]').forEach(button => {
    button.addEventListener('click', () => {
      const answer = document.getElementById(button.getAttribute('aria-controls'));
      const expanded = button.getAttribute('aria-expanded') === 'true';
      button.setAttribute('aria-expanded', String(!expanded));
      if (answer) answer.hidden = expanded;
    });
  });

  const banner = document.querySelector('[data-notice]');
  const closeNotice = document.querySelector('[data-close-notice]');
  if (banner && closeNotice) closeNotice.addEventListener('click', () => banner.remove());

  const password = document.querySelector('#password');
  const strength = document.querySelector('#password-strength');
  if (password && strength) {
    password.addEventListener('input', () => {
      const value = password.value;
      let score = 0;
      if (value.length >= 8) score++;
      if (/[A-Z]/.test(value)) score++;
      if (/[a-z]/.test(value)) score++;
      if (/\d/.test(value)) score++;
      if (/[^A-Za-z0-9]/.test(value)) score++;
      strength.textContent = value ? `Password strength: ${['very weak', 'weak', 'fair', 'good', 'strong', 'very strong'][score]}` : '';
    });
  }
})();

(() => {
  const studentForm = document.getElementById('student-form');
  if (!studentForm) {
    return;
  }

  const tableBody = document.getElementById('students-table-body');
  const alertBox = document.getElementById('student-alert');
  const searchInput = document.getElementById('student-search');
  const formTitle = document.getElementById('form-title');
  const resetButton = document.getElementById('cancel-edit');
  const submitButton = document.getElementById('submit-student');
  const studentIdInput = document.getElementById('student-id');
  const csrfToken = studentForm.querySelector('input[name="csrf_token"]')?.value || '';
  let searchTimer = null;

  function escapeMessage(value) {
    return String(value).replace(/[&<>"']/g, function (char) {
      return ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      })[char];
    });
  }

  function showAlert(type, message) {
    if (!alertBox) {
      return;
    }

    alertBox.className = 'student-alert visible ' + type;
    alertBox.textContent = message;
  }

  function resetForm() {
    studentForm.reset();
    studentIdInput.value = '';
    formTitle.textContent = 'Add a student';
    submitButton.textContent = 'Add student';
    if (resetButton) {
      resetButton.classList.add('hidden');
    }
  }

  function makeRowCell(value, tagName = 'td') {
    const cell = document.createElement(tagName);
    cell.textContent = value;
    return cell;
  }

  function renderStudents(students) {
    if (!tableBody) {
      return;
    }

    tableBody.replaceChildren();

    if (!students.length) {
      const row = document.createElement('tr');
      const cell = document.createElement('td');
      cell.colSpan = 6;
      cell.textContent = 'No students found.';
      cell.className = 'empty-row';
      row.appendChild(cell);
      tableBody.appendChild(row);
      return;
    }

    students.forEach((student) => {
      const row = document.createElement('tr');
      row.appendChild(makeRowCell(student.full_name || ''));
      row.appendChild(makeRowCell(student.email || ''));
      row.appendChild(makeRowCell(student.course || ''));
      row.appendChild(makeRowCell(String(student.semester || '')));

      const statusCell = document.createElement('td');
      const statusTag = document.createElement('span');
      statusTag.className = 'table-status';
      statusTag.textContent = student.status || 'active';
      statusCell.appendChild(statusTag);
      row.appendChild(statusCell);

      const actionCell = document.createElement('td');
      const actionWrap = document.createElement('div');
      actionWrap.className = 'table-actions';

      const editButton = document.createElement('button');
      editButton.type = 'button';
      editButton.textContent = 'Edit';
      editButton.addEventListener('click', () => {
        studentIdInput.value = String(student.id);
        document.getElementById('full_name').value = student.full_name || '';
        document.getElementById('email').value = student.email || '';
        document.getElementById('course').value = student.course || '';
        document.getElementById('semester').value = String(student.semester || '');
        formTitle.textContent = 'Edit student';
        submitButton.textContent = 'Save changes';
        if (resetButton) {
          resetButton.classList.remove('hidden');
        }
        studentForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });

      const deleteButton = document.createElement('button');
      deleteButton.type = 'button';
      deleteButton.className = 'btn-danger';
      deleteButton.textContent = 'Delete';
      deleteButton.addEventListener('click', async () => {
        if (!window.confirm('Delete this student record?')) {
          return;
        }

        try {
          const response = await fetch('api/students.php', {
            method: 'DELETE',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-Token': csrfToken
            },
            credentials: 'same-origin',
            body: JSON.stringify({
              id: Number(student.id),
              csrf_token: csrfToken
            })
          });

          const result = await response.json().catch(() => ({}));
          if (!response.ok) {
            throw new Error(result.error || 'Unable to delete student.');
          }

          showAlert('success', result.message || 'Student deleted successfully.');
          loadStudents();
        } catch (error) {
          showAlert('error', error.message || 'Delete request failed.');
        }
      });

      actionWrap.appendChild(editButton);
      actionWrap.appendChild(deleteButton);
      actionCell.appendChild(actionWrap);
      row.appendChild(actionCell);
      tableBody.appendChild(row);
    });
  }

  async function loadStudents() {
    const query = (searchInput ? searchInput.value.trim() : '');

    try {
      const response = await fetch(`api/students.php?q=${encodeURIComponent(query)}`, {
        headers: {
          'Accept': 'application/json'
        },
        credentials: 'same-origin'
      });

      const result = await response.json().catch(() => ({}));

      if (!response.ok) {
        throw new Error(result.error || 'Unable to load students.');
      }

      renderStudents(Array.isArray(result.students) ? result.students : []);
    } catch (error) {
      showAlert('error', error.message || 'Unable to load students.');
      renderStudents([]);
    }
  }

  studentForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    const formData = new FormData(studentForm);
    const id = Number(studentIdInput.value || 0);
    const payload = {
      csrf_token: csrfToken,
      full_name: String(formData.get('full_name') || '').trim(),
      email: String(formData.get('email') || '').trim(),
      course: String(formData.get('course') || '').trim(),
      semester: String(formData.get('semester') || '').trim(),
      ...(id > 0 ? { id } : {})
    };

    if (!payload.full_name || !payload.email || !payload.course || !payload.semester) {
      showAlert('error', 'Please fill in all required student fields.');
      return;
    }

    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(payload.email)) {
      showAlert('error', 'Please enter a valid email format.');
      return;
    }

    const numericSemester = Number(payload.semester);
    if (!Number.isInteger(numericSemester) || numericSemester < 1 || numericSemester > 8) {
      showAlert('error', 'Please choose a valid semester between 1 and 8.');
      return;
    }

    payload.semester = numericSemester;

    try {
      const response = await fetch('api/students.php', {
        method: id > 0 ? 'PUT' : 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-Token': csrfToken
        },
        credentials: 'same-origin',
        body: JSON.stringify(payload)
      });

      const result = await response.json().catch(() => ({}));

      if (!response.ok) {
        throw new Error(result.error || 'Unable to save student.');
      }

      showAlert('success', result.message || 'Student saved successfully.');
      resetForm();
      await loadStudents();
    } catch (error) {
      showAlert('error', error.message || 'Something went wrong.');
    }
  });

  if (resetButton) {
    resetButton.addEventListener('click', () => {
      resetForm();
    });
  }

  if (searchInput) {
    searchInput.addEventListener('input', () => {
      window.clearTimeout(searchTimer);
      searchTimer = window.setTimeout(() => {
        loadStudents();
      }, 300);
    });
  }

  resetForm();
  loadStudents();
})();

