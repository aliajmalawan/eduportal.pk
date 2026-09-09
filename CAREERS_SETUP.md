# Careers, Job Listings & Applications — Setup

Task 7. Public careers pages, an admin job/application manager, and secure
résumé handling, built on the existing project architecture (`$m` / MysqliDb,
the `ep_site_settings` helpers, the CMS auth + permission system, and the
`includes/head.php` / `includes/header.php` / `includes/footer.php` page shell).

Careers sits **immediately after Contact** in the navbar (desktop and mobile)
and in the footer's Company column. `/careers` and every active job URL are in
`sitemap.xml`; Draft, Closed and past-deadline jobs are excluded automatically,
because the sitemap reuses the same query the public listing uses.

## Verifying a deployment

After deploying, run Google's [Rich Results Test](https://search.google.com/test/rich-results)
against a live job URL (`https://eduportal.pk/careers/{slug}`) — it needs a
publicly reachable page, so it cannot be run against localhost. The page should
report a valid **JobPosting**. Then submit `sitemap.xml` in Search Console so
the roles get discovered.

A job only qualifies while its `validThrough` is in the future, so a posting
whose deadline has passed will correctly show as ineligible.

## Schema

Two tables, `ep_jobs` and `ep_job_applications` — the Task 7 column names with
the project-wide `ep_` prefix that every other table uses.

**ep_jobs** — `id`, `title`, `slug` (unique, auto from title), `department`,
`job_type` (Full Time / Part Time / Contract / Internship), `work_mode`
(Onsite / Remote / Hybrid), `location`, `salary_min`, `salary_max`,
`experience`, `description`, `requirements`, `responsibilities`, `benefits`
(the last three stored as **JSON arrays** of bullet points), `vacancies`,
`deadline`, `status` (Draft / Active / Closed), `posted_at`.

Plus: `summary`, `skills`, `is_featured`, `sort_order`, `meta_title`,
`meta_description`, `apply_email`, `created_at`, `updated_at`.

**ep_job_applications** — `id`, `job_id`, `full_name`, `email`, `whatsapp`,
`city`, `photo`, `resume`, `years_experience`, `relevant_experience`,
`expected_salary`, `joining_time`, `linkedin`, `status`, `admin_notes`,
`applied_at`.

Plus: `job_title_snapshot` (so applications survive a job being deleted —
`job_id` is `ON DELETE SET NULL`), `resume_original_name` / `resume_mime` /
`resume_size` (correct download filename and Content-Type), `cover_letter`,
`source_page`, `ip_address` (**required by the rate limiter**), `user_agent`,
`updated_at`.

`job_id` is nullable: `NULL` marks a general CV sent when no role was open.

## 1. Database

Run once on production via phpMyAdmin:

```
scripts/add-careers.sql
```

It creates both tables and seeds the careers settings into the existing
`ep_site_settings` table.

The feature has never been live, so the script drops and recreates the tables
rather than altering them. `ep_ensure_careers_schema()` does the same for a
table left over from a pre-specification build — but **only when both tables
are empty**; if any rows exist it leaves them alone and logs a notice instead,
so real applications can never be discarded.

Not strictly required: `ep_ensure_careers_schema()` runs from
`cms/includes/bootstrap.php`, so the tables and settings are also created
automatically the first time an admin opens the CMS — the same belt-and-braces
approach the existing permissions and visitor tables use. Running the SQL is
still the recommended path for production, so the schema exists before the
first login.

## 2. Upload directory

`uploads/careers/` (with `cv/` and `photos/` subfolders) is created
automatically, together with a hardened `.htaccess` that:

- denies all direct HTTP access (`Require all denied`), and
- independently strips every script handler (`RemoveHandler` / `RemoveType` /
  `AddType text/plain` / `Options -ExecCGI`), so nothing in there can execute
  even if the deny rule were bypassed.

Confirm after deploying that `https://eduportal.pk/uploads/careers/cv/` returns
403. Résumés and photos are reachable only through `cms/application-file.php`,
which requires an authenticated admin holding `applications.manage`.

## 3. Clean URLs

