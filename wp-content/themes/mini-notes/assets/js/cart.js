(() => {
  const form = document.querySelector('.woocommerce-cart-form');
  if (!form || !window.mnCart) return;

  let timer = 0;
  let running = false;
  let again = false;

  function quantities() {
    const data = {};
    form.querySelectorAll('input.qty').forEach((input) => {
      const match = input.name.match(/^cart\[([^\]]+)\]\[qty\]$/);
      if (!match || input.value === '' || Number.isNaN(Number(input.value))) return;
      data[match[1]] = input.value;
    });
    return data;
  }

  function showNotice(message) {
    const slot = document.querySelector('.woocommerce-notices-wrapper');
    if (!slot) return;
    const list = document.createElement('ul');
    list.className = 'woocommerce-error';
    list.setAttribute('role', 'alert');
    const item = document.createElement('li');
    item.textContent = message;
    list.append(item);
    slot.replaceChildren(list);
  }

  function apply(data) {
    const slot = document.querySelector('.woocommerce-notices-wrapper');
    if (slot) slot.innerHTML = data.notices || '';
    if (data.empty) {
      const root = document.querySelector('.woocommerce');
      if (root) root.innerHTML = (data.notices || '') + (data.emptyHtml || '');
      return;
    }
    Object.entries(data.items || {}).forEach(([key, item]) => {
      const input = form.querySelector(`input.qty[name="cart[${CSS.escape(key)}][qty]"]`);
      const row = input?.closest('tr');
      if (!row) return;
      if (item.removed) {
        row.remove();
        return;
      }
      input.value = String(item.quantity);
      const cell = row.querySelector('.product-subtotal');
      if (cell) cell.innerHTML = item.subtotal;
    });
    if (!data.totals) return;
    const current = document.querySelector('.cart_totals');
    const holder = document.createElement('div');
    holder.innerHTML = data.totals.trim();
    const next = holder.querySelector('.cart_totals');
    if (current && next) current.replaceWith(next);
  }

  async function send() {
    if (running) {
      again = true;
      return;
    }
    running = true;
    form.classList.add('mn-cart-updating');
    form.setAttribute('aria-busy', 'true');
    const body = new FormData();
    body.append('action', 'mn_update_cart');
    body.append('nonce', mnCart.nonce);
    Object.entries(quantities()).forEach(([key, qty]) => body.append(`quantities[${key}]`, qty));
    try {
      const response = await fetch(mnCart.ajaxUrl, { method: 'POST', body, credentials: 'same-origin' });
      const payload = await response.json();
      if (!payload.success) throw new Error(payload.data?.message || 'Could not update the cart.');
      apply(payload.data);
    } catch (error) {
      showNotice(error instanceof Error && error.message ? error.message : 'Could not update the cart. Please try again.');
    } finally {
      running = false;
      form.classList.remove('mn-cart-updating');
      form.removeAttribute('aria-busy');
      if (again) {
        again = false;
        send();
      }
    }
  }

  function schedule() {
    window.clearTimeout(timer);
    timer = window.setTimeout(send, 350);
  }

  form.addEventListener('input', (event) => {
    if (event.target.matches('input.qty')) schedule();
  });
  form.addEventListener('change', (event) => {
    if (event.target.matches('input.qty')) schedule();
  });
  form.addEventListener('submit', (event) => {
    const submitter = event.submitter;
    if (submitter && submitter.name && submitter.name !== 'update_cart') return;
    event.preventDefault();
    window.clearTimeout(timer);
    send();
  });
})();
