# Google Reviews Integration — Setup

> **Deploying to production?** Follow **`GOOGLE_REVIEWS_DEPLOYMENT.md`**
> — an ordered runbook covering environment variables, Google Cloud setup,
> API enablement, the database migration, cron, and go-live verification.
> This document explains *why* the feature behaves as it does; that one is
> the step-by-step *how*.

This feature uses **two separate Google credential types** for two different
purposes. Don't conflate them — they're restricted differently.

| | Used for | Auth type | Where configured |
|---|---|---|---|
| Business Profile API | Fetching/caching your real reviews (`ep_google_reviews`) | OAuth 2.0 (Client ID/Secret + refresh token) | `cms/google-reviews.php` (admin panel) |
| Places API | One-time admin lookup of your real Place ID; static "write a review" / "view on Maps" links | API key | Server's `.env` file only |

Google itself recommends OAuth over API keys for server-to-server calls
wherever a service supports it (per Google's API security best practices,
verified current 2026-08-11) — the Business Profile API supports OAuth, so
it's used there. The Places API's supported auth for this kind of one-off
lookup is a restricted API key, so that's what's used for it.

## 1. Places API key (`.env`)

1. Google Cloud Console → **APIs & Services → Credentials → Create API key**.
2. **Application restriction: IP addresses.** This is Google's documented
   recommendation for server-side-only keys (not HTTP referrer — that's for
   browser-side keys, and this key is never sent to the browser). Add your
   production server's outbound IP address.
   - On shared/cPanel hosting, find this under your host's control panel
     (sometimes listed as "Dedicated IP" or "Server IP"), or run
     `curl ifconfig.me` from an SSH session or cron job on that server —
     don't guess it from your local machine, which has a different IP.
   - If your host uses non-static outbound IPs (some shared/cloud setups
     do), Google's own docs acknowledge IP restriction "might be
     impractical" there — in that case, rely on the API restriction below
     alone, and rotate the key periodically as a mitigation.
3. **API restriction: limit the key to "Places API" only** — nothing else.
   Google's stated best practice is to always pair one application
   restriction with one or more API restrictions; don't leave a key
   unrestricted "to save time."
4. Copy the key into the server's `.env` file (never into `.env.example`,
   never into any PHP/JS file, never pasted into chat/tickets/Slack):
   ```
   GOOGLE_PLACES_API_KEY=your_real_key_here
   ```
5. Confirm `.env` isn't web-servable: `curl https://yourdomain/.env` must
   return 403 (enforced by the `.htaccess` dotfile block added alongside
   this feature — verify it survived any hosting-side `.htaccess` overrides
   after deploy).

The key is read server-side only, via `ep_env('GOOGLE_PLACES_API_KEY')` in
`includes/google-reviews.php`, exclusively from the admin-panel-triggered
Place ID search (`cms/google-reviews.php`) — never from a public page, and
never returned in any page's HTML/JS/API response.

## 2. Business Profile OAuth (admin panel)

1. Google Cloud Console → enable the **Business Profile APIs**, then request
   **Basic Access** (manual approval, ~10+ business days — do this early).
   Requires a Google Business Profile verified and active 60+ days.
2. Create an **OAuth 2.0 Client ID** (Web application type). Add this
   redirect URI (shown on the admin page): `https://yourdomain/cms/google-reviews.php?oauth_callback=1`
3. Enter the Client ID/Secret in `cms/google-reviews.php` (stored in
   `ep_site_settings`, gated behind the `reviews.manage` permission — this
   pair is not the secret STEP 3 was about, per your earlier confirmation
   to keep OAuth config in the existing settings architecture).
4. Click "Connect with Google", pick your business location, then either
   "Sync now" or wait for the cron job.

## 3. Cron

Twice a day satisfies the requirement and stays comfortably under Google's
30-day cache-retention cap (see the sync script's own header comment).

**First, check the server's actual timezone — don't assume.** cPanel's
crontab schedules against the *hosting server's* system clock, which is
frequently NOT the timezone of your visitors (e.g. shared hosting for a
Pakistan-based business is often physically in a US datacenter running
UTC or US time). Verify it before picking cron times, two ways:
1. cPanel → **Cron Jobs** page usually states the server's current time/
   timezone directly above the scheduling form.
2. Or via SSH, if available: `date` (shows the system's local time+zone)
   and, separately, `php -r "echo date_default_timezone_get();"` (shows
   what PHP itself will use for this project's own `date()` calls inside
   the sync script — e.g. for the 30-day purge cutoff — which is
   configured independently of the OS-level cron scheduler and can differ
   from it).

   Concretely: this dev machine's PHP is set to `Europe/Berlin`, which
   would be a wrong assumption for eduportal.pk's actual production
   server too — every environment needs its own check, never copy a
   value from elsewhere.

**Then schedule two runs roughly 12 hours apart, in the server's own
local time.** If the server's local time is already Pakistan Time (PKT,
UTC+5) or you don't care what wall-clock time the sync happens at (it's
invisible to visitors either way — reviews just refresh in the
background), use:
```
0 6 * * *   php /home/USER/public_html/eduportal/scripts/sync-google-reviews.php
0 18 * * *  php /home/USER/public_html/eduportal/scripts/sync-google-reviews.php
```
If you specifically want the fetches to land at 6 AM / 6 PM **Pakistan**
time regardless of the server's own timezone, convert using that
server's UTC offset from step 1 above (PKT is UTC+5, no DST) — e.g. a
server running in UTC would use `0 1 * * *` / `0 13 * * *` instead.

