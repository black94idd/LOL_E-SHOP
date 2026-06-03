# 使用官方的 PHP 8.2 Apache 映像檔作為基礎
FROM php:8.2-apache

# 安裝 MySQL 的 PDO 擴充套件 (這樣 db.php 才能連線)
RUN docker-php-ext-install pdo pdo_mysql

# 將你的專案原始碼全部複製到伺服器的網頁根目錄
COPY . /var/www/html/

# 開放 80 port 讓外部連線
EXPOSE 80
