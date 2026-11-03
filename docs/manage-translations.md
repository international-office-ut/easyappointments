# Manage Translations

> This page will guide you through the management of Easy!Appointments translations.

### Introduction 
Easy!Appointments supports the addition of custom translations in order to display the user interface into many languages and therefore be more user friendly. This page will guide you through the addition of a new translation and the configuration of the application. You can also modify the available translations or even set the default one for the application.

### Details 
Easy!Appointments is based upon CodeIgniter 4 (PHP Framework) and it uses its build-in libraries in order to translate the content into many languages. The application comes with English and German already included, but there is also the ability to add your own translation and change the user interface strings and captions. To add a new translation you must do the following:

  1. **CREATE A TRANSLATION FOLDER INSIDE "/APP/LANGUAGE/" DIRECTORY.** If you want for example to translate the application into French then you will need to create a new folder named "french" inside the `/app/Language/` directory and copy the `Translations.php` file of the "english" folder into it.
  2. **TRANSLATE EACH STRING WITHIN YOUR "TRANSLATIONS.PHP" FILE INTO YOUR LANGUAGE.** You will just have to replace the English strings with your translation. Example >> `$lang['page_title'] = 'Write your translation here!';`
  3. **TELL EASY!APPOINTMENTS THAT YOU HAVE ADDED A NEW TRANSLATION.** When you are finished with the translation you will need to add the new language slug to the `available_languages` list of your deployment configuration (`app/Config/Deployment.php`, point 61 in the class) and clear the framework cache if enabled. Then change the default language, though this is optional (e.g. `app.defaultLocale = 'french'` in your `.env`).

Follow these steps in order to add or adjust your translations and modify the message of the user interface of Easy!Appointments. If you want contribute to the translation process of Easy!Appointments please read the [Get Involved](https://github.com/alextselegidis/easyappointments/wiki/Get-Involved!) wiki page for more information. Please share your translations with the user community. 

[Back](readme.md)
