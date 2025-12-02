# Chat API Documentation - WhatsApp-Style Reply Feature

## Overview
API ini mendukung fitur chat dengan AI (Mei) dengan kemampuan reply to message seperti WhatsApp.

---

## 📡 Endpoints

### 1. **Send Message to AI** (dengan atau tanpa reply)
**POST** `/api/v1/gemini/generate`

#### Request Payload (Tanpa Reply)
```json
{
  "prompt": "Apa itu demam berdarah?",
  "session_id": "123e4567-e89b-12d3-a456-426614174000",
  "public_id": "user-device-123",
  "options": {
    "temperature": 0.7,
    "maxOutputTokens": 1000
  }
}
```

#### Request Payload (Dengan Reply ke Pesan Sebelumnya)
```json
{
  "prompt": "Bagaimana cara mencegahnya?",
  "session_id": "123e4567-e89b-12d3-a456-426614174000",
  "public_id": "user-device-123",
  "reply_to": "1733097600000",
  "options": {
    "temperature": 0.7,
    "maxOutputTokens": 1000
  }
}
```

#### Request Fields
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `prompt` | string | ✅ Yes | Pesan dari user |
| `session_id` | string | ❌ No | ID sesi chat (UUID). Jika tidak ada, akan membuat sesi baru |
| `public_id` | string | ❌ No | ID persisten user/device untuk tracking |
| `reply_to` | string | ❌ No | **ID pesan yang ingin di-reply** (seperti WhatsApp) |
| `new_session` | boolean | ❌ No | Force membuat sesi baru (default: false) |
| `options` | object | ❌ No | Opsi tambahan untuk AI |

#### Response (Success)
```json
{
  "reply": "Demam berdarah adalah penyakit yang disebabkan oleh virus dengue...",
  "raw": { /* Raw Gemini API response */ },
  "urgent": false,
  "actions": [],
  "session_id": "123e4567-e89b-12d3-a456-426614174000",
  "public_id": "user-device-123",
  "title": "Demam Berdarah"
}
```

#### Response Fields
| Field | Type | Description |
|-------|------|-------------|
| `reply` | string | Jawaban dari AI |
| `urgent` | boolean | Apakah situasi darurat (butuh konsultasi dokter) |
| `actions` | array | Action buttons (konsultasi, package recommendations) |
| `session_id` | string | ID sesi chat |
| `public_id` | string | ID persisten user |
| `title` | string | Judul sesi chat (auto-generated oleh AI) |

---

### 2. **Get Specific Message** (untuk preview saat reply)
**GET** `/api/v1/chat-activities/{session_id}/message/{message_id}`

#### Example
```
GET /api/v1/chat-activities/123e4567-e89b-12d3-a456-426614174000/message/1733097600000
```

#### Response
```json
{
  "message": {
    "id": "1733097600000",
    "message": "Demam berdarah adalah penyakit yang disebabkan oleh virus dengue...",
    "sender": "bot",
    "timestamp": "2025-12-02T10:30:00.000Z",
    "replyTo": null
  },
  "session_id": "123e4567-e89b-12d3-a456-426614174000",
  "session_title": "Demam Berdarah"
}
```

---

### 3. **Get Chat Session**
**GET** `/api/v1/chat-activities/{session_id}`

#### Response
```json
{
  "id": "123e4567-e89b-12d3-a456-426614174000",
  "title": "Demam Berdarah",
  "public_id": "user-device-123",
  "user_id": "supabase-user-id",
  "chat_activity_data": {
    "id": "123e4567-e89b-12d3-a456-426614174000",
    "title": "Demam Berdarah",
    "messages": [
      {
        "id": "1733097600000",
        "message": "Apa itu demam berdarah?",
        "sender": "user",
        "timestamp": "2025-12-02T10:30:00.000Z",
        "replyTo": null
      },
      {
        "id": "1733097600010",
        "message": "Demam berdarah adalah...",
        "sender": "bot",
        "timestamp": "2025-12-02T10:30:01.000Z",
        "replyTo": null
      },
      {
        "id": "1733097700000",
        "message": "Bagaimana cara mencegahnya?",
        "sender": "user",
        "timestamp": "2025-12-02T10:31:40.000Z",
        "replyTo": {
          "id": "1733097600010",
          "message": "Demam berdarah adalah...",
          "sender": "bot"
        }
      },
      {
        "id": "1733097700010",
        "message": "Cara mencegah demam berdarah: 1) 3M Plus...",
        "sender": "bot",
        "timestamp": "2025-12-02T10:31:41.000Z",
        "replyTo": null
      }
    ],
    "updatedAt": "2025-12-02T10:31:41.000Z"
  },
  "created_at": "2025-12-02T10:30:00.000000Z",
  "updated_at": "2025-12-02T10:31:41.000000Z"
}
```

---

