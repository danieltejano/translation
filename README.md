## Laravel Translation API

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

### Running Tests
  To Run tests please use the Laravel built in artisan command 
  ```bash
  php artisan test
  ```

### Endpoints
 - ### [GET]/api/translations
    - provides the list of available translations in the API 
    - Query Params:
      - lang - indicates which language the translation is 
      - platform - indicates which platform the translation is targeted.
      - purpose - indicates for which function should the translation be used. (e.g. button.approve should translate to 'Approve' in en)
      - value - actual translation.
 - ### [POST]/api/translations
    - allows an authenticated user to create a new translation
    - Expected Payload
      - purpose (required) - indicates for which function should the translation be used.
      - lang (required) - indicates which language the translation is.
      - platform (requried) - indicates which platform the translation is targeted.
      - value (requried) - actual translation.
 - ### [PUT]/api/translations
    - allows an authenticated user to update an existing translation
    - Expected Payload
      - purpose (requried) - indicates for which function should the translation be used.
      - lang (required) - indicates which language the translation is.
      - platform (required) - indicates which platform the translation is targeted.
      - value (required) - actual translation.
 - ### [GET]/api/translations/{translation_id}
    - allows an authenticated user to view an existing translation given a valid id.
 - ### [DELETE]/api/translations/{translation_id}
    - allows an authenticated user to delete an existing translation given a valid id.
