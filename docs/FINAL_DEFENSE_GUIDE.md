# Alumni Link Final Defense Guide

## 1. One-sentence description

**Alumni Link is a role-based web information and service portal that helps St. Bridget College maintain alumni records, verify alumni accounts, publish school updates, manage event registrations, and track alumni requests from submission to completion.**

This sentence should be the center of the defense. The system is not merely a website or a CRUD application. It connects alumni identity, school records, services, communications, and administrative workflows in one system.

## 2. Suggested opening statement

> Good day, panel members. Our project is Alumni Link, a web-based alumni information and service management system for St. Bridget College. We developed it to address fragmented alumni records, manual account verification, scattered announcements, and requests that are difficult to monitor. The system provides two controlled user roles: administrator and alumni. Administrators manage and import alumni information, verify account requests, publish announcements, activities, and events, process alumni requests, and respond to event registrations. Alumni can create or claim an account, verify their Gmail through OTP, view school updates, register for events, submit requests, and monitor their status. The main contribution of the project is an end-to-end, traceable workflow that combines alumni data management, identity checking, communication, and service processing in a single portal.

## 3. Problem addressed by the system

The defense should begin with the problem, not with the programming language.

The system addresses these practical problems:

- Alumni data may be distributed across spreadsheets, paper records, or separate files.
- Graduates may lose or forget their old Student ID.
- The school needs to distinguish real alumni from unverified visitors.
- Announcements, activities, and event information may be scattered across different channels.
- Alumni requests are difficult to track when handled through messages or manual logs.
- Alumni may not know whether a request is pending, being processed, ready for pickup, completed, or rejected.
- Administrators need efficient search, import, export, and account-management tools.

## 4. Main objectives

The system's objectives are to:

1. Centralize alumni profiles and academic information.
2. Provide controlled self-registration and alumni-record claiming.
3. Protect portal access through administrator approval, role authorization, passwords, and Gmail OTP verification.
4. Let administrators import, search, update, group, and export alumni records.
5. Give alumni a traceable way to submit service requests.
6. Allow the school to publish announcements, activities, and events.
7. Manage event registrations and administrator replies.
8. Notify users about important account, request, and registration updates.

## 5. The most important parts to defend

### A. Alumni identity and account workflow

This is the strongest feature to emphasize because it connects the public registration page, alumni database, administrator approval, and portal security.

There are two registration situations:

1. **The alumnus still knows the Student ID.** The system searches the existing alumni records using Student ID variants. It also compares the first name, last name, and graduation year before allowing an existing record to be claimed. This helps connect the portal account to the correct institutional record.
2. **The alumnus has forgotten or lost the Student ID.** Student ID is optional. The system creates a unique internal temporary identifier and shows no Student ID to users. The account remains pending until an administrator reviews and approves it.

The important defense statement is:

> Making Student ID optional improves inclusion without removing administrative control. Alumni who still know their ID can use it for easier record matching, while alumni who no longer have it can still apply. A no-ID application remains subject to administrator review.

The account states are `pending`, `approved`, and `rejected`. Only an approved alumni account can enter the protected portal. On first successful access, the alumnus must verify a six-digit OTP sent to the registered Gmail account.

### B. Centralized alumni record management

Administrators can:

- Create, edit, search, group, and delete alumni records.
- Import CSV, TSV, TXT, and XLSX alumni files.
- Recognize variations in spreadsheet headers and Student ID formats.
- Update matching records during re-import.
- Export the searchable alumni list to Excel.
- Link an alumni record to its portal user account.
- Manage school levels and programs used by registration.

This is important because it lets the school retain its existing spreadsheet data while gradually moving to a controlled database.

### C. Traceable alumni-request workflow

An authenticated alumnus can submit supported requests such as an Alumni ID, Year Book, or facility-use message. The administrator can move each request through these states:

`Pending -> Processing -> Ready for Pickup -> Claimed`

A request can also be rejected when necessary. Each status change can store administrator notes, the administrator responsible, timestamps, and a separate status-history entry.

The defense statement is:

> The request module is more than CRUD because it implements a controlled business process with ownership, status transitions, history, administrator feedback, and user notifications.

