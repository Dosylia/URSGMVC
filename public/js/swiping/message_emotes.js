// emoteManager.js

import { badWordsList } from './chatFilter.js'
import {
    toggleEmotePickerButton,
    emoteContainer,
    emotes,
    messageInput,
} from './message_utils.js'

export function renderEmotes(message, ownGoldEmotes) {
    const emoteMap = {
        ':surprised-cat:':
            '<img src="public/images/emotes/surprised-cat.png" alt="surprised-cat" class="emote">',
        ':cat-smile:':
            '<img src="public/images/emotes/cat-smile.png" alt="cat-smile" class="emote">',
        ':cat-cute:':
            '<img src="public/images/emotes/cat-cute.png" alt="cat-cute" class="emote">',
        ':goofy-ah-cat:':
            '<img src="public/images/emotes/goofy-ah-cat.png" alt="goofy-ah-cat" class="emote">',
        ':cat-surprised:':
            '<img src="public/images/emotes/cat-surprised.png" alt="cat-surprised" class="emote">',
        ':cat-liked:':
            '<img src="public/images/emotes/cat-liked.png" alt="cat-liked" class="emote">',
        ':cat-sus:':
            '<img src="public/images/emotes/cat-sus.png" alt="cat-sus" class="emote">',
        ':cat-bruh:':
            '<img src="public/images/emotes/cat-bruh.png" alt="cat-bruh" class="emote">',
        ':cat-licking:':
            '<img src="public/images/emotes/cat-licking.png" alt="cat-licking" class="emote">',
        ':cat-laugh:':
            '<img src="public/images/emotes/cat-laugh.png" alt="cat-laugh" class="emote">',
        ':cat-crying:':
            '<img src="public/images/emotes/cat-crying.png" alt="cat-crying" class="emote">',
        ':cat-love:':
            '<img src="public/images/emotes/cat-love.png" alt="cat-love" class="emote">',
    }

    if (ownGoldEmotes) {
        emoteMap[':urpe-stonks:'] =
            '<img src="public/images/emotes/urpe-stonks.png" alt="urpe-stonks" class="emote">'
        emoteMap[':goldurpe-stonks:'] =
            '<img src="public/images/emotes/urpe-stonks.png" alt="urpe-stonks" class="emote">'
        emoteMap[':urpe-cry:'] =
            '<img src="public/images/emotes/urpe-cry.png" alt="urpe-cry" class="emote">'
        emoteMap[':goldurpe-cry:'] =
            '<img src="public/images/emotes/urpe-cry.png" alt="urpe-cry" class="emote">'
        emoteMap[':urpe-sip:'] =
            '<img src="public/images/emotes/urpe-sip.png" alt="urpe-sip" class="emote">'
        emoteMap[':goldurpe-sip:'] =
            '<img src="public/images/emotes/urpe-sip.png" alt="urpe-sip" class="emote">'
        emoteMap[':urpe-jesus:'] =
            '<img src="public/images/emotes/urpe-jesus.png" alt="urpe-jesus" class="emote">'
        emoteMap[':goldurpe-jesus:'] =
            '<img src="public/images/emotes/urpe-jesus.png" alt="urpe-jesus" class="emote">'
        emoteMap[':urpe-hype:'] =
            '<img src="public/images/emotes/urpe-hype.png" alt="urpe-hype" class="emote">'
        emoteMap[':goldurpe-hype:'] =
            '<img src="public/images/emotes/urpe-hype.png" alt="urpe-hype" class="emote">'
        emoteMap[':urpe-hide:'] =
            '<img src="public/images/emotes/urpe-hide.png" alt="urpe-hide" class="emote">'
        emoteMap[':goldurpe-hide:'] =
            '<img src="public/images/emotes/urpe-hide.png" alt="urpe-hide" class="emote">'
        emoteMap[':urpe-heart:'] =
            '<img src="public/images/emotes/urpe-heart.png" alt="urpe-heart" class="emote">'
        emoteMap[':goldurpe-heart:'] =
            '<img src="public/images/emotes/urpe-heart.png" alt="urpe-heart" class="emote">'
        emoteMap[':urpe-dead:'] =
            '<img src="public/images/emotes/urpe-dead.png" alt="urpe-dead" class="emote">'
        emoteMap[':goldurpe-dead:'] =
            '<img src="public/images/emotes/urpe-dead.png" alt="urpe-dead" class="emote">'
        emoteMap[':urpe-blush:'] =
            '<img src="public/images/emotes/urpe-blush.png" alt="urpe-blush" class="emote">'
        emoteMap[':goldurpe-blush:'] =
            '<img src="public/images/emotes/urpe-blush.png" alt="urpe-blush" class="emote">'
        emoteMap[':urpe-blanket:'] =
            '<img src="public/images/emotes/urpe-blanket.png" alt="urpe-blanket" class="emote">'
        emoteMap[':goldurpe-blanket:'] =
            '<img src="public/images/emotes/urpe-blanket.png" alt="urpe-blanket" class="emote">'
        emoteMap[':urpe-cool:'] =
            '<img src="public/images/emotes/urpe-cool.png" alt="urpe-cool" class="emote">'
        emoteMap[':goldurpe-cool:'] =
            '<img src="public/images/emotes/urpe-cool.png" alt="urpe-cool" class="emote">'
        emoteMap[':urpe-eat:'] =
            '<img src="public/images/emotes/urpe-eat.png" alt="urpe-eat" class="emote">'
        emoteMap[':goldurpe-eat:'] =
            '<img src="public/images/emotes/urpe-eat.png" alt="urpe-eat" class="emote">'
        emoteMap[':urpe-notstonks:'] =
            '<img src="public/images/emotes/urpe-notstonks.png" alt="urpe-notstonks" class="emote">'
        emoteMap[':goldurpe-notstonks:'] =
            '<img src="public/images/emotes/urpe-notstonks.png" alt="urpe-notstonks" class="emote">'
        emoteMap[':urpe-madaf:'] =
            '<img src="public/images/emotes/urpe-madaf.png" alt="urpe-madaf" class="emote">'
        emoteMap[':goldurpe-madaf:'] =
            '<img src="public/images/emotes/urpe-madaf.png" alt="urpe-madaf" class="emote">'
        emoteMap[':urpe-sad:'] =
            '<img src="public/images/emotes/urpe-sad.png" alt="urpe-sad" class="emote">'
        emoteMap[':goldurpe-sad:'] =
            '<img src="public/images/emotes/urpe-sad.png" alt="urpe-sad" class="emote">'
        emoteMap[':urpe-run:'] =
            '<img src="public/images/emotes/urpe-run.png" alt="urpe-run" class="emote">'
        emoteMap[':goldurpe-run:'] =
            '<img src="public/images/emotes/urpe-run.png" alt="urpe-run" class="emote">'
    }

    const replacedMessage = message.replace(/:\w+(-\w+)*:/g, function (match) {
        return emoteMap[match] || match
    })

    return replacedMessage
}

export const chatfilter = (textToFilter) => {
    // Combine all bad words from all languages into a single array
    const allBadWords = badWordsList.flatMap(([, badWords]) => badWords)

    // Create a regular expression from all the bad words
    const badWordsRegex = new RegExp(allBadWords.join('|'), 'gi')

    // Replace bad words with '***'
    const filteredText = textToFilter.replace(badWordsRegex, (match) => {
        return '*'.repeat(match.length)
    })

    return filteredText
}

export function initEmoteHandlers() {
    // Emote picker functionality
    if (toggleEmotePickerButton && emoteContainer) {
        toggleEmotePickerButton.addEventListener('click', function () {
            emoteContainer.style.display =
                emoteContainer.style.display === 'none' ? 'flex' : 'none'
        })
    }

    if (emotes && messageInput) {
        emotes.forEach((emote) => {
            emote.addEventListener('click', function () {
                const emoteAlt = emote.alt
                messageInput.value += ` ${emoteAlt} `
                emoteContainer.style.display = 'none'
                messageInput.focus()
            })
        })
    }
}
