# Frontend WebSocket Implementation Guide

## 🎯 Apa yang Perlu Diimplementasikan di Frontend

### 1. **WebSocket Connection**

Frontend perlu connect ke WebSocket server backend kamu:

```javascript
// Connect ke WebSocket server
const websocket = new WebSocket("ws://your-domain.com:6001/app/sigap-key?protocol=7&client=js&version=4.3.1");
```

### 2. **Authentication**

Frontend perlu mengirim token auth untuk subscribe ke private channels:

```javascript
// Authenticate WebSocket connection
fetch("/broadcasting/auth", {
  method: "POST",
  headers: {
    Authorization: "Bearer " + userToken,
    "Content-Type": "application/json",
  },
  body: JSON.stringify({
    socket_id: socketId,
    channel_name: "private-relawan." + userId,
  }),
});
```

### 3. **Listen for Events**

Frontend perlu listen event panic alerts:

```javascript
// Listen untuk panic alerts
websocket.addEventListener("message", (event) => {
  const data = JSON.parse(event.data);
  if (data.event === "panic.alert") {
    showPanicAlert(data.data);
  }
});
```

## 🚀 Implementasi Options

### Option 1: Manual JavaScript Implementation

Kamu bisa implement sendiri dengan vanilla JavaScript atau library seperti:

- Pusher.js
- Socket.io-client
- Laravel Echo

### Option 2: Kasih ke Frontend Developer

Berikan dokumentasi dan contoh kode ke frontend developer untuk diimplementasikan.

## 📋 Yang Perlu Dikasih ke Frontend

### A. **Environment Configuration**

```env
# Production URLs (sesuaikan dengan domain kamu)
REACT_APP_API_URL=https://your-api-domain.com
REACT_APP_WEBSOCKET_HOST=your-api-domain.com
REACT_APP_WEBSOCKET_PORT=6001
REACT_APP_WEBSOCKET_APP_KEY=sigap-key
REACT_APP_WEBSOCKET_APP_ID=sigap-emergency
```

### B. **API Endpoints Documentation**

- Authentication endpoints
- Panic alert endpoints
- WebSocket broadcasting auth endpoint

### C. **WebSocket Configuration**

- Host, port, app key
- Channel names format
- Event names
- Authentication flow

### D. **Sample Code/Libraries**

- JavaScript SDK untuk connect ke API
- WebSocket connection examples
- Event handling examples

## 🎮 Implementation Examples

### React/Vue.js Implementation

```javascript
import Pusher from "pusher-js";

// Setup Pusher/WebSocket
const pusher = new Pusher("sigap-key", {
  wsHost: "your-domain.com",
  wsPort: 6001,
  wssPort: 6001,
  forceTLS: false,
  enabledTransports: ["ws", "wss"],
  auth: {
    headers: {
      Authorization: "Bearer " + userToken,
    },
  },
});

// Subscribe to relawan channel
const channel = pusher.subscribe("private-relawan." + relawanId);

// Listen for panic alerts
channel.bind("panic.alert", (data) => {
  // Show notification to relawan
  showPanicNotification(data.panic);
});
```

### React Native Implementation

```javascript
import Pusher from "pusher-js/react-native";

const pusher = new Pusher("sigap-key", {
  cluster: "mt1",
  authEndpoint: "https://your-api.com/broadcasting/auth",
  auth: {
    headers: {
      Authorization: "Bearer " + userToken,
    },
  },
});
```

## 🔄 Flow Implementasi

### 1. **User Authentication**

```
Frontend → POST /api/login → Get Token → Store Token
```

### 2. **WebSocket Connection**

```
Frontend → Connect WebSocket → Authenticate → Subscribe Channel
```

### 3. **Panic Alert Flow**

```
User Panic → Backend Broadcast → WebSocket → Frontend Receive → Show Alert
```

## 📱 Frontend Components Needed

### A. **Authentication Components**

- Login form
- Token storage (localStorage/AsyncStorage)
- Auth context/state management

### B. **WebSocket Components**

- WebSocket connection service
- Event listener service
- Reconnection logic

### C. **UI Components**

- Panic alert notifications
- Alert sound/vibration
- Alert list/history
- Real-time status indicators

### D. **Panic Button (User App)**

- Location detection
- Panic button UI
- Alert sending confirmation

### E. **Relawan Dashboard (Relawan App)**

- Real-time alert list
- Alert details modal
- Handle/resolve actions
- Map integration

## 🛠 Tools & Libraries Recommended

### JavaScript/Web

- **Pusher.js** - WebSocket client
- **Axios** - HTTP requests
- **React Query/SWR** - Data fetching
- **React Hot Toast** - Notifications

### React Native

- **Pusher React Native** - WebSocket
- **React Native Push Notification** - Local notifications
- **React Native Sound** - Alert sounds
- **React Native Maps** - Location display

### Vue.js

- **Pusher.js** - WebSocket
- **Vue Toastification** - Notifications
- **Pinia/Vuex** - State management

## 📄 Files to Provide to Frontend

1. **API Documentation** (Postman Collection)
2. **WebSocket Configuration** (Environment variables)
3. **JavaScript SDK** (Helper functions)
4. **Sample Implementation** (Working examples)
5. **Authentication Guide** (Token flow)
6. **Event Schemas** (Data structures)

## ⚡ Quick Start for Frontend

### 1. Install Dependencies

```bash
npm install pusher-js axios
# or
npm install @pusher/pusher-websocket-react-native
```

### 2. Setup Environment

```env
REACT_APP_API_URL=https://your-api.com
REACT_APP_WEBSOCKET_KEY=sigap-key
REACT_APP_WEBSOCKET_HOST=your-api.com
REACT_APP_WEBSOCKET_PORT=6001
```

### 3. Implement WebSocket Service

```javascript
// services/websocket.js
import Pusher from "pusher-js";

export class WebSocketService {
  constructor(authToken) {
    this.pusher = new Pusher(process.env.REACT_APP_WEBSOCKET_KEY, {
      wsHost: process.env.REACT_APP_WEBSOCKET_HOST,
      wsPort: process.env.REACT_APP_WEBSOCKET_PORT,
      forceTLS: false,
      auth: {
        headers: {
          Authorization: "Bearer " + authToken,
        },
      },
    });
  }

  subscribeToRelawanChannel(relawanId, onPanicAlert) {
    const channel = this.pusher.subscribe(`private-relawan.${relawanId}`);
    channel.bind("panic.alert", onPanicAlert);
    return channel;
  }
}
```

### 4. Use in Component

```javascript
// components/RelawanDashboard.js
import { WebSocketService } from "../services/websocket";

function RelawanDashboard() {
  useEffect(() => {
    const wsService = new WebSocketService(userToken);

    const channel = wsService.subscribeToRelawanChannel(relawanId, (data) => {
      // Handle panic alert
      showAlert(data.panic);
      playAlertSound();
    });

    return () => channel.unsubscribe();
  }, []);
}
```

## 🎯 Kesimpulan

**Backend (Kamu)**: ✅ Sudah selesai

- WebSocket server running
- Broadcasting system working
- API endpoints ready

**Frontend**: 🔄 Perlu implementasi

- WebSocket client connection
- Event listening & handling
- UI components untuk alerts
- Authentication integration

Kamu bisa kasih semua dokumentasi dan contoh kode ke frontend developer, atau implement sendiri kalau mau buat frontend juga.