### 4. **Get All Sessions for User**
**GET** `/api/v1/chat-activities/all/{public_id}`

#### Example
```
GET /api/v1/chat-activities/all/user-device-123
```

#### Response
```json
{
  "data": [
    {
      "id": "session-uuid-1",
      "title": "Demam Berdarah",
      "public_id": "user-device-123",
      "created_at": "2025-12-02T10:30:00.000000Z",
      "updated_at": "2025-12-02T10:31:41.000000Z"
    },
    {
      "id": "session-uuid-2",
      "title": "Sakit Kepala",
      "public_id": "user-device-123",
      "created_at": "2025-12-01T09:15:00.000000Z",
      "updated_at": "2025-12-01T09:20:00.000000Z"
    }
  ],
  "total": 2
}
```

---

### 5. **Delete Chat Session**
**DELETE** `/api/v1/chat-activities/{session_id}`

#### Response
```json
{
  "message": "Chat session deleted successfully"
}
```

---

### 6. **Delete All Sessions for User**
**DELETE** `/api/v1/chat-activities/all/{public_id}`

#### Response
```json
{
  "message": "All chat sessions deleted",
  "count": 5
}
```

---

## 🎨 UI Implementation Examples

### WhatsApp-Style Reply Component

#### 1. Display Message dengan Reply Preview
```javascript
// Message dengan reply
const message = {
  id: "1733097700000",
  message: "Bagaimana cara mencegahnya?",
  sender: "user",
  timestamp: "2025-12-02T10:31:40.000Z",
  replyTo: {
    id: "1733097600010",
    message: "Demam berdarah adalah penyakit yang disebabkan oleh virus dengue...",
    sender: "bot"
  }
};

// Tampilan UI
<div class="message-container">
  {message.replyTo && (
    <div class="reply-preview">
      <div class="reply-indicator"></div>
      <div class="reply-content">
        <span class="reply-sender">{message.replyTo.sender === 'bot' ? 'Mei' : 'You'}</span>
        <p class="reply-text">{message.replyTo.message.substring(0, 100)}...</p>
      </div>
    </div>
  )}
  <div class="message-content">
    {message.message}
  </div>
</div>
```

#### 2. Send Message dengan Reply
```javascript
async function sendMessageWithReply(prompt, replyToMessageId = null) {
  const response = await fetch('/api/v1/gemini/generate', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      prompt: prompt,
      session_id: currentSessionId,
      public_id: deviceId,
      reply_to: replyToMessageId // null jika tidak reply
    })
  });
  
  return await response.json();
}

// Usage
// Reply ke pesan AI
await sendMessageWithReply("Bagaimana cara mencegahnya?", "1733097600010");

// Kirim tanpa reply
await sendMessageWithReply("Apa itu demam berdarah?", null);
```

#### 3. Long Press untuk Reply (Mobile)
```javascript
function setupMessageLongPress() {
  messageElements.forEach(msgEl => {
    let pressTimer;
    
    msgEl.addEventListener('touchstart', (e) => {
      pressTimer = setTimeout(() => {
        showReplyMenu(msgEl.dataset.messageId);
      }, 500); // 500ms long press
    });
    
    msgEl.addEventListener('touchend', () => {
      clearTimeout(pressTimer);
    });
  });
}

function showReplyMenu(messageId) {
  // Show menu: [Reply] [Copy] [Delete]
  const menu = document.getElementById('message-menu');
  menu.dataset.messageId = messageId;
  menu.classList.add('visible');
}

async function handleReply(messageId) {
  // Get message details
  const response = await fetch(`/api/v1/chat-activities/${sessionId}/message/${messageId}`);
  const data = await response.json();
  
  // Show reply preview di input area
  showReplyPreview(data.message);
  
  // Set state untuk reply
  currentReplyTo = messageId;
}
```

---

## 📱 Message Structure

### Message Object (dalam chat_activity_data)
```typescript
interface Message {
  id: string;              // Timestamp in milliseconds
  message: string;         // Konten pesan
  sender: "user" | "bot";  // Pengirim
  timestamp: string;       // ISO 8601 format
  replyTo: {              // Null jika tidak reply
    id: string;
    message: string;
    sender: "user" | "bot";
  } | null;
}
```

### Session Object
```typescript
interface ChatSession {
  id: string;                    // UUID primary key
  title: string;                 // Auto-generated title
  public_id: string;             // Persistent user/device ID
  user_id: string | null;        // Supabase user ID (jika logged in)
  chat_activity_data: {
    id: string;
    title: string;
    messages: Message[];
    updatedAt: string;
  };
  created_at: string;
  updated_at: string;
}
```

---

## 🎯 Best Practices

