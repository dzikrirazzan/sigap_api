/**
 * Emergency API Client - JavaScript/TypeScript Implementation
 * Ready to use for any frontend framework (React, Vue, Angular, Vanilla JS)
 */

class EmergencyAPIClient {
  constructor(config) {
    this.baseURL = config.apiUrl || "http://localhost:8000/api";
    this.token = localStorage.getItem("auth_token");
  }

  // Authentication
  async login(email, password) {
    try {
      const response = await this.request("POST", "/login", {
        email,
        password,
      });

      if (response.success) {
        this.token = response.token;
        localStorage.setItem("auth_token", this.token);
        localStorage.setItem("user_data", JSON.stringify(response.user));
        return response;
      }
      throw new Error(response.message || "Login failed");
    } catch (error) {
      console.error("Login error:", error);
      throw error;
    }
  }

  async logout() {
    try {
      await this.request("POST", "/logout");
    } catch (error) {
      console.error("Logout error:", error);
    } finally {
      this.token = null;
      localStorage.removeItem("auth_token");
      localStorage.removeItem("user_data");
    }
  }

  getCurrentUser() {
    const userData = localStorage.getItem("user_data");
    return userData ? JSON.parse(userData) : null;
  }

  isAuthenticated() {
    return !!this.token;
  }

  // Panic Alert Functions
  async sendPanicAlert(latitude, longitude, locationDescription = "") {
    try {
      const response = await this.request("POST", "/panic", {
        latitude,
        longitude,
        location_description: locationDescription,
      });

      if (response.success) {
        console.log("Panic alert sent successfully:", response.panic);
        return response;
      }
      throw new Error(response.message || "Failed to send panic alert");
    } catch (error) {
      console.error("Panic alert error:", error);
      throw error;
    }
  }

  // Relawan Functions
  async getTodayPanics() {
    try {
      const response = await this.request("GET", "/panic/today");
      return response;
    } catch (error) {
      console.error("Get today panics error:", error);
      throw error;
    }
  }

  async handlePanic(panicId) {
    try {
      const response = await this.request("PUT", `/panic/${panicId}/handle`);
      if (response.success) {
        console.log("Panic handled successfully:", response.panic);
        return response;
      }
      throw new Error(response.message || "Failed to handle panic");
    } catch (error) {
      console.error("Handle panic error:", error);
      throw error;
    }
  }

  async resolvePanic(panicId) {
    try {
      const response = await this.request("PUT", `/panic/${panicId}/resolve`);
      if (response.success) {
        console.log("Panic resolved successfully:", response.panic);
        return response;
      }
      throw new Error(response.message || "Failed to resolve panic");
    } catch (error) {
      console.error("Resolve panic error:", error);
      throw error;
    }
  }

  async getMyShifts(startDate = null, endDate = null) {
    try {
      const params = new URLSearchParams();
      if (startDate) params.append("start_date", startDate);
      if (endDate) params.append("end_date", endDate);

      const url = `/panic/my-shifts${params.toString() ? "?" + params.toString() : ""}`;
      const response = await this.request("GET", url);
      return response;
    } catch (error) {
      console.error("Get my shifts error:", error);
      throw error;
    }
  }

  // Browser Notifications
  async requestNotificationPermission() {
    if ("Notification" in window) {
      const permission = await Notification.requestPermission();
      return permission === "granted";
    }
    return false;
  }

  showBrowserNotification(title, body, options = {}) {
    if ("Notification" in window && Notification.permission === "granted") {
      const notification = new Notification(title, {
        body,
        icon: "/favicon.ico",
        badge: "/favicon.ico",
        ...options,
      });

      // Auto close after 10 seconds
      setTimeout(() => {
        notification.close();
      }, 10000);

      return notification;
    }
  }

  // Location Functions
  getCurrentLocation() {
    return new Promise((resolve, reject) => {
      if (!navigator.geolocation) {
        reject(new Error("Geolocation is not supported by this browser"));
        return;
      }

      navigator.geolocation.getCurrentPosition(
        (position) => {
          resolve({
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy,
          });
        },
        (error) => {
          reject(error);
        },
        {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 60000,
        }
      );
    });
  }

  // Utility Functions
  async request(method, endpoint, data = null) {
    const url = this.baseURL + endpoint;
    const headers = {
      "Content-Type": "application/json",
      Accept: "application/json",
    };

    if (this.token) {
      headers["Authorization"] = "Bearer " + this.token;
    }

    const config = {
      method,
      headers,
    };

    if (data && (method === "POST" || method === "PUT" || method === "PATCH")) {
      config.body = JSON.stringify(data);
    }

    try {
      const response = await fetch(url, config);
      const responseData = await response.json();

      if (!response.ok) {
        throw new Error(responseData.message || `HTTP error! status: ${response.status}`);
      }

      return responseData;
    } catch (error) {
      console.error("API request error:", error);
      throw error;
    }
  }
}

// Usage Examples:

// 1. Initialize client
const apiClient = new EmergencyAPIClient({
  apiUrl: "http://localhost:8000/api",
});

// 2. Login
async function loginUser() {
  try {
    const result = await apiClient.login("user@example.com", "password");
    console.log("Login successful:", result.user);
  } catch (error) {
    console.error("Login failed:", error.message);
  }
}

// 3. Send Panic Alert
async function sendEmergencyAlert() {
  try {
    const location = await apiClient.getCurrentLocation();
    const result = await apiClient.sendPanicAlert(location.latitude, location.longitude, "Emergency situation at my location");
    console.log("Emergency alert sent:", result);
  } catch (error) {
    console.error("Failed to send emergency alert:", error.message);
  }
}

// 4. Handle Panic (for relawan)
async function handleEmergency(panicId) {
  try {
    const result = await apiClient.handlePanic(panicId);
    console.log("Emergency handled:", result);
  } catch (error) {
    console.error("Failed to handle emergency:", error.message);
  }
}

// Export for module systems
if (typeof module !== "undefined" && module.exports) {
  module.exports = EmergencyAPIClient;
}

// Export for ES6 modules
if (typeof window !== "undefined") {
  window.EmergencyAPIClient = EmergencyAPIClient;
}
