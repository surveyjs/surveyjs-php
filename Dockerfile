FROM ubuntu:16.04

RUN apt-get update && apt-get -y upgrade && DEBIAN_FRONTEND=noninteractive apt-get -y install \
    apache2 php7.0 php7.0-mysql php7.0-pgsql php7.0-sqlite3 libapache2-mod-php7.0 curl lynx-cur

RUN a2enmod php7.0
RUN a2enmod rewrite

RUN sed -i "s/short_open_tag = Off/short_open_tag = On/" /etc/php/7.0/apache2/php.ini
RUN sed -i "s/error_reporting = .*$/error_reporting = E_ERROR | E_WARNING | E_PARSE/" /etc/php/7.0/apache2/php.ini

RUN apt-get update && apt-get -y install php-xdebug \
    && echo "zend_extension=$(find /usr/lib/php/ -name xdebug.so)" > /etc/php/7.0/apache2/php.ini \
    && echo "xdebug.remote_enable=on" >> /etc/php/7.0/mods-available/xdebug.ini \
    && echo "xdebug.remote_autostart=on" >> /etc/php/7.0/mods-available/xdebug.ini \
    && echo "xdebug.remote_connect_back=on" >> /etc/php/7.0/mods-available/xdebug.ini

ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_LOG_DIR /var/log/apache2
ENV APACHE_LOCK_DIR /var/lock/apache2
ENV APACHE_PID_FILE /var/run/apache2.pid

EXPOSE 80

ADD src /var/www/site

ADD config/apache-config.conf /etc/apache2/sites-enabled/000-default.conf

CMD /usr/sbin/apache2ctl -D FOREGROUND