`.htaccess` at the site root maps:

| Public URL | Script |
|---|---|
| `/careers` | `careers.php` |
| `/careers/{slug}` | `career.php?slug={slug}` |
| `/sitemap.xml` | `sitemap.php` |

Requires `mod_rewrite` (already in use on cPanel/LiteSpeed and XAMPP).

## 4. Permissions

Two new keys, assignable per user on **Permissions**:

- `careers.manage` — Manage Jobs
- `applications.manage` — Job Applications, résumé/photo download, Excel export

Super admins get both automatically. They are **off** by default for the
`editor` role, since applications contain personal data.

## 5. Settings

**Settings → Careers Page**:

| Key | Purpose |
|---|---|
| `careers_intro_heading` | Headline on `/careers` |
| `careers_intro_text` | Intro paragraph |
| `careers_no_jobs_text` | Shown when no roles are open (above the general CV form) |
| `careers_notify_email` | Gets an email for each new application |
| `careers_hr_whatsapp` | Optional HR WhatsApp number shown to applicants |

A job can override the notification address with its own **Notification
email**. If neither is set, the existing `support_email` setting is used; if
that is empty too, no email is sent (the application is still saved).

## 6. How a job goes live

1. **Careers → Manage Jobs → Add job**.
2. Fill in the title (everything else is optional), set **Status = Active**.
3. It appears immediately on `/careers`, at `/careers/{slug}`, in the sitemap,
   and with Google `JobPosting` structured data on its detail page (so the role
   is eligible for the free Google Jobs listing).

   `validThrough` is emitted as `YYYY-MM-DDT23:59:59` rather than a bare date,
   so a job stays valid for the whole of its deadline day instead of expiring
   at midnight as it begins. Both forms are valid ISO 8601 for Google.
4. Set a **Deadline** to have the job drop off the site automatically on that
   date — no need to remember to close it.
5. **Duplicate** copies a job as a draft with a fresh slug, for reposting an
   old role without retyping it.

**Status** can also be changed straight from the jobs list — the dropdown in
the Status column saves as soon as you pick a value (a "Set" button appears
instead if JavaScript is off).

**Responsibilities, Requirements and Benefits** use a bullet-point builder, not
a textarea: type a point, press **Add** (or Enter), and it appears below as a
row with a delete button. The collected rows post as `requirements[]` etc. and
are saved with `json_encode()`. Deleting every row clears the field.

Salary is shown as a monthly range; the currency follows the existing
`office_country` setting (PK → PKR). Leave both salary fields blank to hide it
from the page and from the structured data.

## 7. Applications

**Careers → Applications** lists everything received, filterable by position,
status, date range, and free-text search, plus a "General CVs only" toggle for
spontaneous applications (those have no `job_id`).

The list columns are: photo thumbnail, name (with a `https://wa.me/…`
click-to-chat link on the number), job applied for, experience, expected
salary, joining time, date, and a status dropdown that saves inline. Clicking
anywhere on a row opens the full details.

Statuses are **New / Shortlisted / Interviewed / Rejected / Hired**.

The detail view adds: city, LinkedIn, relevant experience, cover letter,
résumé download or in-browser preview, the applicant photo, and an internal
notes box that is never shown to the applicant.

**Export to Excel** downloads the currently filtered rows as UTF-8 CSV with a
BOM — the format Excel opens natively with correct characters. The project has
no spreadsheet library (there is no Composer/vendor directory), so this avoids
adding a dependency. Cells beginning with `=`, `+`, `-` or `@` are prefixed
with `'` so applicant-supplied text can never execute as a formula.

Deleting an application also deletes its résumé and photo from disk.

## 8. The application form

Fields, in order, exactly as specified:

| # | Field | Required |
|---|---|---|
| 1 | Full Name | yes |
| 2 | Email | yes |
| 3 | WhatsApp Number | yes |
| 4 | City | yes |
| 5 | Profile Photo | yes |
| 6 | Resume / CV | yes |
| 7 | Years of Experience | yes |
| 8 | Relevant Experience | yes |
| 9 | Expected Salary (PKR) | yes |
| 10 | How soon can you join | yes — dropdown |
| 11 | LinkedIn Profile | no |
| 12 | Consent checkbox | yes |

