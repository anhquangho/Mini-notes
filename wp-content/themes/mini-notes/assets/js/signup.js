(() => {
  const form = document.querySelector('.footer-signup');
  const config = window.mnSignup;
  if (!form || !config) return;
  const input = form.querySelector('#mn-signup-email');
  const button = form.querySelector('button');
  const status = form.querySelector('.footer-signup-status');

  let sending = false;

  function show(message, isError) {
    status.textContent = message;
    status.classList.toggle('is-error', isError);
  }

  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (sending) return;
    const email = input.value.trim();
    if (!config.companyId || !config.listId) {
      show('Signup is not connected yet.', true);
      return;
    }
    sending = true;
    button.disabled = true;
    show('Joining the list…', false);
    try {
      const response = await fetch('https://a.klaviyo.com/client/subscriptions/?company_id=' + encodeURIComponent(config.companyId), {
        method: 'POST',
        headers: {
          accept: 'application/vnd.api+json',
          'content-type': 'application/vnd.api+json',
          revision: config.revision
        },
        body: JSON.stringify({
          data: {
            type: 'subscription',
            attributes: {
              custom_source: config.source,
              profile: {
                data: {
                  type: 'profile',
                  attributes: {
                    email,
                    subscriptions: { email: { marketing: { consent: 'SUBSCRIBED' } } }
                  }
                }
              }
            },
            relationships: { list: { data: { type: 'list', id: config.listId } } }
          }
        })
      });
      if (response.status !== 202) throw new Error('rejected');
      form.reset();
      show('You are on the list. Watch for a welcome note.', false);
    } catch (error) {
      show('Could not join the list. Try again.', true);
    } finally {
      sending = false;
      button.disabled = false;
    }
  });
})();
