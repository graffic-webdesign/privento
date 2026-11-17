=== Privento – GDPR Cookie Consent ===
Contributors: graffic
Tags: gdpr, cookie consent, cookie banner, google consent mode, privacy
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

GDPR cookie consent for WordPress: consent banner, Google Consent Mode v2, script blocking, consent log and a generated cookie policy.

== Description ==

**Privento** is a cookie consent plugin for WordPress, made by [Graffic](https://graffic.dk).

It shows a consent banner, holds back tracking scripts until the visitor has agreed, passes the choice on to Google through Consent Mode v2, and keeps an anonymised record of every consent.

= Consent =

* Four categories: Necessary, Preferences, Statistics, Marketing
* Accept all / Reject all / choose per category, with equally prominent Accept and Reject buttons
* Google Consent Mode v2, denied by default
* Known tracking scripts and embeds are blocked until consent is given
* Consent log with hashed IP addresses and CSV export
* Consent expires after a period you choose and is then asked for again
* Cookie scanner and a generated Cookie Policy page
* Floating settings button and a `[privento_revoke]` link for withdrawing consent

= Languages =

The banner, the settings window and the cookie policy follow the language of the WordPress site, per page — so they also follow WPML and Polylang. English, Danish, German, Swedish and Norwegian are included. A text you type yourself under **Texts** is shown as typed, in every language; leave a field empty to use the translation.

= Admin =

* Compliance dashboard with a score and the open requirements
* Banner position: bottom bar, centred modal, left or right corner
* Colours with presets, live preview and WCAG contrast readouts; can pick up the palette of your theme or Elementor
* Google Tag Manager, GA4 and Meta Pixel integration
* WCAG focus options

= Updates =

Privento is distributed on GitHub, not on WordPress.org. Installed sites find new releases themselves and offer them on the normal WordPress Updates screen; automatic updates can be switched on per site in the Plugins list.

= Developers =

* REST endpoint for consent recording
* JavaScript API: `window.Privento` and the `privento:accepted`, `privento:rejected`, `privento:saved` events
* Translation template included (`languages/privento.pot`)
* Source: https://github.com/graffic-webdesign/privento

== Installation ==

1. Download `privento.zip` from the latest release at https://github.com/graffic-webdesign/privento/releases
2. In WordPress go to **Plugins → Add New → Upload Plugin**, choose the zip and activate
3. Go to **Settings → Privento**
4. Use the **Cookie Scanner** tab to scan for cookies
5. Use the **Cookie Policy** tab to generate your Cookie Policy page

== Frequently Asked Questions ==

= Does this block Google Analytics before consent? =
Yes. Scripts and embeds from known statistics and marketing services are held back until the visitor grants the matching category.

= Is Google Consent Mode v2 supported? =
Yes — the default "denied" signals are printed at `wp_head` priority 1, before any GTM or GA snippet.

= Can I export consent records? =
Yes — go to **Settings → Privento → Consent Logs** and click Export CSV.

= Is it compatible with caching plugins? =
Yes — consent is handled in the browser, and recording a consent does not depend on anything a page cache could make stale.

= Does it support multiple languages? =
Yes. Texts follow the WordPress site language automatically, including per-page languages from WPML and Polylang. See "Languages" above.

= How do updates work without WordPress.org? =
The plugin checks its GitHub repository for a newer release twice a day, as part of WordPress' own update check. You update it like any other plugin. To look right away, go to Dashboard → Updates and click Check again.

= What happens when I delete the plugin? =
Its settings and the consent log are removed. The Cookie Policy page stays, since it is a normal page on your site. Deactivating removes nothing.

== Changelog ==

= 1.7.2 =
* Fixed: Dashboard → Updates → Check again did not find a new Privento release when clicked within a minute of opening the screen. It now always looks.
* New releases reach sites sooner: the plugin remembers the latest release for one hour instead of twelve.

= 1.7.1 =
* Removed the geo-targeting option from Settings. It never had any effect: the banner was shown to every visitor whether it was on or off, and it still is. Nothing changes on your site.
* First version delivered through the built-in updater.

= 1.7.0 =
* Updates now arrive through WordPress itself. The plugin looks for new releases on GitHub and offers them on the Updates screen, with one-click and automatic updates. This is the last version that has to be uploaded by hand.
* The banner, the settings window and the cookie policy follow the language of the site. English, Danish, German, Swedish and Norwegian are included, and WPML or Polylang pages each get their own language. Texts you have edited under Texts stay exactly as you wrote them; leave a field empty to use the translation. Existing sites keep their current wording.
* Cookie descriptions on the policy page and in the settings window are translated too, also for scans made before this version.
* Fixed: consents were not written to the log on pages served from a full-page cache once the cached page was more than a day old.
* Fixed: videos and maps set to lazy-load by a performance plugin could end up blocked for good.
* Fixed: the cookie scanner listed any cookie whose name merely began with "fr", "IDE" or similar as a Facebook or Google cookie, and described GA4 cookies as the older Analytics cookie.
* A consent given under an older cookie version is asked for again, as the cookie policy has always said.
* The link to the cookie policy now closes the banner text instead of sitting on a line of its own right above the buttons, and its wording can be set under Texts, where the link can also be switched off.
* Fixed: on themes without a `.screen-reader-text` style, the category names were printed across the switches in the settings window.
* Lighter front end: the stylesheet and script are minified (about 40 % smaller) and the script no longer holds up page rendering.
* The CSV export no longer loads the whole log into memory.
* Deleting the plugin removes its settings and the consent log.
* The plugin is now published on GitHub under GPL, made by Graffic.

= 1.6.2 =
* The revoke requirement now also scans where the shortcode is stored, instead of relying only on the shortcode reporting its own renders. 1.6.1 stayed red until a visitor loaded a page carrying the link, which never happened behind a full-page cache and looked simply broken on a fresh upgrade. It checks published post content and the page-builder meta Elementor and SiteOrigin write to, cached for an hour, and only when the floating button is off.

= 1.6.1 =
* Fixed the Dashboard marking "Revoke option available" as unmet on sites that offer withdrawal through a [privento_revoke] link instead of the floating button. The check only looked at the button. It now accepts either, and the requirement row says which one is in use.
* Searching post content for the shortcode would miss page builders, widgets, menus and theme templates, so the shortcode records the date it last rendered on the front end instead — at most one write per day. The sighting counts for 30 days, so the requirement goes back to unmet on its own if the link is removed. It flips to met the first time a visitor loads a page carrying the link.

= 1.6.0 =
* Rebuilt the Dashboard around a compliance score. The six requirements now produce a score out of 100, the first unmet one is promoted into a callout with a button that takes you straight to the fix, and the rest stay in a numbered list with the detail that decides each one (banner position, tag ID, policy slug, scan date, record count).
* The figures are now windowed instead of all-time: accept rate, total consents and the accepted/partial/rejected split cover the last 30 days, with the change in accept rate against the previous 30 days. A 14-day bar chart shows consents per day.
* New Cookie categories panel: how many cookies the scanner found per category, against how often visitors actually granted it.
* Logger gained get_daily_counts() and get_category_acceptance(), and get_stats() takes an optional day window and offset. Its category matching moved from LIKE '%name%' to FIND_IN_SET, which cannot match across the comma-separated list.

= 1.5.0 =
* Rebuilt the Design tab's colour settings as a single Colors card: grouped rows, swatch pickers with a shared palette and hex entry, three one-click presets (Default, Dark, High Contrast), a live banner preview with a desktop/mobile toggle whose buttons show their hover colours when you hover them, and live WCAG AA contrast readouts for the banner text and the accept/reject buttons.
* The floating settings button's colours moved into that same card, so every colour is set in one place.
* New: the [privento_revoke] shortcode link now has its own colour and hover colour.
* New: the Colors card picks up the site's own palette. Every colour picker gets a "From <source>" row of the site's colours, and a matching preset button maps them onto the whole banner in one click. The palette is read from Elementor's global colours first, then the block theme's theme.json, then a classic theme's editor palette; the preset button is disabled when a site has none of those. The mapping derives every button surface from the palette's background, text and primary colours, keeping all measured pairs above WCAG AA.
* New: each of the four banner buttons now has its own hover text colour, not just a hover background. This also fixes the Settings button, whose default hover painted the grey base text on a dark green background — its hover text now defaults to white. The other three keep their previous appearance.

= 1.4.2 =
* Fixed an aria-hidden accessibility violation: when the banner auto-opened with nothing else focused on the page, closing it via Accept/Reject called the no-op body.focus() and assumed it worked, leaving the just-clicked button focused inside a container marked aria-hidden="true".

= 1.4.1 =
* The blue outline left on a cookie category after clicking it is gone in the prominent focus-ring mode too — that mode is on by default, so 1.4.0 only fixed half of it.
* Regenerated the translation files from the current source: privento.pot and privento-da_DK.po/.mo now cover all 225 strings, with the admin interface fully translated to Danish.

= 1.4.0 =
* The admin pages have moved from their own top-level menu to Settings → Privento.
* Removed the blue border left on a cookie category after clicking it — the focus outline now follows the accessibility setting and only shows for keyboard navigation.
* The settings window has a heading of its own, since the consent card that carried it is hidden while the window is open.
* Removed the duplicated copy of the layout CSS that was printed inline on every page; the stylesheet is render-blocking in the head, so it was never needed.

= 1.3.9 =
* Cookie settings now open in their own centered window instead of expanding the consent card, so the details are readable at any banner position. Only the category list scrolls; the buttons stay in place.
* Danish wording for the Texts menu item, the Google Consent Mode hint and the geo-targeting hint.

= 1.3.8 =
* All pre-filled banner texts, category names and descriptions are now Danish out of the box.
* Existing sites are migrated on upgrade: settings still holding the old English defaults are replaced with the Danish wording, while any text you have edited yourself is left untouched.
* The [privento_revoke] shortcode label and the cookie policy page title default to Danish.

= 1.3.7 =
* Fixed the consent card growing off the top of the screen when a cookie category is expanded — the settings panel now scrolls inside the card.
* Fixed long cookie names pushing the details table outside the card, and gave cookie values the full width on narrow corner and mobile layouts.

= 1.3.6 =
* The [privento_revoke] shortcode and the floating button now open the cookie settings panel directly instead of only showing the banner.
* Documented the [privento_revoke] shortcode in the Design tab, next to the floating button toggle.

= 1.3.1 =
* Initial release

== Upgrade Notice ==

= 1.7.0 =
The last update you install by hand: from here on new versions arrive on the WordPress Updates screen. Your banner texts and settings are kept.

= 1.3.1 =
Initial release.
