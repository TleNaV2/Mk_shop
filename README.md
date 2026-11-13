# MK Shop

## Run locally with XAMPP

1. Start Apache and MySQL from the XAMPP Control Panel.
2. Ensure the `mk_shop` database is available in phpMyAdmin. If it is not, create a database named `mk_shop` and import `php_api/database/mk_shop.sql`.
3. From this project directory, run:

   ```sh
   npm install
   npm run serve
   ```

4. Open the local URL printed by Vue CLI (usually `http://localhost:8080`).

The Vue development server forwards `/php_api` requests to this project's PHP API in Apache. The database connection uses the XAMPP defaults in `php_api/condb.php` (`root` with an empty password); update those values if your local MySQL credentials differ.

## Production build

Run `npm run build`. With Apache's document root at `C:\xampp\htdocs`, open `http://localhost/MK_Shop-master/dist/`. The build's public path is based on this project's directory name.
