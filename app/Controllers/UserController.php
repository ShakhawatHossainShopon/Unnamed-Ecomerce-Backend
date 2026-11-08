<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Uploader;
use App\Models\User;

/**
 * User Controller
 * 
 * Handles incoming HTTP requests for User resources.
 * Validates inputs, invokes the User model, and returns standardized responses.
 */
class UserController extends Controller
{
    /**
     * @var User
     */
    private User $userModel;

    /**
     * Constructor initializes the User model
     *
     * @param Request|null $request
     */
    public function __construct(?Request $request = null)
    {
        parent::__construct($request);
        $this->userModel = new User();
    }

    /**
     * GET /api/users
     * Retrieve all users
     */
    public function index(): void
    {
        $users = $this->userModel->all();
        $this->success($users, 'Users retrieved successfully', 200);
    }

    /**
     * GET /api/users/{id}
     * Retrieve a specific user by ID
     *
     * @param mixed $id
     */
    public function show(mixed $id): void
    {
        if (!is_numeric($id) || (int)$id <= 0) {
            $this->error('Invalid user ID supplied', 400);
            return;
        }

        $user = $this->userModel->findById((int)$id);

        if (!$user) {
            $this->error('User not found', 404);
            return;
        }

        $this->success($user, 'User retrieved successfully', 200);
    }

    /**
     * POST /api/users
     * Create a new user record
     */
    public function store(): void
    {
        $input = $this->request->getBody();

        // Validate required fields
        $errors = $this->validate($input, [
            'name'  => 'required|min:2|max:100',
            'email' => 'required|email|max:150',
        ]);

        if (!empty($errors)) {
            $this->error('Validation failed', 422, ['errors' => $errors]);
            return;
        }

        // Check for duplicate email
        $existing = $this->userModel->findByEmail($input['email']);
        if ($existing) {
            $this->error('Email is already registered', 409);
            return;
        }

        $userId = $this->userModel->create([
            'name'  => $input['name'],
            'email' => $input['email'],
        ]);

        $newUser = $this->userModel->findById($userId);
        $this->success($newUser, 'User created successfully', 201);
    }

    /**
     * PUT/PATCH /api/users/{id}
     * Update an existing user record
     *
     * @param mixed $id
     */
    public function update(mixed $id): void
    {
        if (!is_numeric($id) || (int)$id <= 0) {
            $this->error('Invalid user ID supplied', 400);
            return;
        }

        $existing = $this->userModel->findById((int)$id);
        if (!$existing) {
            $this->error('User not found', 404);
            return;
        }

        $input = $this->request->getBody();

        if (empty($input) || (!isset($input['name']) && !isset($input['email']))) {
            $this->error('No update fields provided (name or email required)', 400);
            return;
        }

        $rules = [];
        if (isset($input['name'])) {
            $rules['name'] = 'required|min:2|max:100';
        }
        if (isset($input['email'])) {
            $rules['email'] = 'required|email|max:150';
        }

        $errors = $this->validate($input, $rules);
        if (!empty($errors)) {
            $this->error('Validation failed', 422, ['errors' => $errors]);
            return;
        }

        // Check if email already belongs to another user
        if (isset($input['email']) && strtolower(trim($input['email'])) !== strtolower($existing['email'])) {
            $emailTaken = $this->userModel->findByEmail($input['email'], (int)$id);
            if ($emailTaken) {
                $this->error('Email is already taken by another user', 409);
                return;
            }
        }

        $this->userModel->update((int)$id, $input);
        $updatedUser = $this->userModel->findById((int)$id);

        $this->success($updatedUser, 'User updated successfully', 200);
    }

    /**
     * DELETE /api/users/{id}
     * Remove an existing user record
     *
     * @param mixed $id
     */
    public function destroy(mixed $id): void
    {
        if (!is_numeric($id) || (int)$id <= 0) {
            $this->error('Invalid user ID supplied', 400);
            return;
        }

        $existing = $this->userModel->findById((int)$id);
        if (!$existing) {
            $this->error('User not found', 404);
            return;
        }

        $this->userModel->delete((int)$id);
        $this->success(null, 'User deleted successfully', 200);
    }

    /**
     * POST /api/users/{id}/avatar
     * Upload and update user profile avatar image
     *
     * @param mixed $id
     */
    public function uploadAvatar(mixed $id): void
    {
        if (!is_numeric($id) || (int)$id <= 0) {
            $this->error('Invalid user ID supplied', 400);
            return;
        }

        $existing = $this->userModel->findById((int)$id);
        if (!$existing) {
            $this->error('User not found', 404);
            return;
        }

        if (!$this->request->hasFile('avatar')) {
            $this->error('No avatar image file was uploaded. Please upload a file with key "avatar".', 400);
            return;
        }

        // Upload the file using the universal Uploader
        $uploadResult = Uploader::upload(
            $this->request->file('avatar'),
            'uploads/avatars',
            Uploader::IMAGE_TYPES,
            5242880 // 5MB
        );

        if (!$uploadResult['success']) {
            $this->error($uploadResult['error'], 422);
            return;
        }

        // Remove old avatar if it exists
        if (!empty($existing['avatar'])) {
            Uploader::delete($existing['avatar']);
        }

        // Update database with the new file path
        $this->userModel->updateAvatar((int)$id, $uploadResult['relative_path']);
        $updatedUser = $this->userModel->findById((int)$id);

        $this->success([
            'user'   => $updatedUser,
            'upload' => $uploadResult,
        ], 'Avatar uploaded and profile updated successfully', 200);
    }
}
