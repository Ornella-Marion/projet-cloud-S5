# =============================================
# Dockerfile pour le frontend Ionic/Vue.js
# =============================================

# --- Étape 1 : Build ---
FROM node:22-alpine AS build

WORKDIR /app

# Forcer le registre npm officiel (éviter les miroirs lents)
RUN npm config set registry https://registry.npmjs.org/

# Copier les fichiers de dépendances
COPY package.json package-lock.json* ./

# Installer les dépendances
RUN npm install --legacy-peer-deps

# Copier tout le code source
COPY . .

# Construire pour la production
RUN npm run build

# --- Étape 2 : Serveur Nginx ---
FROM nginx:1.25-alpine

# Copier le build dans Nginx
COPY --from=build /app/dist /usr/share/nginx/html

# Copier la config Nginx personnalisée
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
