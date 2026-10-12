# Access Management Operations

## College Admin creates accounts

1. Sign in with an active Admin account.
2. Open **บัญชีผู้ใช้ → จัดการบุคลากร**. The list and search are restricted to the Admin's College on the server.
3. Create a Lecturer, Staff, or Admin account with the person's name and unique sign-in email. The College is taken from the signed-in Admin; the form does not accept a College ID.
4. Copy the generated temporary password from the one-time response and deliver it through an institution-approved private channel. The application does not email or persist the plaintext password.
5. The new user signs in and must change the temporary password before opening other application pages.

An Admin may edit another account's name, role, or active state. Admins cannot change their own role or disable their own account. The application prevents demoting or disabling the last active Admin in a College. Account changes write an append-only event with actor, subject, event type, and role/status changes; password values and email addresses are excluded from event details. Accounts are not deleted because historical records reference users.

## First Admin bootstrap

After applying the additive migrations to the intended database, run `php artisan sim:bootstrap-admin` from a trusted server console. The command refuses to run if any Admin account already exists. It prompts for a College only when none exists; if several Colleges exist, the operator selects the correct existing College. It prompts for the first Admin's name, unique email, and confirmed password without printing the password. No public registration route is provided.

The operator must use a secret-capable console, keep access to the database/server restricted, and share the chosen password with the Admin through an approved private channel. Never put the password in a shell argument, environment file, report, or Git.

## Password recovery and rotation

- Any signed-in user can change their own password at **บัญชีผู้ใช้ → เปลี่ยนรหัสผ่าน** after confirming the current password.
- A College Admin can issue a temporary password to another same-College account after re-entering the Admin's current password. The target must change it at next sign-in. Password reset expires existing authenticated sessions on their next request.
- If no Admin can sign in, a trusted operator may run `php artisan sim:recover-admin-password {admin-email}` from the server console. It accepts only an existing active Admin, prompts for a hidden confirmed password, forces a password change, and records the recovery event. It does not reactivate a disabled account.
- Email-based invitation, email verification, and self-service email recovery are not configured because no outbound email/identity provider was verified. They remain an integration gate; do not send generated credentials through unapproved channels.

## Database change

Migrations `2026_10_12_000015_add_account_management_fields_to_users_table` and `2026_10_12_000016_add_booking_resource_snapshots` are additive: existing accounts receive active/default password-state values and the new access-event table is created; booking-resource snapshot columns are nullable. They do not reset, delete, or replace users/bookings. Test migrations use guarded SQLite `:memory:`. Both migrations were applied to the local runtime MySQL on 12 October 2026 after a `--pretend` review; no application fixtures were seeded there. Apply migrations to any deployment database only through its approved migration and backup procedure.
