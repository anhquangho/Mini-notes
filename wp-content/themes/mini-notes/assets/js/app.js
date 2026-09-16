// STEP 6/11/21: progressive enhancement. Forms and navigation also work without JavaScript.
(() => {
  const menu = document.querySelector('.menu-toggle');
  const sidebar = document.querySelector('#sidebar');
  function closeMenu() {
    document.body.classList.remove('menu-open');
    menu?.setAttribute('aria-expanded', 'false');
  }
  menu?.addEventListener('click', () => {
    const open = document.body.classList.toggle('menu-open');
    menu.setAttribute('aria-expanded', String(open));
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') closeMenu();
  });
  document.addEventListener('click', event => {
    if (document.body.classList.contains('menu-open') && !sidebar?.contains(event.target) && !menu?.contains(event.target)) closeMenu();
  });

  const title = document.querySelector('#note-title');
  function fitTitle() {
    if (!title) return;
    title.style.height = 'auto';
    title.style.height = title.scrollHeight + 'px';
  }
  title?.addEventListener('input', fitTitle);
  window.addEventListener('resize', fitTitle);
  fitTitle();

  const form = document.querySelector('#note-editor');
  let dirty = false;
  form?.addEventListener('input', () => {
    dirty = true;
    const state = document.querySelector('.save-state');
    if (state) state.textContent = 'Unsaved changes';
  });
  form?.addEventListener('submit', () => { dirty = false; });
  window.addEventListener('beforeunload', event => {
    if (dirty) { event.preventDefault(); event.returnValue = ''; }
  });
  document.addEventListener('keydown', event => {
    if (form && (event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
      event.preventDefault();
      form.requestSubmit();
    }
  });
  const deleteForm = document.querySelector('.delete-form');
  const confirmPanel = deleteForm?.querySelector('.delete-confirmation');
  const deleteButton = deleteForm?.querySelector('[type="submit"]');
  let confirmed = false;
  deleteForm?.addEventListener('submit', event => {
    if (!confirmed) {
      event.preventDefault();
      confirmed = true;
      confirmPanel.hidden = false;
      deleteButton.textContent = 'Confirm move to Trash';
      deleteButton.focus();
    } else {
      dirty = false;
    }
  });
  deleteForm?.querySelector('.cancel-delete')?.addEventListener('click', () => {
    confirmed = false;
    confirmPanel.hidden = true;
    deleteButton.textContent = 'Move to Trash';
    deleteButton.focus();
  });
})();
