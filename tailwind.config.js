/**
 * Builds a Tailwind color scale backed by a `--color-{name}-{shade}` CSS variable
 * per shade, so the palette can be repointed at runtime (or per-tenant, eventually)
 * without touching a single `bg-brand-500` class in a Blade view. Variables are
 * declared as "r g b" - Tailwind's `<alpha-value>` placeholder needs that, not a
 * hex string - see resources/css/app.css.
 */
function cssVarColorScale(name) {
    const shades = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950]
    return Object.fromEntries(
        shades.map((shade) => [shade, `rgb(var(--color-${name}-${shade}) / <alpha-value>)`])
    )
}

module.exports = {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
        "./app/**/*.php",
        "./vendor/rappasoft/laravel-livewire-tables/resources/views/**/*.blade.php",
    ],
    theme: {
        extend: {
            colors: {
                // The app's single accent color - was scattered across templates as the
                // generic Tailwind `sky` palette. Same values today (see app.css), now
                // themeable from one place.
                brand: cssVarColorScale('brand'),
            },
        },
    },
    plugins: [],
}
