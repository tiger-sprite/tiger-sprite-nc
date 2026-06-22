# TigerSprite for Nextcloud V1 Design

## 1. Document Purpose

This document defines the V1 product and technical design baseline for the Nextcloud app `tigersprite`.

The goal is to build a publishable Nextcloud app that adds a Markdown to PDF conversion action for supported Markdown files by integrating the existing `markdown-convert-sprite` tool.

This document is intended to be the implementation baseline for later development.

## 2. Product Positioning

- Internal app id: `tigersprite`
- Display name: `TigerSprite`
- File action label: `TigerSprite Export to PDF`
- Target platform: Nextcloud app ecosystem
- Publishing goal: follow Nextcloud app development conventions and packaging conventions

## 3. V1 Scope

### 3.1 Included in V1

1. Add a file context menu action for Markdown files.
2. The action label is `TigerSprite Export to PDF`.
3. Clicking the action converts one Markdown file to PDF.
4. Support two output behaviors:
   - Download
   - Save to current directory
5. Add an admin settings section and admin settings form.
6. Add an app-wide enable/disable switch.
7. Use Nextcloud temporary directory facilities when available.
8. Use a Nextcloud background job to clean expired cache files.
9. Support multiple languages through Nextcloud native localization.
10. V1 supports only `CLI` mode.
11. PDF generator supports `Auto`, `wkhtmltopdf`, and `dompdf`.

### 3.2 Explicitly Not Included in V1

1. No API mode.
2. No include mode.
3. No batch conversion.
4. No workflow automation.
5. No personal user settings page.
6. No group-based feature restriction.
7. No custom binary/font advanced engine configuration in the first version unless later required by implementation.

## 4. File Action Rules

### 4.1 Supported Files

V1 supports only:

1. `.md`
2. `.markdown`

Extension is the primary decision rule. MIME type can be used only as a secondary helper.

### 4.2 Single Selection Only

V1 supports conversion only when exactly one Markdown file is selected.

Rules:

1. When one supported Markdown file is selected, show `TigerSprite Export to PDF`.
2. When multiple files are selected, do not show the action.
3. When a directory is selected, do not show the action.
4. When the selected file is not a supported Markdown file, do not show the action.

This behavior intentionally matches the current V1 scope and avoids introducing batch conversion requirements.

## 5. Admin Settings Design

The admin settings section name is:

- `TigerSprite`

### 5.1 Admin Settings Fields

1. `Enable TigerSprite`
   - Type: boolean
   - Default: enabled
   - Meaning: global feature switch

2. `Runtime mode`
   - Type: select
   - Value for V1: `CLI`
   - Meaning: reserved for future extension

3. `CLI entry configuration`
   - Preferred design: split fields instead of one free-form shell string
   - Suggested fields:
     - `PHP executable path`
     - `TigerSprite cli.php path`
   - This is more maintainable and safer than a fully free-form command string

4. `PDF generator`
   - Type: select
   - Values:
     - `Auto`
     - `wkhtmltopdf`
     - `dompdf`
   - Default: `Auto`

5. `Output behavior`
   - Type: select
   - Values:
     - `Download`
     - `Save to current directory`
   - Default: `Download`

6. `Cache retention days`
   - Type: integer
   - Default: `1`
   - Minimum recommended value: `0`

7. `Debug logging`
   - Type: boolean
   - Default: disabled

### 5.2 Enable Switch Behavior

When `Enable TigerSprite` is off:

1. The file context menu action is not shown.
2. Backend conversion endpoints reject execution.
3. Existing settings remain stored.
4. Admin settings page remains visible.

Important:

Frontend hiding is not enough. Backend must also check the enable switch before converting.

## 6. Conversion Strategy

### 6.1 Runtime Mode

V1 uses only `CLI`.

Reason:

1. Best isolation from Nextcloud runtime dependencies.
2. Lower risk of autoloader conflicts.
3. Easier to debug and maintain.
4. Easier to evolve independently from the TigerSprite converter codebase.

### 6.2 Generator Strategy

Generator setting behavior:

1. If admin selects `wkhtmltopdf`, use `wkhtmltopdf`.
2. If admin selects `dompdf`, use `dompdf`.
3. If admin selects `Auto`:
   - Check whether `wkhtmltopdf` is available
   - If available, prefer `wkhtmltopdf`
   - Otherwise fall back to `dompdf`

## 7. File Processing Flow

### 7.1 Download Flow

1. User opens file context menu on one supported Markdown file.
2. User clicks `TigerSprite Export to PDF`.
3. Frontend calls app backend endpoint.
4. Backend validates:
   - app enabled
   - user permission
   - exactly one file
   - supported file type
5. Backend reads the file from Nextcloud storage.
6. Backend writes the Markdown content to a temporary working file.
7. Backend runs TigerSprite CLI to generate PDF in a temporary working directory.
8. Backend returns the PDF as a download response.
9. Temporary files are left for scheduled cleanup unless safely removed earlier.

### 7.2 Save To Current Directory Flow

1. User opens file context menu on one supported Markdown file.
2. User clicks `TigerSprite Export to PDF`.
3. Backend validates:
   - app enabled
   - read permission on source file
   - create/write permission in current directory
