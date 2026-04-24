# Guest Signup - Quick Start Guide

## What It Does

Visitors fill out a form on your church website and:
1. Get registered as a member
2. Opt-in to SMS text message updates
3. Receive a confirmation SMS
4. Can reply YES to confirm subscription

## Files Created

| File | Purpose |
|------|---------|
| `src/Http/Controllers/GuestSignupController.php` | Handles form submission and member creation |
| `public/js/guest-signup.js` | Frontend form validation and submission |
| `public/pages/fbc-guest.html` | Example guest signup form |

## API Endpoint

**URL**: `POST /api/guest-signup`

**Public**: Yes (no authentication required)

**Request**:
```json
{
  "name": "John Doe",
  "phone": "(386) 555-0123",
  "email": "john@example.com",
  "heard_from": "A friend",
  "first_time": true,
  "consent": true
}
```

**Response** (Success):
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

## Setup Steps

### 1. Verify Prerequisites

```bash
# Check that migrations have run
php artisan migrate:status

# Verify Twilio credentials in .env
grep TWILIO .env
```

### 2. Add Form to Your Site

Copy the form from `public/pages/fbc-guest.html` to your site page, or create a new site page with:

```html
<form class="space-y-5" novalidate data-guest-signup="true">
  <div class="space-y-2">
    <label for="name" class="text-sm font-medium">Your name <span class="text-destructive">*</span></label>
    <input id="name" placeholder="John Doe" autocomplete="name" class="h-12 text-base">
  </div>
  
  <div class="space-y-2">
    <label for="phone" class="text-sm font-medium">Phone number <span class="text-destructive">*</span></label>
    <input id="phone" type="tel" inputmode="tel" placeholder="(386) 555-0123" autocomplete="tel" class="h-12 text-base">
  </div>
  
  <div class="space-y-2">
    <label for="email" class="text-sm font-medium">Email <span class="text-muted-foreground font-normal">(optional)</span></label>
    <input id="email" type="email" inputmode="email" placeholder="you@example.com" autocomplete="email" class="h-12 text-base">
  </div>
  
  <div class="space-y-2">
    <label for="heardFrom" class="text-sm font-medium">How did you hear about us? <span class="text-muted-foreground font-normal">(optional)</span></label>
    <textarea id="heardFrom" placeholder="A friend, online, drove by..." rows="2" class="text-base resize-none"></textarea>
  </div>
  
  <div class="flex items-center justify-between rounded-xl border border-border bg-secondary/40 px-4 py-3.5">
    <div class="pr-4">
      <label for="firstTime" class="text-sm font-medium cursor-pointer">First time visiting?</label>
      <p class="text-xs text-muted-foreground mt-0.5">We'd love to give you a warm welcome</p>
    </div>
    <input type="checkbox" id="firstTime">
  </div>
  
  <div class="rounded-xl border border-border bg-secondary/40 px-4 py-4">
    <div class="flex items-start gap-3">
      <input type="checkbox" id="consent" class="mt-0.5">
      <label for="consent" class="text-sm font-normal leading-relaxed cursor-pointer">I agree to receive text messages from <strong class="font-semibold">Faith Baptist Church</strong> for follow-up and church updates. Message and data rates may apply. Reply STOP to unsubscribe anytime.</label>
    </div>
  </div>
  
  <button type="submit" class="w-full h-12 bg-primary text-primary-foreground font-medium rounded-lg hover:bg-primary/90 transition-colors">Sign Up & Get Text Updates</button>
</form>

<script src="/js/guest-signup.js"></script>
```

### 3. Test the Form

1. Visit your guest signup page
2. Fill in test data:
   - Name: "Test Guest"
   - Phone: "(386) 555-0123"
   - Email: "test@example.com"
   - Check "First time visiting?"
   - Check "I agree to receive text messages"
3. Click "Sign Up & Get Text Updates"
4. Should see success message: "Thank you! Please check your phone for a confirmation text message."

### 4. Verify in Admin

**Check Member Created**:
1. Go to Church Admin → Members
2. Search for "Test Guest"
3. Verify phone and email are saved
4. Check metadata for signup details

**Check SMS Consent**:
1. Go to Messaging Admin → Guests
2. Search for phone number
3. Verify guest record created
4. Check consent events

**Check SMS Sent**:
1. Check your phone for confirmation SMS
2. Should say: "You're almost done! Reply YES to confirm..."

### 5. Confirm Subscription

1. Reply "YES" to the confirmation SMS
2. Should receive: "Thank you! You're subscribed..."
3. Go back to Messaging Admin → Guests
4. Verify `is_subscribed = true`

## Form Fields

| Field | Required | Type | Notes |
|-------|----------|------|-------|
| name | Yes | Text | Full name of guest |
| phone | Yes | Tel | Phone number for SMS |
| email | No | Email | Optional email address |
| heard_from | No | Textarea | How they heard about church |
| first_time | No | Checkbox | First-time visitor flag |
| consent | Yes | Checkbox | Must check to proceed |

## What Happens Behind the Scenes

1. **Form Submitted** → JavaScript validates and sends to API
2. **Member Created** → Guest registered in `chm_members` table
3. **Metadata Stored** → First-time flag, heard from, signup date, IP
4. **Guest Record Created** → Entry added to `msg_guests` table
5. **Consent Event Logged** → Recorded in `msg_consent_events` for compliance
6. **Confirmation SMS Sent** → Twilio sends double opt-in message
7. **User Confirms** → Replies YES to SMS
8. **Subscription Activated** → Guest can receive messages

## Customization

### Change Button Text

In `public/js/guest-signup.js`, find:
```javascript
submitButton.textContent = 'Sign Up & Get Text Updates';
```

Change to your preferred text.

### Change Success Message

In `public/js/guest-signup.js`, find:
```javascript
showSuccess(form, data.message || 'Thank you! Please check your phone for a confirmation text message.');
```

### Add More Fields

1. Add input to form with unique ID
2. Update validation in `guest-signup.js`
3. Add to payload in `handleFormSubmit()`
4. Update `GuestSignupController::store()` to handle it

### Change SMS Confirmation Message

In `packages/prasso/messaging/src/Http/Controllers/Api/ConsentController.php`:
```php
$confirmation = "Your custom message...";
```

## Troubleshooting

### Form Not Submitting

**Check**:
- Browser console (F12) for JavaScript errors
- Network tab to see API response
- Verify form has `data-guest-signup="true"` attribute

### SMS Not Received

**Check**:
- Phone number is correct and in E.164 format
- Twilio account has credits
- Check `storage/logs/laravel.log` for errors
- Verify `TWILIO_PHONE_NUMBER` in `.env`

### Member Not Created

**Check**:
- Site context is correct
- Database has write permissions
- Check API response for validation errors

### "Consent checkbox must be accepted" Error

**Fix**:
- User must check the SMS consent checkbox before submitting
- This is required by law for SMS marketing

## Next Steps

- [Full Documentation](./GUEST_SIGNUP_INTEGRATION.md)
- [Member Management](./MEMBER_MANAGEMENT.md)
- [SMS Messaging](../../messaging/docs/)

## Support

For issues or questions:
1. Check the full documentation
2. Review logs: `storage/logs/laravel.log`
3. Check database records in admin
4. Verify Twilio configuration
