FROM richarvey/nginx-php-fpm:3.1.6

# Install Node.js and npm
RUN apt-get update && \
    apt-get install -y curl gnupg && \
    curl -sL https://deb.nodesource.com/setup_16.x | bash - && \
    apt-get install -y nodejs && \
    npm install -g npm@latest

# Set working directory
WORKDIR /var/www/html

# Copy the application files
COPY . .

# Install PHP dependencies
RUN php composer.phar install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Install Node.js dependencies
RUN npm install

# Build assets using Vite
RUN npm run build

# Remove development dependencies
RUN npm prune --production

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