### D. Communication and engagement

Administrators can publish announcements, activities, and events with supported media. Alumni and public visitors can view published content, while unpublished content remains under administrator control. The system also records content view counts.

For events, alumni can submit registration details. Administrators can mark the decision as pending, registered, or declined and send a reply. Email and Firebase web-push notifications are used for supported updates.

### E. Security and quality controls

The most important implemented safeguards are:

- Laravel authentication and session handling.
- Password hashing instead of storing plain-text passwords.
- CSRF protection on Laravel web forms.
- Server-side validation for submitted fields.
- Administrator approval for new unmatched alumni accounts.
- Role middleware that separates administrator and alumni routes.
- OTP middleware that protects the alumni dashboard and requests.
- Six-digit OTP codes with a 10-minute lifetime.
- A limit of five incorrect OTP attempts.
- A 30-second OTP resend cooldown.
- Hashed OTP values stored in the session rather than plain text.
- Unique-email and record-conflict checks.
- Database transactions for operations such as importing and request-status updates.
- Feature tests for authorization, validation, imports, account workflows, OTP, password reset, requests, event registration, and notifications.

Do not claim that the system provides perfect security. A stronger and more honest answer is:

> The prototype applies layered application controls, but a production deployment would also require HTTPS, secured server configuration, encrypted backups, formal privacy and retention policies, monitoring, and regular security updates.

## 6. How the system is organized

The project follows Laravel's Model-View-Controller approach with service classes for complex operations.

```text
User's browser
      |
      v
routes/web.php
      |
      v
Authentication, role, and OTP middleware
      |
      v
Controller validation and workflow
      |
      +----> Service classes for import, export, syncing, and content
      |
      v
Eloquent models <----> MySQL database
      |
      v
Blade views, redirects, email, and push notifications
```

Example to explain:

> When an alumnus submits a request, the route sends it to `PortalRequestController`. The controller validates the input and confirms that the user is an authenticated, OTP-verified alumnus. The `RecordRequest` model saves the request in the database. Administrators access a separate protected route to update its status. The update is recorded in the status-history table, and the system attempts to notify the alumnus.

## 7. Important folders in the screenshot

Do not spend equal time on every folder. Present them in this order:

| Folder or file | Purpose | Importance during defense |
|---|---|---|
| `app/` | Controllers, models, middleware, notifications, and service classes containing the main business logic | Highest |
| `routes/web.php` | Defines public, administrator, and alumni URLs and applies access middleware | Highest |
| `database/` | Migrations, seeders, and database structure | Highest |
| `resources/views/` | Blade pages and reusable user-interface templates | High |
| `tests/Feature/` | Automated tests of complete user workflows and access rules | High |
| `config/` | Application and integration configuration | Medium |
| `public/` | Public assets such as CSS, JavaScript, icons, and the web entry point | Medium |
| `storage/` | Runtime files, logs, cache, and protected uploaded files | Medium |
| `bootstrap/` | Starts Laravel and registers middleware aliases | Medium |
| `.env` | Environment-specific database, email, and Firebase settings | Important but secret |
| `composer.json` | PHP and Laravel dependencies | Mention briefly |
| `package.json` | Front-end build and Firebase dependencies | Mention briefly |
| `vendor/` | Installed PHP packages managed by Composer | Do not explain in detail |
| `node_modules/` | Installed JavaScript packages managed by npm | Do not explain in detail |
| `.agents/`, `.codex/`, `tmp/`, and XLSX test artifacts | Development or testing support files | Not part of the main user workflow |

Never display the actual contents of `.env` during the defense because it can contain database, email, and Firebase credentials.

## 8. Technology stack and why it was selected

- **PHP 8.2 and Laravel 12:** provide MVC organization, routing, authentication support, validation, ORM, migrations, notifications, and testing tools.
- **MySQL/MariaDB in the XAMPP environment:** provides persistent relational storage suitable for linked alumni, user, request, and event data.
- **Blade, HTML, CSS, and JavaScript:** create the server-rendered and responsive user interface.
- **Vite and npm:** build and manage front-end assets.
- **Firebase Cloud Messaging:** supports browser push notifications when configured and permitted by the user.
- **Laravel mail notifications:** send OTP, approval, password-reset, request, and registration messages.
- **Pest/PHPUnit:** automate feature and unit testing.

