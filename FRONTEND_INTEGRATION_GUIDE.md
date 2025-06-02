# 🔗 Frontend Integration Guide - Emergency API

## 📋 Yang Perlu Diberikan ke Tim Frontend

### 1. **API Base URL & Endpoints**

#### Production URL (DigitalOcean)

```
Base URL: https://your-domain.com/api
WebSocket URL: wss://your-domain.com:6001
```

#### Development URL (Local)

```
Base URL: http://localhost:8000/api
WebSocket URL: ws://localhost:6001
```

### 2. **Authentication System**

#### Login Endpoint

```
POST /api/login
Content-Type: application/json

Body:
{
  "email": "user@example.com",
  "password": "password"
}

Response:
{
  "success": true,
  "token": "21|xyz...",
  "user": {
    "id": 1,
    "name": "User Name",
    "email": "user@example.com",
    "role": "user" // atau "relawan" atau "admin"
  }
}
```

#### Header Authentication

```
Authorization: Bearer {token}
```

### 3. **Main API Endpoints**

#### A. Panic Alert (User)

```
POST /api/panic
Authorization: Bearer {token}
Content-Type: application/json

Body:
{
  "latitude": -6.2088,
  "longitude": 106.8456,
  "location_description": "Emergency location description"
}

Response:
{
  "success": true,
  "panic": {
    "id": 123,
    "user_id": 1,
    "latitude": -6.2088,
    "longitude": 106.8456,
    "location_description": "Emergency location description",
    "status": "pending",
    "created_at": "2025-05-29T10:30:00.000000Z"
  },
  "message": "Panic report sent to on-duty relawan"
}
```

#### B. Today's Panic Reports (Relawan)

```
GET /api/panic/today
Authorization: Bearer {relawan_token}

Response:
[
  {
    "id": 123,
    "user": {
      "id": 1,
      "name": "Emergency User",
      "email": "user@example.com",
      "no_telp": "081234567890",
      "nik": "1234567890123456"
    },
    "latitude": -6.2088,
    "longitude": 106.8456,
    "location_description": "Emergency location",
    "status": "pending",
    "created_at": "2025-05-29T10:30:00.000000Z"
  }
]
```

#### C. Handle Panic Report (Relawan)

```
PUT /api/panic/{id}/handle
Authorization: Bearer {relawan_token}

Response:
{
  "success": true,
  "panic": { /* updated panic data */ },
  "message": "Panic report is now being handled"
}
```

#### D. Resolve Panic Report (Relawan)

```
PUT /api/panic/{id}/resolve
Authorization: Bearer {relawan_token}

Response:
{
  "success": true,
  "panic": { /* updated panic data */ },
  "message": "Panic report resolved"
}
```

#### E. My Shifts (Relawan)

```
GET /api/panic/my-shifts
Authorization: Bearer {relawan_token}

Optional Query Parameters:
- start_date: YYYY-MM-DD
- end_date: YYYY-MM-DD

Response:
{
  "relawan": {
    "id": 2,
    "name": "Relawan Name",
    "email": "relawan@example.com"
  },
  "is_on_duty_today": true,
  "shifts": [
    {
      "id": 1,
      "shift_date": "2025-05-29",
      "is_today": true,
      "is_past": false,
      "day_name": "Rabu",
      "date_formatted": "29 Mei 2025"
    }
  ],
  "total_shifts": 5
}
```

### 4. **WebSocket Configuration**

#### JavaScript WebSocket Setup

```javascript
// Pusher configuration
const pusher = new Pusher("sigap-key", {
  wsHost: "your-domain.com", // atau 'localhost' untuk dev
  wsPort: 6001,
  wssPort: 6001,
  forceTLS: false, // true untuk production HTTPS
  enabledTransports: ["ws", "wss"],
  auth: {
    headers: {
      Authorization: "Bearer " + userToken,
    },
  },
});

// Subscribe to relawan channel (untuk relawan)
const channel = pusher.subscribe("private-relawan." + relawanId);

// Listen for panic alerts
channel.bind("panic.alert", function (data) {
  console.log("New panic alert:", data);
  // Handle real-time panic alert
  showPanicNotification(data.panic);
});
```

#### WebSocket Authentication

```
POST /broadcasting/auth
Authorization: Bearer {token}
Content-Type: application/x-www-form-urlencoded

Body:
socket_id={socket_id}&channel_name=private-relawan.{relawan_id}

Response:
{
  "auth": "signature_here"
}
```

