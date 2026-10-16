import "./echo.js"

// Copy text to the clipboard and show a Filament toast ("Join link copied").
window.ptCopy = async (text, message = 'Copied') => {
    try {
        await navigator.clipboard.writeText(text)
    } catch {
        // clipboard API is blocked on plain http:// pages - fall back to the old way
        const t = document.createElement('textarea')
        t.value = text
        document.body.appendChild(t)
        t.select()
        document.execCommand('copy')
        t.remove()
    }

    if (window.FilamentNotification) {
        new window.FilamentNotification().title(message).success().send()
    }
}
