# Copy Modules

Copy one or more Moodle course activities and paste them into another section or course.

## Clipboard UX

Version 1.2 replaces the old repeated "Paste here" buttons with a persistent clipboard bar. The teacher can copy an activity or use Moodle bulk selection, open another course, click **Paste**, choose the destination section and choose the exact insertion position.

The clipboard remains available after a paste, so the same activities can be pasted into multiple courses without returning to the source course. Use **Clear** to empty it.

## Security and reliability

Copy, paste and clear operations use POST requests protected by Moodle `sesskey`. The UI is only loaded when the current user has `local/copy:manage` in the course. Source and destination permissions are revalidated during paste.

Paste failures are reported per activity. Administrators receive the underlying restore error text while regular users receive a safe message. Failed pastes do not erase the clipboard.
