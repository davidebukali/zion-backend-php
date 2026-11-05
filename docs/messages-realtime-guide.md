# Tsiyon Messages & Real-Time Delivery Guide
### Frontend & Mobile (Expo React Native) Integration Specification

**Version:** 1.0.0  
**Backend Protocol:** Laravel Reverb (Pusher Protocol v7 Compatible)  
**Authentication:** Laravel Sanctum Bearer Token  
**Base REST URL:** `https://tsiyonapp.com/api/v1` (Production) / `http://localhost/api/v1` (Local Dev)  
**WebSocket Host:** `tsiyonapp.com:443` (WSS / TLS) / `localhost:80` (WS via Nginx Proxy)

---

## 1. Quick Start & Setup

Install the required client libraries in your Expo / React Native project:

```bash
npx expo install pusher-js
npm install laravel-echo
```

### Echo Client Initialization (`services/echo.ts`)

```typescript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Polyfill Pusher on React Native if needed
(window as any).Pusher = Pusher;

export const createEchoClient = (authToken: string) => {
  const isDev = __DEV__;

  return new Echo({
    broadcaster: 'reverb',
    key: process.env.EXPO_PUBLIC_REVERB_APP_KEY, // e.g., 'zion-app-key'
    wsHost: isDev ? 'localhost' : 'tsiyonapp.com',
    wsPort: isDev ? 80 : 443,
    wssPort: isDev ? 80 : 443,
    forceTLS: !isDev,
    enabledTransports: ['ws', 'wss'],
    // Reverb public connection path is /app
    wsPath: '/app',
    authEndpoint: `${isDev ? 'http://localhost' : 'https://tsiyonapp.com'}/api/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${authToken}`,
        Accept: 'application/json',
      },
    },
  });
};
```

> [!IMPORTANT]
> The WebSocket endpoint proxy on Nginx is strictly mapped to `/app` (e.g. `ws://localhost/app/...`). Never attempt to connect to `/apps` as administrative routes are blocked from public access.

---

## 2. Authentication Endpoint (`/api/broadcasting/auth`)

Before any client can subscribe to private channels (`private-*`), Pusher / Laravel Echo sends an authorization handshake:

* **Endpoint:** `POST /api/broadcasting/auth`
* **Headers:**
  ```http
  Authorization: Bearer <sanctum_token>
  Accept: application/json
  Content-Type: application/json
  ```
* **Payload:**
  ```json
  {
    "socket_id": "12345.67890",
    "channel_name": "private-conversations.01JBD1EXAMPLE00000000000001"
  }
  ```
* **Success Response (200 OK):**
  ```json
  {
    "auth": "reverb_key:hmac_signature"
  }
  ```
* **Error Responses:**
  * `401 Unauthorized`: Missing or invalid Sanctum token.
  * `403 Forbidden`: Authenticated user is not a participant in the conversation, or a block relationship exists between the participants.

---

## 3. Channel Catalog & Subscription Lifecycle

There are two distinct private channels used in the messaging architecture:

### 3.1. User Channel: `private-users.{userId}`
* **Purpose:** Global inbox listener. Delivers new incoming messages, updates total unread badge counts, and alerts the user when new conversations are initiated.
* **Lifecycle:** Subscribe on user login; remain subscribed across all screens until logout.
* **Events Received:**
  * `message.sent`: Dispatched when another user sends a message to the authenticated user.

### 3.2. Conversation Channel: `private-conversations.{conversationId}`
* **Purpose:** Active chat thread screen. Delivers all real-time events for the open conversation.
* **Lifecycle:** Subscribe when entering the chat screen; unsubscribe immediately when exiting or backgrounding the conversation.
* **Events Received:**
  * `message.sent`
  * `message.updated`
  * `message.deleted`
  * `message.reaction_updated`
  * `message.read`
  * `typing.started`

---

## 4. Real-Time Event Payloads

### 4.1. `message.sent`
Triggered whenever a message is sent in the conversation.

```typescript
interface MessageSentEvent {
  id: string; // ULID
  conversation_id: string; // ULID
  sender_id: string; // ULID
  sender: {
    id: string;
    name: string;
    username: string;
    avatar: {
      id: string;
      url: string;
    } | null;
  };
  body: string | null;
  media: Array<{
    id: string;
    url: string;
    type: 'image';
    mime_type: string;
    size: number;
    width: number | null;
    height: number | null;
  }>;
  reply_to: {
    id: string;
    sender: { id: string; name: string };
    body: string | null;
    is_deleted_for_everyone: boolean;
  } | null;
  reactions: Array<{
    emoji: string;
    count: number;
    user_reacted: boolean;
  }>;
  is_edited: boolean;
  edited_at: string | null; // ISO 8601
  is_deleted_for_everyone: boolean;
  deleted_for_everyone_at: string | null;
  created_at: string; // ISO 8601
  updated_at: string; // ISO 8601
}
```

