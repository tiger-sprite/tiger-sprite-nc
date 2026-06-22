# TigerSprite

[![GitHub](https://img.shields.io/badge/GitHub-tiger--sprite/tiger--sprite--nc-181717?logo=github)](https://github.com/tiger-sprite/tiger-sprite-nc)

**TigerSprite** is a Nextcloud app that adds one-click Markdown-to-PDF export functionality.

Right-click any Markdown file in the Nextcloud file interface and select **TigerSprite Export to PDF** to quickly convert your document.

---

## Features

- **One-click conversion** — works directly from the file context menu, no need to leave Nextcloud
- **Two output modes** — download the PDF directly or save it to the current directory
- **Multiple PDF engines** — supports `wkhtmltopdf`, `dompdf`, and automatic selection
- **Admin enable switch** — administrators can enable or disable the app globally
- **Automatic cache cleanup** — a background job periodically removes expired temporary files; retention days are configurable
- **Debug logging** — optional verbose logging for troubleshooting
- **Multi-language support** — English, Simplified Chinese, Traditional Chinese, Japanese, Spanish, French, German

---

## Requirements

- **Nextcloud** 30 ~ 33
- **PHP** 8.1 or later (runs in CLI mode)
- **TigerSprite CLI** converter tool (`markdown-convert-sprite`)
- Optional PDF engines:
  - `wkhtmltopdf` (recommended, better rendering quality)
  - `dompdf` (built-in fallback)

---

## Installation

### 1. Get TigerSprite CLI

TigerSprite depends on the standalone CLI converter tool [`markdown-convert-sprite`](https://github.com/tiger-sprite/markdown-convert-sprite). Deploy it on your server first.

### 2. Install the App

Copy the `tigersprite` directory into your Nextcloud `apps/` directory:

```bash
cp -r tigersprite /path/to/nextcloud/apps/
```

Or install it via the Nextcloud App Store (pending listing).

### 3. Enable the App

Go to **Apps** → **Disabled apps** in the Nextcloud admin panel, find **TigerSprite** and enable it.

Alternatively, use the command line:

```bash
occ app:enable tigersprite
```

---

## Admin Settings

Once enabled, a **TigerSprite** configuration section appears in the admin settings. Administrators can configure the following options:

| Setting | Description | Default |
|---------|-------------|---------|
| **Enable TigerSprite** | Global feature switch | Enabled |
| **Runtime mode** | Runtime mode (V1 supports only CLI) | CLI |
| **PHP executable path** | Path to the PHP executable | `php` |
| **TigerSprite cli.php path** | Full path to the CLI converter entry point | Must be configured manually |
| **PDF generator** | PDF engine selection | `Auto` |
| **Output behavior** | Default output mode | `Download` |
| **Cache retention days** | Number of days to keep temporary files | `1` day |
| **Debug logging** | Enable detailed debug logging | Disabled |

### PDF Engine Details

- **Auto** — auto-detect: prefers `wkhtmltopdf`, falls back to `dompdf` if unavailable
- **wkhtmltopdf** — force use `wkhtmltopdf`
- **dompdf** — force use `dompdf`

---

## Usage

### Download PDF

1. Find any `.md` or `.markdown` file in the Nextcloud file list
2. Right-click the file and select **TigerSprite Export to PDF** from the context menu
3. The browser will automatically download the generated PDF

### Save to Current Directory

After setting **Output behavior** to **Save to current directory** in admin settings:
1. Right-click a Markdown file and select **TigerSprite Export to PDF**
2. The generated PDF is saved to the same directory as the source Markdown file
3. If a file with the same name already exists, a non-conflicting name is automatically generated (e.g., `document (1).pdf`)

---

## How It Works

```
User clicks menu → Frontend requests backend → Backend validates permissions and file
  → Reads Markdown content into a temporary working directory
  → Invokes TigerSprite CLI to perform conversion
  → Returns PDF for download or writes it into Nextcloud storage
  → Cleans up temporary files
```

- **Temporary files**: uses Nextcloud's temp directory facilities; each conversion request gets its own isolated working directory
- **Background cleanup**: a Nextcloud background job (`CleanupJob`) periodically removes expired cache files
- **Security isolation**: runs in CLI mode, isolated from the Nextcloud runtime environment to avoid autoloader conflicts

---

## Localization

TigerSprite uses Nextcloud's native localization mechanism. V1 supports the following languages:

- English (en)
- 简体中文 (zh_CN)
- 繁體中文 (zh_TW)
- 日本語 (ja)
- Español (es)
- Français (fr)
- Deutsch (de)

---

## Development

### Project Structure

```
tigersprite/
├── appinfo/
│   ├── info.xml           # App metadata
│   └── routes.php         # Route definitions
├── css/
│   └── tigersprite-admin.css
├── img/
│   ├── app.svg            # App icon
│   └── app-dark.svg       # Dark mode icon
├── js/
│   ├── tigersprite-main.js
│   └── tigersprite-admin.js
├── l10n/                  # Localization files
├── lib/
│   ├── AppInfo/
│   │   └── Application.php
│   ├── BackgroundJob/
│   │   └── CleanupJob.php            # Temp file cleanup
│   ├── Controller/
│   │   ├── ConversionController.php  # Conversion endpoint
│   │   └── SettingsController.php    # Settings endpoint
│   ├── Listeners/
│   │   └── FilesListener.php         # File menu registration
│   ├── Service/
│   │   ├── CommandRunner.php         # CLI executor
│   │   ├── ConfigService.php         # Configuration management
│   │   ├── TempStorageService.php    # Temp file management
│   │   └── TigerSpriteConversionService.php  # Core conversion logic
│   ├── AdminSection.php              # Admin settings section
│   └── AdminSettings.php             # Admin settings form
├── templates/
│   └── admin.php          # Admin page template
├── help/
│   └── DESIGN.md          # Design document
├── README.md
└── LICENSE
```

---

## License

Copyright (C) 2026 adam

This project is licensed under the **GNU Affero General Public License v3.0 (AGPLv3)**. See the [LICENSE](./LICENSE) file for details.
