# Google Reviews — Deployment Runbook

Ordered checklist for taking this feature live on production
(cPanel / `eduportal.pk`). For *why* things work the way they do —
Google's policies, caching limits, structured-data decisions — see
`GOOGLE_REVIEWS_SETUP.md`. This file is the "what to do" version.

> **No credential values appear in this document.** Every key, secret and
> token below is shown as a placeholder. Fill them in on the server only.

---

## 0. Start this first — it has a 10+ working-day lead time

Reviews are read through the **Google Business Profile API**, and Google
does not grant access automatically. A new Google Cloud project starts
with **zero quota** and needs a one-off, manually reviewed access request.

1. Create (or pick) a Google Cloud project.
2. Submit the Business Profile API access request form, tied to that
   project number.
3. Wait for approval — typically 10+ business days.

Prerequisite: the Google Business Profile for EduPortal must be
**verified and active for 60+ days**. Everything else below can be
prepared while you wait, but no reviews will sync until this is granted.

---

## 1. Enable the required APIs

In Google Cloud Console → **APIs & Services → Library**, enable:

| API | Used for |
|---|---|
| **Google My Business API** | Reading reviews (`mybusiness.googleapis.com/v4`) — the one gated behind the access request above |
| **My Business Account Management API** | Listing your accounts during setup |
| **My Business Business Information API** | Listing your business locations during setup |
| **Places API (New)** | *Optional* — Place ID lookup and the admin's live preview |

Enabling an API is **not** the same as being granted quota; step 0 is what
actually unlocks review reads.

---

## 2. Environment variables (`.env`)

Two variables, both in `.env` at the project root. `.env.example` is the
committed template and must stay empty.

```
GOOGLE_PLACES_API_KEY=your_places_api_key_here
GOOGLE_PLACE_ID=your_place_id_here
```

| Variable | Required? | Notes |
|---|---|---|
| `GOOGLE_PLACES_API_KEY` | Optional | Only for the Place ID search and admin live preview. Reviews sync without it. **Secret.** |
| `GOOGLE_PLACE_ID` | Optional | Powers the "View us on Google" / "Write a Google Review" links. Not secret. Can instead be set in the admin panel. |

The OAuth Client ID, Client Secret, refresh token and access token are
**not** environment variables — they live in `ep_site_settings` and are
entered through the admin panel (step 6).

---

## 3. `.env` security requirements

- **Never commit `.env`.** `.gitignore` already lists it. Only
  `.env.example`, with empty values, belongs in version control.
- **Never put a real key in `.env.example`.**
- File permissions: `chmod 600 .env` (owner-only) where the host allows.
- **Verify it is not web-readable after deploying:**
  ```
  curl -I https://eduportal.pk/.env      # expect 403
  ```
  Root `.htaccess` blocks dotfiles, and `includes/.htaccess` +
  `scripts/.htaccess` block those directories. If your host overwrites
  `.htaccess` on deploy, re-check this.
- Restrict the Places API key in Google Cloud Console:
  - **Application restriction → IP addresses**, set to the production
    server's outbound IP (Google's documented recommendation for
    server-side keys; *not* HTTP referrer, which is for browser keys).
    Find the IP with `curl ifconfig.me` **on the server**, not locally.
  - **API restriction → Places API only.**
- If a key is ever pasted into chat, a ticket or a screenshot, rotate it.

---

## 4. Database migration

Run once, via phpMyAdmin (or `mysql` CLI), against the production
database:

```
scripts/add-google-reviews.sql
```

**Prerequisite:** `ep_site_settings` must already exist — the script adds
rows to it but does not create it. That table is core to the site and
present on any live EduPortal database, so this only bites if you run the
script against a genuinely empty database, where it creates the two review
tables and then stops at the settings insert.

It creates `ep_google_reviews` (with the two measured indexes),
`ep_google_review_moderation`, and the `google_reviews` settings rows —
**all empty**. It inserts no review data.

> `ep_google_reviews` is dropped and recreated if it already exists.
> `ep_google_review_moderation` is **not** — it uses
> `CREATE TABLE IF NOT EXISTS` because it holds your hide/show decisions,
> which must survive re-runs.

