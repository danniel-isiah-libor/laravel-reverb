<template>
  <div class="chat-container">
    <h2>Laravel Reverb + Echo POC</h2>

    <div
      class="connection-status"
      :class="connectionStatus === 'connected' ? 'connected' : 'disconnected'"
    >
      {{
        connectionStatus === "connected"
          ? "Connected to WebSocket"
          : `Status: ${connectionStatus}`
      }}
    </div>

    <div class="messages">
      <div v-if="messages.length === 0" class="no-messages">
        No messages yet. Send one below!
      </div>
      <div v-for="(msg, index) in messages" :key="index" class="message">
        <strong>{{ msg.username }}:</strong> {{ msg.message }}
        <span class="timestamp">{{ msg.time }}</span>
      </div>
    </div>

    <div v-if="typingUsers.length" class="typing-indicator">
      {{ typingText }}
    </div>

    <div class="input-area">
      <input
        v-model="username"
        type="text"
        placeholder="Your name"
        class="input-name"
      />
      <input
        v-model="newMessage"
        type="text"
        placeholder="Type a message..."
        class="input-message"
        @keyup.enter="sendMessage"
        @keyup="onTyping"
      />
      <button @click="sendMessage" :disabled="sending">
        {{ sending ? "Sending..." : "Send" }}
      </button>
    </div>

    <div v-if="error" class="error">{{ error }}</div>
  </div>
</template>

<script>
import { ref, computed } from "vue";
import { useEchoPublic, useConnectionStatus } from "@laravel/echo-vue";

export default {
  name: "ChatRoom",
  setup() {
    const messages = ref([]);
    const newMessage = ref("");
    const username = ref("User " + Math.floor(Math.random() * 1000));
    const sending = ref(false);
    const error = ref(null);
    const connectionStatus = useConnectionStatus();
    const typingUsers = ref([]);
    const typingTimers = {};
    let lastTypingSent = 0;

    const typingText = computed(() => {
      if (typingUsers.value.length === 1) {
        return `${typingUsers.value[0]} is typing...`;
      }
      if (typingUsers.value.length === 2) {
        return `${typingUsers.value[0]} and ${typingUsers.value[1]} are typing...`;
      }
      return "Several people are typing...";
    });

    useEchoPublic("chat", ".message.sent", (event) => {
      messages.value.push({
        username: event.username,
        message: event.message,
        time: new Date().toLocaleTimeString(),
      });

      // Remove from typing when they send a message
      typingUsers.value = typingUsers.value.filter((u) => u !== event.username);
    });

    useEchoPublic("chat", ".user.typing", (event) => {
      // Don't show own typing indicator
      if (event.username === username.value) return;

      if (!typingUsers.value.includes(event.username)) {
        typingUsers.value.push(event.username);
      }

      // Clear existing timer for this user
      if (typingTimers[event.username]) {
        clearTimeout(typingTimers[event.username]);
      }

      // Remove after 2 seconds of inactivity
      typingTimers[event.username] = setTimeout(() => {
        typingUsers.value = typingUsers.value.filter(
          (u) => u !== event.username,
        );
        delete typingTimers[event.username];
      }, 2000);
    });

    async function sendMessage() {
      if (!newMessage.value.trim()) return;

      sending.value = true;
      error.value = null;

      try {
        const response = await fetch("http://localhost:8000/api/send-message", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
          },
          body: JSON.stringify({
            message: newMessage.value,
            username: username.value,
          }),
        });

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${await response.text()}`);
        }

        newMessage.value = "";
      } catch (err) {
        error.value = "Failed to send message: " + err.message;
      } finally {
        sending.value = false;
      }
    }

    async function onTyping() {
      const now = Date.now();
      // Throttle: only send typing event every 500ms
      if (now - lastTypingSent < 500) return;
      lastTypingSent = now;

      try {
        await fetch("http://localhost:8000/api/typing", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
          },
          body: JSON.stringify({ username: username.value }),
        });
      } catch {
        // Silently ignore typing errors
      }
    }

    return {
      messages,
      newMessage,
      username,
      sending,
      error,
      connectionStatus,
      typingUsers,
      typingText,
      sendMessage,
      onTyping,
    };
  },
};
</script>

<style scoped>
.chat-container {
  max-width: 600px;
  margin: 0 auto;
  font-family: Arial, sans-serif;
}

h2 {
  text-align: center;
  color: #2c3e50;
}

.connection-status {
  text-align: center;
  padding: 8px;
  border-radius: 4px;
  margin-bottom: 16px;
  font-size: 14px;
  font-weight: bold;
}

.connected {
  background-color: #d4edda;
  color: #155724;
}

.disconnected {
  background-color: #f8d7da;
  color: #721c24;
}

.messages {
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 16px;
  min-height: 200px;
  max-height: 400px;
  overflow-y: auto;
  margin-bottom: 16px;
  background: #f9f9f9;
}

.no-messages {
  color: #888;
  text-align: center;
  padding: 40px 0;
}

.message {
  padding: 8px 12px;
  margin-bottom: 8px;
  background: white;
  border-radius: 6px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.timestamp {
  float: right;
  font-size: 12px;
  color: #999;
}

.input-area {
  display: flex;
  gap: 8px;
}

.input-name {
  width: 120px;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
}

.input-message {
  flex: 1;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
}

button {
  padding: 10px 20px;
  background-color: #42b983;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: bold;
}

button:hover {
  background-color: #38a373;
}

button:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}

.error {
  margin-top: 12px;
  padding: 10px;
  background-color: #f8d7da;
  color: #721c24;
  border-radius: 6px;
  font-size: 14px;
}

.typing-indicator {
  font-size: 13px;
  color: #888;
  font-style: italic;
  padding: 4px 0;
  margin-bottom: 8px;
}
</style>
