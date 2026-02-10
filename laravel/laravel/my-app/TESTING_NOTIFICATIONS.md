# 🔔 Testing Notifications API

Complete guide for testing the automatic notification system with Firebase Cloud Messaging (FCM).

## Prerequisites

- Valid Sanctum authentication token: `1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17`
- Base URL: `http://localhost:8000/api`
- Firebase emulator or real Firebase project configured
- FCM enabled in your Firebase project

## 1. Register Firebase Cloud Messaging Token

Before receiving notifications, each user device must register their FCM token.

### Register FCM Token
```bash
curl -X POST http://localhost:8000/api/firebase-tokens/register \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17" \
  -H "Content-Type: application/json" \
  -d '{
    "token": "YOUR_FCM_TOKEN_HERE",
    "device_name": "iPhone 12",
    "device_type": "ios",
    "device_os": "iOS 15.0"
  }'
```

**Response (201 Created):**
```json
{
  "id": 1,
  "user_id": 1,
  "token": "YOUR_FCM_TOKEN_HERE",
  "device_name": "iPhone 12",
  "device_type": "ios",
  "device_os": "iOS 15.0",
  "is_active": true,
  "last_used_at": "2024-01-15T10:30:45Z",
  "created_at": "2024-01-15T10:30:45Z",
  "updated_at": "2024-01-15T10:30:45Z"
}
```

### List My Tokens
```bash
curl -X GET http://localhost:8000/api/firebase-tokens \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17"
```

### List Active Tokens Only
```bash
curl -X GET http://localhost:8000/api/firebase-tokens/active \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17"
```

### Deactivate a Token (without deleting)
```bash
curl -X POST http://localhost:8000/api/firebase-tokens/{token_id}/deactivate \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17"
```

### Delete a Token
```bash
curl -X DELETE http://localhost:8000/api/firebase-tokens/{token_id} \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17"
```

---

## 2. Test Automatic Status Change Notifications

When a roadwork's status changes, notifications are created automatically and sent via FCM.

### Create a Roadwork
```bash
curl -X POST http://localhost:8000/api/roadworks \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Street Repair - Main Street",
    "description": "Repair and repave Main Street",
    "location": "Main Street, Downtown",
    "status": "planned"
  }'
```

**Save the ID from response** (e.g., ID = 1)

### Update Status to "in_progress" (Triggers Auto-Notification)
```bash
curl -X PATCH http://localhost:8000/api/roadworks/1 \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "in_progress"
  }'
```

**What happens automatically:**
- ✅ `started_at` is set to current timestamp
- ✅ `StatusHistory` entry is created (planned → in_progress)
- ✅ Notification created: "Work started on Street Repair - Main Street"
- ✅ FCM push sent to all users with registered devices

### Update Status to "completed" (Triggers Auto-Notification)
```bash
curl -X PATCH http://localhost:8000/api/roadworks/1 \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "completed"
  }'
```

**What happens automatically:**
- ✅ `completed_at` is set to current timestamp
- ✅ `StatusHistory` entry is created (in_progress → completed)
- ✅ Notification created: "Work completed on Street Repair - Main Street"
- ✅ FCM push sent to all users with registered devices

### Pause the Work
```bash
curl -X PATCH http://localhost:8000/api/roadworks/1 \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "paused"
  }'
```

**What happens automatically:**
- ✅ `StatusHistory` entry is created (completed → paused)
- ✅ Notification created: "Work paused on Street Repair - Main Street"
- ✅ FCM push sent to all users with registered devices

---

## 3. Test Photo Upload Notifications

When a photo is uploaded for a roadwork, a notification is created automatically.

### Upload a Photo (Triggers Auto-Notification)
```bash
curl -X POST http://localhost:8000/api/roadworks/1/photos \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17" \
  -F "file=@/path/to/your/image.jpg" \
  -F "photo_type=before" \
  -F "description=Initial state of the road"
```

**What happens automatically:**
- ✅ Photo stored in `storage/app/public/roadwork_photos/`
- ✅ Notification created: "New photo uploaded - BEFORE state"
- ✅ FCM push sent to all users
- ✅ Photo URL returned in response

