# Homepage Food Poster Slideshow Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rework the HNAJ homepage into a warm editorial food-poster hero with a three-image slideshow while preserving discovery behavior and applying the approved header/copy rules.

**Architecture:** Add a focused `FoodPosterSlideshow` component with static slide metadata and local timer/navigation state. Refactor only the homepage presentation layer and related CSS: the existing `HomePage` remains responsible for filter state, API requests, authentication, and recommendation modal behavior; the slideshow remains independent of discovery data.

**Tech Stack:** React 19, TypeScript, React Router, React Icons, Vite, existing CSS custom properties in `tokens.css` and `App.css`.

## Global Constraints

- Use exactly three user-provided poster images in a fixed slideshow sequence.
- The active slide auto-advances every 5 seconds.
- Use gentle opacity fade and restrained scale animation; do not use hard horizontal carousel movement.
- Pause autoplay while the slideshow is hovered or focused; resume when neither is true.
- Respect `prefers-reduced-motion: reduce` by removing slide animation.
- Header uses the existing logo image and must not render a text-only `HNAJ` wordmark.
- Render `Điểm đến yêu thích` and `Lịch sử` only when a user is authenticated.
- Keep the existing slogan content and do not add `HNAJ` to the slogan.
- Use the exact supporting copy: `Dành cho những ngày không muốn suy nghĩ nhiều. Bỏ qua mọi lo toan và hãy để Hôm nay ăn gì quyết định giúp bạn nha`.
- Do not change filter request semantics, routing, recommendation modal behavior, or authentication behavior outside the requested header presentation.
- Do not overwrite unrelated existing user changes in the working tree.

---

## File Map

- Create: `hnaj-fe/src/components/FoodPosterSlideshow.tsx` — slideshow state, timer lifecycle, controls, indicators, and accessible slide rendering.
- Modify: `hnaj-fe/src/pages/HomePage.tsx` — compose slideshow into the hero, update supporting copy, and keep authenticated personal links conditional.
- Modify: `hnaj-fe/src/components/AuthNav.tsx` only if its current logo/account integration requires a presentation adjustment; do not move auth logic out of the component.
- Modify: `hnaj-fe/App.css` — replace the discovery page presentation with the approved two-column hero, compact filter treatment, slideshow visuals, and responsive rules while retaining shared component styles.
- Add: `hnaj-fe/public/food-poster-01.jpg` — first user-provided poster image.
- Add: `hnaj-fe/public/food-poster-02.jpg` — second user-provided poster image.
- Add: `hnaj-fe/public/food-poster-03.jpg` — third user-provided poster image.
- Verify: `hnaj-fe` build and lint scripts; runtime desktop/mobile inspection.

---

### Task 1: Add the slideshow component

**Files:**
- Create: `hnaj-fe/src/components/FoodPosterSlideshow.tsx`

**Interfaces:**
- Produces a default-exported or named `FoodPosterSlideshow` component with no required props.
- Uses a local `slides` array with `{ src: string; alt: string; caption: string; detail: string }` entries for `/food-poster-01.jpg`, `/food-poster-02.jpg`, and `/food-poster-03.jpg`.

- [ ] **Step 1: Define slide metadata and state types**

```tsx
type FoodPosterSlide = {
  src: string
  alt: string
  caption: string
  detail: string
}

const slides: FoodPosterSlide[] = [
  { src: '/food-poster-01.jpg', alt: 'Poster món ăn Việt Nam với tô bún bò Huế', caption: 'Huế / món đậm vị', detail: 'Một chút cay, một chút thương.' },
  { src: '/food-poster-02.jpg', alt: 'Poster Hà Nội với phở và các địa danh thành phố', caption: 'Hà Nội / phở nóng', detail: 'Đi một vòng rồi ghé ăn.' },
  { src: '/food-poster-03.jpg', alt: 'Poster ẩm thực Nam Định với nhiều món ăn địa phương', caption: 'Nam Định / vị quê nhà', detail: 'Món ngon kể chuyện nơi chốn.' },
]
```

- [ ] **Step 2: Implement active index and pause state**

Use `useState(0)` for `activeIndex` and `useState(false)` for `isPaused`. Use `useEffect` with `activeIndex` and `isPaused` dependencies to create a 5,000ms interval only when not paused, advance with modulo `slides.length`, and clear the interval in the cleanup function.

- [ ] **Step 3: Implement manual navigation**

Add `goTo(index: number)`, `goPrevious()`, and `goNext()` functions. Every manual navigation updates `activeIndex`; because the interval effect depends on `activeIndex`, the timer restarts after manual navigation.

- [ ] **Step 4: Render an accessible slideshow**

