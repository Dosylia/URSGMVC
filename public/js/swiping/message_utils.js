// utils.js

// DOM elements
export const userIdElement = document.getElementById('senderId')
export const friendIdElement = document.getElementById('receiverId')
export const chatInterface = document.querySelector('.chat-interface')
export const messageContainer = document.querySelector('.messages-container')
export const replyPreviewContainer = document.getElementById('reply-preview')
export const chatInput = document.getElementById('message_text')
export const RatingModal = document.getElementById('rating-modal')
export const closeRatingModalBtn = document.getElementById('close-rating-modal')
export const RatingButton = document.getElementById('rating-button')
export const submitRating = document.getElementById('submit-rating')
export const messageInput = document.getElementById('message_text')
export const btnSubmit = document.getElementById('submit_chat')
export const btnDesign = document.getElementById('btnDesign')
export const dropZoneOverlay = document.getElementById('dropZoneOverlay')
export const spamWarning = document.getElementById('spamWarning')
export const toggleEmotePickerButton =
    document.getElementById('toggleEmotePicker')
export const emoteContainer = document.getElementById('emoteContainer')
export const emotes = document.querySelectorAll('.emote')
export const replyBox = document.querySelector('.reply-box')

// Variables
export let userId = userIdElement ? userIdElement.value : null
export let friendId = friendIdElement ? friendIdElement.value : null
export let actualFriendId = friendId
export let currentMessages = []
export let isFirstFetch = true
export let friendData = document.getElementById('friendInfo')
export let currentFriendUsername = null
export let firstFriendId = friendId
export let clearImageVar = false
export let numberofFail = 0
export let lastFriendStatus = null
export let senderId = userIdElement ? userIdElement.value : null
export let receiverId = friendIdElement ? friendIdElement.value : null
export let isActionAllowed = true
export let attachedImages = []
export let messageBurstCount = 0
export let lastMessageTimestamp = 0
export let isCoolingDown = false
export const BURST_LIMIT = 5
export const COOLDOWN_TIME = 2000

// Functions
export function clearImageTrue() {
    clearImageVar = true
}

export function clearImageFalse() {
    clearImageVar = false
    console.log('clearImageVar set to false')
}

export function preventDefaults(e) {
    e.preventDefault()
    e.stopPropagation()
}

export function highlight() {
    dropZoneOverlay.classList.add('active')
}

export function unhighlight() {
    dropZoneOverlay.classList.remove('active')
}

export function resetActionAllowed() {
    isActionAllowed = true
}

export function setActionAllowed(value) {
    isActionAllowed = value
}

export function incrementMessageBurst() {
    messageBurstCount++
}

export function resetMessageBurst() {
    messageBurstCount = 0
}

export function setCoolingDown(value) {
    isCoolingDown = value
}

export function setLastMessageTimestamp(timestamp) {
    lastMessageTimestamp = timestamp
}

export function setFirstFetch(value) {
    isFirstFetch = value
}

export function setNumberOfFail(value) {
    numberofFail = value
}

export function incrementNumberOfFail() {
    numberofFail++
}

export function setCurrentMessages(messages) {
    currentMessages = messages
}

export function setCurrentFriendUsername(username) {
    currentFriendUsername = username
}

export function setLastFriendStatus(status) {
    lastFriendStatus = status
}

export function setFriendId(newFriendId) {
    friendId = newFriendId
    if (friendIdElement) {
        friendIdElement.value = newFriendId
    }
}

export function setActualFriendId(newFriendId) {
    actualFriendId = newFriendId
}

export function getActualFriendId() {
    return actualFriendId
}

export function clearAttachedImages() {
    attachedImages.length = 0
}

export function addAttachedImage(imageUrl) {
    attachedImages.push(imageUrl)
}
