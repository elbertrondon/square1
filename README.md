Square
======

A Symfony project created on August 29, 2018, 3:11 pm.
"# square1" 

I used Symfony Framework to develop the solution

You need to install composer in your system and then install and update all the requirements that i used
following the next steps:

1) install composer with this command: $ curl -s http://getcomposer.org/installer | php
NOTE: Make sure to download the Composer.phar file in the same folder where the compositor.json file of the project is located.

2) install the vendors by executing the following command: $ php composer.phar install

At this point, all the necessary third-party libraries are found in the vendor / directory.

3) Create a database in your server (Mysql)

4) Create a file "parameters.yml" in this route: app/config/

this file should contain this info:

parameters:
    database_host: 127.0.0.1
    database_port: null
    database_name: NAME OF THE DATABASE CREATED AT STEP 3
    database_user: USERNAME OF THE DATABASE CREATED AT STEP 3
    database_password: PASSWORD OF THE DATABASE CREATED AT STEP 3
    mailer_transport: gmail #IN CASE YOU PUT A GMAIL ACCOUNT
    mailer_host: 127.0.0.1
    mailer_user: USERNAME OF THE GMAIL ACCOUNT
    mailer_password: PASSWORD OF THE GMAIL ACCOUNT
    sender_name: Square1
    secret: XXXXXXXXXXX #RANDOM NUMBER 

5) For be sure the connection with database it's ok please type this command: php bin/console doctrine:schema:update --dump-sql --force
This command create all the entities schema in the local database

6) Let's clear the cache with this command: php bin/console cache:clear --env=prod 


At this point the web page must be working good, so we need to extract the data from https://www.appliancesdelivered.ie/
To do this, please type in your browser /syncData (my host it's localhost so, the url looks like: http://localhost:9998/syncData).

This it's a function that gets all the data from de above page and it's inserts into our local database.

If everything it's ok, we should see a list of all the products that we got from the example page.

I put a link in the left side menu called "Synchronize data" to call this function and get all the changes maded to products.

This link should be a cron tab that runs at midnight or something like that, but i put it there to make it more easy for the test.

In the web we can:
* Create new users
* Login with an existing user
* Add products to our wishlist
* Remove products from our wishlist
* Share our wishlist
* See products grid
* Order products by Name and Price





