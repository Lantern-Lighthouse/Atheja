# Atheja
Community-driven web search engine backend

# How to get started
<details>
	<summary>Development</summary>
	
1. Clone the repo and open the clonned folder
2. Inside the repo copy file located at `app/Configs/db.ini.example` as `app/Configs/db.ini`
3. Edit your database config according examples
4. Download the project dependencies using Composer
	```bash
	php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && php composer-setup.php && php composer.phar install && rm composer-setup.php composer.phar
	```
5. To start your new Quicksand, run `php --server="localhost:8081"` inside project root
6. In browser (or in API client), navigate to `http://localhost:8081/api/db/init`
</details>