### 4.2. `message.updated`
Triggered when the original sender edits their message body (permitted only within 15 minutes of sending).

```typescript
interface MessageUpdatedEvent extends MessageSentEvent {
  is_edited: true;
  edited_at: string; // ISO 8601 timestamp
}
```

### 4.3. `message.deleted`
Triggered when the sender permanently deletes a message for everyone. Body and media should be rendered as "This message was deleted".

```typescript
interface MessageDeletedEvent {
  id: string; // ULID of the deleted message
  conversation_id: string; // ULID
  is_deleted_for_everyone: true;
  deleted_for_everyone_at: string; // ISO 8601
}
```

### 4.4. `message.reaction_updated`
Triggered when a user adds, changes, or removes their emoji reaction on a message.

```typescript
interface MessageReactionUpdatedEvent {
  message_id: string; // ULID
  conversation_id: string; // ULID
  user_id: string; // ULID of reacting user
  emoji: string | null; // Selected emoji string (e.g. "🔥" or "❤️"), or null if reaction was removed
}
```

### 4.5. `message.read`
Triggered when the other participant reads messages in the conversation.

```typescript
interface MessageReadEvent {
  conversation_id: string; // ULID
  reader_id: string; // ULID of user who read the messages
  last_read_message_id: string | null; // Watermark message ULID
  read_at: string; // ISO 8601
}
```

### 4.6. `typing.started`
Ephemeral event dispatched when a participant is actively typing. Does not persist in PostgreSQL.

```typescript
interface TypingStartedEvent {
  conversation_id: string; // ULID
  user_id: string; // ULID of typing participant
  user_name: string; // Display name
}
```

---

## 5. REST API Gating & Business Rules

| Action | HTTP Endpoint | Gating Rules & Constraints |
|---|---|---|
| **Start Conversation** | `POST /api/v1/conversations` | Requires that the initiator has an accepted follow toward the recipient (`isFollowing`), or that a conversation already exists between them. Fails with `403` if not following or if either user has blocked the other. Fails with `422` on self-target. Idempotent: returns existing thread if one exists. |
| **List Recipients** | `GET /api/v1/conversations/recipients` | Returns eligible users that the authenticated user follows with follow status = 'accepted' (even if those users do not follow back). Blocked accounts in either direction are automatically filtered out. |
| **Send Message** | `POST /api/v1/conversations/{id}/messages` | Participant only. Blocked conversations return `403`. Maximum 4 images per message. Attached images must be pre-uploaded via Media API (`status: ready`) and cannot be reused. |
| **Send Typing** | `POST /api/v1/conversations/{id}/typing` | Rate limit: Throttle to max 1 request every 2.5–3 seconds. Blocked participants return `403`. |
| **Edit Message** | `PATCH /api/v1/messages/{id}` | Author only. Strictly locked after 15 minutes (`403 Forbidden`). |
| **Delete for Everyone** | `DELETE /api/v1/messages/{id}` | Author only. Redacts body and wipes media attachments for all participants. |
| **Delete for Me** | `POST /api/v1/messages/{id}/delete-for-me` | Affects only the authenticated user. Recipient continues to see the message. |
| **Clear History / Hide** | `DELETE /api/v1/conversations/{id}` | Clears historical messages for current user (`cleared_at`). Resurfaces automatically if recipient sends a new message later. |
| **Block User** | `POST /api/v1/users/{id}/block` | Disallows new messages and real-time subscription in both directions. Historical messages remain visible read-only. |

---

## 6. React Native / Expo Integration Examples

### Example: Active Thread Screen Hook (`useChatThread.ts`)

