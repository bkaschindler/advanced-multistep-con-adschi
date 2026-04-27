=== Smart MultiStep Lead Forms ===
Contributors: mohammadbabaei
Tags: forms, multistep, ajax, leads, drag and drop
Requires at least: 5.9
Requires PHP: 7.4
Tested up to: 6.9.1
Stable tag: 1.2.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A visual multi-step lead form builder for WordPress with AJAX submission, partial lead saving, captcha options, webhooks, email notifications, and CSV export.

== Description ==

Smart MultiStep Lead Forms lets site owners create shortcode-based, multi-step lead forms from the WordPress admin area. Forms can be embedded with `[smlf_form id="123"]`, submitted without a page reload, and saved as partial leads when important fields are entered.

Features:
* Unlimited shortcode forms and steps.
* Drag-and-drop form builder with edit support.
* Text, email, phone, long text, message, card, radio, and drag-and-drop file upload fields.
* Ready-made localized free consultation form template created on activation.
* Modern animated frontend theme with lightweight 3D card interactions.
* Required/email validation in the browser and sanitized server-side processing.
* Partial lead auto-save when enabled.
* AJAX final submission with server-side captcha verification.
* Captcha options: none, custom checkbox, Google reCAPTCHA v2/v3, and Cloudflare Turnstile.
* Admin and user email notifications with email logs.
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
* Enable Partial Lead Saving: saves started/partial leads before final submit.
* Webhook URL: receives JSON payloads for `smlf_lead_partial` and `smlf_lead_completed`.
* Anti-bot / Captcha Method: choose none, custom checkbox, reCAPTCHA v2/v3, or Turnstile.
* Captcha Site Key and Secret Key: required for third-party captcha providers.

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
