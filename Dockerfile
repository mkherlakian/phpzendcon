FROM php:7.2-cli
RUN /usr/bin/yes 'no' | /usr/local/bin/pecl install swoole
RUN echo "extension=swoole.so" > /usr/local/etc/php/conf.d/swoole.ini
RUN mkdir /opt/beer
COPY src/ /opt/beer/src
EXPOSE 9501
ENTRYPOINT php /opt/beer/src/swoole.php
