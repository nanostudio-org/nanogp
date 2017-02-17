# nanogp

Access Google Photos from nanogallery2.

Since february 9, 2017, Google Photos can no more be accessed without account owner's explicit authorization.
Permanent authorization is only possible for server side applications.

nanoGP is a PHP application which delivers Google Photos content to nanogallery2 on your web pages.


#### Pre-requisites:
Web server with PHP version > 5.2


#### Installation
- Create a folder named `nanogp` on your web server.
- Copy the content of the `dist` folder in this folder.

#### Configuration
Settings are defined in `admin/config.php`:

```
  $cfg_client_id = 'yyy';
  $cfg_client_secret = 'zzz';
  $cfg_application_name = 'nanogallery2gp';
```
`cfg_client_id` and `cfg_client_secret` can be obtained from the Google developers console.

#### Enable Google API
Create and configure a new projet.

1. Open page: https://console.developers.google.com
2. Create a new project
<img src="img/google_api_console1.jpg?raw=true" alt="step 1" style="max-width:400px;"/>

  nanogallerygp
3. Credentials
3a. Create a "OAuth consent screen"
  select your email address
  define the "product namaeshown to user": "nanogallerygp"
  others fields are optional
3b. Create a "credential" kind "OAuth Client ID"
  Application type: "Web application"
  Name: "nanogallerygp"
  
  ==> get "Client ID" and "client secret"


 

