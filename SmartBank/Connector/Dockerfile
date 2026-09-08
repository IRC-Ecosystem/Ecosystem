FROM node:20-alpine AS builder

WORKDIR /app

RUN apk add --no-cache openssl

COPY package*.json ./
RUN npm ci

COPY prisma ./prisma/
RUN npx prisma generate

COPY . .
RUN npm run build && npm prune --omit=dev

FROM node:20-alpine

WORKDIR /app

RUN apk add --no-cache openssl

COPY --from=builder /app/package*.json ./
COPY --from=builder /app/node_modules ./node_modules
COPY --from=builder /app/dist ./dist
COPY --from=builder /app/prisma ./prisma
COPY --from=builder /app/scripts/start-docker.js ./scripts/start-docker.js
COPY --from=builder /app/scripts/run-prisma.js ./scripts/run-prisma.js
COPY --from=builder /app/scripts/bootstrap-service.js ./scripts/bootstrap-service.js

EXPOSE 5000

CMD ["node", "scripts/start-docker.js"]
