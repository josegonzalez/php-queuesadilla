.PHONY: test-docker
test-docker:
	@service beanstalkd start > /dev/null && \
	service mysql start > /dev/null && \
	service rabbitmq-server start > /dev/null && \
	service redis-server start > /dev/null && \
	mysql -u root -e 'CREATE DATABASE database_name;' && \
	mysql -u root database_name < config/schema-mysql.sql && \
	mysql -u root -e "CREATE USER 'travis'@'127.0.0.1' IDENTIFIED BY '';" && \
	mysql -u root -e "GRANT ALL PRIVILEGES ON database_name.* TO 'travis'@'127.0.0.1' WITH GRANT OPTION;" && \
	mysql -u root -e "DELETE FROM mysql.user WHERE User=''; FLUSH PRIVILEGES;" && \
	mysqladmin -u root password password && \
	service postgresql start && \
	su postgres -c "createdb database_name" && \
	su postgres -c "psql -d database_name -f config/schema-pgsql.sql" && \
	su postgres -c "psql database_name -c \"CREATE USER travis password 'asdf12';\"" && \
	su postgres -c "psql database_name -c \"ALTER ROLE travis WITH Superuser;\"" && \
	su postgres -c "psql database_name -c \"GRANT ALL PRIVILEGES ON DATABASE database_name TO travis;\"" && \
	cp phpunit.xml.dist phpunit.xml && \
	phpcs --standard=psr2 src/ && \
	vendor/bin/phpmd src/ text cleancode,codesize,controversial,design,naming,unusedcode && \
	phpunit


.PHONY: test-docker-rabbitmq
test-docker-rabbitmq:
	@service rabbitmq-server start > /dev/null && \
	cp phpunit.xml.dist phpunit.xml && \
	phpunit tests/josegonzalez/Queuesadilla/Event/EventTest.php
	# phpunit tests/josegonzalez/Queuesadilla/Engine/RabbitmqEngineTest.php
