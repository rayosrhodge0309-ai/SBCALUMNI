# Table 12. Unit and Integration Testing

The updated test cases below reflect the current functions of the Alumni Link System. In this table, **Unit Test** refers to testing the behavior and validation of one system module, while **Integration Test** refers to testing the flow of data and actions across two or more modules.

| Test Case | Test Level | Target Module | Test Description | Expected Outcome | Results |
|---|---|---|---|---|---|
| Alumni Registration with Valid Information | Unit Test | Registration Module | Register an alumnus using complete personal information, a valid Gmail address, school level, and program or course. | A user and alumni record are created with a **Pending** account status. OTP verification is not required until the account is approved. | Passed |
| Alumni Registration with Invalid Email | Unit Test | Registration Module | Submit registration using a non-Gmail address. | The system displays an email validation error and does not create user or alumni records. | Passed |
| Invalid School Level and Program Combination | Unit Test | Registration Module | Select a strand, program, or course that does not belong to the chosen school level. | The system rejects the mismatched academic selection and displays a validation error. | Passed |
| Alumni Login and Account Status Check | Unit Test | Authentication Module | Sign in using a valid approved alumni Gmail account and password. | The system authenticates the user and sends an unverified account to Gmail OTP verification or opens the portal dashboard when OTP was already verified. | Passed |
| Invalid Alumni Login | Unit Test | Authentication Module | Attempt to sign in using a non-Gmail alumni account. | The system rejects the login and keeps the user signed out. | Passed |
| Gmail OTP Verification | Unit Test | OTP Verification Module | Submit the correct six-digit OTP before it expires. | The system permanently records OTP verification and allows access to the alumni dashboard. | Passed |
| Alumni Password Reset | Unit Test | Password Reset Module | Request a reset code for an approved alumni Gmail account and submit a valid new password. | The system sends the reset code, updates the password, and allows the new password to be used. | Passed |
| Academic Program Management | Unit Test | School Levels and Programs Module | Add, edit, and delete a school program through the administrator workspace. | Program changes are stored and the current choices are shown on alumni registration. | Passed |
| Event Registration Validation | Unit Test | Event Registration Module | Submit an event registration with missing or incorrect required alumni details. | The system displays validation errors and does not save the registration. | Passed |
| Program Catalog and Registration Integration | Integration Test | Academic Programs + Registration | Change a program in the administrator workspace, then open and submit the alumni registration form. | Added or edited programs become selectable during registration, while deleted programs are removed. | Passed |
| Imported Alumni Record Claim | Integration Test | Alumni Records + User Accounts + Authentication | Register using information that matches an imported alumni record. | The system links the alumni record to the user account and allows portal access after the required verification. | Passed |
| Account Approval and Notification | Integration Test | Pending Accounts + Authentication + Notifications | Approve a pending alumni account through the administrator workspace. | The account status becomes **Approved**, the approval time is stored, and the alumnus receives a notification. | Passed |
| OTP and Portal Access Integration | Integration Test | Authentication + OTP Middleware + Dashboard | Log in with an approved account before and after successful OTP verification. | Access is redirected to OTP verification until completion; later logins open the dashboard directly. | Passed |
| Alumni Profile and Database Synchronization | Integration Test | Profile + Users + Alumni Records | Update the profile of an alumni user linked to an alumni record. | Updated information is stored and synchronized with the administrator-facing alumni record. | Passed |
| Record Request Workflow | Integration Test | Alumni Portal + Record Requests + Admin Workspace + Notifications | Submit an Alumni ID, Year Book, or Facility Use request, then let an administrator update its status and reply. | The request is saved, only administrators can process it, status history is updated, and the alumnus receives the response. | Passed |
| Event Registration and Admin Reply | Integration Test | Events + Event Registrations + Notifications | Register an authenticated alumnus for a published event, then let an administrator approve or decline the registration and send a reply. | One pending registration is stored, the administrator can update it, and the new status and reply appear on the alumni dashboard and notification. | Passed |
| Published Content Display | Integration Test | Events + Announcements + Activities + Landing Page | Publish an event, announcement, or activity through the administrator workspace and open the public and alumni views. | Published content and its totals appear in the proper feed; unpublished content remains hidden. | Passed |
| Alumni Import, Search, and Export | Integration Test | Alumni Import + Alumni Records + Export | Import CSV or XLSX alumni data, search by name or student ID, and export the filtered records. | Records are stored with their displayed student IDs, searchable results are correct, and the authorized export contains the selected data. | Passed |
| Role-Based Access Control | Integration Test | Authentication + Route Middleware + Admin/Alumni Workspaces | Access protected administrator and alumni functions using guest, alumni, and administrator accounts. | Each role can access only its permitted pages and actions; unauthorized requests are redirected or forbidden. | Passed |
| Email and Push Notification Integration | Integration Test | Notifications + Gmail/SMTP + Firebase Cloud Messaging | Save an authenticated user's Firebase token and trigger account, request, or event notifications. | The token is stored, database and email notifications are prepared for the correct recipient, and frontend notification handling remains functional. | Passed |

## Test Execution Summary

Tests were executed on **September 25, 2026**.

| Test Suite | Result |
|---|---|
| Laravel backend tests | 75 passed, 1 failed, 488 assertions |
| Frontend JavaScript tests | 34 passed, 0 failed |

The one backend failure is a legacy starter test in `tests/Feature/ExampleTest.php`. It still expects the removed text **“Official Alumni Portal”** on the landing page. The current landing page and content-specific tests passed, so this outdated assertion is not included as a system test case in the table above.
