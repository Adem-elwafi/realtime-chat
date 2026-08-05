# Realtime Chat

A real-time 1:1 chat application built with **Laravel 12**, **Laravel Reverb**, **React 19**, and **Tailwind CSS**. It pairs with the companion **blog-platform** app — both share a single database and a single session (SSO) — and also hosts the friendship/friend-request backend that the blog app's social features consume.

## 🌟 Features

- **Real-time messaging**: instant send/receive over WebSockets via Laravel Reverb (`MessageSent`, `MessageDeleted`, `MessageRead`, `UserTyping` events)
- **Conversations**: 1:1 chat list with latest-message preview, timestamps, and unread-count badges
- **Message controls**: send, delete your own messages (synced live), and mark conversation as read
- **Typing indicators**: see when the other person is typing
- **Online presence**: presence channel (`presence-online-users`) plus `is_online` / `last_seen` indicators on avatars and user lists
- **User discovery**: browse users and search by name or email to start a chat
- **Friendship system**: friend-request API (create / pending / accept / decline / block) and a friends list, with `FriendshipUpdated` events broadcast to both participants
- **Dynamic CSRF**: fresh CSRF token is fetched from `/sanctum/csrf-cookie` before login/register submit to avoid 419s
- **Shared-session SSO**: logging in on this app (or on blog-platform) authenticates both apps

## 🛠️ Tech Stack

| Layer | Choice |
|---|---|
| Backend | Laravel 12 (PHP 8.2+) |
| Frontend | Blade + React 19 SPA (ChatMessages, MessageForm) + Tailwind CSS |
| Real-time | Laravel Reverb (WebSockets) + Laravel Echo / pusher-js |
| Database | MySQL (`shared_app_db`) |
| Auth | Laravel Breeze + Sanctum (shared-session SSO with blog-platform) |
| UI extras | emoji-picker-react, framer-motion |
| Testing | PHPUnit (via Laravel Breeze) |

## 🧩 Architecture — how the two apps fit together

`realtime-chat` (this repo) and `blog-platform` form one system that shares:

- A **single MySQL database** (`shared_app_db`) — this app owns the `users`, `sessions`, `conversations`, `messages`, and `friendships` tables.
- A **single shared session** — same session cookie name (`blog_chat_session`), same `APP_KEY`, same DB session driver. Log in once on either app and you're authenticated on both (SSO).
- The **friendship & chat API** — served by this app on port **8000**. The blog app calls it for Add Friend, the friend-requests inbox, and the friends list, fetching a fresh CSRF token from `/sanctum/csrf-cookie` before every state-changing POST.

| App | Folder | URL | Server |
|---|---|---|---|
| Real-time chat | `realtime-chat` | `http://localhost:8000` | `php artisan serve` |
| Blog platform | `blog-platform` | `http://localhost:8001` | `php artisan serve --port=8001` |
| Reverb (WebSockets) | — | `ws://localhost:8081` | `php artisan reverb:start` |

## 📋 Requirements

- PHP 8.2+
- Composer
- Node.js 18+ / npm
- MySQL
- The `blog-platform` app (for the shared session/SSO and to exercise the social features)

## 🚀 Installation

1) Clone and enter the project
```bash
git clone <repository-url>
cd realtime-chat
```

2) Install dependencies
```bash
composer install
npm install
```

3) Environment and key
```bash
cp .env.example .env
php artisan key:generate
```

> **Important for SSO:** `APP_KEY`, `SESSION_COOKIE`, `SESSION_DRIVER`, and `DB_*` must match `blog-platform`'s `.env` so both apps share the same session and database. Reverb credentials live in the `REVERB_*` / `VITE_REVERB_*` variables.

## 🗄️ Database Setup

This app owns the shared tables (`users`, `sessions`, `conversations`, `messages`, `friendships`) plus the Laravel framework tables:

```bash
php artisan migrate
```

Add a demo user if you like:
```bash
php artisan tinker --execute="App\Models\User::create(['name' => 'Demo', 'email' => 'demo@example.com', 'password' => bcrypt('password')]);"
```

## 📦 Running the Application

The combined dev script starts the HTTP server (port **8000**), queue worker, logs, and Vite:

```bash
composer run dev
```

Then start the WebSocket server (separate terminal):
```bash
php artisan reverb:start
```

Production build:

```bash
npm run build
```

Open `http://localhost:8000`. The Vite dev server runs on `http://localhost:5173`.

## 💬 Using the App

1. Register or log in (`/register`, `/login`).
2. **New Chat** → find a user (search by name/email) → **Chat**.
3. Send a message — it appears instantly on the other person's screen. Typing and online/offline status update live.
4. Delete your own messages; unread conversations show a badge.

## 🤝 Friendship API

Consumed by blog-platform's Add Friend button and Friend Requests inbox:

| Endpoint | Purpose |
|---|---|
| `GET /sanctum/csrf-cookie` | Fetch a fresh CSRF token (required before state-changing POSTs) |
| `POST /api/friend-requests` | Send a friend request (`{ addressee_id }`) |
| `GET /api/friend-requests` | Pending requests: `{ incoming: [...], outgoing: [...] }` |
| `POST /api/friend-requests/{id}/accept` | Accept an incoming request |
| `POST /api/friend-requests/{id}/decline` | Decline an incoming request |
| `POST /api/friend-requests/{id}/block` | Block a user |
| `GET /api/friends` | Accepted friendships (used by the Friends list) |

All endpoints require session auth (Sanctum SPA); `FriendshipUpdated` events are broadcast on the `friends.{userId}` private channel.

## 🎯 Routes

| Route | Purpose |
|---|---|
| `GET /` | Redirects to chat or login |
| `GET /chat` | Conversation list (previews, unread badges, online status) |
| `GET /chat/{userId}` | Chat window with a specific user |
| `POST /chat/message` | Send a message |
| `DELETE /chat/message/{messageId}` | Delete a message |
| `POST /chat/{conversation}/read` | Mark conversation as read |
| `POST /chat/typing` | Send a typing indicator |
| `GET /users` | Browse users |
| `GET /users/search?query=` | Search users by name/email |
| `GET /debug/broadcast` | Broadcast test page |

Broadcast channels (`routes/channels.php`): `chat.{conversationId}` (participants only), `friends.{userId}` (owner only), `presence-online-users` (online tracking).

## 🧪 Testing

```bash
php artisan test
```

## 🚨 Troubleshooting

- **Caches**: `php artisan config:clear && php artisan cache:clear && php artisan view:clear`
- **Messages not arriving in real time**: make sure Reverb is running (`php artisan reverb:start`) and that `BROADCAST_CONNECTION` / `VITE_REVERB_*` are set correctly.
- **SSO / 419 on login**: confirm `APP_KEY`, `SESSION_COOKIE`, and `DB_*` match `blog-platform`, and that the blog app can reach this one on port 8000. The login/register forms fetch a fresh CSRF token before submitting.
- **Assets**: `npm install && npm run dev` (or `npm run build` for production).

## 📚 Useful Links

- [Laravel Docs](https://laravel.com/docs)
- [Laravel Reverb](https://reverb.laravel.com/)
- [Laravel Echo](https://laravel.com/docs/broadcasting#client-side-installation)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)

---

Happy chatting! 💬
