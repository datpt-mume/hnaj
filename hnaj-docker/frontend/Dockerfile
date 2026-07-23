FROM node:24-alpine AS base

WORKDIR /app

COPY hnaj-fe/package*.json ./
RUN npm ci

FROM base AS development

COPY hnaj-fe/ .

EXPOSE 5173

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]

FROM base AS build

COPY hnaj-fe/ .
RUN npm run build

FROM nginx:1.27-alpine AS production

COPY --from=build /app/dist /usr/share/nginx/html
COPY hnaj-docker/frontend/nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
