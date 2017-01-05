FROM ubuntu:14.04

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get -qq update

RUN apt-get -qq install -qq -y software-properties-common python-software-properties curl
RUN add-apt-repository ppa:chris-lea/redis-server
RUN apt-get -qq update
RUN apt-get -qq install -qq -y beanstalkd
RUN apt-get -qq install -qq -y redis-server
RUN apt-get -qq install -qq -y mysql-server
RUN apt-get -qq install -qq -y postgresql postgresql-contrib
RUN apt-get -qq install -qq -y rabbitmq-server
RUN apt-get -qq install -qq -y make
RUN apt-get -qq install -qq -y git

# `language-pack-en-base` is necessary to properly install the key for ppa:ondrej/php5-5.6
RUN \
    locale-gen en_US.UTF-8 && \
    apt-get -qq install -qq -y language-pack-en-base
RUN LC_ALL=en_US.UTF-8 add-apt-repository ppa:ondrej/php5-5.6 && \
    apt-get -qq update
RUN apt-get -qq install -qq -y php5-cli php5-curl php5-mysql php5-pgsql php-pear php5-xdebug php5-redis

RUN \
    curl -LsS https://phar.phpunit.de/phpunit.phar > phpunit.phar && \
    curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer && \
    mv phpunit.phar /usr/local/bin/phpunit && \
    chmod +x /usr/local/bin/composer /usr/local/bin/phpunit && \
    phpunit --version

ADD composer.json /app/composer.json
WORKDIR /app

RUN \
    composer install > /dev/null && \
    pear install --alldeps PHP_CodeSniffer > /dev/null

ENV BEANSTALK_URL="beanstalk://127.0.0.1:11300?queue=default&timeout=1" \
    MEMORY_URL="memory:///?queue=default&timeout=1" \
    MYSQL_URL="mysql://travis@127.0.0.1:3306/database_name?queue=default&timeout=1" \
    NULL_URL="null:///?queue=default&timeout=1" \
    RABBITMQ_URL="rabbitmq://guest:guest@127.0.0.1:5672/?queue=default&timeout=1" \
    REDIS_URL="redis://127.0.0.1:6379/0?queue=default&timeout=1" \
    MEMORY_URL="synchronous:///?queue=default&timeout=1" \
    POSTGRES_URL="pgsql://travis:asdf12@127.0.0.1:5432/database_name?queue=default"

ADD . /app

RUN make test-docker
