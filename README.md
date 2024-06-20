# Production-Grade WordPress on Kubernetes

## Overview
This project sets up a production-grade WordPress application on Kubernetes, complete with MySQL as the database server and Nginx as the reverse proxy with Lua support. The setup also includes monitoring and alerting using Prometheus and Grafana.

## Prerequisites
- Docker
- Kubernetes Cluster
- kubectl
- Helm

## Directory Structure
wordpress-k8s/
├── k8s-manifests/
│ ├── persistent-volumes.yaml
│ ├── mysql-deployment.yaml
│ ├── wordpress-deployment.yaml
│ └── nginx-deployment.yaml
├── mysql/
│ └── Dockerfile
├── wordpress/
│ ├── Dockerfile
│ └── wp-config.php
└── nginx/
├── Dockerfile
└── nginx.conf


## Setup Guide

### Step 1: Build and Push Docker Images

#### WordPress Dockerfile
1. Navigate to the `wordpress` directory:
    ```sh
    cd wordpress
    ```

2. Create the `Dockerfile`:
    ```dockerfile
    FROM wordpress:latest
    COPY wp-config.php /var/www/html/
    ```

3. Build and push the Docker image:
    ```sh
    docker build -t your-docker-repo/wordpress:latest .
    docker push your-docker-repo/wordpress:latest
    ```

#### MySQL Dockerfile
1. Navigate to the `mysql` directory:
    ```sh
    cd ../mysql
    ```

2. Create the `Dockerfile`:
    ```dockerfile
    FROM mysql:latest
    ENV MYSQL_ROOT_PASSWORD=root_password
    ENV MYSQL_DATABASE=wordpress
    ENV MYSQL_USER=wp_user
    ENV MYSQL_PASSWORD=wp_password
    ```

3. Build and push the Docker image:
    ```sh
    docker build -t your-docker-repo/mysql:latest .
    docker push your-docker-repo/mysql:latest
    ```

#### Nginx with OpenResty and Lua
1. Navigate to the `nginx` directory:
    ```sh
    cd ../nginx
    ```

2. Create the `Dockerfile`:
    ```dockerfile
    FROM ubuntu:22.04

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

    RUN ln -s /opt/openresty/nginx/sbin/nginx /usr/local/bin/nginx

    COPY nginx.conf /usr/local/openresty/nginx/conf/nginx.conf

    CMD ["nginx", "-g", "daemon off;"]
    ```

3. Create the `nginx.conf` file:
    ```nginx
    http {
        lua_shared_dict prometheus_metrics 10M;
        init_by_lua_block {
          local prometheus = require("prometheus").init("prometheus_metrics")
          metric_requests = prometheus:counter(
            "nginx_http_requests_total", "Number of HTTP requests", {"host", "status"})
          metric_latency = prometheus:histogram(
            "nginx_http_request_duration_seconds", "HTTP request latency", {"host"})
        }

        log_by_lua_block {
          metric_requests:inc(1, {ngx.var.host, ngx.var.status})
          metric_latency:observe(tonumber(ngx.var.request_time), {ngx.var.host})
        }

        server {
            listen 80;

            location / {
                proxy_pass http://wordpress-svc;
            }

            location /metrics {
                content_by_lua(prometheus.collect)
            }
        }
    }
    ```

4. Build and push the Docker image:
    ```sh
    docker build -t your-docker-repo/nginx:latest .
    docker push your-docker-repo/nginx:latest
    ```

### Step 2: Kubernetes Manifests

1. Create the `persistent-volumes.yaml` file in the `k8s-manifests` directory:
    ```yaml
    apiVersion: v1
    kind: PersistentVolume
    metadata:
      name: mysql-pv
    spec:
      capacity:
        storage: 20Gi
      accessModes:
        - ReadWriteOnce
      hostPath:
        path: "/mnt/data/mysql"
    ---
    apiVersion: v1
    kind: PersistentVolume
    metadata:
      name: wordpress-pv
    spec:
      capacity:
        storage: 20Gi
      accessModes:
        - ReadWriteOnce
      hostPath:
        path: "/mnt/data/wordpress"
    ---
    apiVersion: v1
    kind: PersistentVolumeClaim
    metadata:
      name: mysql-pvc
    spec:
      accessModes:
        - ReadWriteOnce
      resources:
        requests:
          storage: 20Gi
    ---
    apiVersion: v1
    kind: PersistentVolumeClaim
    metadata:
      name: wordpress-pvc
    spec:
      accessModes:
        - ReadWriteOnce
      resources:
        requests:
          storage: 20Gi
    ```

2. Create the `mysql-deployment.yaml` file:
    ```yaml
    apiVersion: apps/v1
    kind: Deployment
    metadata:
      name: mysql
    spec:
      replicas: 1
      selector:
        matchLabels:
          app: mysql
      template:
        metadata:
          labels:
            app: mysql
        spec:
          containers:
            - name: mysql
              image: your-docker-repo/mysql:latest
              ports:
                - containerPort: 3306
              volumeMounts:
                - name: mysql-persistent-storage
                  mountPath: /var/lib/mysql
          volumes:
            - name: mysql-persistent-storage
              persistentVolumeClaim:
                claimName: mysql-pvc
    ---
    apiVersion: v1
    kind: Service
    metadata:
      name: mysql
    spec:
      ports:
        - port: 3306
      selector:
        app: mysql
    ```

