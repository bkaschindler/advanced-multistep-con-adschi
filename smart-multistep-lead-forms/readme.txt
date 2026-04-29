=== Smart MultiStep Lead Forms ===
Contributors: mohammadbabaei
Tags: forms, multistep, ajax, leads, drag and drop
Requires at least: 5.9
Requires PHP: 7.4
Tested up to: 6.9.1
Stable tag: 1.3.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A visual multi-step lead form builder for WordPress with AJAX submission, partial lead saving, captcha options, webhooks, email notifications, and CSV export.

== Description ==

Smart MultiStep Lead Forms lets site owners create shortcode-based, multi-step lead forms from the WordPress admin area. Forms can be embedded with `[smlf_form id="123"]`, submitted without a page reload, and saved as partial leads when important fields are entered.

Features:
* Unlimited shortcode forms and steps.
* Drag-and-drop form builder with edit support.
* Field-level layout controls for full, half, and one-third width fields.
* Choice fields can be displayed as tap cards, dropdowns, or list-style options.
* Consent checkbox block with configurable privacy links and popup content.
* Per-form appearance controls for theme, font family, and key colors.
* Text, email, phone, long text, message, card, radio, and drag-and-drop file upload fields.
* Ready-made localized templates that can be loaded manually from the form builder.
* Template language selector for multilingual sites.
* HVAC 3D sample template for heating and cooling companies.
* Multi-rule conditional step logic.
* Lead pipeline status tracking in the admin area.
* Colored Prospects overview cards for request totals and daily activity.
* File upload preview with per-file removal before submit.
* Modern animated frontend theme with lightweight 3D card interactions.
* Required/email validation in the browser and sanitized server-side processing.
* Partial lead auto-save when enabled.
* AJAX final submission with server-side captcha verification.
* Captcha options: none, custom checkbox, Google reCAPTCHA v2/v3, and Cloudflare Turnstile.
* Admin and user email notifications with email logs.
* Configurable branded email templates with automatic site logo support.
* Webhook delivery for partial and completed leads.
* CSV export for leads with nonce and capability checks.
* RTL and translation-file support.
* Developed by Mohammad Babaei (https://adschi.com)

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/smart-multistep-lead-forms`, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the Plugins screen in WordPress.
3. Open Smart Forms, create a form, and copy its shortcode.
4. Add the shortcode to a page or post, for example: `[smlf_form id="123"]`.

== Settings ==

* Admin Notification Email: address that receives completed lead notifications.
* Email Templates: customize admin/customer email subjects, intro text, footer text, and placeholders.
* Enable Partial Lead Saving: saves started/partial leads before final submit.
* Webhook URL: receives JSON payloads for `smlf_lead_partial` and `smlf_lead_completed`.
* Anti-bot / Captcha Method: choose none, custom checkbox, reCAPTCHA v2/v3, or Turnstile.
* Captcha Site Key and Secret Key: required for third-party captcha providers.
* Upload Limits: configure allowed file extensions, maximum file count, and maximum file size globally.
* Data on Uninstall: choose whether plugin tables and options should remain or be fully deleted when the plugin is uninstalled.

Webhook payload example:

`{"event":"smlf_lead_completed","lead_id":10,"form_id":3,"data":{"smlf_field_email":"person@example.com"}}`

== Frequently Asked Questions ==

= How do I embed a form? =
Use the shortcode shown in Smart Forms, such as `[smlf_form id="123"]`.

= Does it support RTL? =
Yes, the plugin loads RTL styles when WordPress is running in RTL mode.

= Does CSV export require admin access? =
Yes. CSV export requires `manage_options` and a valid export nonce.

== Changelog ==

= 1.3.4 =
* Added colored Prospects overview cards for total requests, completed requests, auto-saved requests, and new requests today.

= 1.3.3 =
* Added bulk request deletion on the Prospects page, including selected-request deletion and delete-all controls.
* Added confirmation prompts, selection counts, and cleanup of related email logs when requests are deleted.

= 1.3.2 =
* Unified admin, builder, and template text so translations are resolved through WordPress translation files.
* Removed hard-coded manual locale branches for builder labels and the default consultation template.

= 1.3.1 =
* Expanded default admin email content to include lead metadata and submitted form details.
* Added customer name placeholder support for customer notification emails.

= 1.3.0 =
* Added a delete action for forms on the Forms admin page while keeping existing leads.

= 1.2.9 =
* Added configurable admin and customer email templates.
* Added branded HTML email layout with automatic site logo/site icon support.
* Added form-language-aware notification emails.
* Enhanced Interessenten with full request details, files, source page, search, filters, sorting, and inline lead status updates.
* Added source page tracking for submissions.

= 1.2.8 =
* Added multi-rule conditional logic controls in the form builder.
* Added template language selection for ready-made templates.
* Added admin lead pipeline status with inline updates.
* Added file upload preview with per-file removal before submit.
* Added HVAC 3D success summary after submission.

= 1.2.7 =
* Version bump after the multilingual form and HVAC branching refinements.

= 1.2.6 =
* Improved HVAC 3D select and upload-field contrast for dark layouts.
* Upload file formats are now always read from global module settings.
* Replaced the upload extension text field with grouped selectable format buttons in Settings.
* Added per-form language selection for multilingual sites.
* Updated the HVAC 3D template so each service category opens its own second-step questions.

= 1.2.5 =
* Fixed required-field validation when category cards auto-advance to the next step.
* Improved frontend input styling and equalized clickable card heights.
* Added configurable upload limits for allowed extensions, file count, and file size.
* Added server-side upload validation based on each form's settings.
* Improved the form builder interface and added admin overview statistic cards.
* Added field width, display mode, font, color, and theme controls.
* Added a modern animated HVAC 3D sample template.
* Added consent checkbox block with linked text, default state, new-tab links, WordPress page popups, and custom popup text.
* Added an uninstall data policy setting to keep or delete all plugin data when uninstalling.
* Changed templates so they are no longer installed automatically; users load the templates they want from the builder.
* Moved upload limits into global plugin settings.
* Reworked validation to show animated inline field errors instead of browser alerts.
* Added required stars beside required field labels.
* Reduced builder clutter with collapsible field setting panels.

= 1.2.4 =
* Fixed frontend template safety for older/cached form schemas.
* Reduced Divi layout conflicts by keeping frontend styling more tightly contained.

= 1.2.3 =
* Added server-side initial display states so Divi/deferred scripts do not leave the form blank.

= 1.2.2 =
* Improved embedded form layout and captcha gate rendering on frontend pages.
* Reduced layout conflicts with WordPress themes and page builders.

= 1.2.1 =
* Fixed builder block click/drag handling and live preview rendering.
* Added per-form captcha method and display timing settings.
* Improved frontend layout isolation, form height, and captcha overlay interaction.

= 1.2.0 =
* Added animated consultation-ready frontend theme.
* Added default localized free consultation form template.
* Added textarea, message, and drag-and-drop file upload fields.
* Added automatic partial save only after valid email input.
* Added lead detail/file preview in the admin leads list.

= 1.1.0 =
* Hardened AJAX form save, partial lead save, final submit, captcha verification, and CSV export.
* Fixed editing existing forms in the builder.
* Added stable field IDs and required-field metadata while preserving existing form data.
* Added frontend required/email validation and safer AJAX error handling.
* Added settings sanitization and nonce-protected CSV export.
* Documented shortcode usage, captcha settings, webhook payloads, and compatibility targets.

= 1.0.0 =
* Initial release.
