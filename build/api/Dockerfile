FROM php:8.2-fpm

# Login to container as root user
USER root

# Symfony dev environment 
ENV APP_ENV=dev

# Install composer
RUN apt-get update && apt-get install -y curl unzip gnupg && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_mysql

# Copy php default configuration
COPY ./build/api/conf/default.ini /usr/local/etc/php/conf.d/default.ini

# Set working directory
WORKDIR /app

# Arguments defined in compose.yml
ARG USER_NAME
ARG USER_ID
ARG GROUP_NAME
ARG GROUP_ID

# Create system user to run Composer and PHP Commands
RUN if [ ! -z ${USER_NAME} ] && [ ! -z ${GROUP_NAME} ] && [ ${USER_ID:-0} -ne 0 ] && [ ${GROUP_ID:-0} -ne 0 ] ; then \
    useradd -G www-data,root -u $USER_ID -d /home/$USER_NAME $USER_NAME && \
    mkdir -p /home/$USER_NAME/.composer  && \
    chown -Rf ${USER_NAME}:${GROUP_NAME} /home/$USER_NAME  && \
    chown -R ${USER_NAME}:${GROUP_NAME} /app \
    ; fi

# Login to container as non-root user 
USER ${USER_ID:-0}:${GROUP_ID:-0}
