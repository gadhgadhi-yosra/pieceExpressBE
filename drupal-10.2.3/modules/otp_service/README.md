CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The OTP Service (otp_service) module provides a simple way to add
OTP functionality to your application. This module provides a block
that allows users to setup their secret using the scan of a QR Code and their 
preferred application (Google Authenticator, Microsoft Authenticator etc... ).
The secret is stored as a field in the user entity to be later retrieved for validation (this data IS NOT encrypted).
The module also provides a service to validate user input OTP's in your application.
This way you can focus on your app functionality without worrying about OTP implementation.


Use Case:

There is some page or functionality that you want to "protect".
Users log in using their password and email/username, no two factor authentication required.
When they access a certain page/feature, they have to provide a OTP to access it.

With this module you can provide a block to users so they can setup their secret with their preferred app.
You can then implement a custom form to collect the code their app provided and on submit of the input call
the service this module provides to validation. You can then use the result of the validation (true/false) to
manage the access to the page/feature. 


REQUIREMENTS
------------

* This module uses the pragmarx/google2fa package to generate secrets and validate OTP's
The package will be automatically pulled via composer.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.


CONFIGURATION
-------------

 * The module provides a block for the users to setup their OTP secret. Use Block Layout or another way to render
 the block where you want it.

 * The module provides a service to validate user input OTP's that you can use anywhere in your application

MAINTAINERS
-----------

Current maintainers:
 * Nelson Alves (nsalves) - https://www.drupal.org/u/nsalves

This project has been sponsored by:
 * NTT DATA
   NTT DATA – a part of NTT Group – is a trusted global innovator of
   IT and business services headquartered in Tokyo.
   NTT is one of the largest IT services provider in the world
   and has 140,000 professionals, operating in more than 50 countries.
   NTT DATA supports clients in their digital development
   through a wide range of consulting and strategic advisory services,
   cutting-edge technologies, applications,
   infrastructure, modernization of IT and BPOs.
   We contribute with vast experience in all sectors of
   economic activity and have extensive 
   knowledge of the locations in which we operate.
