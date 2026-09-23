document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('img').forEach((img) => {
        const fallbackPath = () => {
            const src = img.getAttribute('src') || '';
            if (src.includes('/uploads/profiles/') || img.alt?.toLowerCase().includes('profil')) {
                return 'uploads/profiles/default_profile.png';
            }
            if (src.includes('/uploads/covers/') || src.includes('/uploads/ads/') || src.includes('/uploads/posts/')) {
                return 'uploads/covers/default_cover.png';
            }
            return 'uploads/profiles/default_profile.png';
        };

        img.addEventListener('error', () => {
            if (!img.dataset.fallbackApplied) {
                img.dataset.fallbackApplied = 'true';
                img.src = fallbackPath();
            }
        });
    });
});