**Photo Types Available:**
- `before` - Before work started
- `during` - Work in progress
- `after` - Work completed
- `issue` - Problem/issue documentation

---

## 4. Manage and Read Notifications

### Get My Notifications
```bash
curl -X GET http://localhost:8000/api/notifications \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17"
```

**Response:**
```json
[
  {
    "id": 1,
    "user_id": 1,
    "title": "Work started",
    "message": "Street Repair - Main Street has started",
    "type": "status_change",
    "notifiable_type": "App\\Models\\Roadwork",
    "notifiable_id": 1,
    "read_at": null,
    "created_at": "2024-01-15T10:45:00Z",
    "updated_at": "2024-01-15T10:45:00Z"
  }
]
```

### Get Unread Notifications Only
```bash
curl -X GET "http://localhost:8000/api/notifications?filter=unread" \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17"
```

### Get Read Notifications Only
```bash
curl -X GET "http://localhost:8000/api/notifications?filter=read" \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17"
```

### Get Notifications by Type
```bash
curl -X GET "http://localhost:8000/api/notifications?type=status_change" \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17"
```

**Available Types:**
- `status_change` - When roadwork status changes
- `photo_uploaded` - When photo is added
- `comment_added` - When comment is posted
- `assignment` - When assigned to task

### Get Unread Count
```bash
curl -X GET http://localhost:8000/api/notifications/unread-count \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17"
```

**Response:**
```json
{
  "unread_count": 3
}
```

### Mark Notification as Read
```bash
curl -X PATCH http://localhost:8000/api/notifications/1/mark-read \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17"
```

### Mark Notification as Unread
```bash
curl -X PATCH http://localhost:8000/api/notifications/1/mark-unread \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17"
```

### Mark All Notifications as Read
```bash
curl -X PATCH http://localhost:8000/api/notifications/mark-all-read \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17"
```

### Get Single Notification Details
```bash
curl -X GET http://localhost:8000/api/notifications/1 \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17"
```

---

## 5. Manager: Send Notifications to Multiple Users

Only **managers** and **admins** can use these endpoints.

### Get Summary of All Users
```bash
curl -X GET http://localhost:8000/api/manager/users-summary \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17"
```

**Response:**
```json
{
  "total_users": 5,
  "users": [
    {
      "id": 1,
      "name": "Admin User",
      "email": "admin@example.com",
      "role": "manager",
      "active_devices": 2
    },
    {
      "id": 2,
      "name": "Regular User",
      "email": "user@example.com",
      "role": "user",
      "active_devices": 1
    }
  ]
}
```

### Send Notification to Specific Users
```bash
curl -X POST http://localhost:8000/api/manager/send-notification \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17" \
  -H "Content-Type: application/json" \
  -d '{
    "user_ids": [2, 3, 4],
    "title": "Urgent: Main Street Closure",
    "message": "Main Street will be closed on Friday for emergency repairs"
  }'
```

### Send Notification to All Users
```bash
curl -X POST http://localhost:8000/api/manager/send-notification \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17" \
  -H "Content-Type: application/json" \
  -d '{
    "send_to_all": true,
    "title": "System Maintenance",
    "message": "API will be under maintenance tonight from 10 PM to 2 AM"
  }'
```

**Response (202 Accepted):**
```json
{
  "status": "queued",
  "message": "Notification queued for delivery",
  "recipients": 5,
  "notification_id": 42
}
```

---

## 6. Manager: Firebase Account Synchronization

Sync user accounts with Firebase Auth.

### Check Firebase Connection Status
```bash
curl -X GET http://localhost:8000/api/manager/firebase-status \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17"
```

**Response:**
```json
{
  "connected": true,
  "project_id": "your-firebase-project",
  "last_sync": "2024-01-15T10:30:00Z"
}
```

### Sync Single User to Firebase
```bash
curl -X POST http://localhost:8000/api/manager/sync-user-firebase \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 2
  }'
```

**Response:**
```json
{
  "success": true,
  "message": "User synchronized to Firebase",
  "user": {
    "id": 2,
    "email": "user@example.com",
    "name": "John Doe",
    "firebase_uid": "firebase_uid_here"
  }
}
```

