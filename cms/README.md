# EduPortal CMS

## Login

- URL: `.../cms/login.php`
- Seed super-admin (auto-created if table empty):
  - Email: `admin@eduportal.local`
  - Password: `Admin@12345`

Change this password immediately from `Users` page.

## Sidebar Modules

- Dashboard (`index.php`)
- Visitor Stats (`visitors.php`)
- Leads (`leads.php`)
- Blogs (`blogs.php`)
- Case Studies (`case-studies.php`)
- Video Testimonials (`videos.php`)
- Homepage Features (`features.php`)
- Contact Items (`contact-items.php`)
- Social Links (`social-links.php`)
- Site Settings (`settings.php`)
- Google Reviews (`google-reviews.php`) — see `../GOOGLE_REVIEWS_SETUP.md`
- Manage Jobs (`careers.php`) — see `../CAREERS_SETUP.md`
- Job Applications (`job-applications.php`) — see `../CAREERS_SETUP.md`
- Users (`users.php`, super-admin only)
- Permissions (`permissions.php`, super-admin)
- Media Manager (`media.php`)

## Visitor Tracking

- Endpoint: `api/track-visit.php`
- Public dynamic pages call this endpoint via `includes/partials/public-track.php`.
- Data table: `ep_visitor_hits`

If you want **all** static `.html` pages tracked too, either:
1) convert them to `.php` and include the tracking partial, or
2) add equivalent JS snippet manually in each static page.

## UI Stack (2026 refresh)

- **Bootstrap 5.3** (responsive grid, forms, tables, dropdowns)
- **Bootstrap Icons**
- **Chart.js** on dashboard
- **Inter** font + custom theme: `assets/cms-theme.css`
- Mobile sidebar with overlay drawer

## Advanced Features Added

- Mobile responsive CMS UI with collapsible sidebar.
- Role defaults + per-user permission overrides via `ep_admin_user_permissions`.
- Route-level permission checks for every CMS page.
- Media upload library using `ep_media` + physical files under `uploads/cms-media/`.

### Permission Keys

- `dashboard.view`
- `visitors.view`
- `leads.manage`
- `blogs.manage`
- `case_studies.manage`
- `videos.manage`
- `features.manage`
- `media.manage`
- `contact.manage`
- `social.manage`
- `settings.manage`
- `reviews.manage`
- `careers.manage`
- `applications.manage`
- `users.manage`
- `permissions.manage`
