# Guest Signup Integration

This document describes the guest signup system for church sites, which allows visitors to register as members and opt-in to SMS text messages.

## Overview

The guest signup system integrates:
- **Church Package**: Member registration and management
- **Messaging Package**: SMS consent flow and double opt-in
- **Frontend**: HTML form with JavaScript handler

## Architecture

### Data Flow

```
Guest Form (fbc-guest.html)
    ↓
POST /api/guest-signup
    ↓
GuestSignupController::store()
    ├─ Validate form input
    ├─ Get or create Member record
    ├─ Store metadata (first-time, heard from, etc.)
    └─ Call ConsentController::optInWeb()
        ├─ Create/update MsgGuest record
        ├─ Record consent event
        └─ Send confirmation SMS
    ↓
JSON Response (success/error)
    ↓
JavaScript Handler (guest-signup.js)
    ├─ Display success/error message
    ├─ Clear form on success
    └─ Optional: Redirect to thank you page
```

## Components

### 1. GuestSignupController

**Location**: `packages/prasso/church/src/Http/Controllers/GuestSignupController.php`

**Methods**:
- `store(Request $request)` - Handle form submission
- `getOrCreateMember()` - Create or update member record
- `getSiteFromRequest()` - Determine site context

**Features**:
- Validates required fields (name, phone, consent)
- Creates member with guest status
- Stores metadata (first-time visitor, heard from, signup date, IP)
- Initiates SMS double opt-in flow
- Returns JSON response with member ID and confirmation message

### 2. API Endpoint

**Route**: `POST /api/guest-signup`

**Authentication**: Public (no auth required)

**Request Body**:
```json
{
  "name": "John Doe",
  "phone": "(386) 555-0123",
  "email": "john@example.com",
  "heard_from": "A friend told me about your church",
  "first_time": true,
  "consent": true
}
```

**Response (Success - 201)**:
```json
{
  "success": true,
  "message": "Guest signup recorded. Confirmation SMS sent.",
  "data": {
    "member_id": 123,
    "phone": "(386) 555-0123",
    "email": "john@example.com",
    "message": "Thank you! Please check your phone for a confirmation text message."
  }
}
```



### 3. Frontend Form

**Location**: `public/pages/fbc-guest.html`

**Form Fields**:
- `name` (required) - Guest's full name
- `phone` (required) - Phone number for SMS
- `email` (optional) - Email address
- `heard_from` (optional) - How they heard about the church
- `first_time` (optional) - Checkbox for first-time visitors
- `consent` (required) - SMS consent checkbox

**Form Attributes**:
- `data-guest-signup="true"` - Identifies form for JavaScript handler
- `novalidate` - Disables browser validation (handled by JavaScript)

### 4. JavaScript Handler

**Location**: `public/js/guest-signup.js`

**Features**:
- Validates form input before submission
- Sends form data to API endpoint
- Displays success/error messages
- Clears form on successful submission
- Handles network errors gracefully
- Auto-removes error messages after 5 seconds
- Disables submit button during submission

**Error Handling**:
- Missing required fields
- Invalid phone number format
- Missing consent checkbox
- Network/server errors

## Database Schema

### Members Table (chm_members)