4. Backend reads the file from Nextcloud storage.
5. Backend creates temporary source and output files.
6. Backend runs TigerSprite CLI.
7. Backend writes the generated PDF back to the same directory in Nextcloud storage.
8. Naming rule:
   - First try `original-name.pdf`
   - If already exists, create a non-conflicting name such as `original-name (1).pdf`

Important:

TigerSprite should write to a temporary location first. The Nextcloud app should then write the resulting PDF into Nextcloud storage. Do not let the converter write directly into user storage as the main strategy.

## 8. Temporary Files and Cleanup

### 8.1 Temporary Directory Policy

Use Nextcloud temporary directory support when available.

Preferred approach:

1. Use Nextcloud temp manager for working files and working directories.
2. Create an isolated work directory per conversion request.
3. Keep all intermediate files inside the app-owned temporary work area.

### 8.2 Cleanup Policy

Use a Nextcloud background job for cleanup.

Cleanup rules:

1. Cleanup job runs on schedule.
2. It removes only app-owned temporary files/directories.
3. It removes files older than configured retention days.
4. Cleanup failure should be logged but must not break the app.

## 9. Permissions and Validation Rules

### 9.1 Admin Permissions

Only administrators can change TigerSprite admin settings.

### 9.2 User Permissions

For download behavior:

1. User must have permission to read the Markdown file.

For save-to-current-directory behavior:

1. User must have permission to read the Markdown file.
2. User must have permission to create/write a file in the current directory.

### 9.3 Backend Validation

Every conversion request must validate:

1. App is enabled.
2. User is authenticated.
3. File exists.
4. File is a supported Markdown file.
5. Request is single-file only.
6. Requested output behavior is allowed.
7. User has required permissions.

## 10. Logging

### 10.1 User-Facing Messages

User-facing messages should be short and friendly.

Examples:

1. Conversion completed.
2. Conversion failed.
3. TigerSprite is disabled by the administrator.
4. This action supports only one Markdown file at a time.

### 10.2 Admin/Debug Logs

Use Nextcloud logger.

Recommended fields:

1. User id
2. File id or path
3. Output behavior
4. Generator selection result
5. CLI execution target
6. Duration
7. Exit code
8. Error summary

If debug logging is enabled, log more execution detail.

## 11. Localization

Use only Nextcloud native `l10n`.

Do not introduce an additional standalone `i18n` framework in V1.

Reason:

1. Native fit for Nextcloud apps
2. Cleaner integration for PHP and frontend
3. Better long-term maintainability
4. Better fit for standard app packaging and translation flow

### 11.1 Target Languages for V1

1. `en`
2. `zh_CN`
3. `zh_TW`
4. `ja`
5. `es`
6. `fr`
7. `de`

### 11.2 Translation Coverage

Translate at least:

1. App name
2. Admin settings labels
3. Help texts
4. File action label
5. Success and error messages
6. Any user-visible dialog text

## 12. Nextcloud Standard Compliance

The app should be built as a standard independent Nextcloud app.

Guidelines:

1. Do not modify Nextcloud core.
2. Use standard app structure.
3. Use standard app metadata files.
4. Use standard admin settings extension points.
5. Use standard file action integration points.
6. Use standard Nextcloud storage APIs.
7. Use standard Nextcloud temporary directory support.
8. Use standard Nextcloud background job support.
9. Use standard Nextcloud logging.
10. Use standard Nextcloud localization.

## 13. Main Risks

### 13.1 CLI Execution Availability

Risk:

Some deployments disable command execution or restrict the web server user.

Mitigation:

1. Add runtime check tooling in admin settings later.
2. Provide clear error messages for admin diagnosis.

### 13.2 Storage Abstraction

Risk:

Not every Nextcloud file maps to a simple local filesystem path.

Mitigation:

1. Read file content through Nextcloud APIs.
2. Write temporary source/output files into app temp area.

### 13.3 Long Running Conversion

Risk:

Slow conversion may lead to request timeout or poor UX.

Mitigation:

1. Keep V1 synchronous for simplicity.
2. Keep implementation ready for future async extension.

### 13.4 Rendering Compatibility

Risk:

Chinese text, fonts, images, and Markdown edge cases may render differently depending on generator.

Mitigation:

1. Prefer `Auto` with runtime fallback.
2. Test representative Markdown samples early.

## 14. V1 Frozen Baseline

The V1 baseline is:

1. App id `tigersprite`
2. Display name `TigerSprite`
3. File action label `TigerSprite Export to PDF`
4. Single Markdown file only
5. No multi-select support
6. No batch conversion
7. Admin enable switch
8. CLI-only runtime
9. Generator options `Auto / wkhtmltopdf / dompdf`
10. Output behavior `Download / Save to current directory`
11. Nextcloud temp directory integration
12. Nextcloud background cleanup
13. Nextcloud native localization
14. Publishable structure aligned with Nextcloud conventions

## 15. Next Design Step

After this document, the next recommended design documents are:

1. Admin settings field specification
2. File action request/response contract
3. Backend service layer and CLI boundary definition
4. Localization string list
