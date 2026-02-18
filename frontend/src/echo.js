import { configureEcho } from "@laravel/echo-vue";

configureEcho({
  broadcaster: "reverb",
  key: "b4xurgr93c2bnakobnlr",
  wsHost: "localhost",
  wsPort: 8080,
  wssPort: 8080,
  forceTLS: false,
  enabledTransports: ["ws", "wss"],
});
