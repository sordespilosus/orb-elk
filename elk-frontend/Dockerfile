FROM cis.osep.orb.ru/official/node:20.17.0 AS build

WORKDIR /app

COPY package.json .

RUN npm config set proxy http://172.28.145.10:3128 && npm config set https-proxy http://172.28.145.10:3128 && npm install

COPY . .

COPY .env.prod .env

RUN npm run build

FROM cis.osep.orb.ru/official/nginx:1.27.0-alpine3.19 AS runtime

COPY --from=build /app/dist /usr/share/nginx/html
EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
