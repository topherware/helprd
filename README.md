# HelPRD

Turn a rough app idea into a **feature map** and a **build-ready PRD** (Product Requirements Document) that you can hand to an AI coding assistant.

You describe your idea, answer a few clarifying questions, pick a technology, then edit the feature map. HelPRD generates the PRD, a task list, and a ZIP package (`PRD.md`, `TASKS.md`, per-feature specs, `CLAUDE.md`, `.env.example`, DB schema) ready to build from.

**Bring your own API key.** HelPRD runs on your own machine and calls Claude with *your* Anthropic API key. Nothing is sent anywhere except to the Anthropic API, and no key is included in this repository. There is also a free demo mode that needs no key.

## What you get

- **Idea → questions:** questions are generated from the gaps in your idea (users, rules, accounts, payments, offline use, …), not from a fixed form. Attach reference images, PDFs, or text files, or dictate with the microphone.
- **Feature map:** an editable canvas of features and sub-features, grouped by priority phase.
- **Workspace:** PRD, roadmap, kanban, and per-feature pages in one view, with a chat agent on the side that can answer questions about your plan.
- **Quality standard:** every PRD is checked by deterministic validators (structure, traceability, security, stack fit, build readiness) and gets a score. Rules are generic and not tied to any one kind of app.
- **Plans sidebar:** keep many plans, organise them into folders (drag and drop, rename, delete).
- English and Indonesian interface, light and dark theme.

## Requirements

- PHP 8.0 or newer with the `curl`, `fileinfo`, `mbstring`, `pdo_mysql`, and `zip` extensions
- MySQL or MariaDB
- Apache (for example from [XAMPP](https://www.apachefriends.org/)) is recommended
- An [Anthropic API key](https://console.anthropic.com/) for real AI output (optional, see demo mode)

## Quick start (XAMPP, Windows/macOS/Linux)

1. **Get the code** into XAMPP's web folder (`htdocs`):

   ```
   git clone https://github.com/<your-username>/helprd.git
   ```

2. **Start Apache and MySQL** in the XAMPP control panel.

3. **Create the database** (phpMyAdmin → Import `schema.sql`, or from a terminal):

   ```
   mysql -u root < schema.sql
   php migrate.php
   ```

   `migrate.php` applies the migrations in `migrations/` and is safe to run again after updates.

4. **Create your settings file.** Copy `.env.example` to `.env`:

   ```
   cp .env.example .env
   ```

   By default `.env` is in **demo mode** (`USE_MOCK_AI=true`): the app works end to end with built-in sample answers, so you can try the whole flow for free. To use real AI, edit `.env`:

   ```
   USE_MOCK_AI=false
   API_KEY=your-anthropic-api-key
   ```

   If MySQL has a root password, also set `DB_PASS`.

5. **Open** <http://localhost/helprd/public/> (use the folder name you cloned into).

### Without XAMPP

If you already have PHP and MySQL, you can use PHP's built-in server instead of Apache:

```
php -S localhost:8000 -t .
```

then open <http://localhost:8000/public/>. This is fine for a quick look. Generating a PRD is a long streaming request, so for regular use prefer Apache, or on macOS/Linux start the built-in server with several workers: `PHP_CLI_SERVER_WORKERS=4 php -S localhost:8000 -t .`

## Settings (`.env`)

| Setting | Default | What it does |
|---|---|---|
| `USE_MOCK_AI` | `true` | `true` = demo mode with sample data and no API calls. `false` = call Claude. |
| `API_KEY` | empty | Your Anthropic API key. Only used when demo mode is off. |
| `API_MODEL` | `claude-opus-5` | Model to use. A smaller model is cheaper and faster. |
| `API_BASE_URL` | `https://api.anthropic.com` | Leave as is unless you use a compatible gateway. |
| `AI_EFFORT` | `low` | `low`, `medium`, or `high`. Lower finishes faster. |
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` | XAMPP defaults | Database connection. |
| `RATE_LIMIT_PER_HOUR` | `30` | AI calls per IP per hour. |
| `DOC_MAX_CALLS_PER_PROJECT` | `36` | AI calls allowed for documents per plan. |
| `AI_MIN_INTERVAL_SECONDS` | `2` | Minimum pause between AI calls. |

`.env` is listed in `.gitignore`. Never commit it.

## What it costs

Demo mode is free. With real AI, you pay Anthropic for what you use. A plan with about ten features takes roughly twenty AI calls (questions, feature map, PRD in several parts, tasks, quality audit). Attached files are summarised once and reused. To keep spending under control, set a spending limit in the Anthropic Console, and consider a smaller model in `API_MODEL`.

## Notes

- **Microphone dictation** uses your browser's speech recognition. It works in Chrome, Edge, and Safari on `localhost` or HTTPS, and the button is hidden elsewhere.
- **Attachments:** up to 5 files, 5 MB each (PNG, JPG, WEBP, GIF, PDF, TXT, MD). In demo mode they are only listed by name, with text files quoted; only real AI reads images and PDFs. Files are stored in `storage/uploads/` and are not publicly reachable.
- **Do not put this on a public server as is.** There are no user accounts. Anyone who can open the site would spend *your* API key. It is meant to run locally, or behind your own login or network restrictions.
- If you deploy behind a proxy or on HTTPS, review cookie and client-IP handling in `src/helpers.php` first.
- Plans are kept in your own MySQL database and are tied to the browser (a cookie), not to an account.

## Troubleshooting

| Problem | Fix |
|---|---|
| "Database can't be reached" | Start MySQL, then check `DB_*` in `.env` and that `schema.sql` was imported. |
| Blank page or HTTP 500 | Check `logs/php-error.log` and `logs/app.log`; make sure the PHP extensions above are enabled in `php.ini`. |
| Attachment too large | Raise `upload_max_filesize` and `post_max_size` in `php.ini` (at least 30M). |
| PRD generation stops halfway | Use Apache/XAMPP instead of a tiny hosting plan, and set `max_execution_time` to at least 660. Generation can resume. |
| AI error about the API key | Check `API_KEY`, that `USE_MOCK_AI=false`, and that your Anthropic account has credit. |

## Tests

These run without a database or API:

```
php tests/quality_v12_test.php
php tests/security_test.php
php tests/paritas_test.php
php tests/json_repair_test.php
php tests/lampiran_test.php
```

## Project layout

```
public/      pages and assets (open /public/ in the browser)
api/         JSON endpoints used by the pages
src/         PHP code: AI client, prompts, validators, quality standard
migrations/  database migrations (run with php migrate.php)
docs/        design decisions and architecture notes
tests/       tests
```

More detail: [docs/arsitektur.md](docs/arsitektur.md) (pipeline and quality standard) and [docs/keputusan.md](docs/keputusan.md) (design decisions). The existing docs are written in Indonesian.
