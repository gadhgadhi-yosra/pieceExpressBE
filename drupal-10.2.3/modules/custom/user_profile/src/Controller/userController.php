<?php

namespace Drupal\user_profile\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Password\PasswordInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Entity\EntityStorageException;


class userController extends ControllerBase {

    public function register(Request $request) {
        $content = json_decode($request->getContent(), TRUE);
        
        try {
            $user = User::create([
                'name' => $content['username'],
                'mail' => $content['email'],
                'pass' => $content['password'],
                'status' => 1, // Active or 0 if email verification is required
                'field_address' => $content['address'],  // Assuming the field machine name is field_address
                'field_telephone' => $content['telephone'],  // Assuming the field machine name is field_telephone
            ]);
            $user->save();
            return new JsonResponse([
                'uid' => $user->id(),
                'message' => 'User registered successfully',
            ]);
        } catch (\Exception $e) {
            $this->loggerFactory->get('user_profile')->error($e->getMessage());
            return new JsonResponse(['message' => 'An error occurred during registration.'], 500);
        }
    }

    
    public function login(Request $request) {
        $content = json_decode($request->getContent(), true);

    
        $username = $content['username'];
        $password = $content['password'];

        $uid = \Drupal::service('user.auth')->authenticate($username,$password);

        $user = \Drupal\user\Entity\User::load($uid);    

        user_login_finalize($user);

        if($user){
                  // // Load the user entity
        $user_storage = $this->entityTypeManager()->getStorage('user');
        $current_user = $user_storage->load($uid);

          return new JsonResponse(['message' => "You are connected now"], 200);

        }else{
          return new JsonResponse(['message' => 'User does not exist.'], 404);

        }
    }

    // private function userCheckPassword($username, $password, $user) {
    //     if (\Drupal::hasService('user.password_hasher')) {
    //         $password_hasher = \Drupal::service('user.password_hasher');
    //     } else {
    //         $this->messenger()->addError('Password hasher service is not available.');
    //         return FALSE;
    //     }

    //     return $password_hasher->check($password, $user->getPassword());
    // }

    // public function hashPassword($password) {
    //     if (\Drupal::hasService('user.password_hasher')) {
    //         $password_hasher = \Drupal::service('user.password_hasher');
    //         return $password_hasher->hash($password);
    //     } else {
    //         $this->messenger()->addError('Password hasher service is not available.');
    //         return null;
    //     }
    // }

    
      public function update(Request $request) {

            //Decode the JSON content from the request
        $content = json_decode($request->getContent(), true);

        if (!$content) {
            return new JsonResponse(['message' => 'Invalid or no JSON content provided.'], 400);
        }
        else{
            // Load the user entity
          $user_storage = $this->entityTypeManager()->getStorage('user');
          $user = $user_storage->load($content['uid']);
          if (!$user) {
            return new JsonResponse(['message' => 'User not found.'], 404);
        }else{
          // Update user fields if they exist
            foreach ($content as $field => $value) {
              if ($user->hasField($field)) {
                  $user->set($field, $value);
              } else {
                  return new JsonResponse(['message' => "Field '$field' does not exist."], 400);
              }
          }
          try {
            $user->save();

             // Return success response
            return new JsonResponse(['message' => 'Profile updated successfully.'], 200);

            } catch (EntityStorageException $e) {
                return new JsonResponse(['message' => 'Failed to update profile.'], 500);
            }

        }

        }

       
    }
    
      protected function handleProfileImage($user, $data) {
        if (isset($data['profile_image'])) {
          $file = File::load($data['profile_image']);
          if ($file && $file->access('use')) {
            $file->setPermanent();
            $file->save();
            $user->set('user_picture', $file->id());
          } else {
            return new JsonResponse(['message' => 'Invalid file ID or access denied.'], 400);
          }
        }
      }
    
      public function changePassword(Request $request) {
        if (!$this->currentUser->isAuthenticated()) {
          throw new AccessDeniedHttpException();
        }
    
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['current_password']) || !isset($data['new_password'])) {
          return new JsonResponse(['message' => 'Current and new password required.'], 400);
        }
    
        $user_storage = $this->entityTypeManager->getStorage('user');
        $user = $user_storage->load($this->currentUser->id());
    
        if (!$user || !$user->isActive()) {
          return new JsonResponse(['message' => 'User not found or inactive.'], 404);
        }
    
        if (!$this->passwordService->check($data['current_password'], $user->getPassword())) {
          return new JsonResponse(['message' => 'Incorrect current password.'], 403);
        }
    
        $user->setPassword($data['new_password']);
        $user->save();
    
        return new JsonResponse(['message' => 'Password updated successfully.'], 200);
      }
    
    
    
    public function resetPassword(Request $request) {
         
            $content = json_decode($request->getContent(), TRUE);
            $user_storage = \Drupal::entityTypeManager()->getStorage('user');

            $email = $content['email'];
            $user = user_load_by_mail($email);
        
            if ($user) {
              $user->setPassword($content['password']);
                      $user->save();
        
          
              return new JsonResponse(['message' => 'Password updated successfully.'], 200);

            }else{

              return new JsonResponse(['message' => "Field '$email' does not exist."], 400);

            }        
  
          }
         
    }
