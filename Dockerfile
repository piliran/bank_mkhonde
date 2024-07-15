# Use a base image that includes Node.js and npm
FROM node:16 AS build

# Set the working directory
WORKDIR /app

# Copy package.json and package-lock.json to the working directory
COPY package.json package-lock.json ./

# Install Node.js dependencies
RUN npm install

# Copy the rest of the application files
COPY . .

# Build the assets using Vite
RUN npm run build

# Use the official richarvey/nginx-php-fpm image as the base for the final image
FROM richarvey/nginx-php-fpm:3.1.6

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

# Copy built assets from the build stage
COPY --from=build /app/public/build /var/www/html/public/build

# Copy the rest of the application files
COPY . /var/www/html

# Run Composer install (optional)
# RUN composer install --optimize-autoloader --no-dev

# Expose the necessary ports
EXPOSE 80 443

# Start the server
CMD ["/start.sh"]
