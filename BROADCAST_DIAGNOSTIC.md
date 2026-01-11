# Real-Time Message Broadcasting Diagnostic Guide

## Quick Status Check

Run these commands to verify setup:

```bash
# 1. Check BROADCAST_DRIVER
grep BROADCAST_DRIVER .env

# 2. Check Reverb is running
ps aux | grep reverb

# 3. Check Laravel logs
tail -f storage/logs/laravel.log

# 4. Verify event is ShouldBroadcast
grep -r "implements ShouldBroadcast" app/

# 5. Check if broadcast() is being called
grep -r "broadcast(new" app/
```

---

## Step 1: Verify Configuration

### 1.1 Check Broadcasting Driver
```bash
# Should show: BROADCAST_DRIVER=reverb
cat .env | grep BROADCAST_DRIVER
```

**Expected:**
```
BROADCAST_DRIVER=reverb
```

### 1.2 Check Reverb Service Is Running
```bash
# Start Reverb if not running
php artisan reverb:start

# In another terminal, check connection
curl -i http://127.0.0.1:8081/
```

**Expected:** Should return HTTP headers (not timeout)

### 1.3 Verify Config Cache
```bash
# Clear config cache to ensure fresh settings
php artisan config:clear
php artisan cache:clear
```

---

## Step 2: Check Server-Side Broadcast

### 2.1 Enable Laravel Debug Logs

Open `resources/views/chat/show.blade.php` and add a debug panel:

```blade
@vite(['resources/js/app.jsx'])

@if(env('APP_DEBUG'))
<div class="fixed bottom-0 left-0 bg-gray-900 text-green-400 p-4 text-xs font-mono z-50" style="width: 400px; max-height: 300px; overflow-y-auto;">
    <div class="font-bold mb-2">🔧 Debug Info</div>
    <div>Conversation ID: {{ $conversation->id }}</div>
    <div>Current User: {{ auth()->id() }}</div>
    <div>User 1: {{ $conversation->user_one_id }}</div>
    <div>User 2: {{ $conversation->user_two_id }}</div>
    <div>Messages: {{ $conversation->messages->count() }}</div>
    <div class="mt-2 text-yellow-400">📝 Check browser console for Echo logs</div>
</div>
@endif
```

### 2.2 Send a Test Message and Monitor Logs

1. **Open terminal 1:** Monitor Laravel logs
```bash
tail -f storage/logs/laravel.log | grep -E "(✉️|📡|📤|🔐|✅|❌)"
```

2. **Open terminal 2:** Monitor Reverb logs
```bash
php artisan reverb:start
# Watch for incoming/outgoing messages
```

3. **In browser:** Send a message from chat interface

**What to look for:**
```
✉️ MessageSent event created [message_id: X, conversation_id: Y, sender_id: Z]
📡 Broadcasting on channel [channel: chat.5]
📤 Broadcasting data [id: X, body: "...", sender_id: Z]
🔐 Attempting to authorize user [user_id: 2, conversation_id: 5]
✅ Conversation found [id: 5, user_one_id: 1, user_two_id: 2]
✅ User access result [user_id: 2, allowed: true]
```

---

## Step 3: Check Frontend WebSocket Connection

### 3.1 Test Echo Connection

Open browser console and run:

```javascript
// Check if Echo is initialized
if (window.Echo) {
    console.log('✅ Echo available');
    console.log('Broadcaster:', window.Echo.options.broadcaster);
    console.log('WebSocket URL:', `ws://${window.Echo.options.wsHost}:${window.Echo.options.wsPort}`);
} else {
    console.error('❌ Echo not initialized - check app.jsx imports');
}
```

### 3.2 Test Manual Channel Subscription

```javascript
// Test subscribing to a known conversation ID (replace 5 with actual ID)
const testChannel = window.Echo.private('chat.5');

testChannel.subscribed(() => {
    console.log('✔️ Subscribed to chat.5');
});

testChannel.error((error) => {
    console.error('❌ Error:', error);
});

testChannel.listen('MessageSent', (data) => {
    console.log('✅ Received message:', data);
});
```

### 3.3 Check Network Tab for WebSocket

1. Open DevTools → Network tab
2. Filter by "WS" (WebSocket)
3. Look for connection like: `ws://127.0.0.1:8081/app/REVERB_APP_KEY`
4. Check status: Should be **101 Switching Protocols**

**Expected WebSocket traffic:**
```
GET /app/REVERB_APP_KEY HTTP/1.1
Upgrade: websocket
Connection: Upgrade
Sec-WebSocket-Key: ...
Sec-WebSocket-Version: 13

→ HTTP/1.1 101 Switching Protocols
```

---

## Step 4: Verify Conversation & Message Records

### 4.1 Database Check

```bash
# Open Laravel tinker
php artisan tinker

# Check conversation exists and has correct users
$conv = App\Models\Conversation::find(5);
$conv->user_one_id;  // Should match sender or recipient
$conv->user_two_id;  // Should match sender or recipient
$conv->messages->count();  // Should see sent message

# Check message was created
$msg = App\Models\Message::latest()->first();
$msg->conversation_id;  // Should equal conversation ID
$msg->sender_id;        // Should be authenticated user
$msg->message;          // Should have content
```