### Sync All Users to Firebase
```bash
curl -X POST http://localhost:8000/api/manager/send-accounts-firebase \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17"
```

**Response (202 Accepted):**
```json
{
  "status": "queued",
  "message": "User synchronization queued",
  "total_users": 5
}
```

---

## 7. View Status History

### Get Status History for a Roadwork
```bash
curl -X GET http://localhost:8000/api/roadworks/1/status-history \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17"
```

**Response:**
```json
[
  {
    "id": 1,
    "roadwork_id": 1,
    "old_status": "planned",
    "new_status": "in_progress",
    "changed_by": 1,
    "changed_by_name": "Admin User",
    "changed_at": "2024-01-15T10:45:00Z"
  },
  {
    "id": 2,
    "roadwork_id": 1,
    "old_status": "in_progress",
    "new_status": "completed",
    "changed_by": 1,
    "changed_by_name": "Admin User",
    "changed_at": "2024-01-15T11:30:00Z"
  }
]
```

---

## 8. Complete Testing Workflow

### Step 1: Register FCM Token (Mobile Device)
```bash
curl -X POST http://localhost:8000/api/firebase-tokens/register \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17" \
  -H "Content-Type: application/json" \
  -d '{"token": "DEVICE_FCM_TOKEN", "device_name": "Test Device", "device_type": "android"}'
```

### Step 2: Create a Roadwork
```bash
curl -X POST http://localhost:8000/api/roadworks \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17" \
  -H "Content-Type: application/json" \
  -d '{"title": "Bridge Repair", "description": "Main bridge inspection and repair", "location": "Downtown Bridge", "status": "planned"}'
```

### Step 3: Verify Notification Created
```bash
curl -X GET http://localhost:8000/api/notifications \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17"
```

### Step 4: Change Status (Triggers Notification)
```bash
curl -X PATCH http://localhost:8000/api/roadworks/1 \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17" \
  -H "Content-Type: application/json" \
  -d '{"status": "in_progress"}'
```

### Step 5: Check New Notification
```bash
curl -X GET "http://localhost:8000/api/notifications?filter=unread" \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17"
```

### Step 6: Mark as Read
```bash
curl -X PATCH http://localhost:8000/api/notifications/1/mark-read \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17"
```

### Step 7: Upload Photo (Triggers Notification)
```bash
curl -X POST http://localhost:8000/api/roadworks/1/photos \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17" \
  -F "file=@/path/to/image.jpg" \
  -F "photo_type=before"
```

### Step 8: View All Notifications
```bash
curl -X GET http://localhost:8000/api/notifications \
  -H "Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17"
```

---

## 9. Database Queries for Verification

### Check Notifications Table
```sql
SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10;
```

### Check Firebase Tokens
```sql
SELECT user_id, device_name, device_type, is_active, last_used_at FROM firebase_tokens;
```

### Check Status History
```sql
SELECT * FROM status_histories WHERE roadwork_id = 1 ORDER BY changed_at DESC;
```

### Check Roadwork with Dates
```sql
SELECT id, title, status, started_at, completed_at, created_at FROM roadworks WHERE id = 1;
```

---

## 10. Error Handling

### Invalid Token
```
HTTP 401 Unauthorized
{
  "message": "Unauthenticated."
}
```

### Insufficient Permissions (Non-Manager)
```
HTTP 403 Forbidden
{
  "message": "This action is unauthorized."
}
```

### Invalid Data
```
HTTP 422 Unprocessable Entity
{
  "message": "The given data was invalid.",
  "errors": {
    "status": ["The selected status is invalid."]
  }
}
```

### Resource Not Found
```
HTTP 404 Not Found
{
  "message": "Not Found"
}
```

---

## Notes

- **Automatic behavior**: Status changes and photo uploads automatically create notifications and send FCM push
- **Real-time**: Notifications are queued for FCM delivery immediately
- **Devices**: Users need to register FCM tokens before they can receive push notifications
- **Manager-only**: Firebase sync and broadcast notifications are restricted to managers/admins
- **Rate limiting**: API enforces rate limiting on notification endpoints
- **Database**: All notifications are stored in the database for audit and retrieval

---

**Last Updated:** January 2024
**API Version:** 1.0