The joining-time dropdown accepts only **Immediately / Within 15 days / 1 month
/ 2 months / 3 months**; anything else is rejected server-side, not just hidden
in the UI. Ticking the consent box records `consent_at` on the row, so the
agreement has a timestamp if it is ever questioned.

Every "required" above is enforced **server-side**, not only with the HTML
`required` attribute — the browser attribute is a convenience, the PHP checks
are the actual gate.

The anti-bot math question sits after field 12, since it is a security control
rather than applicant data.

## 9. Application-form protection

The public form is protected by six independent layers:

1. **CSRF** — a per-session token, consumed on success so it cannot be replayed.
2. **Honeypot** — a hidden `website` field; any value means an instant reject.
3. **Math question** — regenerated after every successful submission.
4. **Timing check** — a form completed in under 3 seconds is rejected.
5. **Rate limiting** — **3 applications per IP per hour**, plus 15 per day and
   one per email address per role per day. Counted from `ep_job_applications`
   itself, so there is no separate counter table to maintain.
6. **Upload validation** — see below.

## 10. Upload validation

Both uploads are checked with `finfo_file()` against the file's **real
contents**. The filename extension is never trusted on its own — that is
exactly how a PHP script gets uploaded wearing a `.jpg` extension.

**Résumés** (PDF / DOC / DOCX, 5 MB max) must satisfy **all three** of:

- an allowed file extension,
- a `finfo_file()` MIME type on that extension's allow-list, and
- the correct magic bytes (`%PDF-`, `PK\x03\x04`, or the OLE header).

**Photos** (JPG / PNG only, 2 MB max) must pass the extension check, a
`finfo_file()` type of `image/jpeg` or `image/png`, and `getimagesize()`. They
are then re-encoded through GD to exactly **400×400** JPEG — centre-cropping
the longer side so faces stay centred and nothing is stretched. Re-encoding
discards anything the original carried besides pixels, rather than merely
rejecting it. If GD is unavailable the photo is rejected rather than stored
unverified.

Stored filenames are random (`YYYYMMDD-<32 hex chars>.<ext>`, from
`random_bytes()` rather than `md5(uniqid())` — same idea, but a CSPRNG instead
of a time-derived value). The applicant's original filename is never used on
disk; it is kept in the database for the download name only, and is sanitised.

Uploads live in `uploads/careers/`, which is not web-servable and carries
`php_flag engine off` plus handler stripping — see section 2.

## 11. On successful submit

Three things happen:

1. The applicant is redirected (Post/Redirect/Get, so a refresh cannot
   resubmit) to a **thank-you message** on the same page.
2. The **applicant** receives a confirmation email.
3. **HR** receives a notification email with the applicant's details and a link
   straight to the application in the admin panel.

Both emails go through the project's existing `MysqliDb::htmlmail()` helper.
Delivery failures are logged and swallowed — a mail problem must never lose an
application that has already been saved. HR's address comes from the job's
**Notification email**, else `careers_notify_email`, else `support_email`; if
none is set, no HR mail is sent and the application is still stored.

## 12. Files

**Public**
- `careers.php` — listing, filters (Department, Work Mode, Job Type, plus
  Location and search), general CV form
- `career.php` — job detail, JobPosting schema, application form
- `includes/careers.php` — shared helpers (schema, queries, uploads, validation)
- `includes/partials/application-form.php` — the form, used by both pages
- `css/careers.css`
- `sitemap.php`

**Admin**
- `cms/careers.php` — Manage Jobs (bullet-point builder, inline status)
- `cms/job-applications.php` — Manage Applications
- `cms/application-file.php` — authenticated résumé/photo delivery
- `cms/export-applications.php` — Excel export
- `cms/includes/careers-admin.php` — shared filter/query helpers
- `cms/assets/cms-job-form.js` — bullet-point builder, inline status dropdowns,
  clickable application rows

**Migration**
- `scripts/add-careers.sql`