### 4.2 Verify Relationships

```bash
php artisan tinker

$msg = App\Models\Message::latest()->first();
$msg->conversation;  // Should load conversation
$msg->sender;        // Should load user (sender_id)
```

---

## Step 5: End-to-End Test with Two Users

### 5.1 Setup Two Browser Sessions

1. **Browser 1:** User A (ID: 1)
   - Open http://127.0.0.1:8000/chat/2 (chat with User B)
   - Open DevTools → Console

2. **Browser 2:** User B (ID: 2)
   - Login as User B
   - Open http://127.0.0.1:8000/chat/1 (chat with User A)
   - Open DevTools → Console

### 5.2 Send Message and Observe

**In Browser 1 Console (User A):**
```
🔌 Listening on private channel: chat.5
✅ Echo instance available
🌐 Broadcaster: reverb
✔️ Successfully subscribed to channel
```

**Then in Browser 1, send a message via UI**

**Both Consoles should show:**
```
📩 New message received: {id: 15, body: "Hello", sender_id: 1, sender_name: "User A", created_at: "2026-01-11T..."}
📊 Event data keys: ["id", "body", "sender_id", "sender_name", "created_at"]
```

---

## Common Issues & Fixes

### Issue 1: "Channel authorization failed"

**Symptoms:**
- Chrome DevTools Network tab shows WebSocket 403 response
- Browser console: `❌ Channel subscription error: Authorization failed`

**Debug:**
```bash
# Check if user is properly authenticated
php artisan tinker
auth()->loginUsingId(2);  // Log in as user 2
$user = auth()->user();
// Verify you can access the conversation channel auth
```

**Fix:**
```php
// In routes/channels.php, verify the condition matches your data types
// Often issue is integer vs string comparison
$isParticipant = (
    (int) $conversation->user_one_id === (int) $user->id ||
    (int) $conversation->user_two_id === (int) $user->id
);
```

### Issue 2: "Event not broadcasting"

**Symptoms:**
- Message saves to DB
- But Reverb logs show no broadcast
- No "Broadcasting [MessageSent]..." log

**Debug:**
```php
// In ChatController.php store() method, verify broadcast() is called
broadcast(new MessageSent($message));  // Must return void or Job

// Check if BROADCAST_DRIVER is actually 'reverb'
php artisan tinker
config('broadcasting.default');  // Should show 'reverb'
```

**Fix:**
```bash
# Ensure config cache is cleared
php artisan config:clear

# Verify .env has correct setting
echo $BROADCAST_DRIVER  # Should be 'reverb'
```

### Issue 3: "Frontend not receiving messages"

**Symptoms:**
- Reverb logs show successful broadcast
- But browser console doesn't show "New message received"

**Debug:**
```javascript
// Check if channel subscription succeeded
window.Echo.private('chat.5').subscribed(() => {
    console.log('✅ Connected');
});

// Check if listener is actually attached
// Add before channel.listen()
const channel = window.Echo.private('chat.5');
console.log('Channel listeners:', channel.listeners);
```

**Fix:**
- Ensure conversation_id is correctly passed to ChatMessages component
- Verify channel name matches on both server and client (should be `chat.{ID}`)
- Check that MessageSent event name matches the `.listen('MessageSent', ...)`

### Issue 4: "404 when broadcasting"

**Symptoms:**
- Laravel log shows event created
- But browser shows: `Illuminate\Broadcasting\BroadcastException: Invalid JSON Response`

**Debug:**
```bash
# Check if Reverb is actually running on the right port
curl http://127.0.0.1:8081/health

# Check Reverb config
grep -E "REVERB_" .env
```

**Fix:**
```bash
# Kill any existing Reverb process
pkill -f "reverb:start"

# Start fresh
php artisan reverb:start --host=127.0.0.1 --port=8081
```

---

## Verification Checklist

- [ ] `BROADCAST_DRIVER=reverb` in `.env`
- [ ] `php artisan reverb:start` is running
- [ ] Laravel logs show "✉️ MessageSent event created" when sending message
- [ ] Reverb logs show incoming broadcast
- [ ] Browser WebSocket connection shows **101 Switching Protocols**
- [ ] Console shows "✔️ Successfully subscribed to channel"
- [ ] Console shows "📩 New message received" when other user sends message
- [ ] Message appears in UI from other user
- [ ] Sender's message shows on their side immediately (from form.setBody(''))

---

## Nuclear Option: Full Reset

If everything fails, try this complete reset:

```bash
# 1. Kill all processes
pkill -f "reverb:start"
pkill -f "artisan serve"
pkill -f "npm run dev"

# 2. Clear all caches
php artisan view:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
rm -rf node_modules/.vite

# 3. Restart services
php artisan reverb:start &
npm run dev &
php artisan serve &

# 4. Monitor logs
tail -f storage/logs/laravel.log
```

Then repeat Step 5 (two browser test).

---

## Important Notes

- **Conversation ID must match** between database and channel name
- **User must be participant** of the conversation (user_one_id or user_two_id)
- **Type casting matters** - ensure ID comparisons use explicit type casting
- **Reverb needs to be running** - it's a separate WebSocket server
- **CSRF token must be valid** for channel auth requests
- **App is running over HTTP** (localhost) - no SSL needed for development
