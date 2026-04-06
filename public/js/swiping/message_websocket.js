/**
 * WebSocket manager for real-time chat.
 * Handles connection, reconnection, and message routing.
 * Falls back to HTTP polling when WS is unavailable.
 */

let ws = null
let wsReady = false
let reconnectAttempts = 0
const MAX_RECONNECT_ATTEMPTS = 10
const BASE_RECONNECT_DELAY = 1000
let reconnectTimer = null
let onMessageCallback = null
let onConnectionChangeCallback = null

// Read config from hidden inputs set by the server
function getWsUrl() {
    const el = document.getElementById('wsUrl')
    return el ? el.value : null
}

function getUserId() {
    const el = document.getElementById('senderId')
    return el ? el.value : null
}

function getToken() {
    return localStorage.getItem('masterTokenWebsite')
}

export function isWsConnected() {
    return wsReady && ws && ws.readyState === WebSocket.OPEN
}

export function onWsMessage(callback) {
    onMessageCallback = callback
}

export function onWsConnectionChange(callback) {
    onConnectionChangeCallback = callback
}

export function connectWebSocket() {
    const wsUrl = getWsUrl()
    const userId = getUserId()
    const token = getToken()

    if (!wsUrl || !userId || !token) {
        console.warn('WebSocket: missing config, staying on HTTP polling')
        return
    }

    // Don't reconnect if already connected
    if (
        ws &&
        (ws.readyState === WebSocket.OPEN ||
            ws.readyState === WebSocket.CONNECTING)
    ) {
        return
    }

    const url = `${wsUrl}?userId=${encodeURIComponent(userId)}&token=${encodeURIComponent(token)}`

    try {
        ws = new WebSocket(url)
    } catch (e) {
        console.warn('WebSocket: failed to create connection', e)
        scheduleReconnect()
        return
    }

    ws.onopen = () => {
        wsReady = true
        reconnectAttempts = 0
        console.log('WebSocket: connected')
        if (onConnectionChangeCallback) onConnectionChangeCallback(true)
    }

    ws.onmessage = (event) => {
        try {
            const data = JSON.parse(event.data)
            if (onMessageCallback) onMessageCallback(data)
        } catch (e) {
            console.error('WebSocket: invalid message', e)
        }
    }

    ws.onclose = (event) => {
        wsReady = false
        console.log('WebSocket: disconnected', event.code, event.reason)
        if (onConnectionChangeCallback) onConnectionChangeCallback(false)

        // Don't reconnect if closed deliberately (code 1000) or auth failed
        if (event.code !== 1000) {
            scheduleReconnect()
        }
    }

    ws.onerror = () => {
        // onclose will fire after this, which handles reconnect
        wsReady = false
    }
}

function scheduleReconnect() {
    if (reconnectAttempts >= MAX_RECONNECT_ATTEMPTS) {
        console.warn(
            'WebSocket: max reconnect attempts reached, falling back to polling'
        )
        if (onConnectionChangeCallback) onConnectionChangeCallback(false)
        return
    }

    // Exponential backoff: 1s, 2s, 4s, 8s... capped at 30s
    const delay = Math.min(
        BASE_RECONNECT_DELAY * Math.pow(2, reconnectAttempts),
        30000
    )
    reconnectAttempts++

    console.log(
        `WebSocket: reconnecting in ${delay}ms (attempt ${reconnectAttempts})`
    )
    clearTimeout(reconnectTimer)
    reconnectTimer = setTimeout(connectWebSocket, delay)
}

export function sendWsMessage(payload) {
    if (!isWsConnected()) {
        return false
    }

    try {
        ws.send(JSON.stringify(payload))
        return true
    } catch (e) {
        console.error('WebSocket: send failed', e)
        return false
    }
}

export function disconnectWebSocket() {
    clearTimeout(reconnectTimer)
    reconnectAttempts = MAX_RECONNECT_ATTEMPTS // prevent auto-reconnect
    if (ws) {
        ws.close(1000, 'User navigated away')
        ws = null
    }
    wsReady = false
}