**Exact cPanel steps:** cPanel → Cron Jobs → Add New Cron Job → set the
minute/hour/day/month/weekday fields from whichever line above matches
your server's timezone → paste the full `php /home/.../sync-google-reviews.php`
command (with your actual home directory path) into the Command field.

## 4. Place ID (optional, for the review/Maps links)

Use the "Google Place ID" card in `cms/google-reviews.php` to search and
save your real Place ID (via the Places API key above), or paste a known
one directly. Not a secret — it's meant to be public and appears in the
"write a review" / "view on Maps" links on `/reviews.php`.

## 5. How many reviews are shown, and the one policy limitation

- **Homepage** shows at most **6** reviews, 4- and 5-star only, newest
  first. This is a deliberate presentation choice, not a technical cap.
- **`/reviews.php`** shows **every** cached review with `is_hidden = 0`,
  at every star rating, with no limit applied.
- The cron sync follows Google's `nextPageToken` pagination, so a location
  with more than 50 reviews is fetched in full (Google caps each request
  at `pageSize=50`). There is a 100-page safety bound (5,000 reviews) that
  exists only to stop a malformed/looping token sequence — it is not an
  intended display limit, and it logs loudly if ever reached.

**The one real limitation — Google's 30-day storage policy.** Google's
Business Profile APIs policies ("Content storage") state you "cannot
pre-fetch, cache, index, or store any content provided through the
Business Profile APIs" beyond limited amounts, and that stored content
"must be stored temporarily for no more than 30 calendar days." There is
no longer-retention carve-out for reviews.

So the site can only ever display reviews that Google has reconfirmed
within the last 30 days. Every sync run purges any cached review older
than that, whether or not that run's live fetch succeeded — see
`ep_google_purge_expired_reviews()`. In normal operation this is
invisible: with the cron running twice daily, every review is refreshed
hundreds of times inside each 30-day window. It only takes effect if
syncing is broken or disabled for over a month, at which point continuing
to display month-old unrefreshed content would breach the policy, so the
reviews are removed rather than served stale indefinitely.

The practical consequence: **`/reviews.php` shows all *eligible* saved
reviews, where "eligible" means visible (`is_hidden = 0`) and reconfirmed
by Google within 30 days.** If reviews ever disappear from the page, check
the sync status in the admin panel before assuming data loss.

## 6. Structured data (JSON-LD) — why there is no star rating markup

**Short version: we deliberately do NOT publish `aggregateRating` schema
built from the Google rating, and doing so would be against Google's
rules — not an oversight.**

The homepage and `/reviews.php` both display the real rating and review
count to human visitors. Neither page marks them up as
`aggregateRating` structured data. Google's review snippet guidelines
(developers.google.com/search/docs/appearance/structured-data/review-snippet,
verified 2026-08-19) block this on two independent counts:

1. **Third-party aggregation:** *"Don't aggregate reviews or ratings from
   other websites."* These ratings originate on Google, so they're
   third-party by definition. This rule applies to every schema type, so
   moving the markup onto `SoftwareApplication` or `Product` doesn't
   avoid it.
2. **Self-serving reviews:** *"If the entity that's being reviewed
   controls the reviews about itself, their pages that use
   `LocalBusiness` or any other type of `Organization` structured data
   are ineligible for star review feature."* The documentation
   illustrates this with the exact case here — *"a review about entity A
   is placed on the website of entity A ... (for example, Google Business
   reviews or Facebook reviews widget)"*.

Publishing it anyway would **not** produce star snippets (the page is
explicitly ineligible) and could attract a structured-data manual action
in Search Console, which can suppress rich results across the whole site.

What the pages do emit instead — accurate, compliant, and making no
rating claim:

- Homepage: `Organization`, `WebSite`, `SoftwareApplication`, `WebPage`,
  `BreadcrumbList`
- `/reviews.php`: `Organization`, plus `CollectionPage` and
  `BreadcrumbList`

If Google's policy changes, the values are already available via
`ep_google_reviews_summary()` (`google_rating` / `google_review_count`),
so adding the markup later is a small change — see the comment block
above `$jsonLdSchema` in `reviews.php`.

