# Changelog

## [0.2.0] – 2026-08-13

### Fixed

- File action no longer appears in Nextcloud 33 file list: the "TigerSprite Export to PDF" action was registered into the legacy `_nc_fileactions` registry, which the Nextcloud 33 files app no longer reads
- Register the action into the `@nextcloud/files` v4 registry (`window._nc_files_scope.v4_0.fileActions`) used by NC 30+ (including NC 33), with a fallback to the legacy registry for older versions
- Make the `enabled`/`exec` callbacks compatible with the new context-object signature (`{ nodes, view, folder, contents }`) in addition to the old bare-array signature

## [0.1.0] – 2026-06-22

### Added

- Initial release of TigerSprite
- One-click Markdown-to-PDF export from file context menu
- Two output modes: download PDF or save to current directory
- Multiple PDF engines support: `wkhtmltopdf` and `dompdf` with automatic selection
- Admin settings with global enable/disable switch
- Configurable PHP and CLI converter paths
- Configurable cache retention days with automatic cleanup
- Debug logging for troubleshooting
- Multi-language support: English, Simplified Chinese, Traditional Chinese, Japanese, Spanish, French, German
