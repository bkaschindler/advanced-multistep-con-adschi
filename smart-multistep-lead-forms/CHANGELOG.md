# Changelog
All notable changes to this project will be documented in this file.

## [1.3.2] - 2026-04-28
### Changed
- Unified admin, builder, and template text so translations are resolved through the WordPress translation system.
- Removed hard-coded manual locale branches for builder labels and the default consultation template.

## [1.3.1] - 2026-04-28
### Changed
- Expanded default admin email content to include lead metadata and submitted form details.
- Added customer name placeholder support for customer notification emails.

## [1.3.0] - 2026-04-28
### Added
- Delete action for forms on the Forms admin page while keeping existing leads.

## [1.2.9] - 2026-04-28
### Added
- Configurable admin and customer email subjects, intro text, and footer text.
- Branded HTML email design with automatic site logo/site icon support.
- Form-language-aware notification emails.
- Enhanced Interessenten page with full request details, file links, source page, search, filters, sorting, and inline lead status updates.
- Source page tracking for partial and completed submissions.

## [1.2.8] - 2026-04-28
### Added
- Multi-rule conditional logic controls in the form builder.
- Template language selector for loading ready-made templates per language.
- Admin lead pipeline status with inline updates.
- File upload preview with per-file removal before submit.
- HVAC 3D success summary after submission.

## [1.2.7] - 2026-04-28
### Changed
- Version bump after the multilingual form and HVAC branching refinements.

## [1.2.6] - 2026-04-28
### Changed
- Improved HVAC 3D select and upload-field contrast on dark backgrounds.
- File upload formats are now always resolved from global module settings.
- Replaced manual extension entry with grouped selectable format buttons in Settings.
- Added per-form language selection for multilingual sites.
- Updated the HVAC 3D template so each service category opens its own second-step questions.

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
- Global upload limit settings for file extensions, file count, and file size.

### Changed
- Refined the form builder admin interface with cleaner panels, spacing, and responsive layout.
- Improved frontend input styling and equalized clickable card heights.
- Added a modern animated HVAC 3D frontend theme with layered motion, depth, and glass panels.
- Templates are no longer installed automatically on activation; users load only the templates they want from the form builder.
- Moved upload limits out of the form builder into global module settings.
- Reduced form builder clutter with collapsible field settings.

### Fixed
- Clickable category cards now respect required fields before auto-advancing to the next step.
- File uploads are now validated against form upload settings on both client and server.
- Per-form captcha step timing now uses the saved `on_step` value consistently.
- Validation errors now render inline under fields instead of using browser alert dialogs.
- Required fields now show a visible required star beside their labels.

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
