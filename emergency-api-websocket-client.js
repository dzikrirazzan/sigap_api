/**
 * Emergency API WebSocket Client
 * Ready-to-use JavaScript library untuk connect ke Emergency API
 */

class EmergencyAPIClient {
  constructor(config) {
    this.apiUrl = config.apiUrl;
    this.websocketHost = config.websocketHost;
    this.websocketPort = config.websocketPort;
    this.websocketKey = config.websocketKey;
    this.authToken = null;
    this.pusher = null;
    this.channels = {};
  }

  /**
   * Login user dan dapatkan auth token
   */
  async login(email, password) {
    try {
      const response = await fetch(`${this.apiUrl}/api/login`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({ email, password }),
      });

      const data = await response.json();

      if (data.success) {
        this.authToken = data.token;
        return { success: true, user: data.user, token: data.token };
      } else {
        return { success: false, message: data.message };
      }
    } catch (error) {
      return { success: false, message: error.message };
    }
  }

  /**
   * Setup WebSocket connection
   */
  initWebSocket() {
    if (!this.authToken) {
      throw new Error("Auth token required. Please login first.");
    }

    // Import Pusher (pastikan sudah di-load)
    if (typeof Pusher === "undefined") {
      throw new Error("Pusher.js library not found. Please include it first.");
    }

    this.pusher = new Pusher(this.websocketKey, {
      wsHost: this.websocketHost,
      wsPort: this.websocketPort,
      wssPort: this.websocketPort,
      forceTLS: false,
      enabledTransports: ["ws", "wss"],
      authEndpoint: `${this.apiUrl}/broadcasting/auth`,
      auth: {
        headers: {
          Authorization: "Bearer " + this.authToken,
          Accept: "application/json",
        },
      },
    });

    // Connection event listeners
    this.pusher.connection.bind("connected", () => {
      console.log("✅ WebSocket connected");
      this.onConnectionChange?.(true);
    });

    this.pusher.connection.bind("disconnected", () => {
      console.log("❌ WebSocket disconnected");
      this.onConnectionChange?.(false);
    });

    this.pusher.connection.bind("error", (error) => {
      console.error("WebSocket error:", error);
      this.onError?.(error);
    });

    return this.pusher;
  }

  /**
   * Subscribe ke relawan channel untuk menerima panic alerts
   */
  subscribeToRelawanChannel(relawanId, callbacks = {}) {
    if (!this.pusher) {
      throw new Error("WebSocket not initialized. Call initWebSocket() first.");
    }

    const channelName = `private-relawan.${relawanId}`;
    const channel = this.pusher.subscribe(channelName);

    // Listen for panic alerts
    channel.bind("panic.alert", (data) => {
      console.log("🚨 Panic Alert Received:", data);
      callbacks.onPanicAlert?.(data);
      this.onPanicAlert?.(data);
    });

    // Channel subscription events
    channel.bind("pusher:subscription_succeeded", () => {
      console.log(`✅ Subscribed to ${channelName}`);
      callbacks.onSubscribed?.(channelName);
    });

    channel.bind("pusher:subscription_error", (error) => {
      console.error(`❌ Subscription error for ${channelName}:`, error);
      callbacks.onSubscriptionError?.(error);
    });

    this.channels[channelName] = channel;
    return channel;
  }

  /**
   * Send panic alert (untuk user)
   */
  async sendPanicAlert(latitude, longitude, locationDescription = "") {
    if (!this.authToken) {
      throw new Error("Auth token required. Please login first.");
    }

    try {
      const response = await fetch(`${this.apiUrl}/api/panic`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
          Authorization: "Bearer " + this.authToken,
        },
        body: JSON.stringify({
          latitude,
          longitude,
          location_description: locationDescription,
        }),
      });

      const data = await response.json();
      return data;
    } catch (error) {
      return { success: false, message: error.message };
    }
  }

  /**
   * Get panic reports for today (untuk relawan)
   */
  async getTodayPanicReports() {
    if (!this.authToken) {
      throw new Error("Auth token required. Please login first.");
    }

    try {
      const response = await fetch(`${this.apiUrl}/api/panic/today`, {
        headers: {
          Accept: "application/json",
          Authorization: "Bearer " + this.authToken,
        },
      });

      return await response.json();
    } catch (error) {
      return { success: false, message: error.message };
    }
  }

  /**
   * Handle panic report (untuk relawan)
   */
  async handlePanicReport(panicId) {
    if (!this.authToken) {
      throw new Error("Auth token required. Please login first.");
    }

    try {
      const response = await fetch(`${this.apiUrl}/api/panic/${panicId}/handle`, {
        method: "POST",
        headers: {
          Accept: "application/json",
          Authorization: "Bearer " + this.authToken,
        },
      });

      return await response.json();
    } catch (error) {
      return { success: false, message: error.message };
    }
  }

  /**
   * Resolve panic report (untuk relawan)
   */
  async resolvePanicReport(panicId) {
    if (!this.authToken) {
      throw new Error("Auth token required. Please login first.");
    }

    try {
      const response = await fetch(`${this.apiUrl}/api/panic/${panicId}/resolve`, {
        method: "POST",
        headers: {
          Accept: "application/json",
          Authorization: "Bearer " + this.authToken,
        },
      });

      return await response.json();
    } catch (error) {
      return { success: false, message: error.message };
    }
  }

  /**
   * Test WebSocket functionality
   */
  async testWebSocket() {
    try {
      const response = await fetch(`${this.apiUrl}/api/panic/test-websocket`, {
        method: "POST",
        headers: {
          Accept: "application/json",
        },
      });

      return await response.json();
    } catch (error) {
      return { success: false, message: error.message };
    }
  }

  /**
   * Disconnect WebSocket
   */
  disconnect() {
    if (this.pusher) {
      // Unsubscribe dari semua channels
      Object.keys(this.channels).forEach((channelName) => {
        this.pusher.unsubscribe(channelName);
      });

      this.pusher.disconnect();
      this.pusher = null;
      this.channels = {};
    }
  }

  /**
   * Event listeners (bisa di-override dari luar)
   */
  onConnectionChange = null; // (connected: boolean) => void
  onPanicAlert = null; // (data: PanicAlertData) => void
  onError = null; // (error: any) => void
}

