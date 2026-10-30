# Provider Calendar Feed (iCal)

> This page explains how providers subscribe their appointments in Outlook, Google Calendar or Apple Calendar, so that email notifications are no longer the only channel.

### Introduction

Each provider can own a personal **calendar feed URL**. The feed renders the provider appointments as an iCalendar subscription, so calendar clients automatically merge them — the provider never has to act on an email just to keep the calendar in sync.

The subscription is optional and additive: email notifications continue working as before.

### Generate a Feed

In the backend, open `Users → Providers`, select a provider and scroll to the "Calendar Feed (iCal)" section:

1. Press **Generate feed** — the URL appears exactly once, copy it now.
2. Paste the URL into the calendar client:
   - **Outlook (desktop):** `File → Account Settings → Internet Calendars → New` and paste the URL.
   - **Outlook (web):** `Calendar → Add calendar → Subscribe from web`.
   - **Google Calendar:** `Other calendars → From URL`.
   - **Apple Calendar:** `File → New Calendar Subscription`.
3. **Revoke feed** deletes the token, and the subscribed feed dies immediately in the client on its next poll.

Generating a new feed always revokes the old one (rotation), so a leaked URL can also be deactivated this way.

### How It Works

- The feed URL carries a personal token, e.g. `https://appointments.example.org/index.php/ical/<token>.ics`.
- The application stores only the SHA-256 hash of the token — the plain URL cannot be recovered from the database, and viewing the URL a second time is impossible after the first copy.
- The feed resolves appointments of the owning provider only (last `ICAL_FEED_RANGE_PAST` days + next `ICAL_FEED_RANGE_FUTURE` days, configurable in `config.php`). Customer contact information (email, phone, notes) is intentionally not part of the feed.
- The calendar client polls the URL periodically (typically once per hour). An `ETag` makes the poll return `304 Not Modified` when nothing changed, and a rate limit (`180` requests/hour per token and IP) keeps abusive clients from hammering the server.
- Wrong or revoked tokens return a plain `404`.

### Security Notes

- Share the feed URL over HTTPS only — the web-server configuration of Easy!Appointments takes care of that.
- Treat the feed URL like a password: whoever holds it can read the appointments. Revoke if a URL was forwarded somewhere unexpected.
- The token lives in the URL path (never in a query string) and the response carries `Cache-Control: private, no-store`, so intermediaries do not keep a copy.

### Deployment

Existing installations need migration `027` (runs automatically on the next backend load) and the `provider_calendar_tokens` table. No client software is required.

*This document applies to Easy!Appointments v1.4.2.*

[Back](readme.md)
