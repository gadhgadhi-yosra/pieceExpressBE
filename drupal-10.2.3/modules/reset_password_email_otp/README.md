# Reset Password Email OTP Auth

Drupal by default sends Password Reset URL by email to user's email id in password recovery mail, but Reset Password Email OTP module sends random generated OTP by email instead of URL to the user.


## Table of contents

- Introduction
- Installation
- Configuration
- How to use
- Maintainers


## Introduction

- Reset Password Email OTP module sends random generated OTP by email instead
  of URL to the user.
- Reset Password Email OTP Module provide a block to enter username or email
  of user and send OTP on his email for verification.
- Once OTP verification completed this module allow user to enter new password.


## Installation

Install the Reset Password Email OTP module as you would normally install a
contributed Drupal module. visit https://www.drupal.org/node/1897420 for
further information.


## Configuration

- After enable module Go to this URL "/admin/config/people/reset-password-email-otp"
- Set configuration for OTP length, wrong attempt limit and mail template.
- Make sure in Email Body template [OTP] must be there.
- Save form configuration


## How to use

- Go to the "Block layout" page (under Structure) and use any of the
  "Place block" buttons to create a Reset Password Email OTP Form block.
- save that block


## Maintainers

- Rajan Kumar - [rajan-kumar](https://www.drupal.org/u/rajan-kumar)