<?php

namespace App\Controllers;

use App\Models\AnnouncementModel;

class Announcement extends BaseController
{
    public function index()
    {
        // Create an instance of the AnnouncementModel
        $announcementModel = new AnnouncementModel();
        
        // Fetch all announcements from the database
        // Order by created_at descending to show newest first
        $announcements = $announcementModel->orderBy('created_at', 'DESC')->findAll();
        
        // Pass the announcements data to the view
        $data = [
            'announcements' => $announcements,
            'title' => 'Announcements'
        ];
        
        return view('announcements', $data);
    }
}
