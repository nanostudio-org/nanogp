# nanogp - DEPRECIATED - please upgrade to [nanogp2](https://github.com/nanostudio-org/nanogp2)

Add-on for <b>[nanogallery2](https://github.com/nanostudio-org/nanogallery2)</b> to access **Google Photos** content.  

Since february 9, 2017, Google Photos can no more be accessed without account owner's explicit authorization.
Permanent authorization is only possible for server side applications.

<b>nanogp</b> is a PHP application which delivers Google Photos content to <b>[nanogallery2](https://github.com/nanostudio-org/nanogallery2)</b>.
  
   
---  
<b>Picasa API Deprecation</b>  
- To activate the extension period, between January 2019 and March 2019, please install <b>nanogp V1.3.1</b>   
- **After March 2019, upgrade to [nanogp2](https://github.com/nanostudio-org/nanogp2)**  
- More: https://developers.google.com/picasa-web/docs/3.0/deprecation
  
---  
  

```diff
- --> WARNING: please use NANOGP only with a Google Photos account which does not contain any personal or privat data.
- All your photos albums can be accessed by NANOGP. This may be misused by malicious people.
- 
- --> ### SO, USE A DEDICATED GOOGLE PHOTOS ACCOUNT. ###
- 
- configure the option `$albums_filter` to protect your privacy!
```

## WARNING
THIS SCRIPT IS PUBLISHED AS IS. GOOGLE CONTINUOUSLY CHANGES HIS CONFIGURATUION INTERFACE, AND IT'S IMPOSSIBLE TO REFLECT ALL CHANGES ON THIS PAGE.  
  
  
  
## Pre-requisites:
Web server with PHP version > 5.2  
Cannot be run on `localhost` (workaround, use `http://lvh.me` instead)  


## Installation procedure  

- Create a folder named `nanogp` on your web server.
- Copy the content of the `dist` folder in this folder.
  
## Upgrade procedure  
Copy the content of the `dist` folder to your server, but **never overwrite** the `tools.php` file.  
If you overwrite it, you'll need to set the configuration again.  
  
## Configuration  

Settings are defined in `admin/config.php`:
  
```
  $cfg_client_id     = 'yyy';
  $cfg_client_secret = 'zzz';
  $albums_filter     = ['sauvegarde', 'backup'];
```
  
`$cfg_client_id` and `$cfg_client_secret` can be obtained from the Google developers console.  
`$albums_filter` is used to filter albums out. Albums with a title containing one of the string will not be displayed.
  
  
## Enable Google API - Google developers console
  
Create and configure a new projet.  
  
1) Open page: https://console.developers.google.com  
  
2) Create a new project named **`nanogallery2gp-YOUR-INSTANCE-NAME`** (the project name should be unique, so replace **YOUR-INSTANCE-NAME** with the name of your own instance)
  
<img src="img/google_api_console1.jpg?raw=true" alt="step 1" style="max-width:400px;"/>
  
<img src="img/google_api_console2.jpg?raw=true" alt="step 2" style="max-width:400px;"/>
  
3) Create a consent screen  
  
Select your email address  
  
Define the "product nameshown to user": **"nanogallery2gp-YOUR-INSTANCE-NAME"**  
  
Others fields are optional  
  
<img src="img/google_api_console3.jpg?raw=true" alt="step 3" style="max-width:400px;"/>
  
4) Create `credentials` kind `OAuth Client ID`  
    
<img src="img/google_api_console4.jpg?raw=true" alt="step 4" style="max-width:400px;"/>
  
Application type: "Web application"  
Name: **"nanogallery2gp-YOUR-INSTANCE-NAME"**  

Define the authorized redirect URLs: enter the full path to your `authorize.php`  
  
<img src="img/google_api_console5.jpg?raw=true" alt="step 5" style="max-width:400px;"/>
  
And you get your personal and confidential `Client ID` and `client secret`  
  
<img src="img/google_api_console6.jpg?raw=true" alt="step 6" style="max-width:400px;"/>
  

## Grant authorization

Once the settings are defined, you need to grant authorization to your Google Photos account.  
Use a browser to open the `authorize.php` page: `http://your_webserver/nanogp/authorize.php`  
  
(if you want to grant authorization again, follow steps from the section `Manually revoke authorization`).

## Security  

The `admin` folder should only be accessible to your PHP applications.  
For example, with `deny from all` set in `.htaccess` file.

## Manually revoke authorization  
- delete the folder corresponding to the user in `admin/users`
- delete application's authorization: https://myaccount.google.com/permissions


## OAuth2
More about OAuth2: https://developers.google.com/identity/protocols/OAuth2WebServer  
  
