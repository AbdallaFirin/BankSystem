FROM nginx:1.25-alpine

# Copy config directly into the image — avoids bind-mount file/directory
# conflicts on Linux servers where the host path may not pre-exist.
COPY Docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Remove the default nginx config
RUN rm -f /etc/nginx/conf.d/default.conf.bak 2>/dev/null || true