Suggested answer to “Why Laravel?”:

> Laravel gave us a structured MVC architecture and mature components for routing, validation, database relationships, middleware, password hashing, notifications, migrations, and automated tests. This reduced duplicated code and made access control and future maintenance clearer than building the same features from raw PHP.

## 9. Main database relationships

| Relationship | Meaning |
|---|---|
| Alumni to User: one-to-zero-or-one | An alumni record may exist before a portal account is created or claimed |
| Alumni to Requests: one-to-many | One alumnus can submit multiple service requests |
| Request to Status History: one-to-many | Every request can retain its progress history |
| User to Processed Requests: one-to-many | The system records which administrator processed a request |
| Event to Event Registrations: one-to-many | One event can receive registrations from many attendees |
| User/Alumni to Event Registrations | A registration can be associated with the signed-in portal identity and alumni record |
| Academic Program records grouped by school level | Registration choices are controlled by administrator-managed data |

Suggested answer to “Why are `users` and `alumni` separate?”:

> The `users` table is responsible for authentication, role, approval status, password, and OTP state. The `alumni` table stores institutional and profile information such as Student ID, name, level, program, and graduation year. Keeping them separate allows the school to import alumni records even before those alumni create portal accounts.

## 10. Recommended live-demo sequence

Keep the demo focused on one connected story rather than opening every page.

### Before the defense

- Prepare one administrator account and one approved alumni account.
- Prepare a second email for a new pending registration.
- Open the administrator and alumni accounts in separate browser profiles.
- Test email and Firebase configuration before presentation day.
- Prepare a small, clean XLSX file for import.
- Back up the database.
- Have screenshots or a short recording in case the network or mail server fails.
- Never expose `.env`, database passwords, email passwords, or Firebase service-account files.

### Suggested 8-minute demonstration

1. **Landing page:** briefly show published announcements, activities, and events.
2. **Registration:** show that Student ID is optional and explain why.
3. **Pending account:** switch to the administrator and show the review/approval page.
4. **Secure login:** explain password authentication, approval, and first-login Gmail OTP. Use a prepared verified account if live email is slow.
5. **Alumni dashboard:** show profile information and current school content.
6. **Request submission:** submit an Alumni ID or Year Book request.
7. **Administrator processing:** update it to Processing or Ready for Pickup and add a note.
8. **Alumni tracking:** show the updated status and history on the alumni side.
9. **If time remains:** demonstrate Excel import/search/export or event registration and reply.

End the demonstration by returning to the problem:

> This workflow replaces disconnected records and message-based follow-ups with a centralized, role-controlled, and traceable process.

## 11. Likely panel questions and suggested answers

### What makes this more than a basic CRUD system?

> The project includes identity matching, account approval, Gmail OTP verification, role-based authorization, Excel data migration, linked alumni and user records, status-history tracking, event-registration decisions, and email/push notifications. These features implement complete school workflows rather than only adding, editing, and deleting records.

### Why did you make Student ID optional?

> Some alumni graduated years ago and may have lost or forgotten the ID. Requiring it would prevent legitimate alumni from applying. If they provide it, the system can match an existing record more efficiently. If they do not, the system uses an internal temporary identifier and requires administrator review before access is approved.

### Does an optional Student ID create a security risk?

> It changes the verification route but does not immediately grant portal access. A no-ID account remains pending for administrator review. Access still requires a valid account, password, approval, and Gmail OTP. For production, the school could add registrar-assisted document verification for higher assurance.

### How do you prevent a person from claiming someone else's record?

> The current system checks Student ID together with first name, last name, and graduation year, rejects conflicting linked accounts, and requires Gmail OTP before portal use. We recognize that stronger production identity proofing could include matching the email already held by the school or a manual registrar verification step.

### How do you prevent duplicate records?

