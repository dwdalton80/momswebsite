# Wee Time, Me Time: WordPress site

Two parts, both built from the approved preview:

- `themes/wee-time`: a block theme with the preview's look. Fonts are bundled, so no page calls a font service.
- `plugins/wee-time-core`: gear lists, Kids Corner (Abtuvi cartoons, printables, games, monthly packs), members-only files, the grown-up check, the trial reminder email and the Amazon link setting.

Memberships and payments run on **Paid Memberships Pro** with its Stripe gateway. This plugin never touches card details.

## Promises the code keeps

- **Nothing is collected from children.** Kids Corner pages have no forms, no analytics, no outside embeds, no emoji service and no third-party calls. They send a same-site-only Content-Security-Policy, `Referrer-Policy: no-referrer` and a Permissions-Policy that turns off camera, microphone and location.
- **Only parents have accounts.** Plans and members-only items sit behind a grown-up check (a sum written in words) before anything leads to checkout.
- **Analytics only on the moms side.** Hook your analytics to `do_action( 'wt_moms_analytics' )`. It never fires on a Kids Corner page. Don't add analytics any other way.
- **Members-only files stay private.** PDFs and videos live in `wp-content/uploads/wt-protected/` and are served only through `/wt-file/<id>/` after a membership check. The REST API and feeds hide locked content and file names.

## Setup on a new server

1. Install WordPress and **Paid Memberships Pro**. Connect its Stripe gateway (PMPro > Settings > Payment Gateway).
2. Deploy this repo (see below), then activate the theme and the `Wee Time Core` plugin.
3. Run `wp weetime seed --levels` once. It makes the age groups, the Home, Moms and Kids Corner pages, the gear lists, the Abtuvi episodes, the printable slots and the two plans (Monthly $4.99, Yearly $47).
4. **3-day free trial, card up front.** PMPro's own trial fields bill a reduced amount for a number of payments, not a number of days. For a 3-day trial use PMPro's *Subscription Delays* add-on and set a 3-day delay on both levels. Check which PMPro plan includes that add-on before buying anything.
5. Settings > Wee Time: confirm the member plans, trial days (3) and the Kids Corner page.
6. Settings > Permalinks: press Save once.
7. Upload member files on each printable, pack or episode (the file box on the edit screen), or with `wp weetime attach <post-id> <file>`.

### Protecting the member files folder

Apache uses the `.htaccess` the plugin writes into `wt-protected`. **nginx ignores it**, so add this to the site's server block:

```
location ^~ /wp-content/uploads/wt-protected/ { deny all; }
```

### Amazon Associates

Leave the Amazon tag empty until Amazon gives you an ID (they need a live site first). Then put it in Settings > Wee Time, or define `WT_AMAZON_TAG` in `wp-config.php`. That one setting adds the tag to every product link and shows the Associates disclosure. Product links are marked `rel="sponsored"` either way.

### Trial reminder email

Stripe's own "trial ending" email goes out 3 days before the trial ends, which for a 3-day trial is the moment it starts. So the site sends its own email about a day before the first charge: the plan, the price, the date and how to cancel. It runs hourly on WP-Cron. On a quiet site, have the host call `wp cron event run --due-now` every 15 minutes so it isn't late.

## WP-CLI commands

```
wp weetime seed [--levels]          # create pages, content slots and (optionally) the two plans
wp weetime attach <post> <file>     # put a PDF or MP4 into the protected folder for that item
wp weetime remind [--now=<ts>]      # run the trial reminder check once
```

## Deploying from GitHub

`.github/workflows/deploy.yml` checks the PHP, then copies the theme and plugin to the server over SSH with rsync.

- A push to `main` goes to **staging**.
- **Production** goes out only by hand (Actions > Deploy > Run workflow > production) or when a version tag like `v1.0.0` is pushed.

In the repo's Settings > Environments, make `staging` and `production`, and give each these secrets: `SSH_PRIVATE_KEY`, `SSH_KNOWN_HOSTS`, `SSH_HOST`, `SSH_USER`, `SSH_PORT`, `WP_PATH`. Member files are never in the repo; upload them on the server.

## Testing locally

With a local WordPress that has PMPro and this theme and plugin installed (copied in, as the deploy does):

```
wp eval-file tests/setup-fixtures.php <sample.pdf> <sample.mp4> > fixtures.json
wp eval-file tests/trial-reminder.php
bash tests/http-checks.sh http://localhost:8099 fixtures.json <wordpress-folder>
```

`setup-fixtures.php` makes two test parents (one with a plan, one without), attaches the sample files and prints login cookies for the checks. Run it only on a local or throwaway site.
