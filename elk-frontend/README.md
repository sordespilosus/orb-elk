# ELK Frontend

## Первый запуск

Рекомендуется Node.js 20.17.0. Именно эта версия используется в `Dockerfile`.

Локально можно пробовать Node.js 21, если `npm ci`, `npm run dev` и `npm run build` проходят без ошибок. Для максимально повторяемого окружения лучше использовать 20.17.0.

```bash
node --version
npm --version
npm ci
npm run dev
```

Dev-сервер Vite запускается командой `npm run dev`. После запуска открыть адрес, который Vite покажет в консоли, обычно это `http://localhost:5173/`.

Для локальной разработки используется `.env.development`:

```env
VITE_URL=
```

При пустом `VITE_URL` запросы идут на текущий origin, а `vite.config.ts` проксирует API на `https://test-lk.orb.ru`.

## Основные команды

```bash
# установка зависимостей строго по package-lock.json
npm ci

# запуск локального dev-сервера
npm run dev

# тесты
npm run test

# eslint
npm run lint

# production build
npm run build

# локальный просмотр собранного dist
npm run preview
```

Важно: `npm run build` сначала автоматически запускает `npm run test`, потому что в `package.json` есть `prebuild`.

## Docker build

```bash
docker build -t elk-frontend .
docker run --rm -p 8080:80 elk-frontend
```

После запуска Docker-образа фронт будет доступен на `http://localhost:8080/`.

# React + TypeScript + Vite

This template provides a minimal setup to get React working in Vite with HMR and some ESLint rules.

Currently, two official plugins are available:

- [@vitejs/plugin-react](https://github.com/vitejs/vite-plugin-react/blob/main/packages/plugin-react/README.md) uses [Babel](https://babeljs.io/) for Fast Refresh
- [@vitejs/plugin-react-swc](https://github.com/vitejs/vite-plugin-react-swc) uses [SWC](https://swc.rs/) for Fast Refresh

## Expanding the ESLint configuration

If you are developing a production application, we recommend updating the configuration to enable type aware lint rules:

- Configure the top-level `parserOptions` property like this:

```js
export default tseslint.config({
  languageOptions: {
    // other options...
    parserOptions: {
      project: ['./tsconfig.node.json', './tsconfig.app.json'],
      tsconfigRootDir: import.meta.dirname,
    },
  },
})
```

- Replace `tseslint.configs.recommended` to `tseslint.configs.recommendedTypeChecked` or `tseslint.configs.strictTypeChecked`
- Optionally add `...tseslint.configs.stylisticTypeChecked`
- Install [eslint-plugin-react](https://github.com/jsx-eslint/eslint-plugin-react) and update the config:

```js
// eslint.config.js
import react from 'eslint-plugin-react'

export default tseslint.config({
  // Set the react version
  settings: { react: { version: '18.3' } },
  plugins: {
    // Add the react plugin
    react,
  },
  rules: {
    // other rules...
    // Enable its recommended rules
    ...react.configs.recommended.rules,
    ...react.configs['jsx-runtime'].rules,
  },
})
```
