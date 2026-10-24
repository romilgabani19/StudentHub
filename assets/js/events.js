async function loadEvents() {
  const list = document.querySelector('#event-list');
  const status = document.querySelector('#event-status');
  const search = document.querySelector('#event-search');
  const category = document.querySelector('#event-category');
  if (!list || !status) return;
  let events = [];
  try {
    const response = await fetch('data/events.json');
    if (!response.ok) throw new Error('Could not load events data.');
    events = await response.json();
    const render = () => {
      const term = search.value.trim().toLowerCase();
      const chosen = category.value;
      const filtered = events.filter(event =>
        (event.title + ' ' + event.description + ' ' + event.category).toLowerCase().includes(term) &&
        (!chosen || event.category === chosen)
      ).sort((a, b) => a.date.localeCompare(b.date));
      list.replaceChildren();
      filtered.forEach(event => {
        const article = document.createElement('article');
        article.className = 'event-card';
        const date = document.createElement('p'); date.className = 'event-date'; date.textContent = new Date(event.date + 'T12:00:00').toLocaleDateString();
        const title = document.createElement('h2'); title.textContent = event.title;
        const tag = document.createElement('span'); tag.className = 'pill'; tag.textContent = event.category;
        const description = document.createElement('p'); description.textContent = event.description;
        article.append(date, title, tag, description); list.append(article);
      });
      status.textContent = `${filtered.length} event(s) found.`;
    };
    search.addEventListener('input', render);
    category.addEventListener('change', render);
    render();
  } catch (error) {
    status.textContent = 'Events could not be loaded. Run this project through XAMPP/Apache and check data/events.json.';
  }
}
document.addEventListener('DOMContentLoaded', loadEvents);
