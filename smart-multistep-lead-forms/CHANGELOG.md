# Changelog
All notable changes to this project will be documented in this file.

## [1.2.5] - 2026-04-28
### Added
- Configurable per-form upload limits for allowed extensions, maximum file count, and maximum file size.
- Overview statistic cards on the admin Forms page.
- Field-level layout controls for full, half, and one-third width fields.
- Field display controls for card, dropdown, and list-style choices.
- Form appearance controls for theme, font family, and core colors.
- HVAC 3D sample template for heating and cooling service requests.
- Consent checkbox block with configurable linked text, default checked state, new-tab links, WordPress page popups, and custom text popups.
- Setting to keep or fully delete plugin data when the plugin is uninstalled.

### Changed
- Refined the form builder admin interface with cleaner panels, spacing, and responsive layout.
- Improved frontend input styling and equalized clickable card heights.
- Added a modern animated HVAC 3D frontend theme with layered motion, depth, and glass panels.
- Templates are no longer installed automatically on activation; users load only the templates they want from the form builder.

### Fixed
- Clickable category cards now respect required fields before auto-advancing to the next step.
- File uploads are now validated against form upload settings on both client and server.
- Per-form captcha step timing now uses the saved `on_step` value consistently.

## [1.2.3] - 2026-04-27
### Fixed
- Added server-side initial display states so Divi or deferred JavaScript does not leave only the form shell visible.

## [1.2.2] - 2026-04-27
### Fixed
- Improved embedded frontend form layout so captcha gates do not collapse or disrupt page content.
- Reduced CSS conflicts with WordPress themes and page builders.

## [1.2.1] - 2026-04-27
### Fixed
- Builder blocks now support both click-to-add and drag-and-drop reliably.
- Live preview no longer stays empty while editing.
- Frontend form wrapper has safer height/layout behavior and captcha overlay interaction.

### Added
- Per-form captcha method and display timing controls.

## [1.2.0] - 2026-04-27
### Added
- Animated consultation-ready frontend theme with lightweight 3D interactions.
- Default localized free consultation form template on activation.
- Textarea, message, and drag-and-drop file upload field support.
- Lead detail and uploaded-file preview in the admin leads list.

### Changed
- Partial lead auto-save now waits for valid email input before saving email-based leads.
- Frontend submit now uses FormData for better compatibility with file uploads and page builders.

## [1.1.0] - 2024-05-30
### Added
- Added Webhook integrations for saving leads to external services (Zapier, etc).
- Configurable multiple Anti-bot types (None, Custom, reCAPTCHA v2/v3, Turnstile).
- Leads management enhancement: Added filtering by Form ID, Status, and Export to CSV functionality.
- Conditional logic capability in form builder.

## [1.0.0] - 2024-05-30
### Added
- Initial release of Smart MultiStep Lead Forms.
- Visual drag-and-drop builder.
- AJAX submissions and partial lead saves.
- Anti-bot system.
- Email logging.
- Multilingual and RTL support.
- Developed by Mohammad Babaei (https://adschi.com).