// Export untuk penggunaan di browser atau Node.js
if (typeof module !== "undefined" && module.exports) {
  module.exports = EmergencyAPIClient;
} else if (typeof window !== "undefined") {
  window.EmergencyAPIClient = EmergencyAPIClient;
}

/**
 * Usage Examples:
 *
 * // 1. Initialize client
 * const client = new EmergencyAPIClient({
 *     apiUrl: 'https://your-api-domain.com',
 *     websocketHost: 'your-api-domain.com',
 *     websocketPort: 6001,
 *     websocketKey: 'sigap-key'
 * });
 *
 * // 2. Login
 * const loginResult = await client.login('relawan@example.com', 'password');
 * if (loginResult.success) {
 *     console.log('Login success:', loginResult.user);
 * }
 *
 * // 3. Setup WebSocket
 * client.initWebSocket();
 *
 * // 4. Subscribe to panic alerts (untuk relawan)
 * client.subscribeToRelawanChannel(relawanId, {
 *     onPanicAlert: (data) => {
 *         alert('PANIC ALERT! ' + data.panic.location_description);
 *     }
 * });
 *
 * // 5. Send panic alert (untuk user)
 * const result = await client.sendPanicAlert(-6.2088, 106.8456, 'Bantuan!');
 *
 * // 6. Get today's panic reports (untuk relawan)
 * const reports = await client.getTodayPanicReports();
 *
 * // 7. Handle panic report (untuk relawan)
 * await client.handlePanicReport(panicId);
 *
 * // 8. Cleanup
 * client.disconnect();
 */
