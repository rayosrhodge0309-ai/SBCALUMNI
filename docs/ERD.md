# Alumni Link ERD

This ERD is based on the Laravel migrations and Eloquent models in this repository.

## Main Application Tables

```mermaid
erDiagram
    ALUMNI {
        bigint id PK
        string student_id
        string first_name
        string last_name
        date birthday
        string education_level
        string course
        integer year_graduated
        string email
        string contact_number
        text address
        timestamp created_at
        timestamp updated_at
    }

    USERS {
        bigint id PK
        string name
        string email UK
        string password
        string role
        string account_status
        timestamp approved_at
        timestamp portal_otp_verified_at
        bigint alumni_id FK
        string profile_photo_path
        timestamp email_verified_at
        string remember_token
        text fcm_token
        timestamp created_at
        timestamp updated_at
    }

    REQUESTS {
        bigint id PK
        bigint alumni_id FK
        string request_type
        string year_requested
        text requester_note
        string status
        text admin_notes
        bigint processed_by FK
        timestamp processed_at
        timestamp admin_replied_at
        timestamp created_at
        timestamp updated_at
    }

    ANNOUNCEMENTS {
        bigint id PK
        string label
        string title
        text content
        string media_path
        string media_type
        boolean is_published
        integer views_count
        timestamp published_at
        timestamp created_at
        timestamp updated_at
    }

    ACTIVITIES {
        bigint id PK
        string theme
        string title
        text description
        date activity_date
        string location
        string media_path
        string media_type
        boolean is_published
        integer views_count
        timestamp created_at
        timestamp updated_at
    }

    EVENTS {
        bigint id PK
        string title
        text description
        date event_date
        string location
        string media_path
        string media_type
        boolean is_published
        integer views_count
        timestamp created_at
        timestamp updated_at
    }

    SITE_SETTINGS {
        bigint id PK
        string key UK
        text value
        timestamp created_at
        timestamp updated_at
    }

    NOTIFICATIONS {
        uuid id PK
        string type
        string notifiable_type
        bigint notifiable_id
        json data
        timestamp read_at
        timestamp created_at
        timestamp updated_at
    }

    ALUMNI ||--o| USERS : "linked account"
    ALUMNI ||--o{ REQUESTS : "submits"
    USERS ||--o{ REQUESTS : "processes"
    USERS ||--o{ NOTIFICATIONS : "receives"
```

## Framework And Legacy Tables

```mermaid
erDiagram
    PASSWORD_RESET_TOKENS {
        string email PK
        string token
        timestamp created_at
    }

    SESSIONS {
        string id PK
        bigint user_id
        string ip_address
        text user_agent
        longtext payload
        integer last_activity
    }

    CACHE {
        string key PK
        mediumtext value
        integer expiration
    }

    CACHE_LOCKS {
        string key PK
        string owner
        integer expiration
    }

    STUDENTS {
        bigint id PK
        string name
        string email UK
        integer age
        timestamp created_at
        timestamp updated_at
    }

    RECORDS {
        bigint id PK
        timestamp created_at
        timestamp updated_at
    }

    REUNION {
        bigint id PK
        timestamp created_at
        timestamp updated_at
    }

    ALUMNIS {
        bigint id PK
        timestamp created_at
        timestamp updated_at
    }

    USERS ||--o{ SESSIONS : "may own"
```

## Relationship Notes

- `alumni.id` to `users.alumni_id` is optional one-to-one. One alumni record can have one linked portal user account.
- `users.portal_otp_verified_at` is set only after an alumni user successfully verifies the Gmail OTP on first login. New or newly approved alumni accounts keep this value null until that first-login verification is completed.
- `alumni.id` to `requests.alumni_id` is one-to-many. Deleting an alumni record cascades and deletes related requests.
- `users.id` to `requests.processed_by` is optional one-to-many. If the processing admin user is deleted, `processed_by` is set to null.
- `notifications` uses Laravel's polymorphic `notifiable_type` and `notifiable_id`. In this system, notifications are sent to users through the `User` model.
- `announcements`, `activities`, `events`, and `site_settings` are standalone content/configuration tables in the current schema.
- `students`, `records`, `reunion`, and `alumnis` appear to be early scaffold or legacy tables because the active models/controllers use `alumni`, `users`, `requests`, `announcements`, `activities`, `events`, and `site_settings`.
