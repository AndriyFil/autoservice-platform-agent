# Public vs Admin Surface

AutoService stays one Laravel, Inertia, and Vue application. Public and admin are separate product surfaces, not separate repositories or apps.

## Why Separate Surfaces

Public users and workshop staff have different goals.

Customers use a workshop-provided public page to send a natural service request. That flow should feel customer-facing and should not be mixed conceptually with dashboard, staff, settings, or owner workflows.

Workshop owners and staff use the admin surface to register, log in, manage the workshop, review booking requests, maintain customers, create repair orders, and prepare estimates. Login, registration, dashboard, workshop settings, staff management, customers, repair orders, booking requests, and estimates belong to the admin surface.

## Target Domain Model

Production can eventually separate surfaces by domain:

- `autoservice.com` serves the marketing homepage and public workshop intake pages.
- `admin.autoservice.com` serves owner/staff login, owner registration, dashboard, and admin workflows.

Public examples:

- `/`
- `/w/{workshop:slug}`

Admin examples:

- `/login`
- `/register`
- `/dashboard`
- `/dashboard/customers`
- `/dashboard/repair-orders`
- `/dashboard/workshop/settings`

## Current Local Development Model

Local development supports separate public and admin hostnames inside the same Laravel app:

- Public: `http://autoservice.test:8080`
- Admin: `http://admin.autoservice.test:8080`

Add the hostnames locally:

```txt
127.0.0.1 autoservice.test
127.0.0.1 admin.autoservice.test
```

Use these environment values for the production-style local split:

```env
APP_URL=http://localhost:8080
PUBLIC_APP_URL=http://autoservice.test:8080
ADMIN_APP_URL=http://admin.autoservice.test:8080
```

The app keeps a localhost-compatible fallback. If `PUBLIC_APP_URL` and `ADMIN_APP_URL` resolve to the same host, routes are registered without domain constraints and the old single-host paths continue to work:

```env
APP_URL=http://localhost:8080
PUBLIC_APP_URL=http://localhost:8080
ADMIN_APP_URL=http://localhost:8080
```

## Repository Decision

Keep one repository, one Laravel app, and one Inertia/Vue frontend bundle unless the application structure changes for a proven reason.

The separation happens through route grouping, route comments, page ownership, layout conventions, copy, and generated links. Do not split this project into two repositories, create a second Laravel app, or create a separate frontend app for the admin surface.

## Route And UI Structure

Current route paths remain stable:

- Public surface: `/`, `/w/{workshop:slug}`, `/w/{workshop:slug}/intake`, and the legacy public booking request routes.
- Admin auth surface: `/login`, `/register`, password reset, email verification, password confirmation, and logout.
- Dashboard/admin surface: `/dashboard`, `/dashboard/booking-requests/*`, `/dashboard/customers/*`, `/dashboard/repair-orders/*`, `/dashboard/documents/*`, and `/dashboard/workshop/*`.
- Admin onboarding surface: `/workshop-onboarding`.

When configured hosts differ, public routes are registered under the public host and admin routes are registered under the admin host. When configured hosts are the same, the same route names and paths are registered without domain constraints.

## Generated URLs

Cross-surface links must use the configured root URL for the target surface:

- Workshop public intake links shown inside admin settings use `PUBLIC_APP_URL`, for example `http://autoservice.test:8080/w/main-auto`.
- Public homepage admin actions use `ADMIN_APP_URL`, for example `http://admin.autoservice.test:8080/register` and `http://admin.autoservice.test:8080/login`.
- Admin auth success redirects stay relative in single-host mode and use `ADMIN_APP_URL` in split-host mode.

## Production Migration Notes

Production support for `autoservice.com` and `admin.autoservice.com` still needs deployment, DNS, TLS, and session-cookie review. Example production values may look like:


```env
PUBLIC_APP_URL=https://autoservice.com
ADMIN_APP_URL=https://admin.autoservice.com
```

Keep public intake public and dashboard/auth routes protected. Do not add custom domains, billing, a customer cabinet, or a separate frontend app as part of this surface split.
