<?php

namespace App\Controllers;

use App\Models\NotificationModel;

class Notifications extends BaseController
{
    protected $notificationModel;

    public function __construct()
    {
        $this->notificationModel = new NotificationModel();
    }

    /**
     * Get notifications for the current user
     * Returns JSON response with unread count and list of notifications
     */
    public function get()
    {
        // Check if user is logged in
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Not authenticated'
            ])->setStatusCode(401);
        }

        $userId = (int) session()->get('id');
        
        // Get unread count
        $unreadCount = $this->notificationModel->getUnreadCount($userId);
        
        // Get latest unread notifications only (limit 5)
        $notifications = $this->notificationModel
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->findAll();
        
        return $this->response->setJSON([
            'success' => true,
            'unread_count' => $unreadCount,
            'notifications' => $notifications
        ]);
    }

    /**
     * Mark a notification as read
     * @param int $id Notification ID
     */
    public function mark_as_read($id = null)
    {
        // Check if user is logged in
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Not authenticated'
            ])->setStatusCode(401);
        }

        if (!$id) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Notification ID is required'
            ])->setStatusCode(400);
        }

        $userId = (int) session()->get('id');
        
        // Verify that the notification belongs to the current user
        $notification = $this->notificationModel->find($id);
        
        if (!$notification) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Notification not found'
            ])->setStatusCode(404);
        }

        if ($notification['user_id'] != $userId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ])->setStatusCode(403);
        }

        // Mark as read
        if ($this->notificationModel->markAsRead($id)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to mark notification as read'
            ])->setStatusCode(500);
        }
    }
}