Render a `<section aria-label="Poster ẩm thực nổi bật">` containing:

- a framed visual with one `<img>` for the active slide;
- a caption containing `caption` and `detail`;
- previous/next `<button>` controls with Vietnamese `aria-label`s;
- three indicator buttons with `aria-label={`Xem poster ${index + 1}`}`, `aria-current` on the active indicator, and `onClick={() => goTo(index)}`;
- a focusable root or viewport (`tabIndex={0}`) that sets `isPaused(true)` on focus and `isPaused(false)` on blur;
- `onMouseEnter`/`onMouseLeave` handlers on the viewport for hover pause/resume.

Use `aria-live="polite"` only on the caption/status text, not on the whole image region, to avoid excessive announcements during autoplay.

- [ ] **Step 5: Run the type/build check**

Run `cd hnaj-fe && npm run build`.

Expected: the new component type-checks; the build may still fail only if the three image files are not yet present, which is resolved in Task 3.

- [ ] **Step 6: Commit the component**

```bash
git add hnaj-fe/src/components/FoodPosterSlideshow.tsx
git commit -m "feat: add food poster slideshow component" -m "Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 2: Compose the new homepage hero and enforce copy/auth rules

**Files:**
- Modify: `hnaj-fe/src/pages/HomePage.tsx`
- Modify: `hnaj-fe/src/components/AuthNav.tsx` only if needed after inspecting its rendered markup

**Interfaces:**
- `HomePage` continues to own `filters`, `place`, `excluded`, loading/error state, and all existing handlers.
- `FoodPosterSlideshow` is rendered independently and receives no discovery filters or API data.

- [ ] **Step 1: Inspect `AuthNav` and preserve its auth behavior**

Confirm the existing `AuthNav` renders the logo through the parent `HomePage` wordmark and that personal links are already guarded by `user`. If the component itself renders any personal links for signed-out users, add the same `user` guard there; do not duplicate auth state or introduce a second auth hook.

- [ ] **Step 2: Import and render `FoodPosterSlideshow`**

Add the component import and compose the hero so the slideshow is alongside the discovery content. Keep the existing `home-nav` navigation and `AuthNav` intact, but ensure the header logo remains an `<img src="/logo.png" alt="Hôm nay ăn gì?" />` rather than text `HNAJ`.

- [ ] **Step 3: Apply the approved copy exactly**

Keep the existing slogan/headline content, including its Vietnamese wording and orange highlighted phrase. Replace the supporting paragraph with:

```tsx
<p className="home-discover__lead">
  Dành cho những ngày không muốn suy nghĩ nhiều. Bỏ qua mọi lo toan và hãy để Hôm nay ăn gì quyết định giúp bạn nha
</p>
```

Do not introduce `HNAJ` into the slogan or supporting copy.

- [ ] **Step 4: Keep personal navigation conditional**

The `Điểm đến yêu thích` and `Lịch sử` links must be inside the existing authenticated branch (`user ? (...) : null`) and must not render for signed-out visitors. Keep `Khám phá` visible for everyone.

- [ ] **Step 5: Run lint and build**

Run `cd hnaj-fe && npm run lint && npm run build`.

Expected: PASS after image assets are present; no new lint errors and no changes to discovery request types.

- [ ] **Step 6: Commit the homepage composition**

```bash
git add hnaj-fe/src/pages/HomePage.tsx hnaj-fe/src/components/AuthNav.tsx
 git commit -m "feat: compose poster-led homepage hero" -m "Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 3: Add and verify the three local poster assets

**Files:**
- Add: `hnaj-fe/public/food-poster-01.jpg`
- Add: `hnaj-fe/public/food-poster-02.jpg`
- Add: `hnaj-fe/public/food-poster-03.jpg`

**Interfaces:**
- Vite serves the files at `/food-poster-01.jpg`, `/food-poster-02.jpg`, and `/food-poster-03.jpg`.
- The component from Task 1 consumes those exact public-root URLs.

- [ ] **Step 1: Place the exact user-provided images in `public/`**

Use the three supplied images in the agreed order: Bún bò Huế poster first, Hà Nội phở poster second, and Ẩm thực Nam Định poster third. Preserve the source image content; do not regenerate, crop-export, or substitute remote URLs.

- [ ] **Step 2: Verify dimensions and file readability**

Run `file hnaj-fe/public/food-poster-01.jpg hnaj-fe/public/food-poster-02.jpg hnaj-fe/public/food-poster-03.jpg`.

Expected: all three paths exist and are recognized as readable JPEG image files. If the source format is PNG/WebP, preserve the original format and update the component paths consistently rather than pretending it is JPEG.

