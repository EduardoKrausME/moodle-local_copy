# Changes in 1.2.0

- Replaced repeated "Paste here" buttons with a persistent clipboard bar.
- Added a paste modal with destination section and exact position selection.
- Clipboard now uses `$SESSION->local_copy` and remains available after paste.
- Added migration of clipboard data stored by older versions in `$USER`.
- Copy, paste and clear use AJAX POST requests protected by `sesskey`.
- Added a clear clipboard action.
- The copy UI is only loaded when the user has `local/copy:manage`.
- Changed the capability context level to course context.
- Module IDs are parsed safely instead of stripping all non-digits from DOM IDs.
- Module names are resolved server-side instead of trusting values sent by JavaScript.
- Paste destination resolves explicit `course_sections.id` first and section number as fallback.
- Source and destination capabilities are revalidated before restore.
- Paste failures no longer clear the clipboard.
- Restore errors are tracked per activity; administrators receive the underlying exception message.
- Added Brazilian Portuguese strings.
- Added readable `styles.scss` source while keeping compiled `styles.css` for Moodle automatic loading.