### 5. **Dependencies yang Dibutuhkan Frontend**

#### NPM Packages

```bash
npm install pusher-js
# atau
npm install laravel-echo pusher-js
```

#### CDN (jika tidak pakai bundler)

```html
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
```

### 6. **Environment Variables untuk Frontend**

```javascript
// .env atau config
const API_CONFIG = {
  BASE_URL: process.env.REACT_APP_API_URL || "http://localhost:8000/api",
  WEBSOCKET_HOST: process.env.REACT_APP_WS_HOST || "localhost",
  WEBSOCKET_PORT: process.env.REACT_APP_WS_PORT || "6001",
  PUSHER_KEY: "sigap-key",
  PUSHER_CLUSTER: "mt1",
};
```

### 7. **CORS Configuration**

Backend sudah dikonfigurasi untuk menerima request dari domain lain. Frontend bisa akses dari:

- `http://localhost:3000` (React default)
- `http://localhost:8080` (Vue default)
- `http://127.0.0.1:*`
- Domain production frontend

### 8. **Sample Frontend Implementation**

#### React Example

```jsx
import { useState, useEffect } from "react";
import Pusher from "pusher-js";

function PanicDashboard() {
  const [panics, setPanics] = useState([]);
  const [token, setToken] = useState(localStorage.getItem("auth_token"));

  useEffect(() => {
    // Setup WebSocket
    const pusher = new Pusher("sigap-key", {
      wsHost: "localhost",
      wsPort: 6001,
      forceTLS: false,
      auth: {
        headers: {
          Authorization: "Bearer " + token,
        },
      },
    });

    const channel = pusher.subscribe("private-relawan." + userId);

    channel.bind("panic.alert", (data) => {
      setPanics((prev) => [data.panic, ...prev]);
      // Show notification
      showNotification("New Emergency Alert!", data.panic.location_description);
    });

    return () => {
      pusher.unsubscribe("private-relawan." + userId);
      pusher.disconnect();
    };
  }, [token, userId]);

  const sendPanicAlert = async (location) => {
    try {
      const response = await fetch("http://localhost:8000/api/panic", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: "Bearer " + token,
        },
        body: JSON.stringify({
          latitude: location.lat,
          longitude: location.lng,
          location_description: location.description,
        }),
      });

      const result = await response.json();
      if (result.success) {
        alert("Panic alert sent successfully!");
      }
    } catch (error) {
      console.error("Error sending panic alert:", error);
    }
  };

  return <div>{/* Your frontend UI */}</div>;
}
```

### 9. **Error Handling**

#### Common HTTP Status Codes

```
200: Success
201: Created successfully
400: Bad request (validation error)
401: Unauthorized (invalid token)
403: Forbidden (insufficient permissions)
404: Not found
422: Validation error
500: Server error
```

#### Sample Error Response

```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "latitude": ["The latitude field is required."],
    "longitude": ["The longitude field is required."]
  }
}
```

### 10. **Testing Endpoints**

#### Test WebSocket Connection

```
POST /api/panic/test-websocket

Response:
{
  "success": true,
  "message": "Test WebSocket notification sent to 4 relawan(s)",
  "relawan_notified": ["Relawan 1", "Relawan 2"],
  "test_data": {
    "panic_id": 123,
    "user_name": "Test User",
    "location": "Test Location",
    "coordinates": "-6.2088, 106.8456"
  }
}
```

### 11. **Security Considerations**

1. **Token Storage**: Simpan auth token di localStorage atau secure cookie
2. **Token Expiry**: Handle token expiration dan auto-refresh
3. **HTTPS**: Gunakan HTTPS di production (WSS untuk WebSocket)
4. **Validation**: Validate semua input di frontend sebelum kirim ke API
5. **Sanitization**: Sanitize data yang ditampilkan untuk prevent XSS

---

## 🚀 Quick Start untuk Frontend

1. **Install dependencies**: `npm install pusher-js`
2. **Set environment variables**: API URL, WebSocket config
3. **Implement authentication**: Login/logout flow
4. **Setup WebSocket**: Real-time connection untuk relawan
5. **Create panic button**: Interface untuk user mengirim panic alert
6. **Build relawan dashboard**: Interface untuk relawan handle emergency

**Sample repo/boilerplate bisa dikasih jika diperlukan!** 🎯
