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
