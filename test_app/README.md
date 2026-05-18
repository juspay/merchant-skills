# Test merchant app

A minimal React + Vite e-commerce app you can use to try the `/integrate` flow end-to-end.

## What's in it

[`test-ecomm2/`](./test-ecomm2/) — "Shoply," a small storefront with products, a cart, and a checkout page that's deliberately left empty. That empty checkout is where `/integrate` wires Juspay in.

```
test-ecomm2/
├── package.json
├── vite.config.js
├── index.html
└── src/
    ├── App.jsx
    ├── main.jsx
    ├── products.js
    ├── components/    Header, Footer, ProductCard
    ├── context/       CartContext
    └── pages/         Home, Cart, Checkout  ← /integrate edits this
```

## Try it

Prerequisites: Node.js 18+ and [Claude Code](https://claude.ai/code).

```bash
# 1. Get the demo app
git clone https://github.com/sahyll/juspay-skills.git
cd juspay-skills/test_app/test-ecomm2
npm install

# 2. Install the Juspay CLI (one-time)
npm install -g --foreground-scripts https://github.com/sahyll/juspay-skills/releases/download/cli-v0.2.1/juspay-claude-code-skill-0.2.1.tgz

# 3. Run the app + Claude side-by-side
npm run dev          # in one terminal — Vite at http://localhost:5173
juspay-claude        # in another terminal, from the test-ecomm2 dir
```

Inside Claude:

```
/integrate
```

The wizard will sign you in, read your merchant account, recommend a product, fetch the relevant docs, and generate the actual integration code into `src/pages/Checkout.jsx` (plus any backend it needs you to add).

## Sparse-checkout (only the test app, not the whole repo)

If you only want the demo without cloning the rest:

```bash
git clone --filter=blob:none --no-checkout https://github.com/sahyll/juspay-skills.git
cd juspay-skills
git sparse-checkout init --cone
git sparse-checkout set test_app/test-ecomm2
git checkout main
cd test_app/test-ecomm2 && npm install
```

## Reset between runs

`/integrate` modifies the source. To start fresh:

```bash
cd test_app/test-ecomm2
git checkout .   # discard generated changes
rm -rf node_modules && npm install
```
