<template>
  <div id="app">
    <ChatRoom />
  </div>
</template>

<script>
import ChatRoom from "./components/ChatRoom.vue";

export default {
  name: "App",
  components: {
    ChatRoom,
  },
};
</script>

<style>
#app {
  font-family: Avenir, Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  color: #2c3e50;
  margin-top: 40px;
  padding: 0 20px;
}
</style>
