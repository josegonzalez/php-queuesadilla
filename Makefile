.PHONY: test-docker
test-docker:
	@service beanstalkd start > /dev/null && \
	service mysql start > /dev/null && \
	service redis-server start > /dev/null && \
	mysql -u root -e 'CREATE DATABASE database_name;' && \
	mysql -u root database_name < config/schema-mysql.sql && \
	mysql -u root -e "CREATE USER 'travis'@'127.0.0.1' IDENTIFIED BY '';" && \
	mysql -u root -e "GRANT ALL PRIVILEGES ON database_name.* TO 'travis'@'127.0.0.1' WITH GRANT OPTION;" && \
	mysql -u root -e "DELETE FROM mysql.user WHERE User=''; FLUSH PRIVILEGES;" && \
	mysqladmin -u root password password && \
	phpcs --standard=psr2 src/ && \
	vendor/bin/phpmd src/ text cleancode,codesize,controversial,design,naming,unusedcode && \
	phpunit