```typescript
import { useEffect, useState, useRef, useCallback } from 'react';
import Echo from 'laravel-echo';

interface UseChatThreadProps {
  echo: Echo;
  conversationId: string;
  currentUserId: string;
}

export const useChatThread = ({ echo, conversationId, currentUserId }: UseChatThreadProps) => {
  const [messages, setMessages] = useState<any[]>([]);
  const [isTyping, setIsTyping] = useState<boolean>(false);
  const typingTimeoutRef = useRef<NodeJS.Timeout | null>(null);

  useEffect(() => {
    // 1. Subscribe to the private conversation channel
    const channel = echo.private(`conversations.${conversationId}`);

    // 2. New message received
    channel.listen('.message.sent', (event: any) => {
      setMessages((prev) => {
        // Prevent duplicate if already added optimistically
        if (prev.some((m) => m.id === event.id)) return prev;
        return [...prev, event];
      });
    });

    // 3. Message edited
    channel.listen('.message.updated', (event: any) => {
      setMessages((prev) =>
        prev.map((msg) =>
          msg.id === event.id
            ? { ...msg, body: event.body, is_edited: true, edited_at: event.edited_at }
            : msg
        )
      );
    });

    // 4. Message deleted for everyone
    channel.listen('.message.deleted', (event: any) => {
      setMessages((prev) =>
        prev.map((msg) =>
          msg.id === event.id
            ? {
                ...msg,
                body: null,
                media: [],
                is_deleted_for_everyone: true,
                deleted_for_everyone_at: event.deleted_for_everyone_at,
              }
            : msg
        )
      );
    });

    // 5. Reaction updated or removed
    channel.listen('.message.reaction_updated', (event: any) => {
      setMessages((prev) =>
        prev.map((msg) => {
          if (msg.id !== event.message_id) return msg;

          const existing = msg.reactions || [];
          const isMe = event.user_id === currentUserId;

          // Recompute reaction counts
          let updated = existing.filter((r: any) => !(isMe && r.user_reacted));
          if (event.emoji) {
            const found = updated.find((r: any) => r.emoji === event.emoji);
            if (found) {
              found.count += 1;
              if (isMe) found.user_reacted = true;
            } else {
              updated.push({ emoji: event.emoji, count: 1, user_reacted: isMe });
            }
          }
          return { ...msg, reactions: updated };
        })
      );
    });

    // 6. Typing indicator with auto-expiry
    channel.listen('.typing.started', (event: any) => {
      if (event.user_id === currentUserId) return;

      setIsTyping(true);
      if (typingTimeoutRef.current) clearTimeout(typingTimeoutRef.current);
      typingTimeoutRef.current = setTimeout(() => {
        setIsTyping(false);
      }, 3500);
    });

    // 7. Cleanup on unmount
    return () => {
      if (typingTimeoutRef.current) clearTimeout(typingTimeoutRef.current);
      echo.leave(`conversations.${conversationId}`);
    };
  }, [conversationId, echo, currentUserId]);

  return { messages, setMessages, isTyping };
};
```

### Example: Typing Indicator Trigger (`useSendTyping.ts`)

```typescript
import { useRef, useCallback } from 'react';
import axios from 'axios';

export const useSendTyping = (conversationId: string, apiToken: string) => {
  const lastSentRef = useRef<number>(0);

  const sendTyping = useCallback(async () => {
    const now = Date.now();
    // Throttle to send at most once every 3 seconds
    if (now - lastSentRef.current < 3000) return;

    lastSentRef.current = now;
    try {
      await axios.post(
        `/api/v1/conversations/${conversationId}/typing`,
        {},
        { headers: { Authorization: `Bearer ${apiToken}` } }
      );
    } catch {
      // Ignore typing delivery failures silently
    }
  }, [conversationId, apiToken]);

  return { sendTyping };
};
```

---

## 7. Conversation Data Contract

Responses from `GET /api/v1/conversations`, `POST /api/v1/conversations`, and `GET /api/v1/conversations/{id}` encapsulate conversation details under a consistent `recipient` object:

### TypeScript Interfaces

```typescript
export interface BackendAvatar {
  id: string;
  url: string;
  user_id?: string;
  type?: string;
  mime_type?: string;
  size?: number;
  width?: number;
  height?: number;
  status?: string;
}

export interface ConversationRecipient {
  id: string;
  name: string; // profile.display_name, falling back to users.name (never null)
  username: string | null; // Nullable if not set
  avatar: BackendAvatar | null; // Null if no avatar or if avatar media status is not 'ready'
}

export interface Conversation {
  id: string;
  created_by: string; // ULID of initiator
  recipient: ConversationRecipient | null;
  last_message: Message | null;
  unread_count: number;
  last_read_message_id: string | null;
  partner_last_read_message_id: string | null;
  is_muted: boolean;
  is_archived: boolean;
  can_send_messages: boolean;
  has_blocked_participant: boolean;
  last_message_at: string | null; // ISO 8601
  created_at: string;
  updated_at: string;
}
```


