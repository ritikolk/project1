FROM ubuntu:22.04

# Install dependencies
RUN apt-get update && apt-get install -y \
    wget \
    build-essential \
    libpcre3-dev \
    libssl-dev \
    perl \
    libreadline-dev \
    libncurses5-dev \
    libpcre3 \
    zlib1g \
    zlib1g-dev \
    libgd-dev \
    libssl-dev

# Download and extract OpenResty
RUN wget https://openresty.org/download/openresty-1.21.4.1.tar.gz && \
    tar zxvf openresty-1.21.4.1.tar.gz && \
    cd openresty-1.21.4.1 && \
    ./configure --prefix=/opt/openresty \
                --with-pcre-jit \
                --with-ipv6 \
                --without-http_redis2_module \
                --with-http_iconv_module \
                --with-http_postgres_module \
                -j8 && \
    make && \
    make install

# Create link to the OpenResty NGINX executable
RUN ln -s /opt/openresty/nginx/sbin/nginx /usr/local/bin/nginx

# Add Nginx configuration file
COPY nginx.conf /usr/local/openresty/nginx/conf/nginx.conf

CMD ["nginx", "-g", "daemon off;"]
