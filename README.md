# nanogp

Allows <b>[nanogallery2](https://github.com/nanostudio-org/nanogallery2)</b> to access Google Photos content.  

Since february 9, 2017, Google Photos can no more be accessed without account owner's explicit authorization.
Permanent authorization is only possible for server side applications.

<b>nanogp</b> is a PHP application which delivers Google Photos content to nanogallery2 on your web pages.


## Pre-requisites:
Web server with PHP version > 5.2


## Installation  

- Create a folder named `nanogp` on your web server.
- Copy the content of the `dist` folder in this folder.
  
  
## Configuration  

Settings are defined in `admin/config.php`:
  
```
  $cfg_client_id = 'yyy';
  $cfg_client_secret = 'zzz';
  $cfg_application_name = 'nanogallery2gp';
```
  
`cfg_client_id` and `cfg_client_secret` can be obtained from the Google developers console.  
  
## Enable Google API  
  
Create and configure a new projet.  
  
1) Open page: https://console.developers.google.com  
  
2) Create a new project named `nanogallery2gp`  
  
<img src="img/google_api_console1.jpg?raw=true" alt="step 1" style="max-width:400px;"/>
  
<img src="img/google_api_console2.jpg?raw=true" alt="step 2" style="max-width:400px;"/>
  
3) Create a consent screen  
  
Select your email address  
  
Define the "product namaeshown to user": "nanogallery2gp"  
  
Others fields are optional  
  
<img src="img/google_api_console3.jpg?raw=true" alt="step 3" style="max-width:400px;"/>
  
4) Create `credentials` kind `OAuth Client ID`  
    
<img src="img/google_api_console4.jpg?raw=true" alt="step 4" style="max-width:400px;"/>
  
Application type: "Web application"  
Name: "nanogallery2gp"  

Define the authorized redirect URLs: enter the full path to your `autorize.php`  
  
<img src="img/google_api_console5.jpg?raw=true" alt="step 5" style="max-width:400px;"/>
  
And you get your personal and confidential `Client ID` and `client secret`  
  
<img src="img/google_api_console6.jpg?raw=true" alt="step 6" style="max-width:400px;"/>
  

## Grant authorization

Once the settings are defined, you need to grant authorization to your Google Photos account.  
Use a browser to open the `authorize.php` page.  
http://your_webserver/nanogp/authorize.php

(if you want to grant authorization again, clear the folder `nanogp/admin/users` before).

## Security  

The `admin` folder should only be accessible to your PHP applications.  

  
## OAuth2
More about OAuth2: https://developers.google.com/identity/protocols/OAuth2WebServer
