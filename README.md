### Agus Riyanto (Sr. Programmer PHP)

This repository is for completing SMART Interview Test Case. This repository is including:
- Postman Collection
- or See the online Documentation [here](https://documenter.getpostman.com/view/3445078/2sAXjNZBFG "here") or [here](https://interstellar-meteor-459690.postman.co/workspace/SEMAR-TEAM~6d94f0b7-515b-4366-b9d9-393bbd505c9f/collection/3445078-d50373c3-d8d1-4ac8-8515-7355b4689277?action=share&creator=3445078&active-environment=3445078-0d3ce06b-f02e-49fe-8786-d8130c8966d9 "here")

###Requirement
- PHP 8.2
- Composer

###Features
- Login
- Create a construction project
- Edit a construction project
- Delete a construction project
- View a construction project
- Show the construction project list.

# How to Run

###Using Composer
    composer install
	php bin/console doctrine:migrations:migrate
	php bin/console doctrine:fixtures:load

The Fixture / Seed Migration automatically create user:

email : admin@harakirimail.com
password : password123

###Using Docker
    docker compose up -d //Build the docker container
	docker exec -it <container name> bash //Come in to container shell
	php bin/console doctrine:migrations:migrate //Generate table needed
	php bin/console doctrine:fixtures:load //Generate Fixture
	
	./vendor/bin/phpunit --testdox   //to run unit test



![](https://i.ibb.co.com/H20NvfJ/unit-test.png)

The Fixture / Seed Migration automatically create user:

email : admin@harakirimail.com
password : password123

##### ### IF YOU NEED HELP FOR RUNNING THE SYSTEM PLEASE CONTACT ME : +681234549210

agus.riyanto007@gmail.com