# Emergency API - Frontend Environment Configuration

## For React/Next.js (.env.local)

```
REACT_APP_API_URL=http://localhost:8000/api
REACT_APP_WS_HOST=localhost
REACT_APP_WS_PORT=6001
REACT_APP_PUSHER_KEY=sigap-key
REACT_APP_PUSHER_CLUSTER=mt1

# Production
# REACT_APP_API_URL=https://your-domain.com/api
# REACT_APP_WS_HOST=your-domain.com
# REACT_APP_WS_PORT=6001
```

## For Vue.js (.env)

```
VUE_APP_API_URL=http://localhost:8000/api
VUE_APP_WS_HOST=localhost
VUE_APP_WS_PORT=6001
VUE_APP_PUSHER_KEY=sigap-key
VUE_APP_PUSHER_CLUSTER=mt1
```

## For Angular (environment.ts)

```typescript
export const environment = {
  production: false,
  apiUrl: "http://localhost:8000/api",
  websocket: {
    host: "localhost",
    port: 6001,
    key: "sigap-key",
    cluster: "mt1",
  },
};
```

## JavaScript Configuration Object

```javascript
const CONFIG = {
  API_BASE_URL: "http://localhost:8000/api",
  WEBSOCKET: {
    HOST: "localhost",
    PORT: 6001,
    KEY: "sigap-key",
    CLUSTER: "mt1",
    FORCE_TLS: false,
  },
};
```