### 1. **Reply Preview di Input**
Saat user pilih reply, tampilkan preview pesan yang di-reply di atas input field:
```
┌─────────────────────────────────────┐
│ ↪ Replying to Mei                   │
│ Demam berdarah adalah penyakit...  │
│ [X]                                 │
├─────────────────────────────────────┤
│ Type your message...                │
└─────────────────────────────────────┘
```

### 2. **Scroll to Original Message**
Saat user tap reply preview dalam chat, scroll ke pesan original dan highlight sebentar.

### 3. **Visual Indicator**
Gunakan garis vertikal di sebelah kiri untuk menunjukkan ini adalah reply:
```
┌──────────────────────┐
│ │ Mei               │
│ │ Demam berdarah... │
│                      │
│ Bagaimana cara       │
│ mencegahnya?         │
└──────────────────────┘
```

### 4. **Limit Reply Text**
Potong teks reply jika terlalu panjang (max 100-150 karakter) dan tambahkan "..."

---

## 🔄 Flow Diagram

```
User Action                     Frontend                  Backend
───────────────────────────────────────────────────────────────────
Long press message    →    Show reply menu
Select "Reply"        →    Set reply state
                           Show preview
                           
Type message          →    
Send                  →    POST /gemini/generate
                           with reply_to field    →    Find replied message
                                                       Build context
                                                       Save chat
                           
                      ←    Return response
Show message               with replyTo data
with reply preview    
```

---

## 🐛 Error Handling

### Message Not Found
Jika `reply_to` ID tidak ditemukan dalam session, backend akan ignore dan set `replyTo: null`.

### Session Not Found
Jika session tidak ditemukan, akan otomatis membuat session baru.

---

## 📊 Example Chat Flow

```json
// Message 1: User memulai chat
{
  "id": "1733097600000",
  "message": "Apa itu demam berdarah?",
  "sender": "user",
  "timestamp": "2025-12-02T10:30:00.000Z",
  "replyTo": null
}

// Message 2: Bot reply
{
  "id": "1733097600010",
  "message": "Demam berdarah adalah penyakit yang disebabkan oleh virus dengue yang ditularkan melalui gigitan nyamuk Aedes aegypti.",
  "sender": "bot",
  "timestamp": "2025-12-02T10:30:01.000Z",
  "replyTo": null
}

// Message 3: User reply ke message bot
{
  "id": "1733097700000",
  "message": "Bagaimana cara mencegahnya?",
  "sender": "user",
  "timestamp": "2025-12-02T10:31:40.000Z",
  "replyTo": {
    "id": "1733097600010",
    "message": "Demam berdarah adalah penyakit yang disebabkan oleh virus dengue yang ditularkan melalui gigitan nyamuk Aedes aegypti.",
    "sender": "bot"
  }
}

// Message 4: Bot menjawab dengan konteks reply
{
  "id": "1733097700010",
  "message": "Cara mencegah demam berdarah:\n1. 3M Plus (Menguras, Menutup, Mengubur)\n2. Gunakan kelambu\n3. Pakai lotion anti-nyamuk\n4. Pastikan tidak ada genangan air",
  "sender": "bot",
  "timestamp": "2025-12-02T10:31:41.000Z",
  "replyTo": null
}
```

---

## 🎨 CSS Example (WhatsApp Style)

```css
.message-container {
  margin-bottom: 12px;
  max-width: 80%;
}

.reply-preview {
  display: flex;
  background: rgba(0, 0, 0, 0.05);
  border-radius: 8px;
  padding: 8px;
  margin-bottom: 4px;
  border-left: 3px solid #25D366;
}

.reply-sender {
  color: #25D366;
  font-weight: 600;
  font-size: 13px;
}

.reply-text {
  color: #666;
  font-size: 14px;
  margin: 2px 0 0 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.message-content {
  background: white;
  padding: 12px;
  border-radius: 8px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

/* User message (kanan) */
.message-container.user {
  margin-left: auto;
}

.message-container.user .reply-preview {
  border-left-color: #075E54;
}

.message-container.user .message-content {
  background: #DCF8C6;
}

/* Bot message (kiri) */
.message-container.bot {
  margin-right: auto;
}
```

---

## ✅ Testing Checklist

- [ ] Send message tanpa reply
- [ ] Send message dengan reply ke bot message
- [ ] Send message dengan reply ke user message
- [ ] Get specific message by ID
- [ ] Reply preview ditampilkan dengan benar
- [ ] Reply ke message lama (scroll history)
- [ ] Multiple replies dalam satu session
- [ ] Session tidak ditemukan (create new)
- [ ] Message ID tidak ditemukan (ignore reply)

---

## 🚀 Ready to Implement!

Semua endpoint sudah siap. Frontend tinggal:
1. Implementasi UI long-press/right-click untuk reply
2. Tampilkan reply preview di input area
3. Kirim `reply_to` field saat POST
4. Render message dengan reply preview di chat bubble

Happy coding! 🎉
