## Laravel Translation API


### Rationale
This project went on a single-model architecture, an intentional design choice aimed at maximizing scalability while minimizing technical debt. This streamlined approach eliminates the need for complex joins and redundant database queries, ensuring that the localization layer remains highly performant as the application grows. 

The schema utilizes a `jsonb` data type for the `platform` column, enabling a single translation string to be mapped to multiple platforms or tags simultaneously. By leveraging JSON-based tagging, the system supports an unlimited number of target environments (e.g., Web, Mobile, Desktop) without duplicating content or requiring frequent schema migrations. The `group` column provides a flexible namespacing layer, allowing developers to use dot notation to organize keys into distinct contexts and prevent key collisions. 

This lean structure allows for rapid expansion and the addition of new features without the overhead of managing a complex relational tree for simple translation lookups.


### Prerequisites
 - PHP 8.2+
 - Composer
 - SQLite, MySQL, PostgreSQL

### Setup
 1. Install Dependencies
    ```bash
     composer install
    ```
 2. Environment Setup
    Configure necessary environment variables for both main .env file and env.testing for testing with PestPHP
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
 3. Database Setup
    Run migrations for the database via the command below
    ```bash
    php artisan migrate
    ```

### Docker Setup (docker branch)
   a docker-compose.yml, dockerfile for laravel api as well as a fairly default nginx config file is provided for docker setup. Running `docker compser up --build` will build the whole project with `php-fpm` `nginx` `postgres` in their individual containers. This is done to avoid having the painful experience of setting up supervisor on top of docker.

   ```bash
   docker compose up --build
   ```

### Running Tests
  To Run tests please use the Laravel built in artisan command 
  ```bash
  php artisan test
  ```
  additionally you can use the `--coverage` flag and the `--profile` flag to check the test code coverage and test performance metrics respectively
  ```bash
   php artisan test --coverage --profile
  ```  

### Endpoints
 - ### [GET]/api/translations
    - provides the list of available translations in the API 
    - Query Params:
      - `lang` - indicates which language the translation is 
      - `platform` - indicates which platform the translation is targeted.
      - `key` - indicates for which function should the translation be used. (e.g. button.approve should translate to 'Approve' in en)
      - `value` - actual translation.
 - ### [POST]/api/translations
    - allows an authenticated user to create a new translation
    - Expected Payload
      - `key` (required) - indicates for which function should the translation be used.
      - `lang` (required) - indicates which language the translation is.
      - `platform` (requried) - indicates which platform the translation is targeted.
      - `group` (nullable) - indicates which key group this translation belongs to (e.g. authentication, navigation, etc.)
      - `value` (requried) - actual translation.
 - ### [PUT]/api/translations
    - allows an authenticated user to update an existing translation
    - Expected Payload
      - `key` (requried) - indicates for which function should the translation be used.
      - `lang` (nullable) - indicates which language the translation is.
      - `platform` (nullable) - indicates which platform the translation is targeted.
      - `value` (required) - actual translation.
 - ### [GET]/api/translations/{translation_id}
    - allows an authenticated user to view an existing translation given a valid id.
 - ### [DELETE]/api/translations/{translation_id}
    - allows an authenticated user to delete an existing translation given a valid id.
 - ### [POST]/api/translations/import
    - allows an authenticated user to import in bulk translations from a json file.
       - `file` (required) - file payload should be named file and will be validated bioth in structure or if the file is empty
       - `lang` (required) - should be a valid localization code, determines what locale should the translations be assigned to.
       - `platform` (required) - should be a string separated by commas that indicate which platforms these translations are targeted for.
       - `replace_existing` (nullable) - boolean value of the policy in which existing records should be treated if true importing translations will be updated and will retain their original id but have their values updated. Else existing translations will be skipped.
 - ### [GET]/api/translations/exort/{lang}
    - allows an authenticated user to export saved translations into a `locale_{lang}.json` file
       - `lang` (required) - should be a valid localization code that corresponds to the locale to be exported.
       - `download_file` (nullable) - boolean value that decides if the endpoint should send a file or return with a json http response.
       - `flatten` (nullable) - boolean value that dictates if the data should be in a flat array or not. 