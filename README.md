<p align="center">
    <img src="art/unsplash-toolkit-logo.png" alt="UnsplashToolkit" width="600">
</p>

# UnsplashToolkit

Curated Unsplash photos for Laravel — you approve the photos, Unsplash serves the bytes, and the photographer gets credited correctly every time.

[![Latest version](https://img.shields.io/packagist/v/gabrielesbaiz/unsplash-toolkit.svg?style=flat-square)](https://packagist.org/packages/gabrielesbaiz/unsplash-toolkit)
[![PHP](https://img.shields.io/packagist/dependency-v/gabrielesbaiz/unsplash-toolkit/php?style=flat-square)](composer.json)
[![Laravel](https://img.shields.io/packagist/dependency-v/gabrielesbaiz/unsplash-toolkit/illuminate%2Fcontracts?style=flat-square&label=laravel)](composer.json)
[![Downloads](https://img.shields.io/packagist/dt/gabrielesbaiz/unsplash-toolkit.svg?style=flat-square)](https://packagist.org/packages/gabrielesbaiz/unsplash-toolkit)
[![Stars](https://img.shields.io/github/stars/gabrielesbaiz/unsplash-toolkit?style=flat-square&logo=github)](https://github.com/gabrielesbaiz/unsplash-toolkit/stargazers)
[![Sponsor](https://img.shields.io/github/sponsors/gabrielesbaiz?style=flat-square&label=sponsor&logo=github)](https://github.com/sponsors/gabrielesbaiz)

### 📖 [Read the documentation →](https://gabrielesbaiz.github.io/unsplash-toolkit/)

Every setting, every command, the whole API, eight recipes, and a page on each of
the nine Unsplash API Guidelines this package enforces for you.

> [!CAUTION]
> **Upgrading from 1.x?** Read [UPGRADE.md](UPGRADE.md) first. The facade is now
> `Facades\Unsplash`, the trait moved to `Concerns\HasUnsplashables`, and
> `store()` is gone — the package hotlinks instead of downloading files. Your
> curated rows survive: `unsplash:refresh --backfill` rebuilds them.

> [!IMPORTANT]
> A ⭐ costs you nothing and helps other developers find this package.
> [Sponsoring](https://github.com/sponsors/gabrielesbaiz) keeps it compatible
> with every new Laravel release.

## What it does

If you need one photo on one page, copy the URL out of Unsplash and put it in
your template. Add the credit by hand and you are done — no dependency, no
table, no API key. If you want raw API access and nothing else, the official
[`unsplash/unsplash`](https://github.com/unsplash/unsplash-php) client is
thinner than this and has no opinions.

This package is for when a person has to **choose** the photos and the
application has to keep choosing between them:

- **Curated pools.** Approve photos into named sets, then select from your own database — **zero API calls when a page renders**.
- **Hotlinked and responsive.** Images are resized on Unsplash's CDN, so there is no storage bill, no egress bill, and any width you like.
- **Attribution that cannot be got wrong.** Escaped, UTM-tagged, rendered by default, and it throws rather than emit a credit that would not qualify.
- **Nine guidelines enforced in code**, each with a test named after it, and `unsplash:doctor` to fail a build that breaks one.
- **Caching, retries, timeouts and a rate limiter**, so the 50-per-hour demo budget is spent on curation rather than discovered in production.
- **Readonly DTOs, enums and a fake driver** — no more digging through nested response arrays.

The trade is real: **you never hold the image files**, because the API Guidelines
require hotlinking. If a photographer deletes a photo, your copy goes with it.
A scheduled `unsplash:verify` takes dead photos out of rotation and a colour
fallback keeps the page readable, but it cannot bring the photo back. If you need
bytes you control forever, buy a stock licence and use
[spatie/laravel-medialibrary](https://github.com/spatie/laravel-medialibrary).

## Requirements

- PHP 8.2, 8.3 or 8.4
- Laravel 10, 11 or 12
- An [Unsplash API access key](https://unsplash.com/developers)

## Installation

```bash
composer require gabrielesbaiz/unsplash-toolkit

php artisan vendor:publish --tag=unsplash-toolkit-migrations
php artisan migrate
php artisan vendor:publish --tag=unsplash-toolkit-config

php artisan unsplash:doctor
```

Two environment variables are required. `UNSPLASH_APP_NAME` is not optional: it
becomes the `utm_source` of every photographer credit, and the package throws
rather than emit one without it.

```
UNSPLASH_ACCESS_KEY=your-access-key
UNSPLASH_APP_NAME="Your Application"
```

**[Full installation guide →](https://gabrielesbaiz.github.io/unsplash-toolkit/#/install)**

## Artisan commands

| Command | Purpose |
|---|---|
| `unsplash:search {query}` | Find photos from the console, with orientation and colour filters. |
| `unsplash:curate {id*} --pool=` | Approve photos into a pool, reporting the download event. |
| `unsplash:verify` | Re-check curated photos still exist, retiring those that do not. |
| `unsplash:doctor` | Audit the application against the API Guidelines. Exits non-zero on a violation. |

Nine commands in all, two of which belong in your scheduler. See the
[commands page](https://gabrielesbaiz.github.io/unsplash-toolkit/#/commands).

## Documentation

| | |
|---|---|
| [Documentation site](https://gabrielesbaiz.github.io/unsplash-toolkit/) | Everything: install, configure, curate, operate. |
| [Guide](https://gabrielesbaiz.github.io/unsplash-toolkit/#/guide) | Browsing, curating, pools, Blade components, building a picker. |
| [Configuration](https://gabrielesbaiz.github.io/unsplash-toolkit/#/configuration) | All 40 settings, with defaults and what each one changes. |
| [API reference](https://gabrielesbaiz.github.io/unsplash-toolkit/#/api) | Every method, DTO, enum, event and exception. |
| [Compliance](https://gabrielesbaiz.github.io/unsplash-toolkit/#/compliance) | The nine rules, how each is enforced, and the storage exception. |
| [Recipes](https://gabrielesbaiz.github.io/unsplash-toolkit/#/recipes) | Whole solutions: rotating backgrounds, admin pickers, queued curation. |
| [Troubleshooting](https://gabrielesbaiz.github.io/unsplash-toolkit/#/troubleshooting) | The errors you are most likely to meet. |
| [UPGRADE.md](UPGRADE.md) | Upgrading from 1.x. Read before you start. |
| [CHANGELOG.md](CHANGELOG.md) | What changed, and when. |

## Testing

```bash
composer test        # Pest
composer analyse     # PHPStan, level 6
composer format      # Pint
```

GitHub Actions runs all three across PHP 8.2–8.4 and Laravel 10–12 on every push.
`tests/Compliance` holds one file per guideline — it is what keeps the rules from
rotting, so a change that weakens it needs a test rather than a merge.

## Contributing

Thank you for considering contributing. The
[project page](https://gabrielesbaiz.github.io/unsplash-toolkit/#/project)
covers how to run the suite and what gates a pull request.

## Security vulnerabilities

Please review the
[security policy](https://github.com/gabrielesbaiz/unsplash-toolkit/security/policy)
for reporting a vulnerability, and the
[security page](https://gabrielesbaiz.github.io/unsplash-toolkit/#/security) for
the threat model. Please do not open a public issue.

## Credits

Written and maintained by [Gabriele Sbaiz](https://github.com/gabrielesbaiz).

The 1.x line was forked from
[marksitko/laravel-unsplash](https://github.com/marksitko/laravel-unsplash) by
[Mark Sitko](https://github.com/marksitko). 2.0 is a rewrite, but the idea of a
fluent Unsplash client for Laravel started there.

This package builds on Laravel and
[spatie/laravel-package-tools](https://github.com/spatie/laravel-package-tools).
The photographs come from the photographers on
[Unsplash](https://unsplash.com), who make them available for free.

## Support this package

If it is useful to you:

- ⭐ **Star the repo.** Free, thirty seconds, and it is the first signal other developers look at.
- ❤️ **[Become a sponsor](https://github.com/sponsors/gabrielesbaiz).** From $5 a month.
- 🐛 **Open a good issue.** A clear reproduction is worth more than you think.
- 🗣️ **Tell another Laravel developer.** Word of mouth is how packages survive.

[![Sponsor on GitHub](https://img.shields.io/badge/Sponsor-gabrielesbaiz-ff69b4?style=for-the-badge&logo=github-sponsors)](https://github.com/sponsors/gabrielesbaiz)

## Disclaimer

This package is provided **as is**, without warranty of any kind, express or
implied, including but not limited to the warranties of merchantability,
fitness for a particular purpose, title and non-infringement. To the fullest
extent permitted by applicable law, in no event shall the authors, copyright
holders or contributors be liable for any claim, damages or other liability —
whether in an action of contract, tort or otherwise — arising from, out of or in
connection with this package or its use, including without limitation any
direct, indirect, incidental, special, exemplary, consequential or punitive
damages, loss of data, loss of profits, business interruption, or the suspension
or termination of your Unsplash API access.

This package enforces the Unsplash API Guidelines as they were published when it
was written. The compliance checks are a good-faith implementation, not legal
advice and not an approval from Unsplash. The guidelines can change, and the
terms that apply to your application are between you and Unsplash. Whoever
deploys this package is responsible for reading them and keeping their usage
within them — including, and not limited to, applying for production access,
naming the application correctly, honouring the rules no package can check, and
reviewing the code before putting it in front of traffic you cannot afford to
lose. Images are loaded from a third-party CDN at render time, and neither their
availability nor their continued licensing is within this package's control.

Use of this package is entirely at your own risk.

## License

MIT. See [LICENSE.md](LICENSE.md). The MIT licence's warranty disclaimer and
limitation of liability apply in full, alongside the disclaimer above.
