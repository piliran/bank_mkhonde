# Stage 1: Build the assets
FROM node:16 AS build

# Set working directory
WORKDIR /app

# Copy package.json and package-lock.json
COPY package*.json ./

# Install Node.js dependencies
RUN npm install

# Copy the rest of the application files
COPY . .

# Build assets using Vite
RUN npm run build

# Stage 2: Serve the application
FROM richarvey/nginx-php-fpm:3.1.6

# Set working directory
WORKDIR /var/www/html

# Copy the application files
COPY . .

# Copy the built assets from the build stage
COPY --from=build /app/public/build /var/www/html/public/build

# Install PHP dependencies
RUN php composer.phar install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Set environment variables
ENV SKIP_COMPOSER 1
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1
ENV APP_ENV production
ENV APP_DEBUG false
ENV LOG_CHANNEL stderr
ENV COMPOSER_ALLOW_SUPERUSER 1

# Expose the default port
EXPOSE 80

# Start the application
CMD ["/start.sh"]