> Emails are checked across user and alumni records. Student IDs are normalized into searchable variants, and an existing record is matched before creating a new one. Conflicts are rejected and referred to the administrator. Import operations can update matching Student IDs instead of blindly inserting duplicates.

### What happens if an import fails halfway?

> The import operation uses a database transaction. If a fatal import error occurs, the transaction can roll back instead of leaving a partially applied dataset. The service also validates file types, searches for recognizable headers, and rejects files without importable rows.

### How are administrator and alumni permissions separated?

> Routes are grouped using authentication and role middleware. Administrator-management routes require the `admin` role. Alumni portal routes require the `alumni` role, and protected portal services also require OTP verification. Unauthorized users receive a forbidden response or are redirected to the correct login or verification page.

### How is OTP protected?

> OTP is six digits, expires after ten minutes, allows up to five incorrect attempts, and has a 30-second resend cooldown. The session stores a hash of the OTP rather than the plain code. Successful verification is recorded for the approved account.

### Why does the system require Gmail?

> The current project scope standardizes alumni verification and delivery around Gmail, which simplifies OTP and recovery requirements. It is an operational design choice, not a technical requirement of Laravel. Supporting other verified email providers is a reasonable future enhancement.

### What happens when a notification fails?

> Core data changes are saved independently of external notification delivery. The system catches notification errors, logs them, and can show an administrator warning. This prevents a temporary mail or Firebase problem from discarding the actual request or status update.

### How did you test the system?

> The repository contains 78 feature-test scenarios plus a unit-test area. They cover role restrictions, registration validation, optional Student ID, Gmail rules, OTP, password reset, academic-program management, CSV/XLSX import, export authorization, account claiming, request processing, profile photos, content publishing, event registration, mobile navigation, and notification-token storage. During the defense, we can demonstrate selected test results as evidence rather than only saying that we manually tested it.

### What are the system's current limitations?

> It depends on internet and correctly configured email/Firebase services for external notifications. Gmail-only registration excludes other email providers. No-ID applications require human verification, and the current identity match can be strengthened further. A production rollout would also need HTTPS, formal data-governance policies, automated backups, monitoring, deployment hardening, and user training.

### What are your recommended future improvements?

- Registrar-assisted identity verification and document evidence for no-ID applications.
- Support for verified non-Gmail email addresses.
- Administrator tools for detecting and merging possible duplicate alumni records.
- Reports and analytics by batch, program, engagement, events, and request turnaround time.
- Configurable notification preferences and queued notification delivery.
- Audit logs for sensitive administrator actions.
- Stronger production deployment, backup, monitoring, and privacy controls.

## 12. Statements to avoid

Avoid these weak or inaccurate statements:

- “The system is 100% secure.”
- “The system has no limitations.”
- “Laravel automatically makes everything secure.”
- “We created `vendor` and `node_modules` ourselves.”
- “OTP proves with certainty that a person is the owner of the school record.”
- “Every notification is guaranteed to arrive.”

Use evidence-based statements:

- “The system applies layered controls appropriate to the current project scope.”
- “The automated tests verify the main workflows and authorization boundaries.”
- “OTP confirms access to the registered Gmail; record ownership is additionally checked through record matching or administrator review.”
- “External notification failure does not erase the saved transaction.”

## 13. Short closing statement

> In conclusion, Alumni Link gives St. Bridget College a centralized and controlled way to maintain alumni information, verify portal access, publish updates, manage event participation, and process alumni requests with visible status history. Its main value is not only digitizing records but connecting the complete alumni and administrator workflow. The current system is functional and testable, while its documented limitations provide a clear direction for secure production deployment and future enhancement.

## 14. Final defense reminder

Remember this sequence when answering:

**Problem -> User -> Workflow -> Control -> Evidence -> Limitation -> Improvement**

For example:

> Alumni may forget their Student ID (**problem**). The registration page allows that alumnus to continue (**user and workflow**), but the account remains pending for administrator approval and still requires password and OTP verification (**control**). A feature test covers registration without a Student ID (**evidence**). Manual verification can take time (**limitation**), so registrar-assisted identity verification is proposed for production (**improvement**).
