install:
	composer install
test:
	./vendor/phpunit/phpunit/phpunit -c phpunit.xml
