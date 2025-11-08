document.addEventListener('DOMContentLoaded', function () {
    // const banner = document.getElementById('user-top-content');
    // if (banner) {

    //     banner.addEventListener("mouseenter", () => {
    //         banner.style.backgroundImage = 'url("/public/images/store/AuroraBorealis.gif")';
    //     });

    //     banner.addEventListener("mouseleave", () => {
    //         banner.style.backgroundImage = 'url("/public/images/store/AuroraBorealis.png")';
    //     });
    // }

    // document.addEventListener('DOMContentLoaded', () => {
    //     const img = document.querySelector('#profile-banner img')

    //     if (img) {
    //         console.log("test:", img.dataset)
    //         img.addEventListener('mouseenter', () => {
    //             img.src = img.dataset.gif
    //         })
    //         img.addEventListener('mouseleave', () => {
    //             img.src = img.dataset.static
    //         })
    //     }
    // })

    const banner = document.getElementById('profile-banner')

    if (banner) {
        let bg =
            banner.style.backgroundImage ||
            getComputedStyle(banner).backgroundImage

        // Remove unnecessary parts
        bg = bg.replace(/^url\(["']?/, '').replace(/["']?\)$/, '')

        // Extract names
        const match = bg.match(/(.*\/)?([^/]+)\.(png|jpg|jpeg|webp|gif)$/i)

        if (match) {
            const path = match[1] || '' // The filepath
            const baseName = match[2] // "The filename"
            const ext = match[3]
            console.log('extension:', ext)

            if (ext.toLowerCase() === 'gif') {
                const defaultImg = `url("${path}${baseName}.png")`
                const hoverImg = `url("${path}${baseName}.gif")`

                // Default
                banner.style.backgroundImage = defaultImg

                // Hover-Effect
                banner.addEventListener('mouseenter', () => {
                    banner.style.backgroundImage = hoverImg
                })

                banner.addEventListener('mouseleave', () => {
                    banner.style.backgroundImage = defaultImg
                })
            } else {
                
            }
        } else {
            console.log('Couldnt find banner')
        }
    }
})
