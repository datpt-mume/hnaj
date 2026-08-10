# Homepage Food Poster Slideshow Design

## Goal

Refresh the HNAJ homepage so it feels like a living Vietnamese food poster rather than a dashboard form. Preserve the existing discovery/filter behavior while adding a visual hero slideshow built from the three user-provided poster images.

## User experience

On desktop, the homepage presents a two-column hero below the existing navigation:

- The left column contains the existing discovery headline, a short supporting line, compact filter pills, and the recommendation CTA.
- The right column contains a tall poster-style slideshow with rounded corners, a subtle frame/shadow, caption, pagination dots, and previous/next controls.
- The visual should feel editorial and warm without putting important text directly on top of the photos.

On mobile, the slideshow moves above the headline and filters as a centered portrait visual. The content remains readable and the controls remain touch-friendly.

## Slideshow behavior

- Exactly three user-provided poster images are displayed in a fixed sequence.
- The active slide auto-advances every 5 seconds.
- Transition uses a gentle opacity fade and a restrained scale animation; no hard horizontal carousel movement.
- Previous/next buttons and pagination dots allow manual navigation.
- Manual navigation resets the autoplay timer.
- Autoplay pauses while the slideshow is hovered or focused, and resumes when neither is true.
- The slideshow remains usable with keyboard focus and exposes an accessible label and current slide state.
- With `prefers-reduced-motion: reduce`, slide changes do not animate.

## Asset contract

The three images will be added to `hnaj-fe/public/` using stable filenames:

- `food-poster-01.jpg`
- `food-poster-02.jpg`
- `food-poster-03.jpg`

The implementation should reference them as public-root URLs and use `object-fit: cover`. Each image should include meaningful Vietnamese alt text. If the exact files are not available during implementation, the component can be wired to the filenames and verified with the existing hero image as a temporary local fallback; do not silently substitute remote images.

## Component and data boundaries

Create a focused `FoodPosterSlideshow` component responsible only for:

- active slide state
- autoplay interval lifecycle
- pause/resume interaction state
- keyboard and button navigation
- rendering image, caption, controls, and indicators

Keep filter state and recommendation request logic in `HomePage`/`FilterPanel`. The slideshow has no dependency on discovery API data. Add a small static slide-definition array near the component with image path, alt text, location/category caption, and optional label.

Update the homepage layout to compose the slideshow with the existing discovery content. Keep routing, authentication navigation, recommendation modal, and filter semantics unchanged.

## Visual system

- Continue using the existing warm cream/orange brand tokens.
- Reduce the visual weight of the filter form: compact pill controls, consistent heights, and less enclosing chrome.
- Use a restrained white/cream surface and one strong orange CTA.
- Use a large display headline, but constrain its measure so the hero remains balanced against the portrait image.
- Preserve visible focus styles and minimum touch targets.

## Responsive rules

- Desktop/tablet: two-column hero with a flexible text column and portrait visual column.
- Small screens: single-column flow with slideshow first, then headline and filters.
- Do not allow the poster to force horizontal scrolling.
- Keep navigation behavior and existing mobile breakpoints compatible with the new hero.

## Verification

- Run TypeScript/Vite build and lint.
- Launch the app and inspect desktop and mobile layouts.
- Verify automatic transition, manual controls, pause-on-hover/focus, reduced-motion behavior, and keyboard access.
- Verify the recommendation form still submits the same filter request and opens the existing modal.
- Confirm no unrelated files or existing user changes are overwritten.
