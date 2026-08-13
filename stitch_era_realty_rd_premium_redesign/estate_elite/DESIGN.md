---
name: Estate Elite
colors:
  surface: '#f7f9fb'
  surface-dim: '#d8dadc'
  surface-bright: '#f7f9fb'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f2f4f6'
  surface-container: '#eceef0'
  surface-container-high: '#e6e8ea'
  surface-container-highest: '#e0e3e5'
  on-surface: '#191c1e'
  on-surface-variant: '#45464d'
  inverse-surface: '#2d3133'
  inverse-on-surface: '#eff1f3'
  outline: '#76777d'
  outline-variant: '#c6c6cd'
  surface-tint: '#565e74'
  primary: '#000000'
  on-primary: '#ffffff'
  primary-container: '#131b2e'
  on-primary-container: '#7c839b'
  inverse-primary: '#bec6e0'
  secondary: '#0058be'
  on-secondary: '#ffffff'
  secondary-container: '#2170e4'
  on-secondary-container: '#fefcff'
  tertiary: '#000000'
  on-tertiary: '#ffffff'
  tertiary-container: '#002113'
  on-tertiary-container: '#009668'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#dae2fd'
  primary-fixed-dim: '#bec6e0'
  on-primary-fixed: '#131b2e'
  on-primary-fixed-variant: '#3f465c'
  secondary-fixed: '#d8e2ff'
  secondary-fixed-dim: '#adc6ff'
  on-secondary-fixed: '#001a42'
  on-secondary-fixed-variant: '#004395'
  tertiary-fixed: '#6ffbbe'
  tertiary-fixed-dim: '#4edea3'
  on-tertiary-fixed: '#002113'
  on-tertiary-fixed-variant: '#005236'
  background: '#f7f9fb'
  on-background: '#191c1e'
  surface-variant: '#e0e3e5'
typography:
  display-lg:
    fontFamily: Playfair Display
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 56px
    letterSpacing: -0.02em
  display-lg-mobile:
    fontFamily: Playfair Display
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Playfair Display
    fontSize: 32px
    fontWeight: '600'
    lineHeight: 40px
  headline-md-mobile:
    fontFamily: Playfair Display
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  title-lg:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  label-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 20px
    letterSpacing: 0.05em
  caption:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '400'
    lineHeight: 16px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 4px
  xs: 8px
  sm: 16px
  md: 24px
  lg: 48px
  xl: 80px
  container-max: 1280px
  gutter: 24px
  margin-mobile: 16px
---

## Brand & Style

This design system targets the high-end real estate market, focusing on luxury, reliability, and precision. The visual narrative combines **Minimalism** with **Corporate Modern** sensibilities to evoke a sense of "quiet luxury." 

The design emphasizes heavy whitespace and a strictly ordered information hierarchy to reduce cognitive load during high-stakes property browsing. Surfaces are clean and expansive, utilizing high-quality photography as a primary design element. The emotional response is one of calm confidence—reassuring the user through institutional stability and editorial elegance.

## Colors

The palette is anchored by **Deep Navy (#0F172A)**, used for primary branding and high-level navigation to establish authority. **Pure White (#FFFFFF)** serves as the primary canvas for all property listings, while **Soft Gray (#F8FAFC)** provides subtle containment for background sections.

Functional colors are used with high specificity:
- **Emerald Green**: Exclusively for "Available" statuses and final confirmation actions.
- **Bright Blue**: Reserved for interactive links and primary navigation highlights.
- **Warm Orange**: Used for "Reserved" or "Price Reduced" alerts to signify urgency without alarm.
- **Muted Red**: Indicates "Sold" properties, providing clear visual closure.

## Typography

The typographic system creates a tension between the **Playfair Display** serif (representing the heritage and luxury of the real estate sector) and **Inter** (representing modern efficiency). 

- **Headlines**: Use Playfair Display for property titles and section headers. These should always be high-contrast against their background.
- **Body & UI**: Use Inter for all functional text, descriptions, and data points. 
- **Labels**: Small uppercase Inter with slight tracking is used for property meta-data (e.g., "SQUARE FEET", "BEDROOMS") to create a structured, architectural feel.

## Layout & Spacing

This design system utilizes a **12-column fluid grid** for desktop and a **4-column grid** for mobile. 

- **Rhythm**: All spacing follows a 4px base unit. Component-level spacing typically uses 16px (sm) or 24px (md) increments.
- **Margins**: Mobile devices use a 16px side margin, while desktop views utilize a centered container with a maximum width of 1280px to maintain readability of long-form descriptions.
- **Density**: The layout is intentionally "airy." Vertical rhythm between sections should be generous (48px to 80px) to give property photography space to breathe.

## Elevation & Depth

Visual hierarchy is achieved through **Ambient Shadows** and **Tonal Layers**.

- **Surfaces**: The primary background is Soft Gray (#F8FAFC). Interactive cards use a Pure White (#FFFFFF) surface to "lift" off the background.
- **Shadows**: Use highly diffused, low-opacity shadows (Blur: 20px, Y-Offset: 4px, Color: #0F172A at 4% opacity). This creates a subtle sense of depth without looking heavy or cluttered.
- **Interaction**: Upon hover, elevation should increase slightly by doubling the shadow opacity and shifting the Y-offset to 8px, simulating the physical act of an object being pulled toward the user.

## Shapes

The shape language is sophisticated and consistent. A standard **12px (rounded-lg)** corner radius is applied to all primary property cards and containers. Smaller UI elements like buttons and input fields utilize an **8px (0.5rem)** radius to maintain a professional, slightly more rigid appearance. 

Status badges (e.g., "Available") use a full pill-shape (999px) to distinguish them from structural UI elements like buttons.

## Components

### Buttons
- **Primary**: Deep Navy background with White text. Bold, 16px padding on sides.
- **Secondary**: Transparent background with Deep Navy 1px border.
- **Success (Action)**: Emerald Green background, used sparingly for "Book Viewing" or "Contact Agent."

### Cards
Property cards are the core of this design system. They feature a 12px corner radius, a subtle ambient shadow, and a top-aligned image area. Price and status badges are overlaid on the image in the top corners.

### Form Fields
Inputs use a Soft Gray background with a subtle 1px border (#E2E8F0). On focus, the border transitions to Bright Blue. Labels are always positioned above the field in 12px Inter Medium.

### Status Chips
Small, high-contrast badges used for property status.
- **Available**: Emerald Green background with white text.
- **Sold**: Muted Red background with white text.
- **Reserved**: Warm Orange background with white text.

### Additional Components
- **Property Meta-Grid**: A 3 or 4-column layout for icon-based property details (baths, beds, sqft).
- **Sticky Contact Bar**: On mobile, a fixed footer component containing the "Call" and "Enquire" CTAs for immediate accessibility.