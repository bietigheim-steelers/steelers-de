/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "../templates/**/*.html5",
    "../templates/**/*.twig",
    "./js/**/*.js",
    "./js/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        steelwhite: "#eef0eb",
        steelnav: "#046a38",
        steelgreen: "#046a38",
        steelpogreen: "#00994c",
        steelblue: "#009cde",
        steellightgreen: "#046a38",
      },
      typography: {
        DEFAULT: {
          css: {
            "--tw-prose-body": "#000",
            lineHeight: "auto",
            "--tw-prose-bullets": "#000",
            ul: {
              paddingLeft: "1.25em",
            },
            a: {
              color: "var(--tw-prose-body)",
              "&:hover": {
                color: "#046a38",
              },
            },
            "a.email": {
              color: "var(--tw-prose-body)",
              "&:hover": {
                color: "#046a38",
              },
            },
          },
        },
      },
      aspectRatio: {
        news: "4 / 5",
        video: "16 / 9",
      },
      // Kräftigeres ease-out für die Bewegungen im Dauerkarten-Formular.
      // Die eingebauten CSS-Kurven wirken bei kurzen Dauern zu weich.
      transitionTimingFunction: {
        steelers: "cubic-bezier(0.23, 1, 0.32, 1)",
      },
      spacing: {
        defaultbig: "150px",
      },
      width: {
        p60: "60px",
        p40: "40px",
      },
      height: {
        p60: "60px",
        p40: "40px",
      },
      keyframes: {
        wiggle: {
          "70%,100%": {
            transform: "rotate(0deg) scale(1)",
          },
          "80%": {
            transform: "rotate(-3deg) scale(1.1)",
          },
          "90%": {
            transform: "rotate(3deg) scale(0.9)",
          },
          "95%": {
            transform: "rotate(-3deg) scale(1)",
          },
        },
      },
      animation: {
        wiggle: "wiggle 5s ease-in-out infinite",
      },
      fontFamily: {
        sans: [
          "PP Formula",
          "WhyteReg",
          "Lucida Sans Unicode",
          "Geneva",
          "Verdana",
          "sans-serif",
        ],
        headline: [
          "GroteskBlack",
          "Lucida Sans Unicode",
          "Geneva",
          "Verdana",
          "sans-serif",
        ],
        headlineItalic: [
          "GroteskBlackItalic",
          "Lucida Sans Unicode",
          "Geneva",
          "Verdana",
          "sans-serif",
        ],
      },
    },
  },
  plugins: [
    require("postcss-import"),
    require("@tailwindcss/typography"),
    require("@tailwindcss/forms")({
      strategy: "base", // only generate global styles
    }),
    require("@tailwindcss/aspect-ratio"),
    require("autoprefixer"),
  ],
};
