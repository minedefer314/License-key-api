FROM nginx:stable

# Copy nginx config
COPY ./build/nginx/default.conf /etc/nginx/conf.d/default.conf

# Create de app directory
RUN mkdir -p  /app