Sanity check afterwards:
```sql
SELECT COUNT(*) FROM ep_google_reviews;              -- 0
SELECT COUNT(*) FROM ep_google_review_moderation;    -- 0
```

---

## 5. Place ID configuration

Optional — needed only for the "View us on Google" / "Write a Google
Review" buttons. Either:

- **`.env`:** set `GOOGLE_PLACE_ID=...` (takes priority), or
- **Admin panel:** *Google Reviews → Google Place ID* — with a Places API
  key set you can search for the business and pick the correct result, or
  paste a known ID directly.

Never invent or guess a Place ID. With none configured, the buttons simply
do not render.

---

## 6. Connect the admin panel to Google

Admin Panel → **Settings → Google Reviews** (permission: `reviews.manage`).

1. In Google Cloud Console → **Credentials → Create OAuth client ID →
   Web application**, add this **authorised redirect URI** exactly:
   ```
   https://eduportal.pk/cms/google-reviews.php?oauth_callback=1
   ```
2. Paste the **Client ID** and **Client Secret** into the admin page and
   save. The secret is write-only in the UI — leaving it blank on a later
   save keeps the stored value.
3. Click **Connect with Google** and complete consent.
4. Choose your business location from the list the page loads.
5. Click **Refresh Now** for the first sync.

---

## 7. Cron

Twice daily, per requirement. **Check the server's timezone first** — the
cPanel Cron Jobs page shows it; it is often not your local time.

```
0 6 * * *   php /home/USER/public_html/eduportal/scripts/sync-google-reviews.php
0 18 * * *  php /home/USER/public_html/eduportal/scripts/sync-google-reviews.php
```

Replace `/home/USER/public_html/eduportal` with the real path. Exit code
`0` = success, `1` = failure, so cron mail / monitoring can alert on it.

Do **not** schedule less often than every 30 days: Google's storage policy
caps cached reviews at 30 days and the sync purges anything older, so a
long outage empties the section. Twice daily leaves enormous margin.

---

## 8. Admin refresh process

**Refresh Now** (Sync status card) runs the exact same service as cron —
no duplicated API logic.

- Requires `reviews.manage`, a valid CSRF token, and a connected account.
- Rate-limited to **one manual refresh per 60 seconds**, server-side, to
  protect your API quota. The button shows a live countdown.
- On success: *"Refreshed from Google — N review(s) processed, M new."*
- On failure: an error naming the cause, plus *"Previously saved reviews
  are still being shown."* Existing reviews, rating and count are never
  cleared by a failed refresh.

Hide/show per review is on the same page. Hiding never deletes or edits a
review, and the decision survives both re-syncs and the 30-day purge.

---

## 9. Verify the deployment

```
php /home/USER/public_html/eduportal/scripts/verify-google-reviews-data.php
```

Exits `0` when clean, `1` if it finds reviews that don't carry a genuine
Google resource identifier (i.e. hand-inserted demo data). Run it after
deploying and again after the first sync.

Then check by eye:

- [ ] `curl -I https://eduportal.pk/.env` → 403
- [ ] `/reviews` loads; before the first sync it shows the "reviews will
      appear here" fallback, not placeholder content
- [ ] Homepage reviews section appears only once 4/5-star reviews exist
- [ ] Admin *Sync status* shows a recent successful sync
- [ ] Rating and review count on `/reviews` match the Google listing
- [ ] Cron ran: check the PHP error log for
      `[EduPortal] Google reviews fetch completed successfully.`

---

## Troubleshooting

| Symptom | Likely cause |
|---|---|
| Sync error "Not connected to Google" | Never completed OAuth, or the refresh token was revoked — reconnect in the admin panel |
| Sync error mentioning quota | Access request not yet granted, or rate limit hit — cron retries next run |
| `invalid_credentials` | Client ID/Secret wrong, or consent revoked |
| Reviews vanished after a long outage | 30-day retention purge; restore by fixing the sync — reviews return on the next successful run |
| Google profile buttons missing | No Place ID configured (step 5) |
| Reviews page shows the fallback despite a successful sync | The location has no reviews, or all are hidden in the admin panel |
