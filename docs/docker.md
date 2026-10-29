# Docker

Run the development containers of Easy!Appointments with Docker and Docker Compose utility. Docker allows you to compose 
your application from micro-services, without worrying about inconsistencies between development and production 
environments and without locking into any platform or language. 

Enter the `docker` directory and run `docker-compose up` to start the environment. 

You will need modify the root `config.php` so that it matches the following example: 

```php 
class Config {
    // ------------------------------------------------------------------------
    // GENERAL SETTINGS
    // ------------------------------------------------------------------------
    
    const BASE_URL      = 'http://localhost:8000'; 
    const LANGUAGE      = 'english';
    const DEBUG_MODE    = TRUE;
    const ALTCHA_HMAC_KEY = '';  // Required when CAPTCHA is enabled; openssl rand -hex 32

    // EMAIL (SMTP): server-specific credentials live here, not in the tracked email.php
    const EMAIL_PROTOCOL = 'smtp'; // or 'mail'
    const SMTP_HOST      = 'smtp.example.com';
    const SMTP_PORT      = 587;
    const SMTP_AUTH      = FALSE;   // set TRUE when the server requires authentication
    const SMTP_USER      = '';      // leave empty for anonymous relay
    const SMTP_PASS      = '';
    const SMTP_CRYPTO    = 'tls';   // '', 'ssl' or 'tls'

    // Related: keep "platform-check" disabled in composer.json, because the pinned PHP version
    // (8.3.6) and the eventual Ubuntu security patch release (e.g. 8.3.14) would otherwise
    // break the composer vendor autoload on servers that update PHP via `apt upgrade`.

    // ------------------------------------------------------------------------
    // DATABASE SETTINGS
    // ------------------------------------------------------------------------
    
    const DB_HOST       = 'easyappointments-database:3306';
    const DB_NAME       = 'easyappointments';
    const DB_USERNAME   = 'root';
    const DB_PASSWORD   = 'root';

    // ------------------------------------------------------------------------
    
}
```

In the host machine the server is accessible from `http://localhost:8000` and the database from `localhost:8001`.  

You can remove the docker containers with `docker rm easyappointments-server easyappointments-database`. 

You can remove the server image with `docker rmi easyappointments-server:v1`.

*This document applies to Easy!Appointments v1.4.2.*

[Back](readme.md)