3. Create the `wordpress-deployment.yaml` file:
    ```yaml
    apiVersion: apps/v1
    kind: Deployment
    metadata:
      name: wordpress
    spec:
      replicas: 1
      selector:
        matchLabels:
          app: wordpress
      template:
        metadata:
          labels:
            app: wordpress
        spec:
          containers:
            - name: wordpress
              image: your-docker-repo/wordpress:latest
              ports:
                - containerPort: 80
              env:
                - name: WORDPRESS_DB_HOST
                  value: mysql
                - name: WORDPRESS_DB_USER
                  value: wp_user
                - name: WORDPRESS_DB_PASSWORD
                  value: wp_password
                - name: WORDPRESS_DB_NAME
                  value: wordpress
              volumeMounts:
                - name: wordpress-persistent-storage
                  mountPath: /var/www/html
          volumes:
            - name: wordpress-persistent-storage
              persistentVolumeClaim:
                claimName: wordpress-pvc
    ---
    apiVersion: v1
    kind: Service
    metadata:
      name: wordpress
    spec:
      ports:
        - port: 80
      selector:
        app: wordpress
    ```

4. Create the `nginx-deployment.yaml` file:
    ```yaml
    apiVersion: apps/v1
    kind: Deployment
    metadata:
      name: nginx
    spec:
      replicas: 1
      selector:
        matchLabels:
          app: nginx
      template:
        metadata:
          labels:
            app: nginx
        spec:
          containers:
            - name: nginx
              image: your-docker-repo/nginx:latest
              ports:
                - containerPort: 80
    ---
    apiVersion: v1
    kind: Service
    metadata:
      name: nginx
    spec:
      ports:
        - port: 80
      selector:
        app: nginx
      type: LoadBalancer
    ```

### Step 3: Deploy to Kubernetes

Apply the Kubernetes manifests:

```sh
kubectl apply -f k8s-manifests/persistent-volumes.yaml
kubectl apply -f k8s-manifests/mysql-deployment.yaml
kubectl apply -f k8s-manifests/wordpress-deployment.yaml
kubectl apply -f k8s-manifests/nginx-deployment.yaml

```
### Step 4: Monitoring and Alerting

```
Deploy Prometheus and Grafana
Add Helm charts for Prometheus and Grafana:

helm repo add prometheus-community https://prometheus-community.github.io/helm-charts
helm repo add grafana https://grafana.github.io/helm-charts
helm repo update
Deploy Prometheus:

helm install prometheus prometheus-community/kube-prometheus-stack
Deploy Grafana:

helm install grafana grafana/grafana
Configure Prometheus for Nginx Monitoring
Create a ConfigMap for Prometheus extra scrape configurations:

apiVersion: v1
kind: ConfigMap
metadata:
  name: prometheus-extra-scrape-configs
  namespace: prometheus
data:
  nginx-scrape-configs.yaml: |
    - job_name: 'nginx'
      metrics_path: '/metrics'
      static_configs:
        - targets: ['nginx:80']
Apply the ConfigMap:

kubectl apply -f nginx-prometheus-config.yaml
Upgrade the Prometheus Helm release to include the extra scrape config:

helm upgrade prometheus prometheus-community/kube-prometheus-stack --set additionalScrapeConfigs='{"nginx-scrape-configs.yaml"}'
Create Prometheus Rules for Alerts
Create or edit prometheus-rules.yaml:

apiVersion: monitoring.coreos.com/v1
kind: PrometheusRule
metadata:
  name: nginx-rules
spec:
  groups:
  - name: nginx
    rules:
    - alert: HighNginxErrorRate
      expr: rate(nginx_http_requests_total{status="5xx"}[5m]) > 5
      for: 10m
      labels:
        severity: critical
      annotations:
        summary: "High error rate for Nginx"
        description: "Nginx has a high rate of 5xx errors."
Apply the Prometheus rules:

kubectl apply -f prometheus-rules.yaml
Access Grafana
Get the Grafana admin password:
kubectl get secret --namespace default grafana -o jsonpath="{.data.admin-password}" | base64 --decode ; echo
Port-forward the Grafana service:

kubectl port-forward svc/grafana 3000:80
Access Grafana at http://localhost:3000 and log in with admin as the username and the retrieved password.
```
### Step 5: Create Grafana Dashboards
```
Pod CPU Utilisation:
Add a new panel with the following Prometheus query:

sum(rate(container_cpu_usage_seconds_total{pod=~"wordpress-.*"}[1m])) by (pod)
Total Nginx Request Count:
Add a new panel with the following Prometheus query:

nginx_http_requests_total
Total 5xx Requests:
Add a new panel with the following Prometheus query:

rate(nginx_http_requests_total{status="5xx"}[1m])
Cleanup
To clean up Helm releases:

helm delete prometheus
helm delete grafana
helm delete my-release
To clean up Kubernetes resources:

kubectl delete -f k8s-manifests/
```