```sql
CREATE TABLE chm_members (
  id BIGINT PRIMARY KEY,
  first_name VARCHAR(255),
  last_name VARCHAR(255),
  phone VARCHAR(255),
  email VARCHAR(255),
  site_id BIGINT,
  membership_status VARCHAR(50), -- 'guest', 'member', etc.
  metadata JSON, -- Stores additional data
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

### Metadata Structure

```json
{
  "first_time_visitor": true,
  "heard_from": "A friend told me",
  "guest_signup_date": "2026-04-24 16:05:00",
  "ip_address": "192.168.1.1"
}
```

### Guests Table (msg_guests)

Created by messaging package's ConsentController:

```sql
CREATE TABLE msg_guests (
  id BIGINT PRIMARY KEY,
  team_id BIGINT,
  name VARCHAR(255),
  email VARCHAR(255),
  phone VARCHAR(255),
  phone_hash VARCHAR(64),
  email_hash VARCHAR(64),
  is_subscribed BOOLEAN, -- false until confirmed
  subscription_status_updated_at TIMESTAMP,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

### Consent Events Table (msg_consent_events)

```sql
CREATE TABLE msg_consent_events (
  id BIGINT PRIMARY KEY,
  msg_guest_id BIGINT,
  action VARCHAR(50), -- 'opt_in_request', 'opt_in_confirmed', etc.
  method VARCHAR(50), -- 'web', 'sms', etc.
  source VARCHAR(2000),
  ip VARCHAR(45),
  user_agent TEXT,
  occurred_at TIMESTAMP,
  meta JSON,
  created_at TIMESTAMP
);
```

## SMS Consent Flow

### Double Opt-In Process

1. **User Submits Form**
   - Guest fills out form and checks consent checkbox
   - Form submitted to `/api/guest-signup`

2. **Member Created**
   - GuestSignupController creates Member record
   - Metadata stored with signup details

3. **Consent Request Sent**
   - ConsentController creates MsgGuest record
   - Records 'opt_in_request' consent event
   - Sends confirmation SMS: "You're almost done! Reply YES to confirm..."

4. **User Confirms**
   - User replies "YES" to confirmation SMS
   - Twilio webhook receives reply
   - ConsentController processes confirmation
   - Records 'opt_in_confirmed' consent event
   - Sets `is_subscribed = true` on MsgGuest

5. **Subscription Active**
   - Guest can now receive SMS messages
   - User can reply STOP to unsubscribe
   - User can reply HELP for help

## Implementation Steps

### 1. Verify Database Tables

Ensure the following tables exist:
- `chm_members` (church package)
- `msg_guests` (messaging package)
- `msg_consent_events` (messaging package)

Run migrations if needed:
```bash
php artisan migrate
```

### 2. Verify Configuration

Check that SMS service is configured in `.env`:
```
TWILIO_ACCOUNT_SID=your_sid
TWILIO_AUTH_TOKEN=your_token
TWILIO_PHONE_NUMBER=+1234567890
```

### 3. Add Form to Site Page

Create a site page with the guest signup form:
1. Go to Site Editor
2. Create new page with type "HTML Content"
3. Copy content from `public/pages/fbc-guest.html`
4. Save and publish

### 4. Test the Form

1. Visit the guest signup page
2. Fill out form with test data
3. Submit form
4. Check for success message
5. Verify member was created in admin
6. Verify SMS was sent to phone number
7. Reply "YES" to confirmation SMS
8. Verify subscription confirmed in admin

## Troubleshooting

### Form Not Submitting

**Check**:
- JavaScript console for errors
- Network tab for API response
- Verify `/api/guest-signup` endpoint is accessible

### SMS Not Sending

**Check**:
- Twilio credentials in `.env`
- Phone number format (should be E.164: +1234567890)
- Twilio account has available credits
- Check logs: `storage/logs/laravel.log`

### Member Not Created

**Check**:
- Site context is correct (check getSiteFromRequest logic)
- Database permissions for insert
- Validation errors in response

### Consent Event Not Recorded

**Check**:
- MsgConsentEvent model exists
- Database table has correct schema
- Check logs for exceptions

## API Usage Examples

### cURL

```bash
curl -X POST http://localhost/api/guest-signup \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "phone": "(386) 555-0123",
    "email": "john@example.com",
    "heard_from": "A friend",
    "first_time": true,
    "consent": true
  }'
```

### JavaScript Fetch

```javascript
const response = await fetch('/api/guest-signup', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    name: 'John Doe',
    phone: '(386) 555-0123',
    email: 'john@example.com',
    heard_from: 'A friend',
    first_time: true,
    consent: true,
  }),
});

const data = await response.json();
console.log(data);
```

### jQuery

```javascript
$.ajax({
  url: '/api/guest-signup',
  type: 'POST',
  contentType: 'application/json',
  data: JSON.stringify({
    name: 'John Doe',
    phone: '(386) 555-0123',
    email: 'john@example.com',
    heard_from: 'A friend',
    first_time: true,
    consent: true,
  }),
  success: function(data) {
    console.log('Success:', data);
  },
  error: function(error) {
    console.error('Error:', error);
  },
});
```

## Admin Features

### View Guest Signups

1. Go to Church Admin → Members
2. Filter by `membership_status = 'guest'`
3. View signup metadata in member details

### View SMS Consent

1. Go to Messaging Admin → Guests
2. View consent status and events
3. See confirmation SMS history

### Send Follow-up Messages

1. Go to Messaging Admin → Compose & Send Message
2. Select guest as recipient
3. Send welcome message or next steps

## Customization

### Change Submit Button Text

Edit `public/pages/fbc-guest.html`:
```html
<button type="submit" class="...">Your Custom Text</button>
```

Or in `public/js/guest-signup.js`:
```javascript
submitButton.textContent = 'Your Custom Text';
```

### Add Additional Form Fields

1. Add field to HTML form with unique ID
2. Update validation in `guest-signup.js`
3. Add to payload in `handleFormSubmit()`
4. Update GuestSignupController to handle new field
5. Store in Member.metadata or new column

### Change Error Message Styling

Edit `showError()` function in `guest-signup.js`:
```javascript
errorDiv.className = 'your-custom-classes';
```

### Customize SMS Confirmation Message

Edit `ConsentController::optInWeb()` in messaging package:
```php
$confirmation = "Your custom message...";
```

## Security Considerations

### CSRF Protection

- API endpoint is public (no CSRF token required)
- Form submission uses standard POST with JSON
- Twilio webhooks use signature verification

### Rate Limiting

- Consider adding rate limiting to prevent abuse
- Implement in middleware or controller

### Phone Number Validation

- Phone numbers normalized to 10-digit format
- Hashed for privacy in database
- Validated before SMS sending

### Consent Recording

- All consent events logged with timestamp, IP, user agent
- Audit trail for compliance
- Double opt-in prevents unauthorized signups

## Related Documentation

- [Church Package Documentation](../README.md)
- [Messaging Package Documentation](../../messaging/docs/)
- [SMS Consent Flow](../../messaging/docs/SMS_CONSENT.md)
- [Member Management](./MEMBER_MANAGEMENT.md)
