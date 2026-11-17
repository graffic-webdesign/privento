# Privento

GDPR cookie consent for WordPress: a consent banner, Google Consent Mode v2, script blocking, a consent log and a generated cookie policy.

Made by [graffic.dk](https://graffic.dk).

## Install

1. Download `privento.zip` from the [latest release](https://github.com/graffic-webdesign/privento/releases/latest).
2. In WordPress: **Plugins → Add New → Upload Plugin**, choose the zip, activate.
3. Configure under **Settings → Privento**.

Use the release zip, not GitHub's "Source code" archive — the release zip has the right folder name and leaves out development files.

Requires WordPress 6.0 and PHP 8.0.

## Updates

Privento is not on WordPress.org. It updates from this repository instead:

- WordPress checks for plugin updates twice a day. Privento answers that check with the latest release here.
- A new version shows up on the normal **Updates** screen and in the Plugins list, and installs with one click.
- To update without clicking, enable auto-updates for Privento in the Plugins list. WordPress then emails the site administrator after each update.

Versions before 1.7.0 have no updater; they need 1.7.0 uploaded by hand once.

## Languages

All visitor-facing text follows the WordPress site language, per page, so WPML and Polylang sites get each page in its own language.

Included: English, Danish (`da_DK`), German (`de_DE`, `de_DE_formal`), Swedish (`sv_SE`), Norwegian Bokmål (`nb_NO`).

Text typed under **Settings → Privento → Texts** overrides the translation and is shown as typed in every language. Leave a field empty to follow the site language.

To add a language, copy `languages/privento.pot` to `languages/privento-<locale>.po`, translate it, add the locale to `LOCALES` in `bin/i18n.py` and run the script.

## For site builders

Shortcodes:

| Shortcode | Output |
|---|---|
| `[privento_revoke label="Cookie settings" class="my-link"]` | A link that opens the cookie settings |
| `[privento_cookie_policy]` | The cookie policy, built from the last cookie scan |

JavaScript:

```js
window.Privento.hasConsent('statistics'); // true / false
window.Privento.getConsent();             // { consented, rejected, version, timestamp } or null
window.Privento.show();                   // open the banner

document.addEventListener('privento:accepted', e => console.log(e.detail.consented));
// also: privento:rejected, privento:saved
```

To hold back a script of your own until consent, mark it up like this and Privento activates it when the category is granted:

```html
<script type="text/plain" data-privento-blocked="1" data-privento-category="marketing">
  /* runs after marketing consent */
</script>
```

## Development

No build step is needed to run the plugin from a checkout. Three generated things are committed and must be rebuilt when their sources change — CI fails if they are stale:

| Changed | Run | Needs |
|---|---|---|
| Any translatable string, or a `.po` file | `python bin/i18n.py` | Python 3 |
| `public/css/privento-public.css` or `public/js/privento-public.js` | `bin/minify.sh` | Node.js |

Tests run without a WordPress install:

```bash
php tests/run.php
```

Set `SCRIPT_DEBUG` to `true` in `wp-config.php` to load the unminified front-end files.

## Releasing

1. Set the new version in three places: the `Version:` header and `PRIVENTO_VERSION` in `privento.php`, and `Stable tag:` in `readme.txt`.
2. Add a `= x.y.z =` entry to the changelog in `readme.txt`. It becomes the release notes.
3. Commit, then tag and push:

```bash
git tag v1.7.0
```

```bash
git push origin main --tags
```

The release workflow checks that the tag matches the version, runs the tests, builds `privento.zip` and publishes the release. Sites pick it up within twelve hours, or at once when an administrator clicks **Dashboard → Updates → Check again**.

## Licence

GPL-2.0-or-later. See [LICENSE](LICENSE).
