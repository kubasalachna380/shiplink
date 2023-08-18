# Shiplink - simple order management API

This simple API allow to create and make some features on orders.
API bases on Symfony 5.4, PHP 7.4 and use MySQL database with phpMyAdmin.
Project has been created using Docker container with nginx.
Follow with below steps to prepare your fully working environments:
1. Clone repository into your computer.
2. Using commandline, open directory with clonned project.
3. Run command: docker-compose build
4. Run command: docker-compose up -d
5. Verify app/ directory - if 'vendor' directory doesn't exists move to the step 6, otherwise move to step 8
6. Using commandline, move to 'app' directory
7. Run command: php composer.phar install (and wait a few minutes)
8. In web browser open http://127.0.0.1:8000/ and login to database using credensials places in .env file ('app' directory)
9. Click into Shiplink database - is empty
10. Using commandline, in 'app' directory run command: php bin/console doctrine:migrations:migrate
11. Use 'yes' option
12. In Shiplink database has been created four tables
13. Using Postman (or other) run http://localhost:8081/work-check (GET) -> if you recive positive response that means your environment is ready to use.

Using:
To use this simple API, import Postman collection with seven requests.
Postman collention has Patrycja :)
1. Product list - executes fakestoreapi directly (GET)
2. Work check - verify API working (GET)
3. Product list (using container) - return list od products (GET)
4. Create new order - create and save order in database (POST)
5. Update status - allow to update status of muliple orders in one time (PUT)
6. Delete order - delete order form database (order numer in link) (DELETE)
7. Re-create order - create new order based od existing (base order nuber in link) (POST)