- [ ] **Step 3: Verify Vite serves each asset**

Run `cd hnaj-fe && npm run dev -- --host 127.0.0.1` and request each path from the local dev server in the browser. Confirm no 404s and that each image loads without a broken-image icon.

- [ ] **Step 4: Commit the assets**

```bash
git add hnaj-fe/public/food-poster-01.jpg hnaj-fe/public/food-poster-02.jpg hnaj-fe/public/food-poster-03.jpg
git commit -m "feat: add Vietnamese food poster assets" -m "Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 4: Implement the visual system and responsive layout

**Files:**
- Modify: `hnaj-fe/App.css`
- Modify: `hnaj-fe/tokens.css` only if a missing slideshow token is needed; prefer existing tokens

**Interfaces:**
- CSS class names must match the markup in `HomePage.tsx` and `FoodPosterSlideshow.tsx`.
- Existing shared controls (`.button`, dropdowns, chips, toggles) retain their semantics and focus styles.

- [ ] **Step 1: Add the two-column hero layout rules**

Style the discovery content as a grid with a flexible text column and a portrait visual column. Use a bounded max width, generous but consistent gap, and no enclosing dashboard-style outer card around the entire hero. Keep the page background warm cream.

- [ ] **Step 2: Style the approved headline and supporting copy**

Keep the headline large and constrained to a readable measure. Add `.home-discover__lead` with a muted warm ink color, readable line-height, and a max width so the exact copy does not create an oversized text block.

- [ ] **Step 3: Compact the filter presentation**

Reduce unnecessary surrounding chrome around the filter controls while preserving the existing `FilterPanel` DOM and behavior. Align dropdowns, price controls, tags, location choice, and open-now toggle to a consistent control rhythm. Keep the recommendation CTA visually dominant and aligned with the text column.

- [ ] **Step 4: Style the slideshow**

Add styles for:

- a portrait viewport with `aspect-ratio` and `overflow: hidden`;
- a framed white border and warm shadow;
- active image fade/scale animation;
- caption overlay with a readable gradient;
- dots and arrow controls with visible focus states;
- a subtle hover lift that does not shift surrounding layout.

Use the existing motion tokens where possible. Add a `@media (prefers-reduced-motion: reduce)` override that sets animation duration to zero and disables transform transitions for slideshow elements.

- [ ] **Step 5: Add responsive rules**

At the mobile breakpoint, switch the hero to one column, render the slideshow before the headline, keep the visual centered, make arrow controls at least 44px hit targets, and prevent any horizontal overflow. Keep navigation personal-link behavior unchanged.

- [ ] **Step 6: Run the quality checks**

Run `cd hnaj-fe && npm run lint && npm run build`.

Expected: PASS with no TypeScript or lint errors.

- [ ] **Step 7: Commit the visual implementation**

```bash
git add hnaj-fe/App.css hnaj-fe/tokens.css
 git commit -m "style: add poster-led responsive homepage layout" -m "Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 5: Runtime verification and cleanup

**Files:**
- Modify only files that fail verification; do not touch unrelated pre-existing changes.

- [ ] **Step 1: Launch the frontend**

Run `cd hnaj-fe && npm run dev -- --host 127.0.0.1` and open the homepage in a browser.

- [ ] **Step 2: Verify signed-out navigation**

With no authenticated user, confirm the header shows the logo and `Khám phá`, but does not show `Điểm đến yêu thích` or `Lịch sử`. Confirm no text-only `HNAJ` appears in the header, slogan, or supporting copy.

- [ ] **Step 3: Verify signed-in navigation**

Use the existing app authentication flow or a signed-in browser state. Confirm `Điểm đến yêu thích` and `Lịch sử` appear only after `useAuth()` reports a user.

- [ ] **Step 4: Verify slideshow interaction**

Confirm all three poster images load, autoplay advances after 5 seconds, manual arrows and dots change slides, manual navigation restarts the timer, and hover/focus pauses autoplay.

- [ ] **Step 5: Verify accessibility and reduced motion**

Navigate slideshow controls with keyboard only. Confirm buttons have Vietnamese labels, the current indicator is exposed, and `prefers-reduced-motion` removes the fade/scale animation.

- [ ] **Step 6: Verify discovery behavior**

Submit the existing filter form and confirm the recommendation modal still opens, retry/roll actions still work, location behavior remains intact, and the submitted filter request is unchanged.

- [ ] **Step 7: Review the final diff**

Run `git status --short` and `git diff --stat HEAD~5..HEAD`. Confirm only the planned slideshow, homepage, CSS, asset, and documentation changes were introduced; leave unrelated existing working-tree changes untouched.