## 7. Behaviour when the database or table is unavailable

The review pages degrade quietly instead of breaking:

- **No reviews synced yet** — `/reviews.php` shows *"Reviews are being
  synced from Google. Please check back soon."* and the homepage simply
  omits its reviews section, exactly as it already does when there are no
  video testimonials. No placeholder or sample reviews are ever invented.
- **`ep_google_reviews` table missing** (most likely cause: this feature's
  `scripts/add-google-reviews.sql` was never run on production) — the read
  is caught, logged to the PHP error log, and the pages fall back to the
  same "no reviews yet" state. Before this was handled, both the homepage
  and `/reviews.php` returned HTTP 200 while printing an uncaught
  `mysqli_sql_exception` containing the database name, absolute server
  paths, the SQL statement and a stack trace.
- **Database entirely unreachable** — the request stops with HTTP 503,
  `Retry-After: 120`, and the single line *"Service temporarily
  unavailable. Please try again in a few minutes."* No file path, database
  name, engine or credential location appears in the response; the full
  diagnostic goes to the error log only.

> **Production hardening:** set `display_errors = Off` (with
> `log_errors = On`) in the production PHP configuration. The handling
> above means no PHP warning or SQL error currently reaches a visitor, but
> `display_errors = Off` is the backstop that keeps any *future* unhandled
> warning from leaking paths or queries. This dev environment runs with
> `display_errors = 1`, which is how the leaks above were found.

## 8. Reviewer author photos

Google returns a reviewer avatar URL (`reviewer.profilePhotoUrl` on the
Business Profile API, `authorAttribution.photoUri` on Places). Both are
direct links — neither requires a second "photo media" request the way
Places *place* photos do, so no extra API call is involved.

How this feature treats them:

- **The image is never downloaded or copied onto this server.** Only the
  URL is stored, and it is hot-linked in the page. Google's policies bar
  pre-fetching, caching or storing API content beyond the limited
  allowance, so keeping a permanent local copy of reviewer avatars would
  not be permitted. The stored URL is purged along with the rest of the
  review row at the 30-day cap, like any other cached field.
- **Attribution is satisfied:** the avatar is always shown next to the
  reviewer's name, which is what the policy requires ("You must always
  credit the author when displaying photos or reviews").
- **HTTPS is enforced** on save (`ep_google_safe_photo_url()`). Anything
  that is not a valid `https://` URL — plain http, protocol-relative,
  `javascript:`, malformed, or longer than the 500-character column — is
  stored as `NULL` rather than kept. A plain-http URL would be blocked as
  mixed content on the live site anyway.
- **Missing or broken photos degrade gracefully.** No URL renders a
  coloured initial-letter avatar. If a URL is present but fails to load —
  an expired Google avatar link, or a visitor whose network blocks
  `googleusercontent.com` — an `onerror` handler swaps in that same
  initial-letter avatar instead of leaving a broken image icon.
- `referrerpolicy="no-referrer"` is set so visiting the site does not leak
  the page URL to Google with each avatar request.

## 9. No test or demo reviews — verifying before go-live

**No fabricated reviews exist anywhere in this feature.** The migration
creates the review tables empty and inserts no rows; nothing in the PHP,
JavaScript, CSS or SQL contains sample reviewer names or review text. The
only reviews that can ever appear are ones the sync pulled from Google.
Until the first successful sync the site shows its "reviews will appear
here" fallback rather than placeholder content.

Test data used during development was written directly to the database and
removed afterwards; none of it was ever committed to a file.

**To confirm a server is clean**, run:

```
php /home/USER/public_html/eduportal/scripts/verify-google-reviews-data.php
```

It prints the current state and exits `0` when clean or `1` when it finds
anything suspect, so it can gate a deploy. It works by checking that every
row's `google_review_id` is a real Google resource name of the form
`accounts/{accountId}/locations/{locationId}/reviews/{reviewId}` — the
identifier the sync stores verbatim. Rows added by hand (demo seeds, test
fixtures, anything typed into phpMyAdmin) essentially never match that
shape, so they are caught without having to judge the review text. It also
flags reviews existing when no sync has ever completed, since those cannot
have come from Google.

## 10. "Preview live from Google" (optional, admin-only)

Once a Place ID and Places API key are both set, the same card gets a
"Preview live from Google" button — fetches the place's current rating,
review count, and up to 5 reviews (with author name/photo, star rating,
text, date, and a link to each review on Google Maps) straight from
Places API (New), for comparing against the cached reviews on the site.
Nothing this shows is saved anywhere; it's a live, on-demand call only.
Each click bills at the Enterprise + Atmosphere SKU (~$40/1000 requests
per Google's current pricing), so use it to spot-check, not routinely.
