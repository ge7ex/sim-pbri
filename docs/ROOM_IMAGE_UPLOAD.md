# Room image upload

## Usage

Admin: ทรัพยากร SIM → เพิ่มทรัพยากร (ประเภทห้อง) หรือ แก้ไขข้อมูลห้อง → รูปห้อง → เลือกไฟล์ → บันทึก

Each room supports one image. Administrators can replace or remove it. Selecting a new image provides a local preview; cancelling that selection restores the existing image. Changes take effect on save. Existing rooms without images retain the honest placeholder and remain bookable under existing rules.

## Storage and access

- Accept JPEG, PNG and WebP, at most 5 MiB and 3000 × 3000 pixels. PHP GD is required.
- Decode and encode a new JPEG, longest edge at most 1600 px, white transparency background, quality 85. Original filenames, metadata and trailing payloads are not stored.
- Private dedicated local disk: `storage/app/private/room-images/{college_id}/{uuid}.jpg`. No public storage symlink or public image URL.
- Authenticated `/app/resources/{id}/image` enforces the user's college and ResourceView or BookingCreate permission. Lecturer access is limited to viewing images in their own college; it does not grant resource management.
- Responses use image/jpeg, nosniff and private/no-store. Internal paths are hidden from serialized resources. A version hash refreshes the image URL after replacement.
- Only Admin may mutate through the existing resource permissions. Multipart edits use POST with `_method=put`. Metadata-only updates preserve the existing image.
- Resource metadata and image audit (resource, actor, action, timestamp) commit in one database transaction under a resource lock. New files are cleaned on database failure. Old files are deleted after commit; cleanup failures are logged without exposing private paths or reporting a committed update as failed.

## Deployment on this XAMPP installation

- Source: `C:\xampp\htdocs\sim-pbri`, branch `backend/inertia-foundation`.
- Enabled the already installed GD extension in `C:\xampp\php\php.ini`; prior file saved as `C:\xampp\php\php.ini.before-room-images.bak`. Stop/Start Apache once to load GD and discard its previous opcode cache. No other PHP setting was changed.
- Applied only `2026_10_07_000014_add_room_images`: nullable image_path and sim_resource_image_changes audit table. Existing row counts before/after: users 1/1, colleges 1/1, bookings 0/0, resources 2/2, assets 0/0. No runtime reset or seed.
- Backups should include both the database and private room-image directory. A crash between writing a new file and committing metadata can leave a private orphan; no public exposure occurs. There is no automatic orphan reconciliation job in this feature. Call the save service at the request transaction boundary, not inside an additional external transaction.

## Fresh verification

- Guarded SQLite in-memory suite: `php artisan test --compact`: **125 passed, 953 assertions**. Eight image tests cover formats, resize/reencode, path hiding, audited actor, login/role/college denial, multipart replacement/removal, metadata preservation, invalid/SVG/GIF/oversized/overdimension files, trailing payload removal, path traversal and DB-failure cleanup.
- `php vendor/bin/pint --dirty --test`: passed.
- `npm run build`: passed, 668 modules. Built assets are present in the actual XAMPP public/build directory.
- Local Chrome against synthetic accounts and isolated SQLite: add, replace, remove, authenticated image response, booking photo display, cancelled selection preserving the original. Admin and booking layouts at 1440 and 390 px have no document overflow and no page errors. Expected 404 after removal is a successful negative check.
- Independent QA database/filesystem inspection confirmed uploaded → replaced → removed audit actions and zero private files remaining after removal, exercising real after-commit deletion.
- QA server runs with opcache disabled to avoid sharing stale cached function indexes with the existing Apache process after GD activation. Real-account upload in the Apache process has not been exercised; restart Apache before using the feature there.

Screenshots remain outside the repository under the workspace output/playwright directory. Temporary QA database, sessions and credentials are removed after verification.
