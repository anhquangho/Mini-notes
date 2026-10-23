# Klaviyo signup and Welcome Flow

The footer form subscribes an email to one Klaviyo list. The welcome email is a Flow in Klaviyo, triggered when someone is added to that list.

## Form

The form is in `wp-content/themes/mini-notes/footer.php` and appears on every page. `assets/js/signup.js` posts the email to Klaviyo's client subscription endpoint:

`POST https://a.klaviyo.com/client/subscriptions/?company_id=PUBLIC_SITE_ID`

The request uses the public Company ID only. A `202` response means Klaviyo accepted the subscription. Do not put a private API key in the theme or in JavaScript.

Copy `klaviyo.example.json` to `klaviyo.local.json` in the same theme folder:

```json
{
  "companyId": "YOUR_PUBLIC_SITE_ID",
  "listId": "YOUR_LIST_ID"
}
```

`klaviyo.local.json` is gitignored. In Klaviyo, the public site ID is under Settings → API keys. The list ID is the short id on the list page.

Until both values are filled in, the form stays on the page and Submit says "Signup is not connected yet."

## List

1. In Klaviyo, open Audience → Lists & segments → Create list.
2. Name the list `Mini Notes Welcome`.
3. Set the list to single opt-in. A double opt-in list waits for a confirmation click, so the welcome email does not send at the moment of signup.

## Welcome Flow

1. Open Flows → Create flow → Build your own.
2. Trigger: Added to list → `Mini Notes Welcome`.
3. Add one email step. Do not add a time delay.
4. Subject: `Welcome to Mini Notes`.
5. Body: a short note that the signup came from the shop footer, plus a link back to the shop.
6. Turn the flow Live.

## Proof screenshots

Save both images next to this file:

- `docs/klaviyo/welcome-flow.png` — the flow canvas, trigger visible, status Live.
- `docs/klaviyo/welcome-profile.png` — the profile that subscribed from `Mini Notes footer`, or the welcome email in the inbox.

Subscribe with a real inbox from the site footer after `klaviyo.local.json` is filled in. The profile source should read `Mini Notes footer`.
